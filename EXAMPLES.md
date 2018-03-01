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

### Add category values

```php
$categoryGroup = Craft::$app->categories->getGroupByHandle('category');
$locale = Craft::$app->i18n->getPrimarySiteLocale();

if ($categoryGroup) {
    $category = new CategoryModel();
    $category->groupId = $categoryGroup->id;

    if ($locale) {
        $category->locale = $locale->id;
    }

    $category->enabled = true;
    $category->getContent()->title = 'New Category 2';
    $fieldData = array('plaintext' => 'hello world');
    $category->setContentFromPost($fieldData);

    return Craft::$app->categories->saveCategory($category);
} else {
    return false;
}
```

### Add a new locale and update sections

```php
$newLocale = 'es_us';
$locales = Craft::$app->i18n->getSiteLocaleIds();

if (in_array($newLocale, $locales) === false) {
    $locale = Craft::$app->i18n->addSiteLocale($newLocale);
} else {
    $locale = Craft::$app->i18n->getLocaleById($newLocale);
}

$sectionIds = Craft::$app->sections->getAllSectionIds();
foreach($sectionIds as $id){
    
    $section = Craft::$app->sections->getSectionById($id);
    $locales = $section->getLocales();
    $sectionLocale = new SectionLocaleModel(array(
        'locale' => $locale,
        'enabledByDefault' => false,
        'urlFormat' => $section->getUrlFormat(),
        'nestedUrlFormat' => null
    ));

    $locales[$newLocale] = $sectionLocale;
    $section->setLocales($locales);

    Craft::$app->sections->saveSection($section);
}

return true;
```