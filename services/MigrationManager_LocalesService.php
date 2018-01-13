<?php

namespace Craft;

/**
 * Class MigrationManager_LocalesService
 */
class MigrationManager_LocalesService extends MigrationManager_BaseMigrationService
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
        $locales = craft()->i18n->getSiteLocaleIds();

        if (in_array($data['id'], $locales) === false) {
            $result = craft()->i18n->addSiteLocale($data['id']);
        } else {
            $result = true;
        }

        return $result;
    }
}
