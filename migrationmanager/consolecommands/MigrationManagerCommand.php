<?php

namespace Craft;

/**
 * Class MigrationManagerCommand
 */
class MigrationManagerCommand extends BaseCommand
{
    /**
     * Runs migrations from command line interface
     */
    public function actionRun()
    {
        craft()->migrationManager_migrations->runToTop();
    }
}