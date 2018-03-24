<?php

namespace firstborn\migrationmanager\services;
use Craft;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;

class EntriesContent extends BaseContentMigration
{
    protected $source = 'entry';
    protected $destination = 'entries';

    public function exportItem($id, $fullExport = false)
    {
        $primaryEntry = Craft::$app->entries->getEntryById($id);

        $sites = $primaryEntry->getSection()->getSiteIds();



        $content = array(
            'slug' => $primaryEntry->slug,
            'section' => $primaryEntry->getSection()->handle,
            'sites' => array()
        );

        $this->addManifest($content['slug']);

        if ($primaryEntry->getParent())
        {
            $content['parent'] = $this->exportItem($primaryEntry->getParent()->id, true);
        }

        foreach($sites as $siteId){
            $site = Craft::$app->sites->getSiteById($siteId);
            $entry = Craft::$app->entries->getEntryById($id, $siteId);
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
                $content['sites'][$site->handle] = $entryContent;
            }
        }

        return $content;
    }

    public function importItem(Array $data)
    {

        //$currentEntry = Craft::$app->getEntries()->getEntryById($entry->id, $entry->siteId);


        $primaryEntry = Entry::find()
            ->section($data['section'])
            ->slug($data['slug'])
            ->site('default')
            ->first();

        Craft::error('existing entry: '. $primaryEntry->id);

        if (array_key_exists('parent', $data))
        {
            $this->importItem($data['parent']);
        }

        foreach($data['sites'] as $value) {
            if ($primaryEntry) {
                $value['id'] = $primaryEntry->id;
            }

            $entry = $this->createModel($value);
            $this->getSourceIds($value);
            $this->validateImportValues($value);
            $entry->setFieldValues($value['fields']);

            Craft::error('save entry: '. $entry->id);

           // save entry
            $result = Craft::$app->getElements()->saveElement($entry);
            Craft::error('saved entry: '. ($result ? 'yes' : 'no'));
            if (!$result) {
                Craft::error('error saving entry');
                throw new Exception(print_r($entry->getErrors(), true));
            }

            if (!$primaryEntry) {
                $primaryEntry = $entry;
            }
        }
        return true;
    }

    public function createModel(Array $data)
    {
        $entry = new Entry();

        if (array_key_exists('id', $data)){
            $entry->id = $data['id'];
            $entry->contentId = $data['id'];
        }

        $section = Craft::$app->sections->getSectionByHandle($data['section']);
        $entry->sectionId = $section->id;

        $entryType = $this->getEntryType($data['entryType'], $entry->sectionId);
        if ($entryType) {
            $entry->typeId = $entryType->id;
        }

        $entry->slug = $data['slug'];
        $entry->postDate = DateTimeHelper::toDateTime($data['postDate']);
        $entry->expiryDate = DateTimeHelper::toDateTime($data['expiryDate']);
        $entry->enabled = $data['enabled'];
        $entry->enabledForSite = $data['enabledForSite'];
        $entry->siteId = Craft::$app->sites->getSiteByHandle($data['site'])->id;

        if (array_key_exists('parent', $data))
        {
            $criteria = Craft::$app->elements->getCriteria(ElementType::Entry);
            $criteria->slug = $data['parent'];
            $criteria->section = $section;
            $parent = $criteria->first();
            if ($parent) {
                $entry->parentId = $parent->id;
            }
        }

        $entry->title = $data['title'];

        return $entry;
    }







}