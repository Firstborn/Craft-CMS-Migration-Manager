<?php

namespace firstborn\migrationmanager\services;

use Craft;

class CategoriesContent extends BaseContentMigration
{
    protected $source = 'category';
    protected $destination = 'categories';

    public function exportItem($id, $fullExport = false)
    {
        $primaryCategory = Craft::$app->categories->getCategoryById($id);
        //$locales = $primaryCategory->getGroup()->getLocales();
        $sites = $primaryCategory->getGroup()->getSiteSettings();
        $content = array(
            'slug' => $primaryCategory->slug,
            'category' => $primaryCategory->getGroup()->handle,
            'sites' => array()
        );

        $this->addManifest($content['slug']);

        if ($primaryCategory->getParent())
        {
            $content['parent'] = $this->exportItem($primaryCategory->getParent()->id, true);
        }

        foreach($sites as $siteSetting){
            $site = Craft::$app->sites->getSiteById($siteSetting->siteId);
            $category = Craft::$app->categories->getCategoryById($id, $site->id);
            $categoryContent = array(
                'slug' => $category->slug,
                'category' => $category->getGroup()->handle,
                'enabled' => $category->enabled,
                'site' => $site->handle,
                'enabledForSite' => $category->enabledForSite,
                'title' => $category->title
            );

            if ($category->getParent())
            {
                $categoryContent['parent'] = $category->getParent()->slug;
            }

            $this->getContent($categoryContent, $category);
            $content['sites'][$site->handle] = $categoryContent;
        }

        return $content;
    }

    public function importItem(Array $data)
    {
        $criteria = Craft::$app->elements->getCriteria(ElementType::Category);
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
            }

            $category = $this->createModel($value);

            // save entry
            if (!$success = Craft::$app->categories->saveCategory($category)) {
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

        $group = Craft::$app->categories->getGroupByHandle($data['category']);
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