<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170620_010334_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"fields":[{"group":"Default","name":"Entry","handle":"entry","instructions":"","translatable":"0","required":false,"type":"Entries","typesettings":{"sources":["news","singles"],"limit":"10","selectionLabel":"pick any entry"}}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
