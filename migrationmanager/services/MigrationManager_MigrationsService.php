<?php

namespace Craft;
class MigrationManager_MigrationsService extends BaseApplicationComponent
{

    /**
     * create a new migration file based on input element types
     * @param $data
     * @return bool
     */
    public function create($data)
    {
        $migration = array();

        if (array_key_exists('field', $data))
        {
            $migration['fields'] = craft()->migrationManager_fields->exportFields($data['field']);
        }

        if (array_key_exists('section', $data))
        {
             $migration['sections'] = craft()->migrationManager_sections->exportSections($data['section']);
        }

        //assetSource, imageTransform, global, category, route

        $date = new DateTime();
        $filename = sprintf('m%s_migrationmanager_import', $date->format('ymd_His'));
        $path = sprintf(CRAFT_PLUGINS_PATH . 'migrationmanager/migrations/%s.php', $filename);
        $content = craft()->templates->render('migrationmanager/_migration', array('migration' => $migration, 'className' => $filename, true));
        IOHelper::writeToFile($path, $content);
        return true;
    }

    public function import($data)
    {
        $result = true;
        if (array_key_exists('fields', $data))
        {
            if (craft()->migrationManager_fields->importFields($data['fields']) == false)
            {
                $result = false;
            }
        }

        if (array_key_exists('sections', $data))
        {
            if (craft()->migrationManager_sections->importSections($data['sections']) == false)
            {
                $result = false;
            }
        }

        return $result;


    }

    /**
     *
     *
     * @return mixed
     */
    public function runToTop()
    {
        // This might take a while
        craft()->config->maxPowerCaptain();

        $plugin = craft()->plugins->getPlugin('migrationmanager');

        if (($migrations = $this->getNewMigrations($plugin)) === array())
        {
            MigrationManagerPlugin::log('No new migration(s) found. Your system is up-to-date.', LogLevel::Info, true);
            return true;
        }

        $total = count($migrations);

        MigrationManagerPlugin::log("Total $total new ".($total === 1 ? 'migration' : 'migrations')." to be applied for Craft:", LogLevel::Info, true);

        foreach ($migrations as $migration)
        {
            // Refresh the DB cache
            craft()->db->getSchema()->refresh();

            if (craft()->migrations->migrateUp($migration, $plugin) === false)
            {

                MigrationManagerPlugin::log('Migration ' . $migration . ' failed . All later migrations are canceled.', LogLevel::Error);

                // Refresh the DB cache
                craft()->db->getSchema()->refresh();

                return false;
            } else {
                MigrationManagerPlugin::log('Migration ' . $migration . ' successfully ran'. LogLevel::Info, true);
            }
        }

        if ($plugin)
        {
            MigrationManagerPlugin::log($plugin->getClassHandle().' migrated up successfully.', LogLevel::Info, true);
        }
        else
        {
            MigrationManagerPlugin::log('Craft migrated up successfully.', LogLevel::Info, true);
        }

        // Refresh the DB cache
        craft()->db->getSchema()->refresh();

        return true;
    }

    public function getNewMigrations($plugin = null)
    {

        if ($plugin == null){
            $plugin = craft()->plugins->getPlugin('migrationmanager', false);
        }

        $migrations = craft()->migrations->getNewMigrations($plugin);
        return $migrations;
    }


}
