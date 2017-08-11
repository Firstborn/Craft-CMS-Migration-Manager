<?php
namespace Craft;

/**
 * Generated migration
 */
class m170809_202842_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 * Returning false will rollback the migration
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $json = '{"settings":{"dependencies":{"sections":[{"name":"Blog","handle":"blog","type":"channel","enableVersioning":"1","hasUrls":"1","template":"blog\/_entry","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"1"},"es_us":{"locale":"es_us","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"0"},"en_ca":{"locale":"en_ca","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"0"}},"entrytypes":[{"sectionHandle":"blog","hasTitleField":"1","titleLabel":"Title","name":"Blog","handle":"blog","fieldLayout":{"Blog":["body","asset","category","entry","superTable"]},"requiredFields":[]},{"sectionHandle":"blog","hasTitleField":"1","titleLabel":"Title","name":"Blog Alt","handle":"blogAlt","fieldLayout":{"Blog":["body","asset"]},"requiredFields":["body"]}]},{"name":"Homepage","handle":"homepage","type":"single","enableVersioning":"1","hasUrls":"1","template":"index","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"__home__","nestedUrlFormat":null,"enabledByDefault":"1"},"es_us":{"locale":"es_us","urlFormat":"__home__","nestedUrlFormat":null,"enabledByDefault":"1"}},"entrytypes":[{"sectionHandle":"homepage","hasTitleField":"1","titleLabel":"Title","name":"Homepage","handle":"homepage","fieldLayout":{"Content":["body"]},"requiredFields":["body"]}]}]},"elements":{"sections":[{"name":"Blog","handle":"blog","type":"channel","enableVersioning":"1","hasUrls":"1","template":"blog\/_entry","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"1"},"es_us":{"locale":"es_us","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"0"},"en_ca":{"locale":"en_ca","urlFormat":"blog\/{slug}","nestedUrlFormat":null,"enabledByDefault":"0"}},"entrytypes":[{"sectionHandle":"blog","hasTitleField":"1","titleLabel":"Title","name":"Blog","handle":"blog","fieldLayout":{"Blog":["body","asset","category","entry","superTable"]},"requiredFields":[]},{"sectionHandle":"blog","hasTitleField":"1","titleLabel":"Title","name":"Blog Alt","handle":"blogAlt","fieldLayout":{"Blog":["body","asset"]},"requiredFields":["body"]}]},{"name":"Homepage","handle":"homepage","type":"single","enableVersioning":"1","hasUrls":"1","template":"index","maxLevels":null,"locales":{"en_us":{"locale":"en_us","urlFormat":"__home__","nestedUrlFormat":null,"enabledByDefault":"1"},"es_us":{"locale":"es_us","urlFormat":"__home__","nestedUrlFormat":null,"enabledByDefault":"1"}},"entrytypes":[{"sectionHandle":"homepage","hasTitleField":"1","titleLabel":"Title","name":"Homepage","handle":"homepage","fieldLayout":{"Content":["body"]},"requiredFields":["body"]}]}]}},"content":[]}';
        return craft()->migrationManager_migrations->import($json);
    }

}
