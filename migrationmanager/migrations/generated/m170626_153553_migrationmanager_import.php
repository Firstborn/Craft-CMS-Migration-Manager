<?php
namespace Craft;

/**
 * Generated migration
 */
class m170626_153553_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"dependencies":{"assetSources":[{"name":"Uploads","handle":"uploads","type":"Local","sortOrder":"1","typesettings":{"path":"\/images\/uploads\/","publicURLs":"1","url":"http:\/\/craft.dev\/images\/uploads\/"}},{"name":"Local","handle":"local","type":"Local","sortOrder":"2","typesettings":{"path":"\/images\/","publicURLs":"1","url":"http:\/\/craft.dev\/images\/"}}]},"elements":{"fields":[{"group":"Default","name":"Asset","handle":"asset","instructions":"","translatable":"0","required":false,"type":"Assets","typesettings":{"useSingleFolder":"","sources":[],"defaultUploadLocationSource":"local","defaultUploadLocationSubpath":"","singleUploadLocationSource":"local","singleUploadLocationSubpath":"","restrictFiles":"1","allowedKinds":["access","audio","compressed","excel","flash","html","illustrator","image","javascript","json","pdf","photoshop"],"limit":"1","viewMode":"list","selectionLabel":""}},{"group":"Default","name":"Plain Text","handle":"plaintext","instructions":"","translatable":"0","required":false,"type":"PlainText","typesettings":{"placeholder":"","maxLength":"","multiline":"","initialRows":"4"}}],"assetSources":[{"name":"Uploads","handle":"uploads","type":"Local","sortOrder":"1","typesettings":{"path":"\/images\/uploads\/","publicURLs":"1","url":"http:\/\/craft.dev\/images\/uploads\/"},"fieldLayout":{"Content":["lightswitch"]}},{"name":"Local","handle":"local","type":"Local","sortOrder":"2","typesettings":{"path":"\/images\/","publicURLs":"1","url":"http:\/\/craft.dev\/images\/"},"fieldLayout":{"Content":["plaintext","body"]},"requiredFields":["plaintext"]}]}}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
