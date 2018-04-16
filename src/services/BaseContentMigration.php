<?php

namespace firstborn\migrationmanager\services;

use firstborn\migrationmanager\helpers\MigrationManagerHelper;
use firstborn\migrationmanager\events\ImportEvent;
use Craft;
use craft\fields\BaseOptionsField;
use craft\fields\BaseRelationField;
use craft\base\Element;

abstract class BaseContentMigration extends BaseMigration
{

    /**
     * @param $content
     * @param $element
     */
    protected function getContent(&$content, $element){
        foreach ($element->getFieldLayout()->getFields() as $fieldModel) {
            $this->getFieldContent($content['fields'], $fieldModel, $element);
        }
    }

    /**
     * @param $content
     * @param $fieldModel
     * @param $parent
     */

    protected function getFieldContent(&$content, $fieldModel, $parent)
    {
        $field = $fieldModel;
        $value = $parent->getFieldValue($field->handle);

        Craft::error('FIELD: ' . $field->handle . ' ' .$field->className());

        switch ($field->className()) {
             case 'craft\redactor\Field':
                if ($value){
                    $value = $value->getRawContent();
                } else {
                    $value = '';
                }

                break;
            case 'craft\fields\Matrix':
                $model = $parent[$field->handle];
                $model->limit = null;
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
            case 'Neo':
                $model = $parent[$field->handle];
                $value = $this->getIteratorValues($model, function ($item) {
                    $itemType = $item->getType();
                    $value = [
                        'type' => $itemType->handle,
                        'enabled' => $item->enabled,
                        'modified' => $item->enabled,
                        'collapsed' => $item->collapsed,
                        'level' => $item->level,
                        'fields' => []
                    ];

                    return $value;
                });
                break;
            case 'verbb\supertable\fields\SuperTableField':
                $model = $parent[$field->handle];

                /*if ($field->settings['staticField'] == 1){
                    $value = [
                        'new1' => [
                            'type' => $model->typeId,
                            'fields' => []
                        ]
                    ];
                    //Craft::error();
                    //TODO NEED TO FIGURE THIS OUT
                    //$this->getContent($value['new1']['fields'], $model);
                } else {

                    $value = $this->getIteratorValues($model, function ($item) {
                        $value = [
                            'type' => $item->typeId,
                            'fields' => []
                        ];
                        return $value;
                    });
                }*/
                $value = $this->getIteratorValues($model, function ($item) {
                    $value = [
                        'type' => $item->typeId,
                        'fields' => []
                    ];
                    return $value;
                });

                break;
            case 'craft\fields\Dropdown':
                $value = $value->value;
                break;
            default:
                if ($field instanceof BaseRelationField) {
                    $this->getSourceHandles($value);
                } elseif ($field instanceof BaseOptionsField){
                    $this->getSelectedOptions($value);
                }
                break;
        }
        $content[$field->handle] = $value;
    }


    /**
     * @param $values
     */
    protected function validateImportValues(&$values)
    {
        foreach ($values as $key => &$value) {
            $this->validateFieldValue($values, $key, $value);
        }
    }

    /**
     * @param $parent
     * @param $fieldHandle
     * @param $fieldValue
     */

    protected function validateFieldValue($parent, $fieldHandle, &$fieldValue)
    {
        $field = Craft::$app->fields->getFieldByHandle($fieldHandle);
        if ($field) {
            $event = new ImportEvent(array(
                'field' => $field,
                'parent' => $parent,
                'value' => &$fieldValue
            ));

            $this->onImportFieldContent($event);

            if ($event->isValid == false) {
                $fieldValue = $event->value;

            } else {
                switch ($field->className()) {
                    case 'craft\fields\Matrix':
                        foreach($fieldValue as $key => &$matrixBlock){
                            $blockType = MigrationManagerHelper::getMatrixBlockType($matrixBlock['type'], $field->id);
                            if ($blockType) {
                                $blockFields = Craft::$app->fields->getAllFields(null, 'matrixBlockType:' . $blockType->id);
                                foreach($blockFields as &$blockField){
                                    if ($blockField->className() == 'verbb\supertable\fields\SuperTableField') {
                                        $matrixBlockFieldValue = &$matrixBlock['fields'][$blockField->handle];
                                        $this->updateSupertableFieldValue($matrixBlockFieldValue, $blockField);
                                    }
                                }
                            }
                        }
                        break;
                    case 'Neo':
                        foreach($fieldValue as $key => &$neoBlock){
                            $blockType = MigrationManagerHelper::getNeoBlockType($neoBlock['type'], $field->id);
                            if ($blockType) {
                                $blockTabs = $blockType->getFieldLayout()->getTabs();
                                foreach($blockTabs as $blockTab){
                                    $blockFields = $blockTab->getFields();
                                    foreach($blockFields as &$blockTabField){
                                        $neoBlockField = Craft::$app->fields->getFieldById($blockTabField->fieldId);
                                        if ($neoBlockField->className() == 'verbb\supertable\fields\SuperTableField') {
                                            $neoBlockFieldValue = &$neoBlock['fields'][$neoBlockField->handle];
                                            $this->updateSupertableFieldValue($neoBlockFieldValue, $neoBlockField);
                                        }
                                    }
                                }
                            }
                        }

                        break;
                    case 'verbb\supertable\fields\SuperTableField':
                        $this->updateSupertableFieldValue($fieldValue, $field);
                        break;
                }
            }
        }
    }

