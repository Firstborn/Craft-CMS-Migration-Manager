# Custom Migration Examples

## Migration file
To create an empty migration file click the 'Create Migration' button on the Migration Manager/Create Migrations tab. A new empty migration will be added to the `craft/plugins/migrationmanager/migrations/generated` folder.
```
<?php
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

```
public function safeUp()
{
    $field = craft()->fields->getFieldByHandle('fieldHandle');
    return craft()->fields->deleteFieldById($field->id);
}
```

### Delete a section
```
public function safeUp()
{
    $section = craft()->sections->getSectionByHandle('sectionHandle');
    return craft()->sections->deleteSectionById($section->id);
}
```

### Add category values
```
public function safeUp()
{
    $categoryGroup = craft()->categories->getGroupByHandle('category');
     $locale = craft()->i18n->getPrimarySiteLocale();
     
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
     
         return craft()->categories->saveCategory($category);
     } else {
         return false;
     }
}
```

### Add a new locale and update sections
```
public function safeUp()
{
    $newLocale = 'es_us';
    $locales = craft()->i18n->getSiteLocaleIds();
    
    if (in_array($newLocale, $locales) === false)
    {
        $locale = craft()->i18n->addSiteLocale($newLocale);
    } else {
        $locale = craft()->i18n->getLocaleById($newLocale);
    }
    
    $sectionIds = craft()->sections->getAllSectionIds();
    foreach($sectionIds as $id){
        $section = craft()->sections->getSectionById($id);
        $locales = $section->getLocales();
        $sectionLocale = new SectionLocaleModel(array(
            'locale' => $locale,
            'enabledByDefault' => false,
            'urlFormat' => $section->getUrlFormat(),
            'nestedUrlFormat' => null
        ));
    
        $locales[$newLocale] = $sectionLocale;
        $section->setLocales($locales);
        craft()->sections->saveSection($section);
    }
    return true;
}
```

## Further reading
Reference the CraftCMS [class reference](https://craftcms.com/classreference/) for additional help in creating custom migrations. The `craft/app/controllers` are also helpful in understanding how models are creating and updated.


