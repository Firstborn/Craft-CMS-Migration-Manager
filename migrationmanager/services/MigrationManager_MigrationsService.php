<?php

namespace Craft;

/**
 * Class MigrationManager_MigrationsService
 */
class MigrationManager_MigrationsService extends BaseApplicationComponent
{
    private $_migrationTable;

    private $_settingsMigrationTypes = array(
        'locale' => 'migrationManager_locales',
        'field' => 'migrationManager_fields',
        'section' => 'migrationManager_sections',
        'assetSource' => 'migrationManager_assetSources',
        'assetTransform' => 'migrationManager_assetTransforms',
        'global' => 'migrationManager_globals',
        'tag' => 'migrationManager_tags',
        'category' => 'migrationManager_categories',
        'route' => 'migrationManager_routes',
        'userGroup' => 'migrationManager_userGroups',
        'emailMessages' => 'migrationManager_emailMessages',
    );

    private $_settingsDependencyTypes = array(
        'locale' => 'migrationManager_locales',
        'section' => 'migrationManager_sections',
        'assetSource' => 'migrationManager_assetSources',
        'assetTransform' => 'migrationManager_assetTransforms',
        'tag' => 'migrationManager_tags',
        'category' => 'migrationManager_categories',
    );

    private $_contentMigrationTypes = array(
        'entry' => 'migrationManager_entriesContent',
        'category' => 'migrationManager_categoriesContent',
        'user' => 'migrationManager_usersContent',
        'global' => 'migrationManager_globalsContent',
    );

    public function init()
    {
        $migration = new MigrationRecord('migrationmanager');
        $this->_migrationTable = $migration->getTableName();
    }

    /**
     * create a new migration file based on input element types
     *
     * @param $data
     *
     * @return bool
     */
    public function createSettingMigration($data)
    {

        $manifest = [];

        $migration = array(
            'settings' => array(
                'dependencies' => array(),
                'elements' => array(),
            ),
        );

        $empty = true;

        //build a list of dependencies first to avoid potential cases where items are requested by fields before being created
        //export them without additional fields to prevent conflicts with missing fields, field tabs can be added on the second pass
        //after all the fields have been created

        foreach ($this->_settingsDependencyTypes as $key => $value) {
            $service = craft()->getComponent($value);
            if (array_key_exists($service->getSource(), $data)) {
                $migration['settings']['dependencies'][$service->getDestination()] = $service->export($data[$service->getSource()], false);
                $empty = false;

                if ($service->hasErrors()) {
                    $errors = $service->getErrors();
                    foreach ($errors as $error) {
                        MigrationManagerPlugin::log($error, LogLevel::Error);
                    }

                    return false;
                }
            }
        }

        foreach ($this->_settingsMigrationTypes as $key => $value) {
            $service = craft()->getComponent($value);

            if (array_key_exists($service->getSource(), $data)) {
                $migration['settings']['elements'][$service->getDestination()] = $service->export($data[$service->getSource()], true);
                $empty = false;

                if ($service->hasErrors()) {
                    $errors = $service->getErrors();
                    foreach ($errors as $error) {
                        MigrationManagerPlugin::log($error, LogLevel::Error);
                    }

                    return false;
                }
                $manifest = array_merge($manifest, [$key => $service->getManifest()]);
            }
        }

        if ($empty) {
            $migration = null;
        }

        if (array_key_exists('migrationName', $data)){
            $migrationName = trim($data['migrationName']);
            $migrationName = str_replace(' ', '_', $migrationName);
        } else {
            $migrationName = '';
        }

        $this->createMigration($migration, $manifest, $migrationName);

        return true;
    }

    /**
     * create a new migration file based on selected content elements
     *
     * @param $data
     *
     * @return bool
     */
    public function createContentMigration($data)
    {
        $manifest = [];

        $migration = array(
            'content' => array(),
        );

        $empty = true;

        foreach ($this->_contentMigrationTypes as $key => $value) {
            $service = craft()->getComponent($value);

            if (array_key_exists($service->getSource(), $data)) {
                $migration['content'][$service->getDestination()] = $service->export($data[$service->getSource()], true);
                $empty = false;

                if ($service->hasErrors()) {
                    $errors = $service->getErrors();
                    foreach ($errors as $error) {
                        MigrationManagerPlugin::log($error, LogLevel::Error);
                    }

                    return false;
                }
                $manifest = array_merge($manifest, [$key => $service->getManifest()]);
            }
        }

        if ($empty) {
            $migration = null;
        }

        $this->createMigration($migration, $manifest);

        return true;
    }

