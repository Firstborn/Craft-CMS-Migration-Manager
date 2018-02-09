<?php

namespace firstborn\migrationmanager\controllers;

use Craft;
use craft\web\Controller;
use firstborn\migrationmanager\MigrationManager;


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

        //Craft::$app->getSession()->setError(Craft::t('migrationmanager', 'Migration created'));
        //Craft::$app->getSession()->setNotice(Craft::t('migrationmanager', 'Migration created'));
        //return $this->renderTemplate('migrationmanager/index');

        // Prevent GET Requests
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $post = $request->post();

        if (MigrationManager::getInstance()->migrations->createSettingMigration($post)) {
            Craft::$app->getSession()->setNotice(Craft::t('migrationmanager', 'Migration created.'));

        } else {
            Craft::$app->getSession()->setError(Craft::t('migrationmanager', 'Could not create migration, check log tab for errors.'));
        }

        $this->redirectToPostedUrl();
    }

    /**
     * @throws HttpException
     */
    public function actionCreateGlobalsContentMigration()
    {
        $this->requirePostRequest();
        craft()->userSession->requireAdmin();

        $globalSet = new GlobalSetModel();

        // Set the simple stuff
        $globalSet->id = craft()->request->getPost('setId');
        $globalSet->name = craft()->request->getPost('name');
        $globalSet->handle = craft()->request->getPost('handle');

        // Set the field layout
        $fieldLayout = craft()->fields->assembleLayoutFromPost();
        $fieldLayout->type = ElementType::GlobalSet;
        $globalSet->setFieldLayout($fieldLayout);

        $params['global'] = array(craft()->request->getPost('setId'));

        if (craft()->migrationManager_migrations->createContentMigration($params)) {
            craft()->userSession->setNotice(Craft::t('Migration created.'));
        } else {
            craft()->userSession->setError(Craft::t('Could not create migration, check log tab for errors.'));
        }

        $this->redirectToPostedUrl($globalSet);
    }
}
