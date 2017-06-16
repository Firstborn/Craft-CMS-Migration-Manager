<?php

namespace Craft;

class MigrationManager_SectionsService extends BaseApplicationComponent
{

    /**
     * @param $ids array of section ids to export
     */
    public function exportSections($ids)
    {
         $sections = array();
        foreach ($ids as $id) {
            $sections[] = $this->export($id);
        }
        return $sections;
    }

    public function export($id)
    {
        $section = craft()->sections->getSectionById($id);

        if (!$section) {
            return false;
        }


        $urlFormat = $section->getUrlFormat();
        $locales = $section->getLocales();
        $primaryLocale = craft()->i18n->getPrimarySiteLocaleId();

        $newSection = [
            'name' => $section->attributes['name'],
            'handle' => $section->attributes['handle'],
            'type' => $section->attributes['type'],
            'enableVersioning' => $section->attributes['enableVersioning'],
            'hasUrls' => $section->attributes['hasUrls'],
            'template' => $section->attributes['template'],
            'maxLevels' => $section->attributes['maxLevels']
        ];


        /*
        if ($newSection['type'] === 'single') {
            //    unset($newSection['typesettings']['hasUrls']);
        }*/


        if ((bool) $section->attributes['hasUrls'] === false ) {
            $newSection['locales'] = [];
            foreach ($locales as $locale => $attributes) {
                $newSection['locales'][$locale] = $attributes['enabledByDefault'];
            }

        } else {

            $newSection['locales'] = array();

            foreach ($locales as $key => $locale ) {
                 $newSection['locales'][$key] = [
                    'locale' => $locale->locale,
                    'urlFormat' => $locale->urlFormat,
                    'nestedUrlFormat' => $locale->nestedUrlFormat,
                    'enabledByDefault' => $locale->enabledByDefault,
                ];

            }
        }

        $newSection['entrytypes'] = array();

        $sectionEntryTypes = $section->getEntryTypes();
        foreach ($sectionEntryTypes as $entryType) {
            $newEntryType = [
                'sectionHandle' => $section->attributes['handle'],
                'hasTitleField' => $entryType->attributes['hasTitleField'],
                'titleLabel' => $entryType->attributes['titleLabel'],
                'titleFormat' => $entryType->attributes['titleFormat'],
                'name' => $entryType->attributes['name'],
                'handle' => $entryType->attributes['handle'],
                'titleLabel' => $entryType->attributes['titleLabel'],
                'fieldLayout' => [],
            ];


            if ($newEntryType['titleFormat'] === null) {
                unset($newEntryType['titleFormat']);
            }

            $fieldLayout = $entryType->getFieldLayout();

            foreach ($fieldLayout->getTabs() as $tab) {
                $newEntryType['fieldLayout'][$tab->name] = [];
                foreach ($tab->getFields() as $tabField) {
                    array_push($newEntryType['fieldLayout'][$tab->name], craft()->fields->getFieldById($tabField->fieldId)->handle);
                }
            }

            array_push($newSection['entrytypes'], $newEntryType);
        }

        return $newSection;
    }

    /**
     * @param $ids array of fields ids to export
     */
    public function importSections($data)
    {
        $result = true;
        foreach ($data as $section) {
            if ($this->import($section) === false) {
                $result = false;
            }
        }

        return $result;
    }

    public function import($data)
    {

        $existing = craft()->sections->getSectionByHandle($data['handle']);

        if ($existing) {
              $this->mergeUpdates($data, $existing);
        } else {
            //$data['id'] = 'new';
        }

        MigrationManagerPlugin::log(JsonHelper::encode($data), LogLevel::Error);

        $section = $this->createSection($data);

        MigrationManagerPlugin::log(JsonHelper::encode($section), LogLevel::Error);

        $result = craft()->sections->saveSection($section);

        echo 'saved section ' . ($result ? 'yes' : 'no') . PHP_EOL;
        if ($result) {
            //add entry types
            foreach($data['entrytypes'] as $type)
            {
                $existing = craft()->sections->getEntryTypesByHandle($type['handle']);
                if ($existing)
                {
                    //$entrytype = $this->createEntryType($type);
                }


            }

        } else {
            echo 'save failed' . PHP_EOL;
            $errors = $section->getAllErrors();
            echo JsonHelper::encode($errors) . PHP_EOL;
            MigrationManagerPlugin::log('Could not save the ' . $data['handle'] . ' section.', LogLevel::Error);
            return false;
        }






        return $result;
    }

    private function createSection($data)
    {
        echo JsonHelper::encode($data) . PHP_EOL;
        $section = new SectionModel();
        if (array_key_exists('id', $data)){
            $section->id = $data['id'];
        }

        $section->name = $data['name'];
        $section->handle = $data['handle'];
        $section->type = $data['type'];
        $section->enableVersioning = $data['enableVersioning'];

        // Type-specific attributes
        if ($section->type == SectionType::Single)
        {
            $section->hasUrls = $data['hasUrls'] = true;
        }
        else
        {
            $section->hasUrls = $data['hasUrls'];
        }

        if ($section->hasUrls)
        {
            $section->template = $data['template'];
        }
        else
        {
            $section->template = $data['template'] = null;
        }

        if (array_key_exists('locales', $data))
        {
            $locales = array();
            foreach($data['locales'] as $key => $locale){
                echo 'set locales' . JsonHelper::encode($key . ' ' . $locale["urlFormat"]) . PHP_EOL;
                $locales[$key] = new SectionLocaleModel(array(
                    'locale' => $key,
                    'enabledByDefault' => $locale['enabledByDefault'],
                    'urlFormat' => $locale['urlFormat'],
                    'nestedUrlFormat' => $locale['nestedUrlFormat'],
                ));
            }

            $section->setLocales($locales);

        }



        return $section;



    }


    private function mergeUpdates(&$newSection, $section)
    {
        $newSection['id'] = $section->id;

    }
}