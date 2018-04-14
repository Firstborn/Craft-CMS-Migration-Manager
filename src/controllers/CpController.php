<?php

namespace firstborn\migrationmanager\controllers;

use Craft;
use craft\web\Controller;
use firstborn\migrationmanager\MigrationManager;

/**
 * Class MigrationManagerController
 */
class CpController extends Controller
{

    /**
     * Index
     */

    public function actionIndex()
    {
        $outstanding = MigrationManager::getInstance()->getBadgeCount();
        if ($outstanding){
            Craft::$app->getSession()->setError(Craft::t('migrationmanager','There are pending migrations to run'));
        }
        return $this->renderTemplate('migrationmanager/index');
    }

    /**
     * Shows migrations
     */
    public function actionMigrations()
    {
        $migrator = Craft::$app->getContentMigrator();
        $pending = $migrator->getNewMigrations();
        $applied = $migrator->getMigrationHistory();
        return $this->renderTemplate('migrationmanager/migrations', array('pending' => $pending, 'applied' => $applied));
    }

}
