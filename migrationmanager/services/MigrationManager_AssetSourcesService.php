<?php

namespace Craft;

/**
 * Class MigrationManager_AssetSourcesService
 */
class MigrationManager_AssetSourcesService extends MigrationManager_BaseMigrationService
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

        // Fire an 'onExport' event
        $event = new Event($this, array(
            'element' => $source,
            'value' => $newSource
        ));
        if ($fullExport) {
            $this->onExport($event);
        }
        return $event->params['value'];
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
        $result = craft()->assetSources->saveSource($source);

        if ($result) {
            // Fire an 'onImport' event
            $event = new Event($this, array(
                'element' => $source,
                'value' => $data
            ));
            $this->onImport($event);
        }

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
                        $this->addError('Missing field: '.$field.' can not add to field layout for Asset Source: '.$source->handle);
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

    /**
     * @param array               $newSource
     * @param AssetTransformModel $source
     */
    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }
}
