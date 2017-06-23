<?php

namespace Craft;
class MigrationManager_MigrationsService extends BaseApplicationComponent
{

    private $_migrationTable;

    private $_migrationTypes =  array(
        'field' => array(
            'source' => 'field',
            'destination' => 'fields',
            'service' => 'migrationManager_fields'
        ),
        'section' => array(
            'source' => 'section',
            'destination' => 'sections',
            'service' => 'migrationManager_sections'
        )
    );

    public function init()
    {
        $migration = new MigrationRecord('migrationmanager');
        $this->_migrationTable = $migration->getTableName();
    }

    /**
     * create a new migration file based on input element types
     * @param $data
     * @return bool
     */
    public function create($data)
    {
        $migration = array();
        $empty = true;

        foreach($this->_migrationTypes as $key => $type)
        {
            if (array_key_exists($type['source'], $data)) {
                $service = craft()->getComponent($type['service']);
                $migration[$type['destination']] = $service->export($data[$type['source']]);
                $empty = false;
            }
        }

        //imageTransform, global, category, route

        $date = new DateTime();
        $filename = sprintf('m%s_migrationmanager_import', $date->format('ymd_His'));
        $plugin = craft()->plugins->getPlugin('migrationmanager', false);
        $migrationPath = craft()->migrations->getMigrationPath($plugin);
        $path = sprintf($migrationPath . 'generated/%s.php', $filename);
        $content = craft()->templates->render('migrationmanager/_migration', array('empty' => $empty, 'migration' => $migration, 'className' => $filename, true));
        IOHelper::writeToFile($path, $content);
        return true;

    }

    public function import($data)
    {
        try
        {
            foreach ($this->_migrationTypes as $key => $type) {
                if (array_key_exists($type['destination'], $data)) {
                    $service = craft()->getComponent($type['service']);
                    $service->import($data[$type['destination']]);
                    if ($service->hasErrors())
                    {
                        $errors = $service->getErrors();
                        foreach($errors as $error){
                            MigrationManagerPlugin::log($error, LogLevel::Error);
                        }
                        return false;
                     }
                }
            }
        }
        catch (Exception $e)
        {
            MigrationManagerPlugin::log('Exception handled: ' . $e->getMessage(), LogLevel::Error);
            return false;
        }

        return true;
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

            if ($this->migrateUp($migration, $plugin) === false)
            {
                MigrationManagerPlugin::log('Migration ' . $migration . ' failed . All later migrations are canceled.', LogLevel::Info, true);

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

    /**
     * Gets migrations that have no been applied yet
     *
     * @param $plugin
     *
     * @return array
     */
    public function getNewMigrations($plugin = null)
    {
        $migrations = array();
        if ($plugin == null){
            $plugin = craft()->plugins->getPlugin('migrationmanager', false);
        }
        $migrationPath = craft()->migrations->getMigrationPath($plugin) . 'generated/';

        if (IOHelper::folderExists($migrationPath) && IOHelper::isReadable($migrationPath))
        {
            $applied = array();

            foreach (craft()->migrations->getMigrationHistory($plugin) as $migration)
            {
                $applied[] = $migration['version'];
            }

            $handle = opendir($migrationPath);

            while (($file = readdir($handle)) !== false)
            {
                if ($file[0] === '.')
                {
                    continue;
                }

                $path = IOHelper::normalizePathSeparators($migrationPath.$file);
                $class = IOHelper::getFileName($path, false);

                // Have we already run this migration?
                if (in_array($class, $applied))
                {
                    continue;
                }

                if (preg_match('/^m(\d\d)(\d\d)(\d\d)_(\d\d)(\d\d)(\d\d)_\w+\.php$/', $file, $matches))
                {
                    $migrations[] = $class;
                }
            }

            closedir($handle);
            sort($migrations);
        }

        return $migrations;
    }

    /**
     * @param      $class
     * @param null $plugin
     *
     * @return bool|null
     */
    private function migrateUp($class, $plugin = null)
    {
        if($class === craft()->migrations->getBaseMigration())
        {
            return null;
        }

        if ($plugin)
        {
            MigrationManagerPlugin::log('Applying migration: '.$class.' for plugin: '.$plugin->getClassHandle(), LogLevel::Info, true);
        }
        else
        {
            MigrationManagerPlugin::log('Applying migration: '.$class, LogLevel::Info, true);
        }

        $start = microtime(true);
        $migration = $this->instantiateMigration($class, $plugin);

        if ($migration->up() !== false)
        {
            if ($plugin)
            {
                $pluginInfo = craft()->plugins->getPluginInfo($plugin);

                craft()->db->createCommand()->insert($this->_migrationTable, array(
                    'version' => $class,
                    'applyTime' => DateTimeHelper::currentTimeForDb(),
                    'pluginId' => $pluginInfo['id']
                ));
            }
            else
            {
                craft()->db->createCommand()->insert($this->_migrationTable, array(
                    'version' => $class,
                    'applyTime' => DateTimeHelper::currentTimeForDb()
                ));
            }

            $time = microtime(true) - $start;
            MigrationManagerPlugin::log('Applied migration: '.$class.' (time: '.sprintf("%.3f", $time).'s)', LogLevel::Info, true);
            return true;
        }
        else
        {
            //$time = microtime(true) - $start;
            //MigrationManagerPlugin::log('Failed to apply migration: '.$class.' (time: '.sprintf("%.3f", $time).'s)', LogLevel::Error);
            return false;
        }
    }

    /**
     * @param       $class
     * @param  null $plugin
     *
     * @throws Exception
     * @return mixed
     */
    private function instantiateMigration($class, $plugin = null)
    {
        $file = IOHelper::normalizePathSeparators(craft()->migrations->getMigrationPath($plugin) . 'generated/' .$class.'.php');

        if (!IOHelper::fileExists($file) || !IOHelper::isReadable($file))
        {
            MigrationManagerPlugin::log('Tried to find migration file '.$file.' for class '.$class.', but could not.', LogLevel::Error);
            throw new Exception(Craft::t('Could not find the requested migration file.'));
        }

        require_once($file);

        $class = __NAMESPACE__.'\\'.$class;
        $migration = new $class;
        $migration->setDbConnection(craft()->db);

        return $migration;
    }


}
