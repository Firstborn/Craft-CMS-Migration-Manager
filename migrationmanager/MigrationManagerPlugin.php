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
        return '1.0.3';
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


    public function registerCpRoutes()
    {
        return array(
            'migrationmanager/run-migration' => array('action' => 'migrationManager/runMigration'),
            'migrationmanager/migrations' => array('action' => 'migrationManager/migrations'),
            'migrationmanager/log' => array('action' => 'migrationManager/log')

        );
    }

    function init(){
        require CRAFT_PLUGINS_PATH.'/migrationshelper/helpers/MigrationsHelper.php';
    }



}