<?php
namespace Craft;


class MigrationManagerPlugin extends BasePlugin
{
    function getName()
    {
        return Craft::t('Migration Manager');
    }

    function getVersion()
    {
        return '1.0.4';
    }

    function getDeveloper()
    {
        return 'Derrick Grigg';
    }

    function getDeveloperUrl()
    {
        return 'http://dgrigg.com';
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
        require_once(CRAFT_PLUGINS_PATH . '/migrationmanager/helpers/MigrationManagerHelper.php');
        require_once(CRAFT_PLUGINS_PATH . '/migrationmanager/services/MigrationManager_IMigrationService.php');
        require_once(CRAFT_PLUGINS_PATH . '/migrationmanager/services/MigrationManager_BaseMigrationService.php');
        require_once(CRAFT_PLUGINS_PATH . '/migrationmanager/actions/MigrationManager_MigrateCategoryElementAction.php');
        require_once(CRAFT_PLUGINS_PATH . '/migrationmanager/actions/MigrationManager_MigrateEntryElementAction.php');
        require_once(CRAFT_PLUGINS_PATH . '/migrationmanager/actions/MigrationManager_MigrateUserElementAction.php');


    }


}