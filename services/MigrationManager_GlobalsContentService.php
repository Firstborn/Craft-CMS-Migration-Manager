<?php
namespace Craft;

class MigrationManager_GlobalsContentService extends MigrationManager_BaseContentMigrationService
{
    protected $source = 'global';
    protected $destination = 'globals';

    public function exportItem($id, $fullExport = false)
    {

        $globalSet = craft()->globals->getSetById($id);
        $locales = $globalSet->getLocales();

        $content = array(
            'handle' => $globalSet->handle,
            'locales' => array()
        );

        $this->addManifest($globalSet->handle);


        foreach($locales as $locale){
            $set = craft()->globals->getSetById($id, $locale);

            $setContent = array(
                'slug' => $set->handle,
            );

            $this->getContent($setContent, $set);
            $content['locales'][$locale] = $setContent;
        }

        return $content;
    }

    public function importItem(Array $data)
    {
        $globalSet = craft()->globals->getSetByHandle($data['handle']);

        foreach($data['locales'] as $key => $value) {

            $set = craft()->globals->getSetById($globalSet->id, $key);

            $this->getSourceIds($value);
            $this->validateImportValues($value);
            $set->setContentFromPost($value);

            // save set
            if (!$success = craft()->globals->saveContent($set)) {
                throw new Exception(print_r($set->getErrors(), true));
            }
        }
        return true;
    }

    public function createModel(Array $data)
    {
        return null;
    }

}