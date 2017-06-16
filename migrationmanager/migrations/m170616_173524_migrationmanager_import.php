<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170616_173524_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"sections":[{"name":"News","handle":"news","type":"channel","enableVersioning":"1","hasUrls":"1","template":"news\/_entry","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"news\/{slug}","nestedUrlFormat":null,"enabledByDefault":"0"},"en_ca":{"locale":"en_ca","urlFormat":"news\/{slug}","nestedUrlFormat":null,"enabledByDefault":"0"}},"entrytypes":[{"sectionHandle":"news","hasTitleField":"1","titleLabel":"Title","name":"News","handle":"news","fieldLayout":{"Default":["asset","matrix","richText","supertable","text"]}}]}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
