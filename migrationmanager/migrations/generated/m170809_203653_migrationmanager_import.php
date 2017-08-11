<?php
namespace Craft;

/**
 * Generated migration
 */
class m170809_203653_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $json = '{"settings":{"dependencies":{"sections":[{"name":"Homepage","handle":"homepage","type":"single","enableVersioning":"1","hasUrls":"1","template":"index","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"__home__","nestedUrlFormat":null,"enabledByDefault":"1"},"es_us":{"locale":"es_us","urlFormat":"__home__","nestedUrlFormat":null,"enabledByDefault":"1"}},"entrytypes":[{"sectionHandle":"homepage","hasTitleField":"1","titleLabel":"Title","name":"Homepage","handle":"homepage","fieldLayout":{"Content":["body","asset"]},"requiredFields":["body"]}]}]},"elements":{"sections":[{"name":"Homepage","handle":"homepage","type":"single","enableVersioning":"1","hasUrls":"1","template":"index","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"__home__","nestedUrlFormat":null,"enabledByDefault":"1"},"es_us":{"locale":"es_us","urlFormat":"__home__","nestedUrlFormat":null,"enabledByDefault":"1"}},"entrytypes":[{"sectionHandle":"homepage","hasTitleField":"1","titleLabel":"Title","name":"Homepage","handle":"homepage","fieldLayout":{"Content":["body","asset"]},"requiredFields":["body"]}]}]}},"content":[]}';
        return craft()->migrationManager_migrations->import($json);
    }

}
