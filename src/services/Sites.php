<?php

namespace firstborn\migrationmanager\services;

use Craft;

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
        $locales = Craft::$app->i18n->getSiteLocaleIds();

        if (in_array($data['id'], $locales) === false) {
            $result = Craft::$app->i18n->addSiteLocale($data['id']);
        } else {
            $result = true;
        }

        return $result;
    }
}
