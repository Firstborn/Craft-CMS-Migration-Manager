<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170616_175222_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"fields":[{"group":"Default","name":"Rich Text","handle":"richText","instructions":"","translatable":"0","required":false,"type":"RichText","typesettings":{"configFile":"","availableAssetSources":["s3","local"],"availableTransforms":"","cleanupHtml":"1","purifyHtml":"1","purifierConfig":"","columnType":"text"}},{"group":"Default","name":"Supertable","handle":"supertable","instructions":"","translatable":"0","required":false,"type":"SuperTable","typesettings":{"fieldLayout":"row","staticField":null,"selectionLabel":null,"maxRows":null,"minRows":null,"blockTypes":{"new":{"fields":{"new1":{"name":"Matrix","handle":"matrix","instructions":"","required":"0","type":"Matrix","width":"","typesettings":{"maxBlocks":null,"blockTypes":{"new1":{"name":"Block","handle":"block","fields":{"new1":{"name":"Description","handle":"description","instructions":"","required":"0","type":"PlainText","typesettings":{"placeholder":"","maxLength":"","multiline":"","initialRows":"4"}},"new2":{"name":"Image","handle":"image","instructions":"","required":"0","type":"Assets","typesettings":{"useSingleFolder":"","sources":["folder:2"],"defaultUploadLocationSource":"1","defaultUploadLocationSubpath":"","singleUploadLocationSource":"1","singleUploadLocationSubpath":"","restrictFiles":"1","allowedKinds":["image"],"limit":"","viewMode":"list","selectionLabel":""}}}}}}},"new2":{"name":"Text","handle":"text","instructions":"","required":"0","type":"RichText","width":"","typesettings":{"configFile":"","availableAssetSources":"*","availableTransforms":"*","cleanupHtml":"1","purifyHtml":"1","purifierConfig":"","columnType":"text"}},"new3":{"name":"Asset","handle":"asset","instructions":"","required":"0","type":"Assets","width":"","typesettings":{"useSingleFolder":"","sources":["folder:1"],"defaultUploadLocationSource":"1","defaultUploadLocationSubpath":"uploadpath","singleUploadLocationSource":"1","singleUploadLocationSubpath":"","restrictFiles":"","limit":"","viewMode":"list","selectionLabel":""}}}}}}}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
