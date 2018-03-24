<?php

namespace firstborn\migrationmanager\services;

use Craft;

class AssetVolumes extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'assetVolume';

    /**
     * @var string
     */
    protected $destination = 'assetVolumes';

    /**
     * @param int  $id
     * @param bool $fullExport
     *
     * @return array|bool
     */
    public function exportItem($id, $fullExport = false)
    {
        $volume = Craft::$app->volumes->getVolumeById($id);
        if (!$volume) {
            return false;
        }

        $this->addManifest($volume->handle);

        $newVolume = [
            'name' => $volume->name,
            'handle' => $volume->handle,
            'type' => get_class($volume),
            'sortOrder' => $volume->sortOrder,
            'typesettings' => $volume->settings,
        ];

        if ($volume->hasUrls){
            $newVolume['hasUrls'] = 1;
            $newVolume['url'] = $volume->url;
        }

        if ($fullExport) {
            $newVolume['fieldLayout'] = array();
            $fieldLayout = $volume->getFieldLayout();
            foreach ($fieldLayout->getTabs() as $tab) {
                $newVolume['fieldLayout'][$tab->name] = array();
                foreach ($tab->getFields() as $tabField) {

                    $newVolume['fieldLayout'][$tab->name][] = Craft::$app->fields->getFieldById($tabField->fieldId)->handle;
                    if ($tabField->required) {
                        $newVolume['requiredFields'][] = Craft::$app->fields->getFieldById($tabField->fieldId)->handle;
                    }
                }
            }
        }
        return $newVolume;
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function importItem(Array $data)
    {
        $existing = $volume = Craft::$app->volumes->getVolumeByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $volume = $this->createModel($data);
        $result = Craft::$app->volumes->saveVolume($volume);

        return $result;
    }

    /**
     * @param array $data
     *
     * @return VolumeInterface
     */
    public function createModel(Array $data)
    {

        $volumes = Craft::$app->getVolumes();

        $volume = $volumes->createVolume([
            'id' => $data['id'],
            'type' => $data['type'],
            'name' => $data['name'],
            'handle' => $data['handle'],
            'hasUrls' => array_key_exists('hasUrls', $data) ? $data['hasUrls'] : false,
            'url' => array_key_exists('hasUrls', $data) ? $data['url'] : '',
            'settings' => $data['typesettings']
        ]);

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
                        $this->addError('error', 'Missing field: '.$field.' can not add to field layout for Asset Volume: '.$volume->handle);
                    }
                }
                $layout[$key] = $fieldIds;
            }

            $fieldLayout = Craft::$app->fields->assembleLayout($layout, $requiredFields);
            $fieldLayout->type = Asset::class;
            $volume->fieldLayout = $fieldLayout;
        }

        return $volume;
    }

    /**
     * @param array               $newVolume
     * @param AssetTransformModel $volume
     */
    private function mergeUpdates(&$newVolume, $volume)
    {
        $newVolume['id'] = $volume->id;
    }
}
