<?php

namespace firstborn\migrationmanager\services;
use Craft;

class GlobalsContent extends BaseContentMigration
{
    protected $source = 'global';
    protected $destination = 'globals';

    /**
     * @param int $id
     * @param bool $fullExport
     * @return array
     */
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

            $setContent = $this->onBeforeExport($set, $setContent);
            $content['sites'][$site->handle] = $setContent;
        }

        return $content;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function importItem(Array $data)
    {
        $globalSet = Craft::$app->globals->getSetByHandle($data['handle']);

        foreach($data['sites'] as $key => $value) {
            $site = Craft::$app->sites->getSiteByHandle($key);
            $set = Craft::$app->globals->getSetById($globalSet->id, $site->id);

            $this->getSourceIds($value);
            $this->validateImportValues($value);
            $set->setFieldValues($value['fields']);

            $event = $this->onBeforeImport($set, $value);
            if ($event->isValid) {
                // save set
                $result = Craft::$app->getElements()->saveElement($event->element);
                if ($result) {
                    $this->onAfterImport($event->element, $data);
                } else {
                    $this->addError('error', 'Could not save the ' . $data['handle'] . ' global.');
                    $this->addError('error', join(',', $event->element->getErrors()));
                    return false;
                }
            } else {
                $this->addError('error', 'Error importing ' . $data['handle'] . ' global.');
                $this->addError('error', $event->error);
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $data
     * @return null
     */

    public function createModel(Array $data)
    {
        return null;
    }

}