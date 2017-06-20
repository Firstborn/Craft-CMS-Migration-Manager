<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170620_170933_migrationmanager_import extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
	    $migrationJson = '{"fields":[{"group":"Default","name":"Asset","handle":"asset","instructions":"","translatable":"0","required":false,"type":"Assets","typesettings":{"useSingleFolder":"1","sources":[],"defaultUploadLocationSource":"local","defaultUploadLocationSubpath":"","singleUploadLocationSource":"uploads","singleUploadLocationSubpath":"\/images\/uploads","restrictFiles":"1","allowedKinds":["access","audio","compressed","excel","flash","html","illustrator","image","javascript","json","pdf","photoshop"],"limit":"1","viewMode":"list","selectionLabel":""}},{"group":"Default","name":"Body","handle":"body","instructions":"","translatable":"0","required":false,"type":"RichText","typesettings":{"configFile":"Standard.json","availableAssetSources":["uploads"],"availableTransforms":["square"],"cleanupHtml":"1","purifyHtml":"1","purifierConfig":"","columnType":"text"}},{"group":"Default","name":"Category","handle":"category","instructions":"","translatable":"0","required":false,"type":"Categories","typesettings":{"source":"category","limit":"1","selectionLabel":""}},{"group":"Default","name":"Category2","handle":"category2","instructions":"","translatable":"0","required":false,"type":"Categories","typesettings":{"source":"category","limit":"","selectionLabel":""}},{"group":"Default","name":"Checkboxes","handle":"checkboxes","instructions":"","translatable":"0","required":false,"type":"Checkboxes","typesettings":{"options":[{"label":"Label 1","value":"label1","default":"1"},{"label":"Label 2","value":"label2","default":""},{"label":"Label 3","value":"label3","default":""}]}},{"group":"Default","name":"Date","handle":"date","instructions":"","translatable":"0","required":false,"type":"Date","typesettings":{"minuteIncrement":"60","showTime":1,"showDate":1}},{"group":"Default","name":"Dropdown","handle":"dropdown","instructions":"","translatable":"0","required":false,"type":"Dropdown","typesettings":{"options":[{"label":"Label 1","value":"label1","default":"1"},{"label":"Label 2","value":"label2","default":""},{"label":"Label 3","value":"label3","default":""}]}},{"group":"Default","name":"Entry","handle":"entry","instructions":"","translatable":"0","required":false,"type":"Entries","typesettings":{"sources":[],"limit":"10","selectionLabel":"pick any entry"}},{"group":"Default","name":"Label","handle":"label","instructions":"","translatable":"0","required":false,"type":"SuperTable_Label","typesettings":{"value":"Label"}},{"group":"Default","name":"Lightswitch","handle":"lightswitch","instructions":"","translatable":"0","required":false,"type":"Lightswitch","typesettings":{"default":""}},{"group":"Default","name":"Matrix","handle":"matrix","instructions":"","translatable":"0","required":false,"type":"Matrix","typesettings":{"maxBlocks":null,"blockTypes":{"new1":{"name":"Block","handle":"block","fields":{"new1":{"name":"Asset","handle":"asset","instructions":"","required":"0","type":"Assets","typesettings":{"useSingleFolder":"","sources":"*","defaultUploadLocationSource":"1","defaultUploadLocationSubpath":"","singleUploadLocationSource":"1","singleUploadLocationSubpath":"","restrictFiles":"","limit":"","viewMode":"list","selectionLabel":""}},"new2":{"name":"Label","handle":"label","instructions":"","required":"0","type":"PlainText","typesettings":{"placeholder":"","maxLength":"","multiline":"","initialRows":"4"}}}}}}},{"group":"Default","name":"Multiselect","handle":"multiselect","instructions":"","translatable":"0","required":false,"type":"MultiSelect","typesettings":{"options":[{"label":"Label 1","value":"label1","default":"1"},{"label":"Label 2","value":"label2","default":""},{"label":"Label 3","value":"label3","default":"1"}]}},{"group":"Default","name":"Number","handle":"number","instructions":"","translatable":"0","required":false,"type":"Number","typesettings":{"min":"0","max":"10","decimals":"1"}},{"group":"Default","name":"Plain Text","handle":"plaintext","instructions":"","translatable":"0","required":false,"type":"PlainText","typesettings":{"placeholder":"","maxLength":"","multiline":"","initialRows":"4"}},{"group":"Default","name":"PositionSelect","handle":"positionselect","instructions":"","translatable":"0","required":false,"type":"PositionSelect","typesettings":{"options":{"left":true,"center":true,"right":true,"full":true,"drop-left":true,"drop-right":true}}},{"group":"Default","name":"Radio Buttons","handle":"radioButtons","instructions":"","translatable":"0","required":false,"type":"RadioButtons","typesettings":{"options":[{"label":"Label 1","value":"label1","default":"1"},{"label":"Label 2","value":"label2","default":""},{"label":"Label 3","value":"label3","default":""}]}},{"group":"Default","name":"Rich Text","handle":"richText","instructions":"","translatable":"0","required":false,"type":"RichText","typesettings":{"configFile":"","availableAssetSources":[],"availableTransforms":["square"],"cleanupHtml":"","purifyHtml":"1","purifierConfig":"","columnType":"mediumtext"}},{"group":"Default","name":"Table","handle":"table","instructions":"","translatable":"0","required":false,"type":"Table","typesettings":{"columns":{"col1":{"heading":"Column 1","handle":"column1","width":"50","type":"singleline"},"col2":{"heading":"Column 2","handle":"column2","width":"50","type":"multiline"},"col3":{"heading":"Column 3","handle":"column3","width":"50","type":"number"},"col4":{"heading":"Column 4","handle":"column4","width":"50","type":"checkbox"}},"defaults":{"row1":{"col1":"value a","col2":"value b","col3":"1","col4":"1"}}}},{"group":"Default","name":"Tags","handle":"tags","instructions":"","translatable":"0","required":false,"type":"Tags","typesettings":{"source":"tags","selectionLabel":""}},{"group":"Default","name":"User","handle":"user","instructions":"","translatable":"0","required":false,"type":"Users","typesettings":{"sources":["admins","editor"],"limit":"1","selectionLabel":""}}]}';
        $data = json_decode($migrationJson, true);
        return craft()->migrationManager_migrations->import($data);
    }

}
