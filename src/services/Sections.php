<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\EntryType;
use craft\models\Entry;
use firstborn\migrationmanager\events\ExportEvent;


class Sections extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'section';

    /**
     * @var string
     */
    protected $destination = 'sections';

    /**
     * {@inheritdoc}
     */
    public function exportItem($id, $fullExport = false)
    {
        $section = Craft::$app->sections->getSectionById($id);

        if (!$section) {
            return false;
        }

        $newSection = [
            'name' => $section->attributes['name'],
            'handle' => $section->attributes['handle'],
            'type' => $section->attributes['type'],
            'enableVersioning' => $section->attributes['enableVersioning'],
            'propagateEntries' => $section->attributes['propagateEntries'],
        ];

        if ($section->type == Section::TYPE_STRUCTURE){
            $newSection['maxLevels'] =  $section->attributes['maxLevels'];
        }

        $this->addManifest($section->attributes['handle']);

        $siteSettings = $section->getSiteSettings();

        $newSection['sites'] = array();

        foreach ($siteSettings as $siteSetting) {
            $site = Craft::$app->sites->getSiteById($siteSetting->siteId);
            $newSection['sites'][$site->handle] = [
                'site' => $site->handle,
                'hasUrls' => $siteSetting->hasUrls,
                'uriFormat' => $siteSetting->uriFormat,
                'enabledByDefault' => $siteSetting->enabledByDefault,
                'template' => $siteSetting->template,
            ];
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
                'requiredFields' => array(),
            ];

            if ($newEntryType['titleFormat'] === null) {
                unset($newEntryType['titleFormat']);
            }

            $fieldLayout = $entryType->getFieldLayout();

            foreach ($fieldLayout->getTabs() as $tab) {
                $newEntryType['fieldLayout'][$tab->name] = array();
                foreach ($tab->getFields() as $tabField) {

                    $newEntryType['fieldLayout'][$tab->name][] = $tabField->handle;
                    if ($tabField->required) {
                        $newEntryType['requiredFields'][] = $tabField->handle;
                    }
                }
            }

            array_push($newSection['entrytypes'], $newEntryType);
        }


        if ($fullExport) {
            $newSection = $this->onBeforeExport($section, $newSection);
        }

        return $newSection;
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(array $data)
    {
        $result = true;
        $existing = Craft::$app->sections->getSectionByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $section = $this->createModel($data);

        $event = $this->onBeforeImport($section, $data);
        if ($event->isValid) {
            if (Craft::$app->sections->saveSection($event->element)) {
                $this->onAfterImport($event->element, $data);
                if (!$existing) {
                    //wipe out the default entry type
                    $defaultEntryType = Craft::$app->sections->getEntryTypesBySectionId($section->id);
                    if ($defaultEntryType) {
                        Craft::$app->sections->deleteEntryTypeById($defaultEntryType[0]->id);
                    }
                }

                //add entry types
                foreach ($data['entrytypes'] as $key => $newEntryType) {
                    $existingType = $this->getSectionEntryTypeByHandle($newEntryType['handle'], $section->id);
                    if ($existingType) {
                        $this->mergeEntryType($newEntryType, $existingType);
                    }

                    $entryType = $this->createEntryType($newEntryType, $section);

                    if (!Craft::$app->sections->saveEntryType($entryType)) {
                        $result = false;
                    }
                }
            } else {
                $this->addError('error', 'Could not save the ' . $data['handle'] . ' section.');
                $result = false;
            }

        } else {
            $this->addError('error', 'Error importing ' . $data['handle'] . ' section.');
            $this->addError('error', $event->error);
            return false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createModel(array $data)
    {
        $section = new Section();
        if (array_key_exists('id', $data)) {
            $section->id = $data['id'];
        }

        $section->name = $data['name'];
        $section->handle = $data['handle'];
        $section->type = $data['type'];
        $section->enableVersioning = $data['enableVersioning'];
        $section->propagateEntries = $data['propagateEntries'];

        if ($section->type == Section::TYPE_STRUCTURE){
            $section->maxLevels = $data['maxLevels'];
        }

        $allSiteSettings = [];
        if (array_key_exists('sites', $data)) {

            foreach ($data['sites'] as $key => $siteData) {
                //determine if locale exists
                $site = Craft::$app->getSites()->getSiteByHandle($key);
                $siteSettings = new Section_SiteSettings();
                $siteSettings->siteId = $site->id;
                $siteSettings->hasUrls = $siteData['hasUrls'];
                $siteSettings->uriFormat = $siteData['uriFormat'];
                $siteSettings->template = $siteData['template'];
                $siteSettings->enabledByDefault = (bool)$siteData['enabledByDefault'];
                $allSiteSettings[$site->id] = $siteSettings;
            }
        }

        $section->setSiteSettings($allSiteSettings);

        return $section;
    }

    /**
     * @param array        $data
     * @param SectionModel $section
     *
     * @return EntryTypeModel
     */
    private function createEntryType($data, $section)
    {
        $entryType = new EntryType(array(
            'sectionId' => $section->id,
            'name' => $data['name'],
            'handle' => $data['handle'],
            'hasTitleField' => $data['hasTitleField'],
            'titleLabel' => $data['titleLabel'],
        ));

        if (array_key_exists('titleFormat', $data)) {
            $entryType->titleFormat = $data['titleFormat'];
        }

        if (array_key_exists('id', $data)) {
            $entryType->id = $data['id'];
        }

        $requiredFields = array();
        if (array_key_exists('requiredFields', $data)) {
            foreach ($data['requiredFields'] as $handle) {
                $field = Craft::$app->fields->getFieldByHandle($handle);
                if ($field) {
                    $requiredFields[] = $field->id;
                }
            }
        }

        $layout = array();
        foreach ($data['fieldLayout'] as $key => $fields) {
            $fieldIds = array();
            foreach ($fields as $field) {
                $existingField = Craft::$app->fields->getFieldByHandle($field);
                if ($existingField) {
                    $fieldIds[] = $existingField->id;
                }
            }
            $layout[$key] = $fieldIds;
        }

        $fieldLayout = Craft::$app->fields->assembleLayout($layout, $requiredFields);
        $fieldLayout->type = Entry::class;
        $entryType->fieldLayout = $fieldLayout;

        return $entryType;
    }

    /**
     * @param array        $newSection
     * @param SectionModel $section
     */
    private function mergeUpdates(&$newSection, $section)
    {
        $newSection['id'] = $section->id;
    }

    /**
     * @param array          $newEntryType
     * @param EntryTypeModel $entryType
     */
    private function mergeEntryType(&$newEntryType, $entryType)
    {
        $newEntryType['id'] = $entryType->id;
    }

    /**
     * @param string $handle
     * @param int    $sectionId
     *
     * @return bool
     */
    private function getSectionEntryTypeByHandle($handle, $sectionId)
    {
        $entryTypes = Craft::$app->sections->getEntryTypesBySectionId($sectionId);
        foreach ($entryTypes as $entryType) {
            if ($entryType->handle == $handle) {
                return $entryType;
            }
        }

        return false;
    }
}
