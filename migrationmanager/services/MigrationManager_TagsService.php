<?php

namespace Craft;

class MigrationManager_TagsService extends MigrationManager_BaseMigrationService
{
    protected $source = 'tag';
    protected $destination = 'tags';

    public function exportItem($id, $fullExport)
    {
        $tag = craft()->tags->getTagGroupById($id);

        if (!$tag) {
            return false;
        }

        $newTag = [
            'name' => $tag->name,
            'handle' => $tag->handle
        ];

        $this->addManifest($tag->handle);

        if ($fullExport)
        {
            $newTag['fieldLayout'] = array();
            $newTag['requiredFields'] =  array();

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

        return $newTag;
    }


    public function importItem(Array $data)
    {

        $existing = craft()->tags->getTagGroupByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $tag = $this->createModel($data);
        $result = craft()->tags->saveTagGroup($tag);

        return $result;
    }

    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }

    public function createModel(Array $data)
    {
        $tag = new TagGroupModel();
        if (array_key_exists('id', $data)){
            $tag->id = $data['id'];
        }

        $tag->name = $data['name'];
        $tag->handle = $data['handle'];

        if (array_key_exists('fieldLayout', $data))
        {
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