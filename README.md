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

## Custom migrations
In addition to generated migrations you can use the MigrationManger to create empty migrations that can be used for tasks like deleting fields and creating content. To create an empty migration simply click the 'Create Migration' on the Migration Manager/Create Migrations tab. A new empty migration will be added to the `craft/plugins/migrationmanager/migrations/generated` folder.

View the [examples](migrationmanager/EXAMPLES.md).



##### Icon Credit
Flying Duck by Agne Alesiute from the Noun Project