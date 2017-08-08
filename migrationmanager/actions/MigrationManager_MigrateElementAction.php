<?php
namespace Craft;

/**
 * Delete Element Action
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @link      http://craftcms.com
 * @package   craft.app.elementactions
 * @since     2.3
 */
class MigrationManager_MigrateElementAction extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Create migration');
	}

	/**
	 * @inheritDoc IElementAction::performAction()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return bool
	 */
	public function performAction(ElementCriteriaModel $criteria)
	{

	    $params['entry'] = $criteria->ids();
        if (craft()->migrationManager_migrations->create($params)) {
            //craft()->migrationManager_entries->export($criteria->ids());
            $this->setMessage(Craft::t('Migration created.'));
            return true;

        } else {
            $this->setMessage(Craft::t('Migration could not be created.'));
            return false;

        }

	}


}
