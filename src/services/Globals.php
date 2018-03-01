<?php

namespace firstborn\migrationmanager\services;

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
        $source = Craft::$app->globals->getSetById($id);

        if (!$source) {
            return false;
        }

        $newSource = [
            'name' => $source->name,
            'handle' => $source->handle,
            'fieldLayout' => array(),
            'requiredFields' => array(),
        ];

        $this->addManifest($source->handle);

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

        return $newSource;
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
        $result = Craft::$app->globals->saveSet($set);

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
