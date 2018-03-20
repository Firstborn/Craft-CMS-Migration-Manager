<?php

namespace Craft;

abstract class MigrationManager_BaseContentMigrationService extends MigrationManager_BaseMigrationService
{

    protected function getContent(&$content, $element){
        foreach ($element->getFieldLayout()->getFields() as $fieldModel) {
            $this->getFieldContent($content, $fieldModel, $element);
        }
    }

    protected function getFieldContent(&$content, $fieldModel, $parent)
    {
        $field = $fieldModel->getField();
        $value = $parent->getFieldValue($field->handle);

        // Fire an 'onExportField' event
        $event = new Event($this, array(
            'field' => $field,
            'parent' => $parent,
            'value' => $value
        ));

        $this->onExportFieldContent($event);

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
                case 'SuperTable':
                    $model = $parent[$field->handle];

                    if ($field->settings['staticField'] == 1){
                        $value = [
                            'new1' => [
                                'type' => $model->typeId,
                                'fields' => []
                            ]
                        ];
                        $this->getContent($value['new1']['fields'], $model);
                    } else {

                        $value = $this->getIteratorValues($model, function ($item) {
                            $value = [
                                'type' => $item->typeId,
                                'fields' => []
                            ];
                            return $value;
                        });
                    }
                    break;
                case 'Dropdown':
                    $value = $value->value;
                    break;
                default:
                    if ($field->getFieldType() instanceof BaseElementFieldType) {
                        $this->getSourceHandles($value);
                    } elseif ($field->getFieldType() instanceof BaseOptionsFieldType){
                        $this->getSelectedOptions($value);
                    }
                    break;
            }
        }

        $content[$field->handle] = $value;
    }

    protected function validateImportValues(&$values)
    {
        foreach ($values as $key => &$value) {
            $this->validateFieldValue($values, $key, $value);
        }
    }

    protected function validateFieldValue($parent, $fieldHandle, &$fieldValue)
    {
        $field = craft()->fields->getFieldByHandle($fieldHandle);

        if ($field) {
            // Fire an 'onImportFieldContent' event
            $event = new Event($this, array(
                'field' => $field,
                'parent' => $parent,
                'value' => &$fieldValue
            ));

            $this->onImportFieldContent($event);

            if ($event->performAction == false) {
                $fieldValue = $event->params['value'];

            } else {
                switch ($field->type) {
                    case 'Matrix':
                        foreach($fieldValue as $key => &$matrixBlock){
                            $blockType = MigrationManagerHelper::getMatrixBlockType($matrixBlock['type'], $field->id);
                            if ($blockType) {
                                $blockFields = craft()->fields->getAllFields(null, 'matrixBlockType:' . $blockType->id);
                                foreach($blockFields as &$blockField){
                                    if ($blockField->type == 'SuperTable') {
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
                                        $neoBlockField = craft()->fields->getFieldById($blockTabField->fieldId);
                                        if ($neoBlockField->type == 'SuperTable') {
                                            $neoBlockFieldValue = &$neoBlock['fields'][$neoBlockField->handle];
                                            $this->updateSupertableFieldValue($neoBlockFieldValue, $neoBlockField);
                                        }
                                    }
                                }
                            }
                        }

                        break;
                    case 'SuperTable':
                        $this->updateSupertableFieldValue($fieldValue, $field);
                        break;
                }
            }
        }

    }

    protected function updateSupertableFieldValue(&$fieldValue, $field){
        $blockType = craft()->superTable->getBlockTypesByFieldId($field->id)[0];
        foreach ($fieldValue as $key => &$value) {
            $value['type'] = $blockType->id;
        }
    }

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

    protected function getEntryType($handle, $sectionId)
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

    protected function getSourceHandles(&$value)
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

    protected function populateIds(&$value)
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

    protected function localizeData(BaseElementModel $element, Array &$data)
    {
        //look for matrix/supertables/neo that are not localized and update the keys to ensure the locale values on child elements remain intact
        $fieldLayout = $element->getFieldLayout();

        foreach ($fieldLayout->getTabs() as $tab) {
            foreach ($tab->getFields() as $tabField) {
                $field = craft()->fields->getFieldById($tabField->fieldId);
                $fieldValue = $element[$field->handle];
                if ($field->translatable == false) {
                    if ( in_array ($field->type , ['Matrix', 'SuperTable', 'Neo']) ) {
                        if ($field->type == 'SuperTable' && $field->settings['staticField'] == 1){
                            $data[$field->handle][$fieldValue->id] = $data[$field->handle]['new1'];
                        } else {
                            $items = $fieldValue->getIterator();
                            $i = 1;
                            foreach ($items as $item) {
                                $data[$field->handle][$item->id] = $data[$field->handle]['new' . $i];
                                unset($data[$field->handle]['new' . $i]);
                                $i++;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Fires an 'onExportFieldContent' event. Event handlers can prevent the default field handling by setting $event->performAction to false.
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['parent'] - field parent
     *          $event->params['value'] - current field value, change this value in the event handler to output a different value
     *
     * @return null
     */
    public function onExportFieldContent(Event $event)
    {
        //route this through fields service for simplified event listening
        craft()->migrationManager_fields->onExportFieldContent($event);
    }

    /**
     * Fires an 'onImportFieldContent' event. Event handlers can prevent the default field handling by setting $event->performAction to false.
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['parent'] - field parent
     *          $event->params['value'] - current field value, change this value in the event handler to import a different value
     *
     * @return null
     */
    public function onImportFieldContent(Event $event)
    {
        //route this through fields service for simplified event listening
        craft()->migrationManager_fields->onImportFieldContent($event);
    }




}