<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170620_210907_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"fields":[{"group":"Default","name":"Super Table","handle":"superTable","instructions":"","translatable":"0","required":false,"type":"SuperTable","typesettings":{"fieldLayout":"table","staticField":null,"selectionLabel":"Add a row","maxRows":"10","minRows":"1","blockTypes":{"new":{"fields":{"new1":{"name":"Column 1","handle":"column1","instructions":"","required":"1","type":"PlainText","width":"","typesettings":{"placeholder":"","maxLength":"","multiline":"","initialRows":"4"}},"new2":{"name":"Column 2","handle":"column2","instructions":"","required":"0","type":"Assets","width":"","typesettings":{"useSingleFolder":"","sources":[],"defaultUploadLocationSource":"local","defaultUploadLocationSubpath":"","singleUploadLocationSource":"local","singleUploadLocationSubpath":"","restrictFiles":"1","allowedKinds":["image"],"limit":"1","viewMode":"list","selectionLabel":""}}}}}}}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
