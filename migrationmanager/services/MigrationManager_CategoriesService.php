<?php

namespace Craft;

class MigrationManager_CategoriesService extends MigrationManager_BaseMigrationService
{
    protected $source = 'category';
    protected $destination = 'categories';

    public function exportItem($id, $fullExport = false)
    {
        $category = craft()->categories->getGroupById($id);

        if (!$category) {
            return false;
        }

        $this->addManifest($category->handle);

        $newCategory = [
            'name' => $category->name,
            'handle' => $category->handle,
            'hasUrls' => $category->hasUrls,
            'template' => $category->template,
            'maxLevels' => $category->maxLevels
        ];

        $locales = $category->getLocales();

        $newCategory['locales'] = array();
        foreach ($locales as $key => $locale ) {
            $newCategory['locales'][$key] = [
                'locale' => $locale->locale,
                'urlFormat' => $locale->urlFormat,
                'nestedUrlFormat' => $locale->nestedUrlFormat
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

                        $newCategory['fieldLayout'][$tab->name][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                        if ($tabField->required) {
                            $newCategory['requiredFields'][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                        }
                    }
                }
            }
        }

        // Fire an 'onExport' event
        $event = new Event($this, array(
            'element' => $category,
            'value' => $newCategory
        ));
        if ($fullExport) {
            $this->onExport($event);
        }
        return $event->params['value'];
    }


    public function importItem(Array $data)
    {

        $existing = craft()->categories->getGroupByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $category = $this->createModel($data);
        $result = craft()->categories->saveGroup($category);

        if ($result) {
            // Fire an 'onImport' event
            $event = new Event($this, array(
                'element' => $category,
                'value' => $data
            ));
            $this->onImport($event);
        }

        return $result;
    }

    public function createModel(Array $data)
    {

        $category = new CategoryGroupModel();
        if (array_key_exists('id', $data)){
            $category->id = $data['id'];
        }

        $category->name = $data['name'];
        $category->handle = $data['handle'];
        $category->hasUrls = $data['hasUrls'];
        $category->template = $data['template'];
        $category->maxLevels = $data['maxLevels'];

        if (array_key_exists('locales', $data))
        {
            $locales = array();
            foreach($data['locales'] as $key => $locale){
                //determine if locale exists
                if (in_array($key, craft()->i18n->getSiteLocaleIds())) {
                    $locales[$key] = new CategoryGroupLocaleModel(array(
                        'locale' => $key,
                        'urlFormat' => $locale['urlFormat'],
                        'nestedUrlFormat' => $locale['nestedUrlFormat'],
                    ));
                } else {
                    $this->addError('missing locale: ' . $key . ' in category: ' . $category->handle . ', locale not defined in system');
                }
            }
            $category->setLocales($locales);
        }

        if (array_key_exists('fieldLayout', $data)) {

            $requiredFields = array();
            if (array_key_exists('requiredFields', $data)) {
                foreach ($data['requiredFields'] as $handle) {
                    $field = craft()->fields->getFieldByHandle($handle);
                    if ($field) {
                        $requiredFields[] = $field->id;
                    }
                }
            }

            $layout = array();
            foreach ($data['fieldLayout'] as $key => $fields) {
                $fieldIds = array();
                foreach ($fields as $field) {
                    $existingField = craft()->fields->getFieldByHandle($field);
                    if ($existingField) {
                        $fieldIds[] = $existingField->id;
                    } else {
                        $this->addError('Missing field: ' . $field . ' can not add to field layout for Category: ' . $category->handle);
                    }
                }
                $layout[$key] = $fieldIds;
            }


            $fieldLayout = craft()->fields->assembleLayout($layout, $requiredFields);
            $fieldLayout->type = ElementType::Category;
            $category->fieldLayout = $fieldLayout;

        }

        return $category;

    }

    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }

}