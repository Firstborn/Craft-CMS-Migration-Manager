<?php
namespace Craft;

class MigrationManager_MigrateCategoryElementAction extends BaseElementAction
{

	public function getName()
	{
		return Craft::t('Create migration');
	}

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
