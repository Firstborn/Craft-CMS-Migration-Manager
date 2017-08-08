<?php
namespace Craft;

class MigrationManager_EntriesService extends MigrationManager_BaseMigrationService
{

    protected $source = 'entry';
    protected $destination = 'entries';

    public function exportItem($id, $fullExport)
    {
        $primaryEntry = craft()->entries->getEntryById($id);
        $locales = $primaryEntry->getSection()->getLocales();
        $content = array(
            'slug' => $primaryEntry->slug,
            'section' => $primaryEntry->getSection()->handle,
            'locales' => array()
        );

        foreach($locales as $locale){
            $entry = craft()->entries->getEntryById($id, $locale->locale);
            $entryContent = array(
                'slug' => $entry->slug,
                'section' => $entry->getSection()->handle,
                'enabled' => $entry->enabled,
                'locale' => $entry->locale,
                'localeEnabled' => $entry->localeEnabled,
                'postDate' => $entry->postDate,
                'expiryDate' => $entry->expiryDate,
                'title' => $entry->title,
                'entryType' => $entry->type->handle
            );

            foreach ($entry->getFieldLayout()->getFields() as $fieldModel) {
                $this->getElementContent($entryContent, $fieldModel, $entry);
            }
            $content['locales'][$locale->locale] = $entryContent;
        }
        return $content;
    }

    public function importItem(Array $data)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = $data['section'];
        $criteria->slug = $data['slug'];

        Craft::log('find entry: ' . $data['section'] . ' ' . $data['slug'] , LogLevel::Error);

        $primaryEntry = $criteria->first();

        foreach($data['locales'] as $key => $value) {

            //Craft::log('entries: ' . count($primaryEntry), LogLevel::Error);

            if ($primaryEntry) {
                $entry = craft()->entries->getEntryById($primaryEntry->id, $key);

                if ($entry) {
                    //Craft::log('found locale entry', LogLevel::Error);
                    //Craft::log(JsonHelper::encode($entry), LogLevel::Error);
                } else {
                    //Craft::log('no locale entry found', LogLevel::Error);
                    //Craft::log(JsonHelper::encode($value), LogLevel::Error);
                    $entry = new EntryModel();
                    $entry->id = $primaryEntry->id;
                    $entry->sectionId = $primaryEntry->section->id;
                    $entry->locale = $key;

                    $entryType = $this->getEntryType($value['entryType'], $entry->sectionId);
                    if ($entryType) {
                        $entry->typeId = $entryType->id;
                    }
                }
            } else {
                Craft::log('could not find primary entry', LogLevel::Error);
                Craft::log(JsonHelper::encode($value), LogLevel::Error);
                $entry = new EntryModel();
                $section = craft()->sections->getSectionByHandle($value['section']);
                $entry->sectionId = $section->id;
                $entry->locale = $key;

                $entryType = $this->getEntryType($value['entryType'], $section->id);
                if ($entryType) {
                    $entry->typeId = $entryType->id;
                }
            }

            $entry->slug = $value['slug'];
            $entry->postDate = $value['postDate'];
            $entry->expiryDate = $value['expiryDate'];
            $entry->enabled = $value['enabled'];
            $entry->localeEnabled = $value['localeEnabled'];
            $entry->getContent()->title = $value['title'];

            $this->getSourceIds($value);

            Craft::log('SAVE ENTRY ' . $entry->id, LogLevel::Error);
            Craft::log(JsonHelper::encode($value), LogLevel::Error);

            $entry->setContentFromPost($value);

            // save entry
            if (!$success = craft()->entries->saveEntry($entry)) {

                throw new Exception(print_r($entry->getErrors(), true));
            } else {
                Craft::log('entry was saved', LogLevel::Error);
            }

            if (!$primaryEntry) {
                $primaryEntry = $entry;
            }
        }

        return true;

