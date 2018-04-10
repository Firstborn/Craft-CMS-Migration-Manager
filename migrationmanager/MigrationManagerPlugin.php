<?php

namespace Craft;

/**
 * Migration Manager plugin for Craft CMS
 *
 * Create Craft migrations to easily migrate settings and content between website environments.
 *
 * @author    Derrick Grigg
 * @copyright Copyright (c) 2017 Firstborn
 * @link      https://firstborn.com
 * @package   MigrationManager
 * @since     1.0.0
 */

namespace Craft;

class MigrationManagerPlugin extends BasePlugin
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Craft::t('Migration Manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0.9.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getDeveloper()
    {
        return 'Derrick Grigg';
    }

    /**
     * {@inheritdoc}
     */
    public function getDeveloperUrl()
    {
        return 'https://www.firstborn.com';
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/Firstborn/Craft-CMS-Migration-Manager/master/releases.json';
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentationUrl()
    {
        return 'https://github.com/Firstborn/Craft-CMS-Migration-Manager/tree/master/README.md';
    }

    /**
     * {@inheritdoc}
     */
    function init(){
        Craft::import('plugins.migrationmanager.helpers.MigrationManagerHelper');
        Craft::import('plugins.migrationmanager.services.MigrationManager_IMigrationService');
        Craft::import('plugins.migrationmanager.service.MigrationManager_BaseMigrationService');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateCategoryElementAction');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateEntryElementAction');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateUserElementAction');

         // check we have a cp request as we don't want to this js to run anywhere but the cp
        // and while we're at it check for a logged in user as well
        if ( craft()->request->isCpRequest() && craft()->userSession->isLoggedIn()) {

            // add a Create Migration button to the globals screen
            if (craft()->request->getSegment(1) == 'globals' ) {
                // the includeJsResource method will add a js file to the bottom of the page
                craft()->templates->includeJsResource('migrationmanager/js/MigrationManagerGlobalsExport.js');
                craft()->templates->includeJs("new Craft.MigrationManagerGlobalsExport();");
            }

            //show alert on sidebar if migrations are pending
            $pendingMigrations = count(craft()->migrationManager_migrations->getNewMigrations());
            if ($pendingMigrations > 0) {
                craft()->templates->includeCssResource('migrationmanager/css/styles.css');
                craft()->templates->includeJsResource('migrationmanager/js/MigrationManagerSideBar.js');
                craft()->templates->includeJs("new Craft.MigrationManagerSideBar({$pendingMigrations});");
            }
        }

    }

    /**
     * {@inheritdoc}
     */
    public function hasCpSection()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function registerCpRoutes()
    {
        return array(
            'migrationmanager' => array('action' => 'migrationManager/index'),
            'migrationmanager/create' => array('action' => 'migrationManager/create'),
            'migrationmanager/pending' => array('action' => 'migrationManager/pending'),
            'migrationmanager/applied' => array('action' => 'migrationManager/applied'),
            'migrationmanager/logs' => array('action' => 'migrationManager/logs'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addEntryActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateEntryElementAction(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addCategoryActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateCategoryElementAction(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addUserActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateUserElementAction(),
        );
    }
}
