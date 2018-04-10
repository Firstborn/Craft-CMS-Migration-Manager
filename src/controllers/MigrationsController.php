<?php

namespace firstborn\migrationmanager\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\GlobalSet;
use firstborn\migrationmanager\MigrationManager;
use craft\web\assets\updates\UpdatesAsset;


/**
 * Class MigrationManagerController
 */
class MigrationsController extends Controller
{

    /**
     * @throws HttpException
     */
    public function actionCreateMigration()
    {
        // Prevent GET Requests
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $post = $request->post();

        if (MigrationManager::getInstance()->migrations->createSettingMigration($post)) {
            Craft::$app->getSession()->setNotice(Craft::t('migrationmanager', 'Migration created.'));

        } else {
            Craft::$app->getSession()->setError(Craft::t('migrationmanager', 'Could not create migration, check log tab for errors.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @throws HttpException
     */
    public function actionCreateGlobalsContentMigration()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $request = Craft::$app->getRequest();
        $post = $request->post();
        $params['global'] = array($post['setId']);

        if (MigrationManager::getInstance()->migrations->createContentMigration($params)) {
            Craft::$app->getSession()->setNotice(Craft::t('migrationmanager','Migration created.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('migrationmanager','Could not create migration, check log tab for errors.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @throws HttpException
     */
    public function actionStart()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $data = array(
            'data' => array(
                'migrations' =>  $request->getParam('migration', ''),
                'applied' =>  $request->getParam('applied', 0),
             ),
            'nextAction' => 'migrationmanager/run/start'
        );

        return $this->renderTemplate('migrationmanager/actions/run', $data);
    }

    public function actionRerun(){

        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        if ($request->getParam('migration') == false){
            Craft::$app->getSession()->setError(Craft::t('migrationmanager', 'You must select a migration to re run'));
            return $this->redirectToPostedUrl();
        } else {
            $data = array(
                'data' => array(
                    'migrations' => $request->getParam('migration', ''),
                    'applied' => $request->getParam('applied', 0),
                ),
                'nextAction' => $request->getParam('nextAction', 'migrationmanager/run/start')
            );
            return $this->renderTemplate('migrationmanager/actions/run', $data);
        }
    }
}
