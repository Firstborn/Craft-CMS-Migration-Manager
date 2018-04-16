<?php

namespace firstborn\migrationmanager\services;
use Craft;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;

class EntriesContent extends BaseContentMigration
{
    protected $source = 'entry';
    protected $destination = 'entries';

    /**
     * @param int $element
     * @param bool $fullExport
     * @return array
     */
    public function exportItem($element, $fullExport = false)
    {
        $primaryEntry = Craft::$app->entries->getEntryById($element->id, $element->siteId);

        if ($primaryEntry) {
            $sites = $primaryEntry->getSection()->getSiteIds();

            $content = array(
                'slug' => $primaryEntry->slug,
                'section' => $primaryEntry->getSection()->handle,
                'sites' => array()
            );

            $this->addManifest($content['slug']);

            if ($primaryEntry->getParent()) {
                $content['parent'] = $this->exportItem($primaryEntry->getParent(), true);
            }

            foreach ($sites as $siteId) {
                $site = Craft::$app->sites->getSiteById($siteId);
                $entry = Craft::$app->entries->getEntryById($element->id, $siteId);
                if ($entry) {
                    $entryContent = array(
                        'slug' => $entry->slug,
                        'section' => $entry->getSection()->handle,
                        'enabled' => $entry->enabled,
                        'site' => $site->handle,
                        'enabledForSite' => $entry->enabledForSite,
                        'postDate' => $entry->postDate,
                        'expiryDate' => $entry->expiryDate,
                        'title' => $entry->title,
                        'entryType' => $entry->type->handle
                    );

                    if ($entry->getParent()) {
                        $entryContent['parent'] = $primaryEntry->getParent()->slug;
                    }

                    $this->getContent($entryContent, $entry);
                    $entryContent = $this->onBeforeExport($entry, $entryContent);

                    $content['sites'][$site->handle] = $entryContent;
                }
            }
        }
        return $content;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function importItem(Array $data)
    {
        $primaryEntry = Entry::find()
            ->section($data['section'])
            ->slug($data['slug'])
            ->site('default')
            ->first();

        if (array_key_exists('parent', $data))
        {
            $this->importItem($data['parent']);
        }

        foreach($data['sites'] as $value) {
            if ($primaryEntry) {
                $value['id'] = $primaryEntry->id;
                $this->localizeData($primaryEntry, $value);
            }

            $entry = $this->createModel($value);
            $this->getSourceIds($value);
            $this->validateImportValues($value);
            $entry->setFieldValues($value['fields']);

            $event = $this->onBeforeImport($entry, $value);
            if ($event->isValid) {

                $result = Craft::$app->getElements()->saveElement($event->element);

                if ($result) {
                    $this->onAfterImport($event->element, $data);
                } else {
                    $this->addError('error', 'Could not save the ' . $data['slug'] . ' entry.');
                    $this->addError('error', join(',', $event->element->getErrors()));
                    return false;
                }
            } else {
                $this->addError('error', 'Error importing ' . $data['slug'] . ' field.');
                $this->addError('error', $event->error);
                return false;
            }

            if (!$primaryEntry) {
                $primaryEntry = $entry;
            }
        }
        return true;
    }

    /**
     * @param array $data
     * @return Entry
     */
    public function createModel(Array $data)
    {
        $entry = new Entry();

        if (array_key_exists('id', $data)){
            $entry->id = $data['id'];
        }

        $section = Craft::$app->sections->getSectionByHandle($data['section']);
        $entry->sectionId = $section->id;

        $entryType = $this->getEntryType($data['entryType'], $entry->sectionId);
        if ($entryType) {
            $entry->typeId = $entryType->id;
        }

        $entry->slug = $data['slug'];
        $entry->postDate = DateTimeHelper::toDateTime($data['postDate']);
        $entry->expiryDate = is_null($data['expiryDate']) ? '' : DateTimeHelper::toDateTime($data['expiryDate']);

        $entry->enabled = $data['enabled'];
        $entry->siteId = Craft::$app->sites->getSiteByHandle($data['site'])->id;

        if (array_key_exists('parent', $data))
        {
            $query = Entry::find();
            $query->sectionId($entry->sectionId);
            $query->siteId($entry->siteId);
            $query->slug($data['parent']);
            $parent = $query->one();
            if ($parent) {
                $entry->newParentId = $parent->id;
            }
        }

        $entry->title = $data['title'];

        //grab the content id for existing entries
        if (!is_null($entry->id)){
            $contentEntry = Craft::$app->entries->getEntryById($entry->id, $entry->siteId);
            if ($contentEntry) {
                $entry->contentId = $contentEntry->contentId;
            }
        }

        return $entry;
    }
}