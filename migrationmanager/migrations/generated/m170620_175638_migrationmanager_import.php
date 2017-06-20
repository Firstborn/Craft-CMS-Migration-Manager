<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170620_175638_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"fields":[{"group":"Default","name":"Matrix","handle":"matrix","instructions":"","translatable":"0","required":false,"type":"Matrix","typesettings":{"maxBlocks":null,"blockTypes":{"new1":{"name":"Block","handle":"block","fields":{"new1":{"name":"Asset","handle":"asset","instructions":"","required":"0","type":"Assets","typesettings":{"useSingleFolder":"","sources":[],"defaultUploadLocationSource":"local","defaultUploadLocationSubpath":"","singleUploadLocationSource":"local","singleUploadLocationSubpath":"","restrictFiles":"","limit":"","viewMode":"list","selectionLabel":""}},"new2":{"name":"Label","handle":"label","instructions":"","required":"0","type":"PlainText","typesettings":{"placeholder":"","maxLength":"","multiline":"","initialRows":"4"}}}}}}}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
