<?php

namespace Craft;

class MigrationManagerController extends BaseController
{

    public function actionCreateMigration()
    {
        // Prevent GET Requests
        $this->requirePostRequest();
        $post = craft()->request->getPost();

        if (craft()->migrationManager_migrations->createSettingMigration($post))
        {
            craft()->userSession->setNotice(Craft::t('Migration created.'));
        } else {
            craft()->userSession->setError(Craft::t('Could not create migration, check log tab for errors.'));
        }

        $this->renderTemplate('migrationmanager/index');
    }

    public function actionCreateGlobalsContentMigration()
    {
        $this->requirePostRequest();
        craft()->userSession->requireAdmin();

        $globalSet = new GlobalSetModel();

        // Set the simple stuff
        $globalSet->id     = craft()->request->getPost('setId');
        $globalSet->name   = craft()->request->getPost('name');
        $globalSet->handle = craft()->request->getPost('handle');

        // Set the field layout

        $fieldLayout = craft()->fields->assembleLayoutFromPost();
        $fieldLayout->type = ElementType::GlobalSet;
        $globalSet->setFieldLayout($fieldLayout);

        $post = craft()->request->getPost();

        $params['global'] = array(craft()->request->getPost('setId'));

        if (craft()->migrationManager_migrations->createContentMigration($params))
        {
            craft()->userSession->setNotice(Craft::t('Migration created.'));
        } else {
            craft()->userSession->setError(Craft::t('Could not create migration, check log tab for errors.'));
        }

        $this->redirectToPostedUrl($globalSet);


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
                'migrations' => craft()->request->getPost('migration'),
                'applied' => craft()->request->getPost('applied')
            )
        );

        MigrationManagerPlugin::log('runMigration', LogLevel::Error);
        MigrationManagerPlugin::log(json_encode($data['data']['migrations']), LogLevel::Error);

        $this->renderTemplate('migrationmanager/run', $data);

    }

    public function actionRunAppliedMigration()
    {
        $this->requirePostRequest();


        $migrations = craft()->request->getPost('migration');

        if ($migrations == false){
            craft()->userSession->setError(Craft::t('You must select a migration to re run'));
            $this->renderTemplate('migrationmanager/migrations-applied');
        } else {

            //unset the selected migrations
            craft()->migrationManager_migrations->setMigrationsAsNotApplied($migrations);
            $this->actionRunMigration();
        }
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

        //reset the status of the migrations if they were reruns
        if ($data['applied'] == 1){
            craft()->migrationManager_migrations->setMigrationsAsApplied($data['migrations']);
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

        if (craft()->migrationManager_migrations->runToTop($data['migrations']))
        {
            $this->returnJson(array('alive' => true, 'finished' => true, 'returnUrl' => 'migrationManager/migrations'));
        } else {
            $this->returnJson(array('alive' => true, 'errorDetails' => 'Check to migration <a href="log">log</a> for details. ', 'nextStatus' => Craft::t('An error was encountered. Rolling back…'), 'nextAction' => 'migrationManager/rollback', 'data' => $data));
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

}