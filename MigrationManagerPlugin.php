<?php

namespace Craft;

/**
 * Migration Manager plugin for Craft CMS
 *
 * Create Craft migrations to easily migrate settings and content between website environments.
 *
 * @author    Derrick Grigg
 * @copyright Copyright (c) 2017 FirstBorn
 * @link      https://firstborn.com
 * @package   MigrationManager
 * @since     1.0.0
 */
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
        return '1.0.6';
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
        return 'https://github.com/Firstborn/Craft-CMS-Migration-Manager/tree/master/migrationmanager/releases.json';
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        Craft::import('plugins.migrationmanager.helpers.MigrationManagerHelper');
        Craft::import('plugins.migrationmanager.services.MigrationManager_IMigrationService');
        Craft::import('plugins.migrationmanager.service.MigrationManager_BaseMigrationService');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateCategoryElementAction');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateEntryElementAction');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateUserElementAction');

        // add a Create Migration button to the globals screen
        // check we have a cp request as we don't want to this js to run anywhere but the cp
        // and while we're at it check for a logged in user as well
        if (craft()->request->isCpRequest() && craft()->userSession->isLoggedIn() && craft()->request->getSegment(1) == 'globals') {
            // the includeJsResource method will add a js file to the bottom of the page
            craft()->templates->includeJsResource('migrationmanager/js/MigrationManagerGlobalsExport.js');
            craft()->templates->includeJs("new Craft.MigrationManagerGlobalsExport();");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasCpSection()
    {
        if (craft()->userSession->isAdmin()) {
            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerCpRoutes()
    {
        return array(
            'migrationmanager/run-migration' => array('action' => 'migrationManager/runMigration'),
            'migrationmanager/migrations' => array('action' => 'migrationManager/migrations'),
            'migrationmanager/log' => array('action' => 'migrationManager/log'),
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
