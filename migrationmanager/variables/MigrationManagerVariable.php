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
    public function getNewMigrations()
    {
        return craft()->migrationManager_migrations->getNewMigrations();
    }

    public function getAssetSources()
    {
        return craft()->assetSources->getAllSources();
    }

    public function getAssetTransforms()
    {
        return craft()->assetTransforms->getAllTransforms();
    }




}