        //return true;
    }



    public function createModel(Array $data)
    {
        return false;
    }

    private function getElementContent(&$content, $fieldModel, $parent)
    {
        $field = $fieldModel->getField();
        $value = $parent->getFieldValue($field->handle);

        // Fire an 'onExportField' event
        $event = new Event($this, array(
            'field' => $field,
            'parent' => $parent,
            'value' => $value
        ));

        $this->onExportField($event);

        if ($event->performAction == false) {
            $value = $event->params['value'];
        } else {
            switch ($field->type) {
                case 'RichText':
                    if ($value){
                        $value = $value->getRawContent();
                    } else {
                        $value = '';
                    }

                    break;
                case 'Matrix':
                    $model = $parent[$field->handle];
                    $value = $this->getIteratorValues($model, function ($item) {
                        $itemType = $item->getType();
                        $value = [
                            'type' => $itemType->handle,
                            'enabled' => $item->enabled,
                            'fields' => []
                        ];

                        return $value;
                    });
                    break;
                case 'SuperTable':

                    $model = $parent[$field->handle];
                    $value = $this->getIteratorValues($model, function () {
                        $value = [
                            'type' => 1,
                            'fields' => []
                        ];

                        return $value;
                    });

                    break;
                default:
                    if ($field->getFieldType() instanceof BaseElementFieldType) {

                        $this->getSourceHandles($value);
                    }
                    break;
            }
        }

        $content[$field->handle] = $value;

    }

    private function getIteratorValues($element, $settingsFunc)
    {
        $items = $element->getIterator();
        $value = [];

        $i = 1;
        foreach ($items as $item) {
            $itemType = $item->getType();
            $itemFields = $itemType->getFieldLayout()->getFields();
            $itemValue = $settingsFunc($item);

            $fields = [];

            foreach ($itemFields as $field) {
                $this->getElementContent($fields, $field, $item);
            }

            $itemValue['fields'] = $fields;
            $value['new' . $i] = $itemValue;
            $i++;
        }


        return $value;
    }

    private function getEntryType($handle, $sectionId)
    {
        $entryTypes = craft()->sections->getEntryTypesBySectionId($sectionId);
        foreach($entryTypes as $entryType)
        {
            if ($entryType->handle == $handle){
                return $entryType;
            }

        }

        return false;
    }

    private function getSourceHandles(&$value)
    {
        $elements = $value->elements();
        $value = [];
        if ($elements) {
            foreach ($elements as $element) {
                switch ($element->getElementType()) {
                    case 'Asset':
                        $item = [
                            'elementType' => 'Asset',
                            'filename' => $element->filename,
                            'folder' => $element->getFolder()->name,
                            'source' => $element->getSource()->handle
                        ];
                        break;
                    case 'Category':
                        $item = [
                            'elementType' => 'Category',
                            'slug' => $element->slug,
                            'category' => $element->getGroup()->handle
                        ];
                        break;
                    case 'Entry':
                        $item = [
                            'elementType' => 'Entry',
                            'slug' => $element->slug,
                            'section' => $element->getSection()->handle
                        ];
                        break;
                    case 'User':
                        $item = [
                            'elementType' => 'User',
                            'username' => $element->username
                        ];
                        break;
                    default:
                        $item = null;
                }

                if ($item)
                {
                    $value[] = $item;
                }


            }
        }

        return $value;
    }

    private function getSourceIds(&$value){

        //Craft::log('getSourceIds: ' . JsonHelper::encode($value), LogLevel::Error);

        if (is_array($value))
        {
            if (is_array($value)) {
                $this->populateIds($value);
            } else {
                $this->getSourceIds($value);
            }
        }
        return;
    }

    private function populateIds(&$value)
    {
        //Craft::log('populateIds ' . JsonHelper::encode($value), LogLevel::Error);

        //$elements = $value->elements();
        $ids = [];
        //if ($elements) {
        $isElementField = true;
        foreach ($value as &$element) {

            if (is_array($element) && key_exists('elementType', $element)) {

                switch ($element['elementType']) {
                    case 'Asset':
                        $source = $newSource = MigrationManagerHelper::getAssetSourceByHandle($element['source']);
                        Craft::log('find asset: ' . JsonHelper::encode($element), LogLevel::Error);

                        if ($source) {
                            $folderCriteria = new FolderCriteriaModel();
                            $folderCriteria->name = $element['folder'];
                            $folderCriteria->sourceId = $source->id;
                            $folder = craft()->assets->findFolder($folderCriteria);
                            //$folder = $folderCriteria->find();
                            if ($folder) {
                                $criteria = craft()->elements->getCriteria(ElementType::Asset);
                                $criteria->sourceId = $source->id;
                                $criteria->folderId = $folder->id;
                                $criteria->filename = $element['filename'];

                                $asset = $criteria->first();
                                if ($asset) {

                                    $ids[] = $asset->id;
                                    Craft::log('found asset: ' . serialize($asset), LogLevel::Error);
                                } else {
                                    Craft::log('no asset: ' . $element['filename'], LogLevel::Error);
                                }
                            } else {
                                Craft::log('no folder: ' . $element['folder'], LogLevel::Error);
                            }
                        } else {
                            Craft::log('no source:' . $element['source'], LogLevel::Error);
                        }


                        break;
                    case 'Category':
                        Craft::log('find category: ' . $element['slug'], LogLevel::Error);

                        break;
                    case 'Entry':
                        Craft::log('find entry: ' . $element['slug'], LogLevel::Error);

                        break;
                    case 'User':
                        Craft::log('find user: ' . $element['username'], LogLevel::Error);

                        break;
                    default:

                        break;

                }
            } else {
                $isElementField = false;
                $this->getSourceIds($element);

            }

            //$value[] = $item;
        }
        //}

        if ($isElementField){
            Craft::log('replace values with ids', LogLevel::Error);
            Craft::log(JsonHelper::encode($ids), LogLevel::Error);

            $value = $ids;
        }

        return true;
    }




    /**
     * Fires an 'onExportField' event. Event handlers can prevent the default field handling by setting $event->performAction to false.
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['parent'] - field parent
     *          $event->params['value'] - current field value, change this value in the event handler to output a different value
     *
     * @return null
     */
    public function onExportField(Event $event)
    {
         $this->raiseEvent('onExportField', $event);
    }



}