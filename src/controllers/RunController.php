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
        $request = Craft::$app->getRequest();

        return $this->asJson(array(
                'data' => $request->getParam('data'),
                'alive' => true,
                'nextAction' => 'migrationmanager/run/prepare'
            )
        );
    }

    /**
     * @throws HttpException
     */
    public function actionPrepare()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $data = Craft::$app->request->getRequiredParam('data');

        return $this->asJson(array(
            'alive' => true,
            'nextStatus' => Craft::t('app', 'Backing-up database ...'),
            'nextAction' => 'migrationmanager/run/backup',
            'data' => $data,
        ));
    }

    /**
     * @throws HttpException
     */
    public function actionBackup()
    {
        $this->requirePostRequest();

        $data = Craft::$app->request->getRequiredParam('data');
        $backup = Craft::$app->getConfig()->getGeneral()->getBackupOnUpdate();
        $db = Craft::$app->getDb();

        if ($backup) {
            try {
                $db->backup();
                return $this->asJson(array(
                    'alive' => true,
                    'nextStatus' => Craft::t('migrationmanager', 'Running migrations ...'),
                    'nextAction' => 'migrationmanager/run/migrations',
                    'data' => $data,
                ));

            } catch (\Throwable $e) {
                Craft::$app->disableMaintenanceMode();

                return $this->asJson(array(
                    'alive' => true,
                    'errorDetails' => $e->getMessage(),
                    'nextStatus' => Craft::t('migrationmanager', 'An error was encountered. Rolling back ...'),
                    'nextAction' => 'migrationmanager/run/rollback',
                    'data' => $data,
                ));
            }
        }
    }

    /**
     * @throws HttpException
     */
    public function actionMigrations()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $data = Craft::$app->request->getParam('data');

        $migrations = $data['migrations'];


        if (!is_array($migrations)){
            $migrations = [];
        }

        // give a little on screen pause
        sleep(2);

        if (MigrationManager::getInstance()->migrations->runMigrations($migrations)) {
            return $this->asJson(array(
                'alive' => true,
                'finished' => true,
                'returnUrl' => 'migrationmanager/migrations',
            ));
        } else {
            return $this->asJson(array(
                'alive' => true,
                'errorDetails' => 'Check the logs for details. ',
                'nextStatus' => Craft::t('migrationmanager', 'An error was encountered. Rolling back ...'),
                'nextAction' => 'migrationmanager/run/rollback',
                'data' => $data,
            ));
        }
    }

    /**
     * @throws Exception
     * @throws HttpException
     */
    public function actionRollback()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // give a little on screen pause
        sleep(2);

        return $this->asJson(array('alive' => true, 'finished' => true, 'rollBack' => true));
    }


}
