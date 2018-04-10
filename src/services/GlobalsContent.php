<?php

namespace firstborn\migrationmanager\services;
use Craft;

class GlobalsContent extends BaseContentMigration
{
    protected $source = 'global';
    protected $destination = 'globals';

    public function exportItem($id, $fullExport = false)
    {

        $globalSet = Craft::$app->globals->getSetById($id);
        $sites = Craft::$app->sites->getAllSiteIds();

        $content = array(
            'handle' => $globalSet->handle,
            'locales' => array()
        );

        $this->addManifest($globalSet->handle);

        foreach($sites as $siteId){
            $site = Craft::$app->sites->getSiteById($siteId);
            $set = Craft::$app->globals->getSetById($id, $site->id);

            $setContent = array(
                'slug' => $set->handle,
            );

            $this->getContent($setContent, $set);
            $content['sites'][$site->handle] = $setContent;
        }

        return $content;
    }

    public function importItem(Array $data)
    {
        $globalSet = Craft::$app->globals->getSetByHandle($data['handle']);

        foreach($data['sites'] as $key => $value) {
            $site = Craft::$app->sites->getSiteByHandle($key);
            $set = Craft::$app->globals->getSetById($globalSet->id, $site->id);

            $this->getSourceIds($value);
            $this->validateImportValues($value);
            $set->setFieldValues($value['fields']);

            // save set
            if (!$success = Craft::$app->getElements()->saveElement($set)) {
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