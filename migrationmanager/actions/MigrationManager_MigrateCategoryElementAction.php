<?php

namespace Craft;

/**
 * Class MigrationManager_MigrateCategoryElementAction
 */
class MigrationManager_MigrateCategoryElementAction extends BaseElementAction
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Craft::t('Create migration');
    }

    /**
     * {@inheritdoc}
     */
    public function performAction(ElementCriteriaModel $criteria)
    {
        $params['category'] = $criteria->ids();

        if (craft()->migrationManager_migrations->createContentMigration($params)) {

            $this->setMessage(Craft::t('Migration created.'));
            return true;
        } else {

            $this->setMessage(Craft::t('Migration could not be created.'));
            return false;
        }
    }
}
