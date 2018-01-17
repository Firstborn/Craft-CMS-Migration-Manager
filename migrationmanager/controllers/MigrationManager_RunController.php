<?php

namespace Craft;

/**
 * Class MigrationManager_RunController
 */
class MigrationManager_RunController extends BaseController
{
    /**
     * @throws HttpException
     */
    public function actionStart()
    {
        $this->requirePostRequest();

        $plugin = craft()->plugins->getPlugin('migrationmanager');

        $data = array(
            'data' => array(
                'handle' => craft()->security->hashData($plugin->getClassHandle()),
                'uid' => craft()->security->hashData(StringHelper::UUID()),
                'migrations' => craft()->request->getPost('migration'),
                'applied' => craft()->request->getPost('applied'),
            ),
        );

        $this->renderTemplate('migrationManager/actions/run', $data);
    }

    /**
     * @throws HttpException
     */
    public function actionRerunApplied()
    {
        $this->requirePostRequest();

        $migrations = craft()->request->getPost('migration');
        if (empty($migrations)) {

            craft()->userSession->setError(Craft::t('You must select a migration to re run'));
            $this->redirectToPostedUrl();

        } else {

            // unset the selected migrations
            craft()->migrationManager_migrations->setMigrationsAsNotApplied($migrations);
            $this->actionStart();
        }
    }

    /**
     * @throws HttpException
     */
    public function actionMigrations()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        $data = craft()->request->getRequiredPost('data');

        // give a little on screen pause
        sleep(2);

        if (craft()->migrationManager_migrations->runToTop($data['migrations'])) {
            $this->returnJson(array(
                'alive' => true,
                'finished' => true,
                'returnUrl' => 'migrationmanager/pending',
            ));
        } else {
            $this->returnJson(array(
                'alive' => true,
                'errorDetails' => 'Check the migration <a href="logs">log</a> for details. ',
                'nextStatus' => Craft::t('An error was encountered. Rolling back…'),
                'nextAction' => 'migrationManager/run/rollback',
                'data' => $data,
            ));
        }
    }

    /**
     * @throws HttpException
     */
    public function actionPrepare()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $data = craft()->request->getRequiredPost('data');

        $this->returnJson(array(
            'alive' => true,
            'nextStatus' => Craft::t('Backing-up database ...'),
            'nextAction' => 'migrationManager/run/backupDatabase',
            'data' => $data,
        ));
    }

    /**
     * @throws HttpException
     */
    public function actionBackupDatabase()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        $data = craft()->request->getRequiredPost('data');

        if (craft()->config->get('backupDbOnUpdate')) {
            $return = craft()->updates->backupDatabase();

            MigrationManagerPlugin::log('running database backup', LogLevel::Info);

            if (!$return['success']) {
                $this->returnJson(array(
                    'alive' => true,
                    'errorDetails' => $return['message'],
                    'nextStatus' => Craft::t('An error was encountered. Rolling back…'),
                    'nextAction' => 'migrationManager/run/rollback',
                    'data' => $data,
                ));
            }
        }

        $this->returnJson(array(
            'alive' => true,
            'nextStatus' => Craft::t('Running migrations ...'),
            'nextAction' => 'migrationManager/run/migrations',
            'data' => $data,
        ));
    }

    /**
     * @throws Exception
     * @throws HttpException
     */
    public function actionRollback()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        MigrationManagerPlugin::log('rolling back database', LogLevel::Error);

        $data = craft()->request->getRequiredPost('data');
        $handle = craft()->security->validateData($data['uid']);
        $uid = craft()->security->validateData($data['uid']);

        if (!$uid) {
            throw new Exception(('Could not validate UID'));
        }

        if (isset($data['dbBackupPath'])) {
            $dbBackupPath = craft()->security->validateData($data['dbBackupPath']);

            if (!$dbBackupPath) {
                throw new Exception('Could not validate database backup path.');
            }

            $return = craft()->updates->rollbackUpdate($uid, $handle, $dbBackupPath);
        } else {
            $return = craft()->updates->rollbackUpdate($uid, $handle);
        }

        //reset the status of the migrations if they were reruns
        if ($data['applied'] == 1) {
            craft()->migrationManager_migrations->setMigrationsAsApplied($data['migrations']);
        }

        if (!$return['success']) {
            // Let the JS handle the exception response.
            throw new Exception($return['message']);
        }

        $this->returnJson(array('alive' => true, 'finished' => true, 'rollBack' => true));
    }
}
