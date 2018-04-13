<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\elements\GlobalSet;
use firstborn\migrationmanager\events\ExportEvent;

class Globals extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'global';

    /**
     * @var string
     */
    protected $destination = 'globals';

    /**
     * {@inheritdoc}
     */
    public function exportItem($id, $fullExport = false)
    {
        $set = Craft::$app->globals->getSetById($id);

        if (!$set) {
            return false;
        }

        $newSet = [
            'name' => $set->name,
            'handle' => $set->handle,
            'fieldLayout' => array(),
            'requiredFields' => array(),
        ];

        $this->addManifest($set->handle);

        $fieldLayout = $set->getFieldLayout();

        foreach ($fieldLayout->getTabs() as $tab) {
            $newSet['fieldLayout'][$tab->name] = array();
            foreach ($tab->getFields() as $tabField) {
                $newSet['fieldLayout'][$tab->name][] = $tabField->handle;
                if ($tabField->required) {
                    $newSet['requiredFields'][] = $tabField->handle;
                }
            }
        }

        if ($fullExport) {
            $newSet = $this->onBeforeExport($set, $newSet);
        }



        return $newSet;
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(array $data)
    {
        $existing = Craft::$app->globals->getSetByHandle($data['handle']);
        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $set = $this->createModel($data);

        $event = $this->onBeforeImport($set, $data);
        if ($event->isValid) {
            $result = Craft::$app->globals->saveSet($event->element);

            if ($result) {
                $this->onAfterImport($event->element, $data);
            } else {
                $this->addError('error', 'Could not save the ' . $data['handle'] . ' global.');
            }
        } else {
            $this->addError('error', 'Error importing ' . $data['handle'] . ' global.');
            $this->addError('error', $event->error);
            return false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createModel(array $data)
    {
        $globalSet = new GlobalSet();
        if (array_key_exists('id', $data)) {
            $globalSet->id = $data['id'];
            $globalSet->contentId = $data['id'];
        }

        $globalSet->name = $data['name'];
        $globalSet->handle = $data['handle'];

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
                    $this->addError('error', 'Missing field: ' . $field . ' can not add to field layout for Global: ' . $globalSet->handle);
                }
            }
            $layout[$key] = $fieldIds;
        }

        $fieldLayout = Craft::$app->fields->assembleLayout($layout, $requiredFields);
        $fieldLayout->type =  GlobalSet::class;
        $globalSet->setFieldLayout($fieldLayout);

        return $globalSet;
    }

    /**
     * @param array $newSource
     * @param GlobalSetModel $source
     */
    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }
}
