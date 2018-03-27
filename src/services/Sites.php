<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\models\Site;
use craft\models\SiteGroup;

class Sites extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'site';

    /**
     * @var string
     */
    protected $destination = 'sites';

    /**
     * {@inheritdoc}
     */
    public function exportItem($id, $fullExport = false)
    {
        $site = Craft::$app->sites->getSiteById($id);
        $newSite = [
            'name' => $site->name,
            'handle' => $site->handle,
            'group' => $site->group->name,
            'language' => $site->language,
            'hasUrls' => $site->hasUrls,
            'baseUrl' => $site->baseUrl,
            'primary' => $site->primary,
            'sortOrder' => $site->sortOrder
        ];

        $this->addManifest($site->handle);

        return $newSite;
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(Array $data)
    {
        $result = true;
        $existing = Craft::$app->sites->getSiteByHandle($data['handle']);

        if ($existing){
            $this->mergeUpdates($data, $existing);
        }

        $group = $this->getSiteGroup($data['group']);
        if (!$group){
            $group = new SiteGroup();
            $group->name = $data['group'];
            Craft::$app->sites->saveGroup($group);
        }

        $data['groupId'] = $group->id;
        $site = $this->createModel($data);

        if ($site) {
           if (Craft::$app->sites->saveSite($site)) {
               $result = true;
           }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createModel(array $data)
    {
        $site = new Site();

        if (array_key_exists('id', $data)) {
            $site->id = $data['id'];
        }

        $site->name = $data['name'];
        $site->handle = $data['handle'];
        $site->groupId = $data['groupId'];
        $site->language = $data['language'];
        $site->hasUrls = $data['hasUrls'];
        $site->baseUrl = $data['baseUrl'];
        $site->primary = $data['primary'];
        $site->sortOrder = $data['sortOrder'];
        return $site;
    }

    /**
     * @param array        $newSection
     * @param SectionModel $section
     */
    private function mergeUpdates(&$newSite, $site)
    {
        $newSite['id'] = $site->id;
    }

    /**
     * @param $name
     * @return SiteGroup
     */

    public function getSiteGroup($name){
        $groups = Craft::$app->sites->getAllGroups();
        foreach($groups as $group){
            if ($group->name == $name){
                return $group;
            }
        }
        return false;
    }
}
