<?php
namespace Craft;

class MigrationManager_EntriesContentService extends MigrationManager_BaseContentMigrationService
{
    protected $source = 'entry';
    protected $destination = 'entries';

    public function exportItem($id, $fullExport = false)
    {
        $primaryEntry = craft()->entries->getEntryById($id);
        $locales = $primaryEntry->getSection()->getLocales();
        $content = array(
            'slug' => $primaryEntry->slug,
            'section' => $primaryEntry->getSection()->handle,
            'locales' => array()
        );

        $this->addManifest($content['slug']);

        if ($primaryEntry->getParent())
        {
            $content['parent'] = $this->exportItem($primaryEntry->getParent()->id, true);
        }

        foreach($locales as $locale){
            $entry = craft()->entries->getEntryById($id, $locale->locale);
            if ($entry) {
                $entryContent = array(
                    'slug' => $entry->slug,
                    'section' => $entry->getSection()->handle,
                    'enabled' => $entry->enabled,
                    'locale' => $entry->locale,
                    'localeEnabled' => $entry->localeEnabled,
                    'postDate' => $entry->postDate,
                    'expiryDate' => $entry->expiryDate,
                    'title' => $entry->title,
                    'entryType' => $entry->type->handle
                );

                if ($entry->getParent()) {
                    $entryContent['parent'] = $primaryEntry->getParent()->slug;
                }

                $this->getContent($entryContent, $entry);
                $content['locales'][$locale->locale] = $entryContent;
            }
        }

        return $content;
    }

    public function importItem(Array $data)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = $data['section'];
        $criteria->slug = $data['slug'];
        $primaryEntry = $criteria->first();

        if (array_key_exists('parent', $data))
        {
            $this->importItem($data['parent']);
        }

        foreach($data['locales'] as $value) {
            if ($primaryEntry) {
                $value['id'] = $primaryEntry->id;
                $this->localizeData($primaryEntry, $value);
            }

            $entry = $this->createModel($value);
            $this->getSourceIds($value);
            $this->validateImportValues($value);
            $entry->setContentFromPost($value);

            // save entry
            if (!$success = craft()->entries->saveEntry($entry)) {

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

        $section = craft()->sections->getSectionByHandle($data['section']);
        $entry->sectionId = $section->id;

        $entryType = $this->getEntryType($data['entryType'], $entry->sectionId);
        if ($entryType) {
            $entry->typeId = $entryType->id;
        }

        $entry->locale = $data['locale'];
        $entry->slug = $data['slug'];
        $entry->postDate = $data['postDate'];
        $entry->expiryDate = $data['expiryDate'];
        $entry->enabled = $data['enabled'];
        $entry->localeEnabled = $data['localeEnabled'];

        if (array_key_exists('parent', $data))
        {
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
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