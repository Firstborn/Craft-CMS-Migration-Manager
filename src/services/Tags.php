<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\elements\Tag;
use craft\models\TagGroup;
use firstborn\migrationmanager\events\ExportEvent;

class Tags extends BaseMigration
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
        $tag = Craft::$app->tags->getTagGroupById($id);

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
                    $newTag['fieldLayout'][$tab->name][] = $tabField->handle;
                    if ($tabField->required) {
                        $newTag['requiredFields'][] = $tabField->handle;
                    }
                }
            }
        }

        if ($fullExport) {
            $newTag = $this->onBeforeExport($tag, $newTag);
        }


        return $newTag;
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(Array $data)
    {
        $existing = Craft::$app->tags->getTagGroupByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $tag = $this->createModel($data);
        $event = $this->onBeforeImport($tag, $data);

        if ($event->isValid) {
            $result = Craft::$app->tags->saveTagGroup($event->element);
            if ($result) {
                $this->onAfterImport($event->element, $data);
            } else {
                $this->addError('error', 'Could not save the ' . $data['handle'] . ' tag.');
            }
        } else {
            $this->addError('error', 'Error importing ' . $data['handle'] . ' tag.');
            $this->addError('error', $event->error);
            return false;
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
        $tag = new TagGroup();
        if (array_key_exists('id', $data)) {
            $tag->id = $data['id'];
        }

        $tag->name = $data['name'];
        $tag->handle = $data['handle'];

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
                        $this->addError('error', 'Missing field: ' . $field . ' can not add to field layout for Tag: ' . $tag->handle);
                    }
                }
                $layout[$key] = $fieldIds;
            }


            $fieldLayout = Craft::$app->fields->assembleLayout($layout, $requiredFields);
            $fieldLayout->type = Tag::class;
            $tag->fieldLayout = $fieldLayout;
        }

        return $tag;
    }
}