    /**
     * @param $fieldValue
     * @param $field
     */
    protected function updateSupertableFieldValue(&$fieldValue, $field){
        $blockType = Craft::$app->superTable->getBlockTypesByFieldId($field->id)[0];
        foreach ($fieldValue as $key => &$value) {
            $value['type'] = $blockType->id;
        }
    }

    /**
     * @param $element
     * @param $settingsFunc
     * @return array
     */
    protected function getIteratorValues($element, $settingsFunc)
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

    /**
     * @param $handle
     * @param $sectionId
     * @return bool
     */
    protected function getEntryType($handle, $sectionId)
    {
        $entryTypes = Craft::$app->sections->getEntryTypesBySectionId($sectionId);
        foreach($entryTypes as $entryType)
        {
            if ($entryType->handle == $handle){
                return $entryType;
            }

        }

        return false;
    }

    /**
     * @param $value
     * @return array
     */
    protected function getSourceHandles(&$value)
    {
        $elements = $value->all();
        $value = [];
        if ($elements) {
            foreach ($elements as $element) {
                switch ($element->className()) {
                    case 'craft\elements\Asset':
                        $item = [
                            'elementType' => $element->className(),
                            'filename' => $element->filename,
                            'folder' => $element->getFolder()->name,
                            'source' => $element->getVolume()->handle
                        ];
                        break;
                    case 'craft\elements\Category':
                        $item = [
                            'elementType' => $element->className(),
                            'slug' => $element->slug,
                            'category' => $element->getGroup()->handle
                        ];
                        break;
                    case 'craft\elements\Entry':
                        $item = [
                            'elementType' => $element->className(),
                            'slug' => $element->slug,
                            'section' => $element->getSection()->handle
                        ];
                        break;
                    case 'craft\elements\Tag':
                        $tagValue = [];
                        $this->getContent($tagValue, $element);
                        $item = [
                            'elementType' => $element->className(),
                            'slug' => $element->slug,
                            'group' => $element->getGroup()->handle,
                            'value' => $tagValue
                        ];
                        break;
                    case 'craft\elements\User':
                        $item = [
                            'elementType' => $element->className(),
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

    /**
     * @param $value
     */
    protected function getSourceIds(&$value)
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

    /**
     * @param $value
     * @return array
     */
    protected function getSelectedOptions(&$value){
        $options = $value->getOptions();
        $value = [];
        foreach($options as $option){
            if ($option->selected)
            {
                $value[] = $option->value;
            }
        }
        return $value;

    }

    /**
     * @param $value
     * @return bool
     */
    protected function populateIds(&$value)
    {
        $isElementField = true;
        $ids = [];
        foreach ($value as &$element) {
            if (is_array($element) && key_exists('elementType', $element)) {
                $elementType = str_replace('/', '\\', $element['elementType']);
                $func = null;
                switch ($elementType) {
                    case 'craft\elements\Asset':
                         $func = 'firstborn\migrationmanager\helpers\MigrationManagerHelper::getAssetByHandle';
                        break;
                    case 'craft\elements\Category':
                        $func = 'firstborn\migrationmanager\helpers\MigrationManagerHelper::getCategoryByHandle';
                        break;
                    case 'craft\elements\Entry':
                        $func = 'firstborn\migrationmanager\helpers\MigrationManagerHelper::getEntryByHandle';
                        break;
                    case 'craft\elements\Tag':
                        $func = 'firstborn\migrationmanager\helpers\MigrationManagerHelper::getTagByHandle';
                        break;
                    case 'craft\elements\User':
                        $func = 'firstborn\migrationmanager\helpers\MigrationManagerHelper::getUserByHandle';
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
     * Look for matrix/supertables/neo that are not localized and update the keys to
     * ensure the site/locale values on child elements remain intact
     * @param BaseElementModel $element
     * @param array $data function foo($method)
    **/
    protected function localizeData(Element $element, Array &$data)
    {
        $fieldLayout = $element->getFieldLayout();
        foreach ($fieldLayout->getTabs() as $tab) {
            foreach ($tab->getFields() as $tabField) {
                $field = Craft::$app->fields->getFieldById($tabField->id);
                $fieldValue = $element[$field->handle];
                if ( in_array ($field->className() , ['craft\fields\Matrix', 'verbb\supertable\fields\SuperTableField', 'Neo']) ) {
                    if ($field->localizeBlocks == false) {
                        $items = $fieldValue->getIterator();
                        $i = 1;
                        foreach ($items as $item) {
                            $data['fields'][$field->handle][$item->id] = $data['fields'][$field->handle]['new' . $i];
                            unset($data['fields'][$field->handle]['new' . $i]);
                            $i++;
                        }
                    }
                }
            }
        }
    }

}