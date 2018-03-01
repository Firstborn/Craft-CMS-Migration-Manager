<?php

namespace Craft;

/**
 * Deploy Variable provides access to database objects from templates
 */
class MigrationManagerVariable
{
    /**
     * @return string
     */
    public function getName()
    {
        return Craft::$app->plugins->getPlugin('migrationManager')->getName();
    }

    /**
     * @return array
     */
    public function getNewMigrations()
    {
        return Craft::$app->migrationManager_migrations->getNewMigrations();
    }

    /**
     * @return array
     */
    public function getAppliedMigrations()
    {
        return Craft::$app->migrationManager_migrations->getAppliedMigrations();
    }

    /**
     * @return array
     */
    public function getAssetSources()
    {
        return Craft::$app->assetSources->getAllSources();
    }

    /**
     * @return array
     */
    public function getAssetTransforms()
    {
        return Craft::$app->assetTransforms->getAllTransforms();
    }

    /**
     * @return array
     */
    public function getAllTagGroups()
    {
        return Craft::$app->tags->getAllTagGroups();
    }
}
