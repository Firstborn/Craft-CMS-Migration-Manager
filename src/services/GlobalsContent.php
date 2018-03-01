<?php

namespace firstborn\migrationmanager\services;

class GlobalsContent extends BaseContentMigration
{
    protected $source = 'global';
    protected $destination = 'globals';

    public function exportItem($id, $fullExport = false)
    {

        $globalSet = Craft::$app->globals->getSetById($id);
        $locales = $globalSet->getLocales();

        $content = array(
            'handle' => $globalSet->handle,
            'locales' => array()
        );

        $this->addManifest($globalSet->handle);


        foreach($locales as $locale){
            $set = Craft::$app->globals->getSetById($id, $locale);

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
        $globalSet = Craft::$app->globals->getSetByHandle($data['handle']);

        foreach($data['locales'] as $key => $value) {

            $set = Craft::$app->globals->getSetById($globalSet->id, $key);

            $this->getSourceIds($value);
            $this->validateImportValues($value);
            $set->setContentFromPost($value);

            // save set
            if (!$success = Craft::$app->globals->saveContent($set)) {
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