<?php

namespace firstborn\migrationmanager\services;
use Craft;

class EntriesContent extends BaseContentMigration
{
    protected $source = 'entry';
    protected $destination = 'entries';

    public function exportItem($id, $fullExport = false)
    {
        $primaryEntry = Craft::$app->entries->getEntryById($id);

        $sites = $primaryEntry->getSection()->getSiteIds();
        //$locales = $primaryEntry->getSection()->getLocales();

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
        $criteria = Craft::$app->elements->getCriteria(ElementType::Entry);
        $criteria->section = $data['section'];
        $criteria->slug = $data['slug'];
        $primaryEntry = $criteria->first();

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
            $entry->setContentFromPost($value);

           // save entry
            if (!$success = Craft::$app->entries->saveEntry($entry)) {

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
        $entry = new EntryModel();

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
        $entry->postDate = $data['postDate'];
        $entry->expiryDate = $data['expiryDate'];
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

        $entry->getContent()->title = $data['title'];

        return $entry;
    }







}