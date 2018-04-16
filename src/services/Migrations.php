<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\FileHelper;
use firstborn\migrationmanager\MigrationManager;
use DateTime;

class Migrations extends Component
{
    private $_migrationTable;

    private $_settingsMigrationTypes = array(
        'site' => 'sites',
        'field' => 'fields',
        'section' => 'sections',
        'assetVolume' => 'assetVolumes',
        'assetTransform' => 'assetTransforms',
        'global' => 'globals',
        'tag' => 'tags',
        'category' => 'categories',
        'route' => 'routes',
        'userGroup' => 'userGroups',
        'systemMessages' => 'systemMessages',
    );

    private $_settingsDependencyTypes = array(
        'site' => 'sites',
        'section' => 'sections',
        'assetVolume' => 'assetVolumes',
        'assetTransform' => 'assetTransforms',
        'tag' => 'tags',
        'category' => 'categories',
    );

    private $_contentMigrationTypes = array(
        'entry' => 'entriesContent',
        'category' => 'categoriesContent',
        'user' => 'usersContent',
        'global' => 'globalsContent',
    );


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
        $plugin = MigrationManager::getInstance();

        foreach ($this->_settingsDependencyTypes as $key => $value) {
            $service = $plugin->get($value);
            if (array_key_exists($service->getSource(), $data)) {
                $migration['settings']['dependencies'][$service->getDestination()] = $service->export($data[$service->getSource()], false);
                $empty = false;

                if ($service->hasErrors()) {
                    $errors = $service->getErrors();
                    foreach ($errors as $error) {
                        Craft::error($error, __METHOD__);
                    }

                    return false;
                }
            }
        }

        foreach ($this->_settingsMigrationTypes as $key => $value) {
            $service = $plugin->get($value);
            if (array_key_exists($service->getSource(), $data)) {
                $migration['settings']['elements'][$service->getDestination()] = $service->export($data[$service->getSource()], true);
                $empty = false;

                if ($service->hasErrors()) {
                    $errors = $service->getErrors();
                    foreach ($errors as $error) {

                        Craft::error($log, __METHOD__);
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
        $plugin = MigrationManager::getInstance();

        foreach ($this->_contentMigrationTypes as $key => $value) {
            $service = $plugin->get($value);

            if (array_key_exists($service->getSource(), $data)) {
                $migration['content'][$service->getDestination()] = $service->export($data[$service->getSource()], true);
                $empty = false;

                if ($service->hasErrors()) {
                    $errors = $service->getErrors();
                    foreach ($errors as $error) {
                        Craft::error($error);
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

        $migrator = Craft::$app->getContentMigrator();
        $migrationPath = $migrator->migrationPath;

        $path = sprintf($migrationPath . '/%s.php', $filename);

        $pathLen = strlen($path);
        if ($pathLen > 255) {
            $migrationPathLen = strlen($migrationPath);
            $filename = substr($filename, 0, 250 - $migrationPathLen);
            $path = sprintf($migrationPath . '/%s.php', $filename);
        }

        $migration = json_encode($migration, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $content = Craft::$app->view->renderTemplate('migrationmanager/_migration', array('empty' => $empty, 'migration' => $migration, 'className' => $filename, 'manifest' => $manifest, true));

        FileHelper::writeToFile($path, $content);

        // mark the migration as completed if it's not a blank one
        if (!$empty) {
            $migrator->addMigrationHistory($filename);
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

        $plugin = MigrationManager::getInstance();

        try {
            if (array_key_exists('settings', $data)) {
                // run through dependencies first to create any elements that need to be in place for fields, field layouts and other dependencies
                foreach ($this->_settingsDependencyTypes as $key => $value) {
                    $service = $plugin->get($value);
                    if (array_key_exists($service->getDestination(), $data['settings']['dependencies'])) {
                        $service->import($data['settings']['dependencies'][$service->getDestination()]);
                        if ($service->hasErrors()) {
                            $errors = $service->getErrors();
                            foreach ($errors as $error) {
                                Craft::error($error);
                            }
                            return false;
                        }
                    }
                }

                foreach ($this->_settingsMigrationTypes as $key => $value) {
                    $service = $plugin->get($value);
                    if (array_key_exists($service->getDestination(), $data['settings']['elements'])) {
                        $service->import($data['settings']['elements'][$service->getDestination()]);
                        if ($service->hasErrors()) {
                            $errors = $service->getErrors();
                            foreach ($errors as $error) {
                                Craft::error($error);
                            }
                            return false;
                        }
                    }
                }
            }

            if (array_key_exists('content', $data)) {
                foreach ($this->_contentMigrationTypes as $key => $value) {
                    $service = $plugin->get($value);
                    if (array_key_exists($service->getDestination(), $data['content'])) {
                        $service->import($data['content'][$service->getDestination()]);
                        if ($service->hasErrors()) {
                            $errors = $service->getErrors();
                            foreach ($errors as $error) {
                                Craft::error($error);
                            }
                            return false;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Craft::error('Exception handled: ' . $e->getMessage());
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
    public function runMigrations($migrationNames = [])
    {
        // This might take a while
        App::maxPowerCaptain();

        if (empty($migrationNames)) {
            $migrationNames = $this->getNewMigrations();
        }

        $total = count($migrationNames);
        $n = count($migrationNames);

        if ($n === $total) {
            $logMessage = "Total $n new ".($n === 1 ? 'migration' : 'migrations').' to be applied:';
        } else {
            $logMessage = "Total $n out of $total new ".($total === 1 ? 'migration' : 'migrations').' to be applied:';
        }

        foreach ($migrationNames as $migrationName) {
            $logMessage .= "\n\t$migrationName";
        }

        foreach ($migrationNames as $migrationName) {
             try {
                $migrator = Craft::$app->getContentMigrator();
                $migrator->migrateUp($migrationName);
            } catch (MigrationException $e) {
                Craft::error('Migration failed. The rest of the migrations are cancelled.', __METHOD__);
                throw $e;
            }
        }

        return true;
    }

    /**
     * Gets migrations that have no been applied yet
     *
     * @param BasePlugin $plugin
     *
     * @return array
     * @throws Exception
     */
    public function getNewMigrations()
    {
        $migrator = Craft::$app->getContentMigrator();
        $newMigrations = $migrator->getNewMigrations();
        return $newMigrations;
    }


}
