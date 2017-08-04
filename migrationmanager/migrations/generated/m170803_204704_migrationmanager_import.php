<?php
namespace Craft;

/**
 * Generated migration
 */
class m170803_204704_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $json = '{"dependencies":[],"elements":{"fields":[{"group":"Default","name":"Link It","handle":"linkIt","instructions":"","translatable":"0","required":false,"type":"FruitLinkIt","typesettings":{"types":["email","tel","entry","category","asset"],"defaultText":"Default Linky Text","allowCustomText":"1","allowTarget":"1","entrySources":["singles","blog"],"entrySelectionLabel":"Select an entry","assetSources":["uploads","local"],"assetSelectionLabel":"Select an asset","categorySources":["categoryGroup","moreCategories"],"categorySelectionLabel":"Select a category"}}]}}';
        return craft()->migrationManager_migrations->import($json);
    }

}
