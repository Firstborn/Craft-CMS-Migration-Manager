<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170619_204633_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"fields":[{"group":"Default","name":"Category","handle":"category","instructions":"","translatable":"0","required":false,"type":"Categories","typesettings":{"source":"category","limit":"1","selectionLabel":""}},{"group":"Default","name":"Entry","handle":"entry","instructions":"","translatable":"0","required":false,"type":"Entries","typesettings":{"sources":["news"],"limit":"","selectionLabel":""}}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
