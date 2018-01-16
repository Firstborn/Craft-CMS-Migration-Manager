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
        return craft()->plugins->getPlugin('migrationManager')->getName();
    }

    /**
     * @return array
     */
    public function getNewMigrations()
    {
        return craft()->migrationManager_migrations->getNewMigrations();
    }

    /**
     * @return array
     */
    public function getAppliedMigrations()
    {
        return craft()->migrationManager_migrations->getAppliedMigrations();
    }

    /**
     * @return array
     */
    public function getAssetSources()
    {
        return craft()->assetSources->getAllSources();
    }

    /**
     * @return array
     */
    public function getAssetTransforms()
    {
        return craft()->assetTransforms->getAllTransforms();
    }

    /**
     * @return array
     */
    public function getAllTagGroups()
    {
        return craft()->tags->getAllTagGroups();
    }
}
