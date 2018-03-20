<?php

namespace Craft;

/**
 * Class MigrationManager_GlobalsService
 */
class MigrationManager_GlobalsService extends MigrationManager_BaseMigrationService
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
        $set = craft()->globals->getSetById($id);

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
                $newSet['fieldLayout'][$tab->name][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                if ($tabField->required) {
                    $newSet['requiredFields'][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                }
            }
        }

        // Fire an 'onExport' event
        $event = new Event($this, array(
            'element' => $set,
            'value' => $newSet
        ));
        if ($fullExport) {
            $this->onExport($event);
        }
        return $event->params['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(array $data)
    {
        $existing = craft()->globals->getSetByHandle($data['handle']);
        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $set = $this->createModel($data);
        $result = craft()->globals->saveSet($set);

        if ($result) {
            // Fire an 'onImport' event
            $event = new Event($this, array(
                'element' => $set,
                'value' => $data
            ));
            $this->onImport($event);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createModel(array $data)
    {
        $globalSet = new GlobalSetModel();
        if (array_key_exists('id', $data)) {
            $globalSet->id = $data['id'];
        }

        $globalSet->name = $data['name'];
        $globalSet->handle = $data['handle'];

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
                    $this->addError('Missing field: ' . $field . ' can not add to field layout for Global: ' . $globalSet->handle);
                }
            }
            $layout[$key] = $fieldIds;
        }

        $fieldLayout = craft()->fields->assembleLayout($layout, $requiredFields);
        $fieldLayout->type = ElementType::GlobalSet;
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
