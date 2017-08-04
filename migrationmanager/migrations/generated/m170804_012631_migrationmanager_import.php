<?php
namespace Craft;

/**
 * Generated migration
 */
class m170804_012631_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $json = '{"dependencies":[],"elements":{"fields":[{"group":"Default","name":"Link It","handle":"linkIt","instructions":"","translatable":"0","required":false,"type":"FruitLinkIt","typesettings":{"types":["email","custom","asset"],"defaultText":"","allowCustomText":"","allowTarget":"","entrySources":["blog"],"entrySelectionLabel":"Select entry","assetSources":"*","assetSelectionLabel":"Select an asset","categorySources":["categoryGroup"],"categorySelectionLabel":"Select a category"}}]}}';
        return craft()->migrationManager_migrations->import($json);
    }

}
