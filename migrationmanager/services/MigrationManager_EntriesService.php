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
            $entries[] = $entry;
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

            $this->getContent($entryContent, $entry);


            $content['locales'][$locale->locale] = $entryContent;
        }

        return $content;
    }

    public function importItem(Array $data)
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = $data['section'];
        $criteria->slug = $data['slug'];
        $primaryEntry = $criteria->first();
        //$entry = false;

        foreach($data['locales'] as $value) {

            if ($primaryEntry) {

                //$entry = craft()->entries->getEntryById($primaryEntry->id, $key);
                //if (!$entry) {
                $value['id'] = $primaryEntry->id;
                //}
            }

            $entry = $this->createModel($value);

            $this->getSourceIds($value);
            $entry->setContentFromPost($value);

            // save entry
            if (!$success = craft()->entries->saveEntry($entry)) {
                throw new Exception(print_r($entry->getErrors(), true));
            }

            if (!$primaryEntry) {
                $primaryEntry = $entry;
            }
        }

        return true;

    }



    public function createModel(Array $data)
    {

        $entry = new EntryModel();

        if (array_key_exists('id', $data)){
            $entry->id = $data['id'];
        }

        $section = craft()->sections->getSectionByHandle($data['section']);
        $entry->sectionId = $section->id;



        $entryType = $this->getEntryType($data['entryType'], $entry->sectionId);
        if ($entryType) {
            $entry->typeId = $entryType->id;
        }

        $entry->locale = $data['locale'];
        $entry->slug = $data['slug'];
        $entry->postDate = $data['postDate'];
        $entry->expiryDate = $data['expiryDate'];
        $entry->enabled = $data['enabled'];
        $entry->localeEnabled = $data['localeEnabled'];
        $entry->getContent()->title = $data['title'];

        return $entry;
    }

    private function getContent(&$content, $element){
        foreach ($element->getFieldLayout()->getFields() as $fieldModel) {
            $this->getFieldContent($content, $fieldModel, $element);
        }
    }

    private function getFieldContent(&$content, $fieldModel, $parent)
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
                $this->getFieldContent($fields, $field, $item);
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
                    case 'Tag':
                        Craft::log('get tag', LogLevel::Error);
                        $tagValue = [];
                        $this->getContent($tagValue, $element);
                        $item = [
                            'elementType' => 'Tag',
                            'slug' => $element->slug,
                            'group' => $element->getGroup()->handle,
                            'value' => $tagValue
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

    private function getSourceIds(&$value)
    {
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
        $isElementField = true;
        $ids = [];
        foreach ($value as &$element) {
            if (is_array($element) && key_exists('elementType', $element)) {
                $func = null;
                switch ($element['elementType']) {
                    case 'Asset':
                        $func = '\Craft\MigrationManagerHelper::getAssetByHandle';
                        break;
                    case 'Category':
                        $func = '\Craft\MigrationManagerHelper::getCategoryByHandle';
                        break;
                    case 'Entry':
                        $func = '\Craft\MigrationManagerHelper::getEntryByHandle';
                        break;
                    case 'Tag':
                        $func = '\Craft\MigrationManagerHelper::getTagByHandle';
                        break;
                    case 'User':
                        $func = '\Craft\MigrationManagerHelper::getUserByHandle';
                        break;
                    default:
                        break;
                }

                if ($func){
                    $item = $func( $element );
                    if ($item)
                    {
                        $ids[] = $item->id;
                    }
                }
            } else {
                $isElementField = false;
                $this->getSourceIds($element);
            }
        }

        if ($isElementField){
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