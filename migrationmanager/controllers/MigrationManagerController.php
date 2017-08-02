<?php

namespace Craft;

class MigrationManagerController extends BaseController
{

    public function actionCreateMigration()
    {
        // Prevent GET Requests
        $this->requirePostRequest();
        $post = craft()->request->getPost();
        if (craft()->migrationManager_migrations->create($post))
        {
            craft()->userSession->setNotice(Craft::t('Migration created.'));
        } else {
            craft()->userSession->setError(Craft::t('Could not create migration, check log tab for errors.'));
        }

        $this->renderTemplate('migrationmanager/index');
    }

    public function actionMigrations(){
        $this->renderTemplate('migrationmanager/migrations');
    }

    public function actionRunMigration()
    {
        $this->requirePostRequest();

        $plugin = craft()->plugins->getPlugin('migrationmanager');

        $data = array(
            'data' => array(
                'handle' => craft()->security->hashData($plugin->getClassHandle()),
                'uid' => craft()->security->hashData(StringHelper::UUID()),
                'migrations' => craft()->request->getPost('migration')

            )
        );

        $this->renderTemplate('migrationmanager/run', $data);

    }

    public function actionPrepare()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        $data = craft()->request->getRequiredPost('data');

        $this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Backing-up database ...'), 'nextAction' => 'migrationManager/backupDatabase', 'data' => $data));
    }

    public function actionBackupDatabase(){

        $this->requirePostRequest();
        $this->requireAjaxRequest();
        $data = craft()->request->getRequiredPost('data');

        if (craft()->config->get('backupDbOnUpdate'))
        {
            $plugin = craft()->plugins->getPlugin('migrationmanager');

            // make sure there are new migrations before backing up the database.
            if ($plugin && craft()->migrations->getNewMigrations($plugin) )
            {
                $return = craft()->updates->backupDatabase();

                MigrationManagerPlugin::log('running database backup', LogLevel::Info);

                if (!$return['success'])
                {
                    $this->returnJson(array('alive' => true, 'errorDetails' => $return['message'], 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'migrationManager/rollback', 'data' => $data));
                }
            }
        }

        $this->returnJson(array('alive' => true, 'nextStatus' => Craft::t('Running migrations ...'), 'nextAction' => 'migrationManager/runMigrations', 'data' => $data));

    }

    public function actionRollback()
    {
        MigrationManagerPlugin::log('rolling back database', LogLevel::Error);
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $data = craft()->request->getRequiredPost('data');
        $handle = craft()->security->validateData($data['uid']);
        $uid = craft()->security->validateData($data['uid']);

        if (!$uid)
        {
            throw new Exception(('Could not validate UID'));
        }

        if (isset($data['dbBackupPath']))
        {
            $dbBackupPath = craft()->security->validateData($data['dbBackupPath']);

            if (!$dbBackupPath)
            {
                throw new Exception('Could not validate database backup path.');
            }

            $return = craft()->updates->rollbackUpdate($uid, $handle, $dbBackupPath);
        }
        else
        {
            $return = craft()->updates->rollbackUpdate($uid, $handle);
        }

        if (!$return['success'])
        {
            // Let the JS handle the exception response.
            throw new Exception($return['message']);
        }

        $this->returnJson(array('alive' => true, 'finished' => true, 'rollBack' => true ));
    }

    public function actionRunMigrations()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        $data = craft()->request->getRequiredPost('data');

        //give a little on screen pause
        sleep(2);

        Craft::log(JsonHelper::encode($data), LogLevel::Error);



        if (craft()->migrationManager_migrations->runToTop($data['migrations']))
        {
            $this->returnJson(array('alive' => true, 'finished' => true, 'returnUrl' => 'migrationManager/migrations'));
        } else {
            $this->returnJson(array('alive' => true, 'errorDetails' => 'Check to migration <a href="log">log</a> for details. ', 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'update/rollback', 'data' => $data));
        }
    }



    public function actionLog(){

        $path = craft()->path->getLogPath() . 'migrationmanager.log';
        $contents = IOHelper::getFileContents($path);
        $requests = explode('******************************************************************************************************', $contents);
        //$log = array();
        $logEntries = array();

        foreach ($requests as $request)
        {
            $logChunks = preg_split('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) \[(.*?)\] \[(.*?)\] /m', $request, null, PREG_SPLIT_DELIM_CAPTURE);

            // Ignore the first chunk
            array_shift($logChunks);

            // Loop through them
            $totalChunks = count($logChunks);
            for ($i = 0; $i < $totalChunks; $i += 4)
            {
                $logEntryModel = new LogEntryModel();

                $logEntryModel->dateTime = DateTime::createFromFormat('Y/m/d H:i:s', $logChunks[$i]);
                $logEntryModel->level = $logChunks[$i+1];
                $logEntryModel->category = $logChunks[$i+2];

                $message = $logChunks[$i+3];
                $message = str_replace('[Forced]', '', $message);
                $rowContents = explode("\n", $message);

                $logEntryModel->message = $rowContents[0];

                array_unshift($logEntries, $logEntryModel);
            }
        }

        $this->renderTemplate('migrationmanager/log', array(
            'logEntries' => $logEntries
        ));
    }

    public function actionManualImport()
    {
        HeaderHelper::setHeader(['Content-Type: text/json']);

        $migrations = craft()->migrationManager_migrations->getNewMigrations();
        $migration = array_pop($migrations);

        $plugin = craft()->plugins->getPlugin('migrationmanager', false);

        echo 'RUN MIGRATION: '. $migration . PHP_EOL;

        if (craft()->migrations->migrateUp($migration, $plugin) === false)
        {

            echo 'Migration ' . $migration . ' failed . All later migrations are canceled.' . PHP_EOL;

            // Refresh the DB cache
            craft()->db->getSchema()->refresh();


        } else {

            echo 'Migration ' . $migration . ' successfully ran'. PHP_EOL;
        }

        $this->returnJson(true);

    }

    public function actionManualExport()
    {
        HeaderHelper::setHeader(['Content-Type: text/json']);

        $get = craft()->request->getQuery();


        if (craft()->migrationManager_migrations->create($get))
        {
            craft()->userSession->setNotice(Craft::t('Migration created.'));
        } else {
            craft()->user->setError(Craft::t('Could not create migration.'));
        }


        $this->returnJson(true);
    }



    public function actionEntry()
    {
        HeaderHelper::setHeader(['Content-Type: text/json']);

        $content = craft()->migrationManager_entries->export([24]);



        echo JsonHelper::encode($content);

        $this->returnJson(true);
    }




}