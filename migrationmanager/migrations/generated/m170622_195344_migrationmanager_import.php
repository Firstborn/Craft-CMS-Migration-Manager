<?php
namespace Craft;

/**
 * Generated migration
 */
class m170622_195344_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"fields":[{"group":"Default","name":"Asset","handle":"asset","instructions":"","translatable":"0","required":false,"type":"Assets","typesettings":{"useSingleFolder":"","sources":[],"defaultUploadLocationSource":"local","defaultUploadLocationSubpath":"","singleUploadLocationSource":"local","singleUploadLocationSubpath":"","restrictFiles":"1","allowedKinds":["access","audio","compressed","excel","flash","html","illustrator","image","javascript","json","pdf","photoshop"],"limit":"1","viewMode":"list","selectionLabel":""}}],"sections":[{"name":"Blog","handle":"blog","type":"channel","enableVersioning":"1","hasUrls":"1","template":"blog\/_entry","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"1"},"fr_ca":{"locale":"fr_ca","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"0"},"es_us":{"locale":"es_us","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"0"}},"entrytypes":[{"sectionHandle":"blog","hasTitleField":"1","titleLabel":"Title","name":"Blog","handle":"blog","fieldLayout":{"Blog":["body","category"]},"requiredFields":[]},{"sectionHandle":"blog","hasTitleField":"1","titleLabel":"Title","name":"Blog 2","handle":"blog2","fieldLayout":{"Blog":["body","asset"]},"requiredFields":["body"]}]}],"assetSources":[{"name":"Uploads","handle":"uploads","type":"Local","sortOrder":"1","typesettings":{"path":"\/images\/uploads\/","publicURLs":"1","url":"http:\/\/craft.dev\/images\/uploads\/"},"fieldLayout":[]}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
