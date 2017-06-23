<?php
namespace Craft;

/**
 * Generated migration
 */
class m170623_141617_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"fields":[{"group":"Default","name":"Rich Text","handle":"richText","instructions":"","translatable":"0","required":false,"type":"RichText","typesettings":{"configFile":"","availableAssetSources":[],"availableTransforms":["rectangle","square"],"cleanupHtml":"","purifyHtml":"1","purifierConfig":"","columnType":"mediumtext"}}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
