<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170621_154823_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"sections":[{"name":"Single","handle":"single","type":"single","enableVersioning":"1","hasUrls":"1","template":"single","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"single","nestedUrlFormat":null,"enabledByDefault":"1"}},"entrytypes":[{"sectionHandle":"single","hasTitleField":"0","titleLabel":null,"titleFormat":"{section.name|raw}","name":"Single","handle":"single","fieldLayout":{"Tab 1":["asset"],"Tab 3":["category"]},"requiredFields":["asset"]}]}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
