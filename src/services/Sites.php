<?php

namespace firstborn\migrationmanager\services;

class Sites extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'sites';

    /**
     * @var string
     */
    protected $destination = 'sites';

    /**
     * {@inheritdoc}
     */
    public function exportItem($id, $fullExport = false)
    {
        $site =
        $newSite = [
            'handle' => $site->handle,
            'group' => $site->getGroup()->handle,
            'language' => $site->language
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
