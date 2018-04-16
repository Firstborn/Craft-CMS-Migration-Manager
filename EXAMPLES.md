# Examples of custom migrations

## Migration file

To create an empty migration file click the 'Create Migration' button on the Migration Manager/Create Migrations tab. A new empty migration will be added to the `craft/plugins/migrationmanager/migrations/generated` folder.

```php
namespace Craft;

/**
 * Generated migration
 */
class m170621_190506_migrationmanager_import extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     * Returning false will rollback the migration
     *
     * @return bool
     */
    public function safeUp()
    {
        //add your custom migration code, see the EXAMPLES.md for examples
        return true;
    }
}
```

Put any code to be executed during the migration in the `safeUp` method.

Make sure that the method returns true or false to indicate migration success. Returning false will notify the system that the migration has failed, the database will be restored to it's previous state, and all migration changes will be undone.

## Sample migration tasks

### Delete a field

```php
$field = Craft::$app->fields->getFieldByHandle('fieldHandle');
return Craft::$app->fields->deleteFieldById($field->id);
```

### Delete a section

```php
$section = Craft::$app->sections->getSectionByHandle('sectionHandle');
return Craft::$app->sections->deleteSectionById($section->id);
```

### Add a category value
```php
$categoryGroup = Craft::$app->categories->getGroupByHandle('category');

if ($categoryGroup) {
    $category = new Category();
    $category->groupId = $categoryGroup->id;
    $category->enabled = true;
    $category->enabledForSite = 1;
    $category->title = 'Hello World';

    $fieldData = array('plainText' => 'hey there');
    $category->setFieldValues($fieldData);
    return Craft::$app->getElements()->saveElement($category);
} else {
    Craft::error('no category added');
    return false;
}
```
