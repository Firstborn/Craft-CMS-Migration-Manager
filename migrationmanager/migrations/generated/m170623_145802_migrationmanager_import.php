<?php
namespace Craft;

/**
 * Generated migration
 */
class m170623_145802_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"assetSources":[{"name":"Uploads","handle":"uploads","type":"Local","sortOrder":"1","typesettings":{"path":"\/images\/uploads\/","publicURLs":"1","url":"http:\/\/craft.dev\/images\/uploads\/"},"fieldLayout":{"Content":["lightswitch"]}},{"name":"Local","handle":"local","type":"Local","sortOrder":"2","typesettings":{"path":"\/images\/","publicURLs":"1","url":"http:\/\/craft.dev\/images\/"},"fieldLayout":{"Content":["plaintext","body"]},"requiredFields":["plaintext"]}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
