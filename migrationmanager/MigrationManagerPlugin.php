<?php

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
    function getName()
    {
        return Craft::t('Migration Manager');
    }

    function getVersion()
    {
        return '1.0.6';
    }

    function getDeveloper()
    {
        return 'Derrick Grigg';
    }

    function getDeveloperUrl()
    {
        return 'https://www.firstborn.com';
    }

    public function hasCpSection()
    {
        if (craft()->userSession->isAdmin()) {
            return true;
        }
    }

    public function addEntryActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateEntryElementAction()
        );
    }

    public function addCategoryActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateCategoryElementAction()
        );
    }

    public function addUserActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateUserElementAction()
        );
    }

    public function registerCpRoutes()
    {
        return array(
            'migrationmanager/run-migration' => array('action' => 'migrationManager/runMigration'),
            'migrationmanager/migrations' => array('action' => 'migrationManager/migrations'),
            'migrationmanager/log' => array('action' => 'migrationManager/log')
        );
    }

    function init(){
        Craft::import('plugins.migrationmanager.helpers.MigrationManagerHelper');
        Craft::import('plugins.migrationmanager.services.MigrationManager_IMigrationService');
        Craft::import('plugins.migrationmanager.service.MigrationManager_BaseMigrationService');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateCategoryElementAction');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateEntryElementAction');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateUserElementAction');

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


}
