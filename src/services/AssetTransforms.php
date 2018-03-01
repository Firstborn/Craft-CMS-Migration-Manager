<?php

namespace firstborn\migrationmanager\services;

/**
 * Class MigrationManager_AssetTransformsService
 */
class AssetTransforms extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'assetTransform';

    /**
     * @var string
     */
    protected $destination = 'assetTransforms';

    /**
     * @param int $id
     * @param bool $fullExport
     *
     * @return array|bool
     */
    public function exportItem($id, $fullExport = false)
    {
        $transform = MigrationManagerHelper::getTransformById($id);
        if (!$transform) {
            return false;
        }

        $this->addManifest($transform->handle);

        $newTransform = [
            'name' => $transform->name,
            'handle' => $transform->handle,
            'width' => $transform->width,
            'height' => $transform->height,
            'format' => $transform->format,
            'mode' => $transform->mode,
            'position' => $transform->position,
            'quality' => $transform->quality,
        ];

        return $newTransform;
    }

    public function importItem(Array $data)
    {
        $existing = Craft::$app->assetTransforms->getTransformByHandle($data['handle']);
        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $transform = $this->createModel($data);
        $result = Craft::$app->assetTransforms->saveTransform($transform);

        return $result;
    }

    public function createModel(Array $data)
    {
        $transform = new AssetTransformModel();
        if (array_key_exists('id', $data)) {
            $transform->id = $data['id'];
        }

        $transform->name = $data['name'];
        $transform->handle = $data['handle'];
        $transform->width = $data['width'];
        $transform->height = $data['height'];
        $transform->format = $data['format'];
        $transform->mode = $data['mode'];
        $transform->position = $data['position'];
        $transform->quality = $data['quality'];

        return $transform;
    }

    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }
}