<?php
namespace Craft;

class MigrationManager_CategoriesContentService extends MigrationManager_BaseContentMigrationService
{
    protected $source = 'category';
    protected $destination = 'categories';

    public function exportItem($id, $fullExport = false)
    {
        $primaryCategory = craft()->categories->getCategoryById($id);
        $locales = $primaryCategory->getGroup()->getLocales();
        $content = array(
            'slug' => $primaryCategory->slug,
            'category' => $primaryCategory->getGroup()->handle,
            'locales' => array()
        );

        $this->addManifest($content['slug']);

        if ($primaryCategory->getParent())
        {
            $content['parent'] = $this->exportItem($primaryCategory->getParent()->id, true);
        }

        foreach($locales as $locale){
            $category = craft()->categories->getCategoryById($id, $locale->locale);
            $categoryContent = array(
                'slug' => $category->slug,
                'category' => $category->getGroup()->handle,
                'enabled' => $category->enabled,
                'locale' => $category->locale,
                'localeEnabled' => $category->localeEnabled,
                'title' => $category->title
            );

            if ($category->getParent())
            {
                $categoryContent['parent'] = $category->getParent()->slug;
            }

            $this->getContent($categoryContent, $category);
            $content['locales'][$locale->locale] = $categoryContent;
        }

        return $content;
    }

    public function importItem(Array $data)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Category);
        $criteria->group = $data['category'];
        $criteria->slug = $data['slug'];
        $primaryCategory = $criteria->first();
        //$entry = false;

        if (array_key_exists('parent', $data))
        {
            $this->importItem($data['parent']);
        }

        foreach($data['locales'] as $value) {
            if ($primaryCategory) {
                $value['id'] = $primaryCategory->id;
                $this->localizeData($primaryCategory, $value);
            }

            $category = $this->createModel($value);

            // save entry
            if (!$success = craft()->categories->saveCategory($category)) {
                throw new Exception(print_r($category->getErrors(), true));
            }

            if (!$primaryCategory) {
                $primaryCategory = $category;
            }
        }

        return true;

    }



    public function createModel(Array $data)
    {

        $category = new CategoryModel();

        if (array_key_exists('id', $data)){
            $category->id = $data['id'];
        }

        $group = craft()->categories->getGroupByHandle($data['category']);
        $category->groupId = $group->id;
        $category->locale = $data['locale'];
        $category->slug = $data['slug'];
        $category->enabled = $data['enabled'];
        $category->localeEnabled = $data['localeEnabled'];
        $category->getContent()->title = $data['title'];

        if (array_key_exists('parent', $data))
        {
            $parent = MigrationManagerHelper::getCategoryByHandle(array('slug' => $data['parent'], 'category' => $data['category']));
            if ($parent) {
                $category->newParentId = $parent->id;
            }
        }

        $this->getSourceIds($data);
        $this->validateImportValues($data);
        $category->setContentFromPost($data);
        return $category;
    }





}