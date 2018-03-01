<?php

namespace firstborn\migrationmanager\services;

class AssetVolumes extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'assetSource';

    /**
     * @var string
     */
    protected $destination = 'assetSources';

    /**
     * @param int  $id
     * @param bool $fullExport
     *
     * @return array|bool
     */
    public function exportItem($id, $fullExport = false)
    {
        $source = Craft::$app->assetSources->getSourceById($id);
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

                    $newSource['fieldLayout'][$tab->name][] = Craft::$app->fields->getFieldById($tabField->fieldId)->handle;
                    if ($tabField->required) {
                        $newSource['requiredFields'][] = Craft::$app->fields->getFieldById($tabField->fieldId)->handle;
                    }
                }
            }
        }

        return $newSource;
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function importItem(Array $data)
    {
        $existing = MigrationManagerHelper::getAssetSourceByHandle($data['handle']);
        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $source = $this->createModel($data);
        $result = Craft::$app->assetSources->saveSource($source);

        return $result;
    }

    /**
     * @param array $data
     *
     * @return AssetSourceModel
     */
    public function createModel(Array $data)
    {
        $source = new AssetSourceModel();
        if (array_key_exists('id', $data)) {
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
                        $this->addError('error', 'Missing field: '.$field.' can not add to field layout for Asset Volume: '.$source->handle);
                    }
                }
                $layout[$key] = $fieldIds;
            }

            $fieldLayout = Craft::$app->fields->assembleLayout($layout, $requiredFields);
            $fieldLayout->type = ElementType::Asset;
            $source->fieldLayout = $fieldLayout;
        }

        return $source;
    }

    /**
     * @param array               $newSource
     * @param AssetTransformModel $source
     */
    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }
}
