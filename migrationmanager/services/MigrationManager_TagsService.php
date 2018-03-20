<?php

namespace Craft;

/**
 * Class MigrationManager_TagsService
 */
class MigrationManager_TagsService extends MigrationManager_BaseMigrationService
{
    /**
     * @var string
     */
    protected $source = 'tag';

    /**
     * @var string
     */
    protected $destination = 'tags';

    /**
     * {@inheritdoc}
     */
    public function exportItem($id, $fullExport = false)
    {
        $tag = craft()->tags->getTagGroupById($id);

        if (!$tag) {
            return false;
        }

        $newTag = [
            'name' => $tag->name,
            'handle' => $tag->handle,
        ];

        $this->addManifest($tag->handle);

        if ($fullExport) {

            $newTag['fieldLayout'] = array();
            $newTag['requiredFields'] = array();

            $fieldLayout = $tag->getFieldLayout();

            foreach ($fieldLayout->getTabs() as $tab) {
                $newTag['fieldLayout'][$tab->name] = array();
                foreach ($tab->getFields() as $tabField) {

                    $newTag['fieldLayout'][$tab->name][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                    if ($tabField->required) {
                        $newTag['requiredFields'][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                    }
                }
            }
        }

        // Fire an 'onExport' event
        $event = new Event($this, array(
            'element' => $tag,
            'value' => $newTag
        ));
        if ($fullExport) {
            $this->onExport($event);
        }
        return $event->params['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(Array $data)
    {
        $existing = craft()->tags->getTagGroupByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $tag = $this->createModel($data);
        $result = craft()->tags->saveTagGroup($tag);

        if ($result) {
            // Fire an 'onImport' event
            $event = new Event($this, array(
                'element' => $tag,
                'value' => $data
            ));
            $this->onImport($event);
        }

        return $result;
    }

    /**
     * @param array $newSource
     * @param TagGroupModel $source
     */
    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }

    /**
     * @param array $data
     *
     * @return TagGroupModel
     */
    public function createModel(array $data)
    {
        $tag = new TagGroupModel();
        if (array_key_exists('id', $data)) {
            $tag->id = $data['id'];
        }

        $tag->name = $data['name'];
        $tag->handle = $data['handle'];

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
                        $this->addError('Missing field: ' . $field . ' can not add to field layout for Tag: ' . $tag->handle);
                    }
                }
                $layout[$key] = $fieldIds;
            }


            $fieldLayout = craft()->fields->assembleLayout($layout, $requiredFields);
            $tag->fieldLayout = $fieldLayout;
        }

        return $tag;
    }
}
