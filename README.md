# Migration Manager for [Craft CMS](https://craftcms.com/)

CraftCMS Plugin to migrate Section, Entry Type and Field updates between Craft environments.

## Installation
1. Move the `migrationmanager` directory into your `craft/plugins` directory.
2. Go to Settings &gt; Plugins from your Craft control panel and enable the `Migration Manager` plugin

## Use
The Migration Manager can create migrations for the following tasks:
- adding and updating fields
- adding and updating sections
- adding and updating section entry types
- updating entry type field layouts (ie adding or removing fields from field tabs)

To create a migration select the fields and/or sections you wish to migrate and then click the 'Create Migration' button at the bottom of the page.

![Migration Manager](screenshots/create-migration.png)

A migration file will be created in the `craft/plugins/migrationmanager/migrations/generated` folder. Move the new migration file to your destination environment, ideally with version control.

- In your development environment:   
```
 git add .
 git commit -am 'new migration'
 git push
```
- In your destination environment:
 ```
 git pull
 ```

Once the migration(s) are in your destination environment, go to the Migration Manager panel in your destination environment, click on the 'Migrations' tab and run the new migrations.
 
 ![Pending Migration](screenshots/pending-migrations.png)
 
Before executing the migration(s) the Craft database will be backed up and stored in the backups directory. To disable backups from being created each time a migration is run you can use the `backupDbOnUpdate` config setting (https://craftcms.com/docs/config-settings#backupDbOnUpdate). **This is not recommended**.

If a migration fails to execute the migration will be rolled back and the database will be restored to state before the migration started. You can check the Log tab to review previous migrations and see any error messages.

## Field type support
Migration manager currently supports all core CraftCMS fields types:
- Assets
- Categories
- Checkboxes
- Color
- Date/Time
- Dropdown
- Entries
- Lightswitch
- Matrix
- Multi-select
- Number
- Plain Text
- Position Select
- Radio Buttons
- Rich Text
- Table
- Tags
- Users

In addition it also supports:
- [SuperTable](https://github.com/engram-design/SuperTable)
 
To support additional field types you can use event handlers for customized import/export functions.

The following events are available to 
- migrationManager_entries.exportField
- migrationManager_entries.importField
- migrationManager_fields.exportField
- migrationManager_fields.importField

```
craft()->on('migrationManager_entries.exportField', function(Event $event){
    $event->params['value'] = 'oh yeah';
    $event->performAction = false;
});
```

This is an example of a custom plugin that listens for the 'migrationManager_fields.exportField' and 'migrationManager.importField' events and then modifies the settings source values for a FruitLinkIt field.
```
public function init()
{

    craft()->on('migrationManager_entries.exportField', function(Event $event){
        echo 'on entries export field event';
        Craft::log('export field', LogLevel::Error);
        $event->params['value'] = 'oh yeah';
        $event->performAction = false;


    });

    craft()->on('migrationManager_fields.exportField', function(Event $event){
        if ($event->params['field']['type'] == 'FruitLinkIt')
        {
            //replace source ids with handles
            foreach ($event->params['value']['typesettings']['entrySources'] as $key => $value) {
                $section = craft()->sections->getSectionById(intval(substr($value, 8)));
                if ($section) {
                     $event->params['value']['typesettings']['entrySources'][$key] = $section->handle;
                }
            }

            foreach ($event->params['value']['typesettings']['categorySources'] as $key => $value) {
                if (substr($value, 0, 6) == 'group:') {
                    $categories = craft()->categories->getAllGroupIds();
                    $categoryId = intval(substr($value, 6));
                    if (in_array($categoryId, $categories)) {
                        $category = craft()->categories->getGroupById($categoryId);
                        if ($category) {
                            $event->params['value']['typesettings']['categorySources'][$key] = $category->handle;
                        }
                    }
                }
            }


            foreach ($event->params['value']['typesettings']['assetSources'] as $key => $value) {
                if (substr($value, 0, 7) == 'folder:') {
                    $source = craft()->assetSources->getSourceById(intval(substr($value, 7)));
                    if ($source) {
                        $event->params['value']['typesettings']['assetSources'][$key] = $source->handle;
                    }
                }
            }

            //$event->performAction = false;
            //$event->params['error'] = 'something screwed up';
        }
    });

    craft()->on('migrationManager_fields.importField', function(Event $event){
        Craft::log('import field: '. $event->params['field']['type'] , LogLevel::Error);
        if ($event->params['field']['type'] == 'FruitLinkIt')
        {
            Craft::log(JsonHelper::encode($event->params['value']), LogLevel::Error);
            //replace source handles with ids
            $entrySources = [];
            foreach ($event->params['value']['typesettings']['entrySources'] as $value) {
                $source = craft()->sections->getSectionByHandle($value);
                if ($source)
                {
                    $entrySources[] = 'section:' . $source->id;
                }
                elseif ($value == 'singles')
                {
                    $entrySources[] = $value;
                }
            }
            $event->params['value']['typesettings']['entrySources'] = $entrySources;

            $categorySources = [];
            foreach ($event->params['value']['typesettings']['categorySources'] as $value) {

                $source = craft()->categories->getGroupByHandle($value);
                if ($source) {
                    $categorySources[] = 'group:' . $source->id;
                }
            }
            $event->params['value']['typesettings']['categorySources'] = $categorySources;

            $assetSources = [];
            foreach ($event->params['value']['typesettings']['assetSources'] as $value) {
                $source = MigrationManagerHelper::getAssetSourceByHandle($value);
                if ($source) {
                    $assetSources[] = 'folder:' . $source->id;
                }
            }
            $event->params['value']['typesettings']['assetSources'] = $assetSources;

            //$event->performAction = false;
            //$event->params['error'] = 'test';





        }
    });
}

```



## Custom migrations
In addition to generated migrations you can use the MigrationManger to create empty migrations that can be used for tasks like deleting fields and creating content. To create an empty migration simply click the 'Create Migration' on the Migration Manager/Create Migrations tab. A new empty migration will be added to the `craft/plugins/migrationmanager/migrations/generated` folder.

View the [examples](migrationmanager/EXAMPLES.md).



##### Icon Credit
Flying Duck by Agne Alesiute from the Noun Project