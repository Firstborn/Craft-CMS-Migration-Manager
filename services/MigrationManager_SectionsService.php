<?php

namespace Craft;


class MigrationManager_SectionsService extends MigrationManager_BaseMigrationService
{
    protected $source = 'section';
    protected $destination = 'sections';

    public function exportItem($id, $fullExport)
    {
        $section = craft()->sections->getSectionById($id);

        if (!$section) {
            return false;
        }

        $newSection = [
            'name' => $section->attributes['name'],
            'handle' => $section->attributes['handle'],
            'type' => $section->attributes['type'],
            'enableVersioning' => $section->attributes['enableVersioning'],
            'hasUrls' => $section->attributes['hasUrls'],
            'template' => $section->attributes['template'],
            'maxLevels' => $section->attributes['maxLevels']
        ];

        $this->addManifest($section->attributes['handle']);

        $locales = $section->getLocales();

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
                'fieldLayout' => array(),
                'requiredFields' => array()
            ];

            if ($newEntryType['titleFormat'] === null) {
                unset($newEntryType['titleFormat']);
            }

            $fieldLayout = $entryType->getFieldLayout();

            foreach ($fieldLayout->getTabs() as $tab) {
                $newEntryType['fieldLayout'][$tab->name] = array();
                foreach ($tab->getFields() as $tabField) {

                    $newEntryType['fieldLayout'][$tab->name][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                    if ($tabField->required)
                    {
                        $newEntryType['requiredFields'][] = craft()->fields->getFieldById($tabField->fieldId)->handle;
                    }
                }
            }

            array_push($newSection['entrytypes'], $newEntryType);
        }

        return $newSection;
    }



    public function importItem(Array $data)
    {
        $result = true;
        $existing = craft()->sections->getSectionByHandle($data['handle']);

        if ($existing) {
              $this->mergeUpdates($data, $existing);
        }

        $section = $this->createModel($data);

        if ($section) {
            if (craft()->sections->saveSection($section)) {
                //add entry types
                foreach ($data['entrytypes'] as $key => $newEntryType) {
                    $existingType = $this->getSectionEntryTypeByHandle($newEntryType['handle'], $section->id);
                    if ($existingType) {
                        $this->mergeEntryType($newEntryType, $existingType);
                    }

                    $entryType = $this->createEntryType($newEntryType, $section);

                    if (!craft()->sections->saveEntryType($entryType)) {
                        $result = false;
                    }
                }

            } else {
                $result = false;
            }
        } else {
            $result = false;

        }

        return $result;
    }

    public function createModel(Array $data)
    {

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
                //determine if locale exists
                if (in_array($key, craft()->i18n->getSiteLocaleIds())) {
                    if ($section->hasUrls) {
                        $locales[$key] = new SectionLocaleModel(array(
                            'locale' => $key,
                            'enabledByDefault' => array_key_exists('enabledByDefault', $locale) ? $locale['enabledByDefault'] : true,
                            'urlFormat' => $locale['urlFormat'],
                            'nestedUrlFormat' => $locale['nestedUrlFormat'],
                        ));
                    } else {
                        $locales[$key] = new SectionLocaleModel(array(
                            'locale' => $key
                        ));
                    }
                } else {
                    $this->addError('missing locale: ' . $key . ' in section: ' . $section->handle . ', locale not defined in system');
                }
            }
            $section->setLocales($locales);
        }

        return $section;

    }



    private function createEntryType($data, $section)
    {
        $entryType = new EntryTypeModel(array(
            'sectionId' => $section->id,
            'name' => $data['name'] ,
            'handle' => $data['handle'] ,
            'hasTitleField' => $data['hasTitleField'],
            'titleLabel' => $data['titleLabel']
        ));

        if (array_key_exists('titleFormat', $data))
        {
            $entryType->titleFormat = $data['titleFormat'];
        }

        if (array_key_exists('id', $data)){
            $entryType->id = $data['id'];
        }

        $requiredFields = array();
        if (array_key_exists('requiredFields', $data)) {
            foreach ($data['requiredFields'] as $handle) {
                $field = craft()->fields->getFieldByHandle($handle);
                if ($field) {
                    $requiredFields[] = $field->id;
                }
            }
        }

        $layout = array();
        foreach($data['fieldLayout'] as $key => $fields)
        {
            $fieldIds = array();
            foreach($fields as $field) {
                $existingField = craft()->fields->getFieldByHandle($field);
                if ($existingField) {
                    $fieldIds[] = $existingField->id;
                }
            }
            $layout[$key] = $fieldIds;
        }


        $fieldLayout = craft()->fields->assembleLayout($layout, $requiredFields);
        $fieldLayout->type = ElementType::Entry;
        $entryType->fieldLayout = $fieldLayout;

        return $entryType;


    }


    private function mergeUpdates(&$newSection, $section)
    {
        $newSection['id'] = $section->id;
    }

    private function mergeEntryType(&$newEntryType, $entryType)
    {
        $newEntryType['id'] = $entryType->id;
    }

    private function getSectionEntryTypeByHandle($handle, $sectionId)
    {
        $entryTypes = craft()->sections->getEntryTypesBySectionId($sectionId);
        foreach($entryTypes as $entryType)
        {
            if ($entryType->handle == $handle)
            {
                return $entryType;
            }
        }
        return false;
    }


}