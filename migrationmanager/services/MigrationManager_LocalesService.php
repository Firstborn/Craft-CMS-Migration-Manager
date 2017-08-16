<?php

namespace Craft;

class MigrationManager_LocalesService extends MigrationManager_BaseMigrationService
{
    protected $source = 'locale:settings';
    protected $destination = 'locales:settings';

    public function exportItem($id, $fullExport)
    {
        $locale = ['id' => $id];

        return $locale;
    }

    public function importItem(Array $data)
    {
        $locales = craft()->i18n->getSiteLocaleIds();

        if (in_array($data['id'], $locales) === false)
        {
            $result = craft()->i18n->addSiteLocale($data['id']);
        } else {
            $result = true;
        }

        return $result;
    }

    public function createModel(Array $data)
    {
        return null;

    }






}