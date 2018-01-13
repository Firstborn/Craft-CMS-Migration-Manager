<?php

namespace Craft;

class MigrationManager_AssetSourcesService extends MigrationManager_BaseMigrationService
{
    protected $source = 'assetSource';
    protected $destination = 'assetSources';

    public function exportItem($id, $fullExport)
    {
        $source = craft()->assetSources->getSourceById($id);

        if (!$source) {
            return false;
        }

        $this->addManifest($source->handle);

        $newSource = [
            'name' => $source->name,
            'handle' => $source->handle,
            'type' => $source->type,
            'sortOrder' => $source->sortOrder,
            'typesettings' => $source->settings,
        ];

        if ($fullExport) {
            $newSource['fieldLayout'] = array();

            $fieldLayout = $source->getFieldLayout();

            foreach ($fieldLayout->getTabs() as $tab) {
                $newSource['fieldLayout'][$tab->name] = array();
                foreach ($tab->getFields() as $tabField) {

                    $newSource['fieldLayout'][$tab->name][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                    if ($tabField->required) {
                        $newSource['requiredFields'][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                    }
                }
            }
        }

        return $newSource;
    }


    public function importItem(Array $data)
    {

        $existing = MigrationManagerHelper::getAssetSourceByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $source = $this->createModel($data);
        $result = craft()->assetSources->saveSource($source);

        return $result;
    }

    public function createModel(Array $data)
    {
        $source = new AssetSourceModel();
        if (array_key_exists('id', $data)){
            $source->id = $data['id'];
        }

        $source->name = $data['name'];
        $source->handle = $data['handle'];
        $source->type = $data['type'];
        $source->sortOrder = $data['sortOrder'];
        $source->settings = $data['typesettings'];

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
                        $this->addError('Missing field: ' . $field . ' can not add to field layout for Asset Source: ' . $source->handle);
                    }
                }
                $layout[$key] = $fieldIds;
            }


            $fieldLayout = craft()->fields->assembleLayout($layout, $requiredFields);
            $fieldLayout->type = ElementType::Asset;
            $source->fieldLayout = $fieldLayout;
        }



        return $source;

    }

    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }




}