    /**
     * @param mixed $migration data to write in migration file
     * @param array $manifest
     *
     * @throws Exception
     */
    private function createMigration($migration, $manifest = array(), $migrationName = '')
    {
        $empty = is_null($migration);
        $date = new DateTime();
        $name = 'm%s_migration';
        $description = [];

        if ($migrationName == '') {

            foreach ($manifest as $key => $value) {
                $description[] = $key;
                foreach ($value as $item) {
                    $description[] = $item;
                }
            }
        } else {
            $description[] = $migrationName;
        }

        if (!$empty || count($description)>0) {
            $description = implode('_', $description);
            $name .= '_' . $description;
        }

        $filename = sprintf($name, $date->format('ymd_His'));
        $filename = substr($filename, 0, 250);
        $filename = str_replace('-', '_', $filename);

        $plugin = craft()->plugins->getPlugin('migrationmanager', false);
        $migrationPath = craft()->migrations->getMigrationPath($plugin) . '/generated';
        $path = sprintf($migrationPath . '/%s.php', $filename);

        $pathLen = strlen($path);
        if ($pathLen > 255) {
            $migrationPathLen = strlen($migrationPath);
            $filename = substr($filename, 0, 250 - $migrationPathLen);
            $path = sprintf($migrationPath . '/%s.php', $filename);
        }

        $migration = json_encode($migration, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $content = craft()->templates->render('migrationmanager/_migration', array('empty' => $empty, 'migration' => $migration, 'className' => $filename, 'manifest' => $manifest, true));

        IOHelper::writeToFile($path, $content);

        // mark the migration as completed if it's not a blank one
        if (!$empty) {
            $plugin = craft()->plugins->getPlugin('migrationmanager');
            $pluginInfo = craft()->plugins->getPluginInfo($plugin);

            craft()->db->createCommand()->insert($this->_migrationTable, array(
                'version' => $filename,
                'applyTime' => DateTimeHelper::currentTimeForDb(),
                'pluginId' => $pluginInfo['id'],
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function import($data)
    {
        $data = str_replace('\\', '\/', $data);
        $data = str_replace('\/r', '\r', $data);
        $data = str_replace('\/n', '\n', $data);
        $data = json_decode($data, true);

        try {
            if (array_key_exists('settings', $data)) {
                // run through dependencies first to create any elements that need to be in place for fields, field layouts and other dependencies
                foreach ($this->_settingsDependencyTypes as $key => $value) {
                    $service = craft()->getComponent($value);

                    if (array_key_exists($service->getDestination(), $data['settings']['dependencies'])) {
                        $service->import($data['settings']['dependencies'][$service->getDestination()]);

                        if ($service->hasErrors()) {
                            $errors = $service->getErrors();
                            foreach ($errors as $error) {
                                MigrationManagerPlugin::log($error, LogLevel::Error);
                            }

                            return false;
                        }
                    }
                }

                foreach ($this->_settingsMigrationTypes as $key => $value) {
                    $service = craft()->getComponent($value);

                    if (array_key_exists($service->getDestination(), $data['settings']['elements'])) {
                        $service->import($data['settings']['elements'][$service->getDestination()]);

                        if ($service->hasErrors()) {
                            $errors = $service->getErrors();
                            foreach ($errors as $error) {
                                MigrationManagerPlugin::log($error, LogLevel::Error);
                            }

                            return false;
                        }
                    }
                }
            }

            if (array_key_exists('content', $data)) {

                foreach ($this->_contentMigrationTypes as $key => $value) {
                    $service = craft()->getComponent($value);

                    if (array_key_exists($service->getDestination(), $data['content'])) {
                        $service->import($data['content'][$service->getDestination()]);

                        if ($service->hasErrors()) {
                            $errors = $service->getErrors();
                            foreach ($errors as $error) {
                                MigrationManagerPlugin::log($error, LogLevel::Error);
                            }

                            return false;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            MigrationManagerPlugin::log('Exception handled: ' . $e->getMessage(), LogLevel::Error);

            return false;
        }

        return true;
    }

    /**
     * @param array $migrationsToRun
     *
     * @return bool
     * @throws \CDbException
     */
    public function runToTop($migrationsToRun = null)
    {
        // This might take a while
        craft()->config->maxPowerCaptain();

        $plugin = craft()->plugins->getPlugin('migrationmanager');

        if (is_array($migrationsToRun)) {
            $migrations = array();
            foreach ($migrationsToRun as $migrationFile) {
                $migration = $this->getNewMigration($migrationFile);
                if ($migration) {
                    $migrations[] = $migration;
                }
            }
        } else {
            $migrations = $this->getNewMigrations();
        }

        $total = count($migrations);

        if ($total == 0) {
            MigrationManagerPlugin::log('No new migration(s) found. Your system is up-to-date.', LogLevel::Info, true);

            return true;
        }

        MigrationManagerPlugin::log("Total $total new " . ($total === 1 ? 'migration' : 'migrations') . " to be applied for Craft:", LogLevel::Info, true);

        foreach ($migrations as $migration) {
            // Refresh the DB cache
            craft()->db->getSchema()->refresh();

            if ($this->migrateUp($migration, $plugin) === false) {
                MigrationManagerPlugin::log('Migration ' . $migration . ' failed . All later migrations are canceled.', LogLevel::Info, true);

                // Refresh the DB cache
                craft()->db->getSchema()->refresh();

                return false;
            } else {
                MigrationManagerPlugin::log('Migration ' . $migration . ' successfully ran' . LogLevel::Info, true);
            }
        }

        if ($plugin) {
            MigrationManagerPlugin::log($plugin->getClassHandle() . ' migrated up successfully.', LogLevel::Info, true);
        } else {
            MigrationManagerPlugin::log('Craft migrated up successfully.', LogLevel::Info, true);
        }

        // Refresh the DB cache
        craft()->db->getSchema()->refresh();

        return true;
    }

    /**
     * Remove applied migrations from the migration table so they can be run again
     *
     * @param $migrations
     */
    public function setMigrationsAsNotApplied($migrations)
    {
        $plugin = craft()->plugins->getPlugin('migrationmanager');
        $pluginInfo = craft()->plugins->getPluginInfo($plugin);

        foreach ($migrations as $migration) {
            craft()->db->createCommand()->delete($this->_migrationTable, array(
                'version' => $migration,
                'pluginId' => $pluginInfo['id'],
            ));
        }
    }

    /**
     * Add applied migrations to the migration table
     *
     * @param $migrations
     */
    public function setMigrationsAsApplied($migrations)
    {
        $plugin = craft()->plugins->getPlugin('migrationmanager');
        $pluginInfo = craft()->plugins->getPluginInfo($plugin);

        foreach ($migrations as $migration) {
            $plugin = craft()->plugins->getPlugin('migrationmanager');
            $pluginInfo = craft()->plugins->getPluginInfo($plugin);

            craft()->db->createCommand()->insert($this->_migrationTable, array(
                'version' => $migration,
                'applyTime' => DateTimeHelper::currentTimeForDb(),
                'pluginId' => $pluginInfo['id'],
            ));
        }
    }

    /**
     * Gets migrations that have no been applied yet
     *
     * @param BasePlugin $plugin
     *
     * @return array
     * @throws Exception
     */
    public function getAppliedMigrations($plugin = null)
    {
        $migrations = array();
        if ($plugin == null) {
            $plugin = craft()->plugins->getPlugin('migrationmanager', false);
        }

        $migrationPath = craft()->migrations->getMigrationPath($plugin) . 'generated/';

        if (IOHelper::folderExists($migrationPath) && IOHelper::isReadable($migrationPath)) {
            $applied = array();

            foreach (craft()->migrations->getMigrationHistory($plugin) as $migration) {
                $applied[] = $migration['version'];
            }

            $handle = opendir($migrationPath);

            while (($file = readdir($handle)) !== false) {
                if ($file[0] === '.') {
                    continue;
                }

                $migration = $this->getMigration($file, $plugin);
                if ($migration) {

                    // Have we already run this migration?
                    if (in_array($migration, $applied)) {
                        $migrations[] = $migration;
                    }
                }
            }

            closedir($handle);
            sort($migrations);
        }

        return $migrations;
    }

    /**
     * Gets migrations that have no been applied yet
     *
     * @param BasePlugin $plugin
     *
     * @return array
     * @throws Exception
     */
    public function getNewMigrations($plugin = null)
    {
        $migrations = array();
        if ($plugin == null) {
            $plugin = craft()->plugins->getPlugin('migrationmanager', false);
        }

        $migrationPath = craft()->migrations->getMigrationPath($plugin) . 'generated/';

        if (IOHelper::folderExists($migrationPath) && IOHelper::isReadable($migrationPath)) {
            $applied = array();

            foreach (craft()->migrations->getMigrationHistory($plugin) as $migration) {
                $applied[] = $migration['version'];
            }

            $handle = opendir($migrationPath);

            while (($file = readdir($handle)) !== false) {
                if ($file[0] === '.') {
                    continue;
                }

                $migration = $this->getMigration($file, $plugin);
                if ($migration) {

                    // Have we already run this migration?
                    if (in_array($migration, $applied)) {
                        continue;
                    }
                    $migrations[] = $migration;
                }
            }

            closedir($handle);
            sort($migrations);
        }

        return $migrations;
    }

    /**
     * @param int $id
     *
     * @return bool|string
     * @throws Exception
     */
    public function getNewMigration($id)
    {
        $plugin = craft()->plugins->getPlugin('migrationmanager', false);

        $applied = array();
        foreach (craft()->migrations->getMigrationHistory($plugin) as $migration) {
            $applied[] = $migration['version'];
        }

        $migration = $this->getMigration($id . '.php', $plugin);

        if ($migration) {

            // Have we already run this migration?
            if (in_array($migration, $applied)) {
                return false;
            }

            return $migration;
        }

        return false;
    }

    /**
     * @param string          $file
     * @param BasePlugin|null $plugin
     *
     * @return bool|string
     * @throws Exception
     */
    public function getMigration($file, $plugin)
    {
        $migrationPath = craft()->migrations->getMigrationPath($plugin) . 'generated/';
        $path = IOHelper::normalizePathSeparators($migrationPath . $file);
        $class = IOHelper::getFileName($path, false);

        if (preg_match('/^m(\d\d)(\d\d)(\d\d)_(\d\d)(\d\d)(\d\d)_\w+\.php$/', $file, $matches)) {
            return $class;
        } else {
            return false;
        }
    }

    /**
     * @param string          $class
     * @param BasePlugin|null $plugin
     *
     * @return bool|null
     * @throws Exception
     */
    private function migrateUp($class, $plugin = null)
    {
        if ($class === craft()->migrations->getBaseMigration()) {
            return null;
        }

        if ($plugin) {
            MigrationManagerPlugin::log('Applying migration: ' . $class . ' for plugin: ' . $plugin->getClassHandle(), LogLevel::Info, true);
        } else {
            MigrationManagerPlugin::log('Applying migration: ' . $class, LogLevel::Info, true);
        }

        $start = microtime(true);
        $migration = $this->instantiateMigration($class, $plugin);

        if ($migration->up() !== false) {
            if ($plugin) {
                $pluginInfo = craft()->plugins->getPluginInfo($plugin);

                craft()->db->createCommand()->insert($this->_migrationTable, array(
                    'version' => $class,
                    'applyTime' => DateTimeHelper::currentTimeForDb(),
                    'pluginId' => $pluginInfo['id'],
                ));
            } else {
                craft()->db->createCommand()->insert($this->_migrationTable, array(
                    'version' => $class,
                    'applyTime' => DateTimeHelper::currentTimeForDb(),
                ));
            }

            $time = microtime(true) - $start;
            MigrationManagerPlugin::log('Applied migration: ' . $class . ' (time: ' . sprintf("%.3f", $time) . 's)', LogLevel::Info, true);

            return true;
        }

        return false;
    }

    /**
     * @param string          $class
     * @param BasePlugin|null $plugin
     *
     * @throws Exception
     * @return mixed
     */
    private function instantiateMigration($class, $plugin = null)
    {
        $file = IOHelper::normalizePathSeparators(craft()->migrations->getMigrationPath($plugin) . 'generated/' . $class . '.php');

        if (!IOHelper::fileExists($file) || !IOHelper::isReadable($file)) {
            MigrationManagerPlugin::log('Tried to find migration file ' . $file . ' for class ' . $class . ', but could not.', LogLevel::Error);
            throw new Exception(Craft::t('Could not find the requested migration file.'));
        }

        require_once($file);

        $class = __NAMESPACE__ . '\\' . $class;
        $migration = new $class;
        $migration->setDbConnection(craft()->db);

        return $migration;
    }
}
