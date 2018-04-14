<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\elements\Category;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use firstborn\migrationmanager\events\ExportEvent;

class Categories extends BaseMigration
{
    protected $source = 'category';
    protected $destination = 'categories';

    /**
     * @param int $id
     * @param bool $fullExport
     * @return array|bool
     */
    public function exportItem($id, $fullExport = false)
    {
        $category = Craft::$app->categories->getGroupById($id);

        if (!$category) {
            return false;
        }

        $this->addManifest($category->handle);

        $newCategory = [
            'name' => $category->name,
            'handle' => $category->handle,
            'maxLevels' => $category->maxLevels
        ];


        $siteSettings = $category->getSiteSettings();
        $newCategory['sites'] = array();
        foreach ($siteSettings as $siteSetting) {
            $site = Craft::$app->sites->getSiteById($siteSetting->siteId);
            $newCategory['sites'][$site->handle] = [
                'site' => $site->handle,
                'hasUrls' => $siteSetting->hasUrls,
                'uriFormat' => $siteSetting->uriFormat,
                'template' => $siteSetting->template,
            ];
        }

        if ($fullExport)
        {
            $fieldLayout = $category->getFieldLayout();

            if ($fieldLayout) {

                $newCategory['fieldLayout'] = array();
                $newCategory['requiredFields'] = array();

                foreach ($fieldLayout->getTabs() as $tab) {
                    $newCategory['fieldLayout'][$tab->name] = array();
                    foreach ($tab->getFields() as $tabField) {

                        $newCategory['fieldLayout'][$tab->name][] = $tabField->handle;
                        if ($tabField->required) {
                            $newCategory['requiredFields'][] =$tabField->handle;
                        }
                    }
                }
            }
        }

        if ($fullExport) {
            $newCategory = $this->onBeforeExport($category, $newCategory);
        }

        return $newCategory;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function importItem(Array $data)
    {

        $existing = Craft::$app->categories->getGroupByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $category = $this->createModel($data);
        $event = $this->onBeforeImport($category, $data);

        if ($event->isValid) {
            $result = Craft::$app->categories->saveGroup($event->element);
            if ($result) {
                $this->onAfterImport($event->element, $data);
            } else {
                $this->addError('error', 'Could not save the ' . $data['handle'] . ' category.');
            }

        } else {
            $this->addError('error', 'Error importing ' . $data['handle'] . ' field.');
            $this->addError('error', $event->error);
            return false;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return CategoryGroup
     */
    public function createModel(Array $data)
    {
        $category = new CategoryGroup();
        if (array_key_exists('id', $data)){
            $category->id = $data['id'];
        }

        $category->name = $data['name'];
        $category->handle = $data['handle'];
        $category->maxLevels = $data['maxLevels'];

        $allSiteSettings = [];
        if (array_key_exists('sites', $data)) {
            foreach ($data['sites'] as $key => $siteData) {
                //determine if locale exists
                $site = Craft::$app->getSites()->getSiteByHandle($key);
                $siteSettings = new CategoryGroup_SiteSettings();
                $siteSettings->siteId = $site->id;
                $siteSettings->hasUrls = $siteData['hasUrls'];
                $siteSettings->uriFormat = $siteData['uriFormat'];
                $siteSettings->template = $siteData['template'];
                $allSiteSettings[$site->id] = $siteSettings;
            }
            $category->setSiteSettings($allSiteSettings);
        }

        if (array_key_exists('fieldLayout', $data)) {

            $requiredFields = array();
            if (array_key_exists('requiredFields', $data)) {
                foreach ($data['requiredFields'] as $handle) {
                    $field = Craft::$app->fields->getFieldByHandle($handle);
                    if ($field) {
                        $requiredFields[] = $field->id;
                    }
                }
            }

            $layout = array();
            foreach ($data['fieldLayout'] as $key => $fields) {
                $fieldIds = array();
                foreach ($fields as $field) {
                    $existingField = Craft::$app->fields->getFieldByHandle($field);
                    if ($existingField) {
                        $fieldIds[] = $existingField->id;
                    } else {
                        $this->addError('error', 'Missing field: ' . $field . ' can not add to field layout for Category: ' . $category->handle);
                    }
                }
                $layout[$key] = $fieldIds;
            }


            $fieldLayout = Craft::$app->fields->assembleLayout($layout, $requiredFields);
            $fieldLayout->type = Category::class;
            $category->fieldLayout = $fieldLayout;

        }
        return $category;

    }

    /**
     * @param $newSource
     * @param $source
     */
    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }

}