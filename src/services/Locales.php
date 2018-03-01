<?php

namespace firstborn\migrationmanager\services;

class Locales extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'locale';

    /**
     * @var string
     */
    protected $destination = 'locales';

    /**
     * {@inheritdoc}
     */
    public function exportItem($id, $fullExport = false)
    {
        $locale = ['id' => $id];

        $this->addManifest($id);

        return $locale;
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
