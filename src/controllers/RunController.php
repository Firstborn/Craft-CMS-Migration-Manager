<?php

namespace firstborn\migrationmanager\controllers;

use Craft;
use craft\web\Controller;
use firstborn\migrationmanager\MigrationManager;


/**
 * Class MigrationManager_RunController
 */
class RunController extends Controller
{
    /**
     * @throws HttpException
     */
    public function actionStart()
    {



        $this->requirePostRequest();

        $plugin = MigrationManager::getInstance();
        $request = Craft::$app->getRequest();
        $post = $request->post();

        $data = array(
            'data' => array(
                'handle' => Craft::$app->security->hashData($plugin->getClassHandle()),
                'uid' => Craft::$app->security->hashData(StringHelper::UUID()),
                'migrations' =>  $post('migration')
            ),
        );

        //$this->renderTemplate('migrationManager/migrations', array('pending' => $pending, 'applied' => $applied));

        return $this->renderTemplate('migrationManager/actions/run', $data);
    }

    /**
     * @throws HttpException
     */
    public function actionRerunApplied()
    {
        $this->requirePostRequest();

        $migrations = Craft::$app->request->getPost('migration');
        if (empty($migrations)) {

            Craft::$app->userSession->setError(Craft::t('You must select a migration to re run'));
            $this->redirectToPostedUrl();

        } else {

            // unset the selected migrations
            Craft::$app->migrationManager_migrations->setMigrationsAsNotApplied($migrations);
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
        $data = Craft::$app->request->getRequiredPost('data');

        // give a little on screen pause
        sleep(2);

        if (Craft::$app->migrationManager_migrations->runToTop($data['migrations'])) {
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

        $data = Craft::$app->request->getRequiredPost('data');

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
        $data = Craft::$app->request->getRequiredPost('data');

        if (Craft::$app->config->get('backupDbOnUpdate')) {
            $return = Craft::$app->updates->backupDatabase();

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

        $data = Craft::$app->request->getRequiredPost('data');
        $handle = Craft::$app->security->validateData($data['uid']);
        $uid = Craft::$app->security->validateData($data['uid']);

        if (!$uid) {
            throw new Exception(('Could not validate UID'));
        }

        if (isset($data['dbBackupPath'])) {
            $dbBackupPath = Craft::$app->security->validateData($data['dbBackupPath']);

            if (!$dbBackupPath) {
                throw new Exception('Could not validate database backup path.');
            }

            $return = Craft::$app->updates->rollbackUpdate($uid, $handle, $dbBackupPath);
        } else {
            $return = Craft::$app->updates->rollbackUpdate($uid, $handle);
        }

        //reset the status of the migrations if they were reruns
        if ($data['applied'] == 1) {
            Craft::$app->migrationManager_migrations->setMigrationsAsApplied($data['migrations']);
        }

        if (!$return['success']) {
            // Let the JS handle the exception response.
            throw new Exception($return['message']);
        }

        $this->returnJson(array('alive' => true, 'finished' => true, 'rollBack' => true));
    }


}
