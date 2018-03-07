<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\models\FieldGroup;
use craft\db\Query;
use yii\base\Event;
use firstborn\migrationmanager\events\FieldEvent;


class Fields extends BaseMigration
{
    // Constants
    // =========================================================================

    /**
     * @event FieldEvent The event that is triggered before a field is exported
     * You may set [[FieldEvent::isValid]] to `false` to prevent the field from being exported.
     */

    const EVENT_EXPORT_FIELD = 'exportField';

    /**
     * @event FieldEvent The event that is triggered before a field is imported
     * You may set [[FieldEvent::isValid]] to `false` to prevent the field from being imported
     */
    const EVENT_IMPORT_FIELD = 'importField';

    /**
     * @event FieldEvent The event that is triggered before a field's content is exported
     * You may set [[FieldEvent::isValid]] to `false` to prevent the content from being exported
     */
    const EVENT_EXPORT_FIELD_CONTENT = 'exportFieldContent';

    /**
     * @event FieldEvent The event that is triggered before a field's content is imported
     * You may set [[FieldEvent::isValid]] to `false` to prevent the content from being imported
     */
    const EVENT_IMPORT_FIELD_CONTENT = 'importFieldContent';


    protected $source = 'field';
    protected $destination = 'fields';

    public function exportItem($id, $fullExport = false){
        $includeID = false;
        $field = Craft::$app->fields->getFieldById($id);
        if (!$field){
            return false;
        }

        $this->addManifest($field->handle);

        $newField = [
            'group' => $field->group->name,
            'name' => $field->name,
            'handle' => $field->handle,
            'instructions' => $field->instructions,
            'translationMethod' => $field->translationMethod,
            'translationKeyFormat' => $field->translationKeyFormat,
            'required' => $field->required,
            'type' => $field->className(),
            'typesettings' => $field->settings
        ];

        if ($field->className() == 'Matrix') {
            $this->getMatrixField($newField, $field->id, $includeID);
        }

        if ($field->className() == 'SuperTable') {
            $this->getSuperTableField($newField, $field->id, $includeID);
        }

        if ($field->className() == 'Neo'){
            $this->getNeoField($newField, $field->id, $includeID);
        }

        $this->getSettingHandles($newField);


        // Fire an 'onExportField' event
        $event = new FieldEvent(array(
            'field' => $field,
            'value' => $newField
        ));
        $this->onExportField($event);

        if ($event->isValid){
            return $event->value;
        } else {
            $this->addError('error', 'Error exporting ' . $field->handle . ' field.');
            $this->addError('error', $event->params['error']);
            return false;
        }

    }


    public function importItem(Array $data)
    {

        $existing = Craft::$app->fields->getFieldByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        } else {
            $data['id'] = 'new';
        }

        // Fire an 'onImportField' event
        $event = new FieldEvent(array(
            'field' => $existing,
            'value' => $data
        ));
        $this->onImportField($event);

        if ($event->isValid) {
            $field = $this->createModel($event->value);

            $result = Craft::$app->fields->saveField($field);
            if ($result) {
             } else {
                $this->addError('error', 'Could not save the ' . $data['handle'] . ' field.');
            }

            return $result;
        } else {
            $this->addError('error', 'Error importing ' . $data['handle'] . ' field.');
            $this->addError('error', $event->params['error']);
            return false;
        }
    }

    public function createModel(Array $data)
    {
        $fieldsService = Craft::$app->getFields();

        $group = $this->getFieldGroupByName($data['group']);
        if (!$group){
            $group = new FieldGroup();
            $group->name = $data['group'];
            $fieldsService->saveGroup($group);
        }

        //go get any extra settings that need to be set based on handles
        $this->getSettingIds($data);

        $field = $fieldsService->createField([
            'type' => str_replace('/', '\\', $data['type']),
            'id' => $data['id'],
            'groupId' => $group->id,
            'name' => $data['name'],
            'handle' => $data['handle'],
            'instructions' => $data['instructions'],
            'translationMethod' => $data['translationMethod'],
            'translationKeyFormat' => $data['translationKeyFormat'],
            'settings' => $data['typesettings']
        ]);

        return $field;
    }

    private function getFieldGroupByName($name)
    {
        $query = (new Query())
            ->select(['id', 'name'])
            ->from(['fieldgroups'])
            ->orderBy(['name' => SORT_DESC])
            ->where(['name' => $name]);

        $result = $query->one();

        if ($result){
            $group = new FieldGroup();
            $group->id = $result['id'];
            $group->name = $result['name'];
            return $group;


        } else {
            return false;
        }
    }

    /**
     * Fires an 'onExportField' event. To prevent execution of the export set $event->performAction to false and set a reason in $event->params['error'] to be logged.
     *
     * @param Event $event
     *          $event->params['field'] - field to export
     *          $event->params['value'] - field data that will be written to migration file, change this value to affect the migration export
     *
     * @return null
     */
    public function onExportField(FieldEvent $event)
    {
        if ($this->hasEventHandlers(self::EVENT_EXPORT_FIELD)) {
            $this->trigger(self::EVENT_EXPORT_FIELD, $event);
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
    public function onExportFieldContent(FieldEvent $event)
    {
        $this->trigger(Fields::EVENT_EXPORT_FIELD_CONTENT, $event);
    }

    /**
     * Fires an 'onImportField' event. To prevent execution of the import set $event->performAction to false and set a reason in $event->params['error'] to be logged. When checking the field type use the $event->params['value'] object as the ['field'] could be empty (ie field doesn't exist yet)
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['value'] - field data that will be imported, change this value to affect the migration import
     *
     * @return null
     */
    public function onImportField(FieldEvent $event)
    {
        $this->trigger(Fields::EVENT_IMPORT_FIELD, $event);
    }

    /**
     * Fires an 'onImportFieldContent' event. Event handlers can prevent the default field handling by setting $event->performAction to false.
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['parent'] - field parent
     *          $event->params['value'] - current field value, change this value in the event handler to output a different value
     *
     * @return null
     */
    public function onImportFieldContent(FieldEvent $event)
    {
        $this->trigger(Fields::EVENT_IMPORT_FIELD_CONTENT, $event);
    }

    private function getMatrixField(&$newField, $fieldId, $includeID = false)
    {
        $blockTypes = Craft::$app->matrix->getBlockTypesByFieldId($fieldId);
        $blockCount = 1;
        foreach ($blockTypes as $blockType)
        {
            if ($includeID)
            {
                $blockId = $blockType->id;
            } else {
                $blockId = 'new'.$blockCount;
            }

            $newField['typesettings']['blockTypes'][$blockId] = [
                'name' => $blockType->name,
                'handle' => $blockType->handle,
                'fields' => [],
            ];
            $fieldCount = 1;
            foreach ($blockType->fields as $blockField)
            {
                if ($includeID)
                {
                    $fieldId = $blockField->id;
                } else {
                    $fieldId = 'new'.$fieldCount;
                }

                $newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId] = [
                    'name' => $blockField->name,
                    'handle' => $blockField->handle,
                    'instructions' => $blockField->instructions,
                    'required' => $blockField->required,
                    'type' => $blockField->className(),
                    'typesettings' => $blockField->settings,
                ];
                /*if ($blockField->type == 'PositionSelect')
                {
                    $options = [];
                    foreach ($blockField->settings['options'] as $value) {
                        $options[$value] = true;
                    }
                    $newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId]['typesettings']['options'] = $options;
                }*/

                if ($blockField->className() == 'SuperTable') {
                    $this->getSuperTableField($newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId], $blockField->id);
                }

                ++$fieldCount;
            }
            ++$blockCount;
        }
    }

    private function getSuperTableField(&$newField, $fieldId, $includeID = false)
    {
        $blockTypes = Craft::$app->superTable->getBlockTypesByFieldId($fieldId);
        $fieldCount = 1;
        foreach ($blockTypes as $blockType) {
            if ($includeID) {
                $blockId = $blockType->id;
            } else {
                $blockId = 'new';
            }
            foreach ($blockType->getFields() as $field) {
                if ($includeID) {
                    $fieldId = $field->id;
                } else {
                    $fieldId = 'new'.$fieldCount;
                }
                $columns = array_values($newField['typesettings']['columns']);
                $newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId] = [
                    'name' => $field->name,
                    'handle' => $field->handle,
                    'instructions' => $field->instructions,
                    'required' => $field->required,
                    'type' => $field->className(),
                    'width' => isset($columns[$fieldCount - 1]['width']) ? $columns[$fieldCount - 1]['width'] : '',
                    'typesettings' => $field->settings,
                ];

                /*if ($field->type == 'PositionSelect') {
                    $options = [];
                    foreach ($field->settings['options'] as $value) {
                        $options[$value] = true;
                    }
                    $newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId]['typesettings']['options'] = $options;
                }*/
                if ($field->className() == 'Matrix') {
                    $this->getMatrixField($newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId], $field->id);
                }

                ++$fieldCount;
            }
        }
        unset($newField['typesettings']['columns']);
    }

    private function getNeoField(&$newField, $fieldId, $includeID = false)
    {
        $groups = Craft::$app->neo->getGroupsByFieldId($fieldId);
        if (count($groups)){
            $newField['typesettings']['groups'] = [
                'name' => [],
                'sortOrder' => []
            ];

            foreach($groups as $group){
                $newField['typesettings']['groups']['name'][] = $group->name;
                $newField['typesettings']['groups']['sortOrder'][] = $group->sortOrder;
            }
        }

        $blockTypes = Craft::$app->neo->getBlockTypesByFieldId($fieldId);
        $blockCount = 1;
        foreach ($blockTypes as $blockType)
        {
            if ($includeID)
            {
                $blockId = $blockType->id;
            } else {
                $blockId = 'new'.$blockCount;
            }

            $newField['typesettings']['blockTypes'][$blockId] = [
                'name' => $blockType->name,
                'handle' => $blockType->handle,
                'maxBlocks' => $blockType->maxBlocks,
                'maxChildBlocks' => $blockType->maxChildBlocks,
                'childBlocks' => $blockType->childBlocks,
                'topLevel' => $blockType->topLevel,
                'sortOrder' => $blockType->sortOrder,
                'fieldLayout' => []
            ];

            $fieldLayout = $blockType->getFieldLayout();
            foreach ($fieldLayout->getTabs() as $tab) {
                $newField['typesettings']['blockTypes'][$blockId]['fieldLayout'][$tab->name] = array();
                foreach ($tab->getFields() as $tabField) {

                    $newField['typesettings']['blockTypes'][$blockId]['fieldLayout'][$tab->name][] = $this->exportItem($tabField->fieldId, true);
                    if ($tabField->required)
                    {
                        $newField['typesettings']['blockTypes'][$blockId]['requiredFields'][] = Craft::$app->fields->getFieldById($tabField->fieldId)->handle;
                    }
                }
            }

            ++$blockCount;
        }
    }

    /**
     * @param $field
     */

    private function getSettingHandles(&$field)
    {

        $this->getSourceHandles($field);
        $this->getTransformHandles($field);

        if ($field['type'] == 'Matrix' && key_exists('blockTypes', $field['typesettings']))
        {
            foreach ($field['typesettings']['blockTypes'] as &$blockType) {
                foreach ($blockType['fields'] as &$childField) {
                    $this->getSettingHandles($childField);
                }
            }
        }

        if ($field['type'] == 'SuperTable' && key_exists('blockTypes', $field['typesettings']))
        {
            foreach ($field['typesettings']['blockTypes'] as &$blockType) {
                foreach ($blockType['fields'] as &$childField) {
                    $this->getSettingHandles($childField);
                }
            }
        }

    }

    private function getSourceHandles(&$field)
    {
        if ($field['type'] == 'Assets') {
            if (array_key_exists('sources', $field['typesettings']) && is_array($field['typesettings']['sources'])) {
                foreach ($field['typesettings']['sources'] as $key => $value) {
                    if (substr($value, 0, 7) == 'folder:') {
                        $source = MigrationManagerHelper::getAssetSourceByFolderId(intval(substr($value, 7)));
                        if ($source) {
                            $field['typesettings']['sources'][$key] = $source->handle;
                        } else {

                        }
                    }
                }
            } else {
                $field['typesettings']['sources'] = array();
            }

            if (array_key_exists('defaultUploadLocationSource', $field['typesettings'])) {
                $source = Craft::$app->assetSources->getSourceById(intval($field['typesettings']['defaultUploadLocationSource']));
                if ($source) {
                    $field['typesettings']['defaultUploadLocationSource'] = $source->handle;
                }
            }

            if (array_key_exists('singleUploadLocationSource', $field['typesettings'])) {
                $source = Craft::$app->assetSources->getSourceById(intval($field['typesettings']['singleUploadLocationSource']));
                if ($source) {
                    $field['typesettings']['singleUploadLocationSource'] = $source->handle;
                }
            }

        }

        if ($field['type'] == 'RichText') {
            if (array_key_exists('availableAssetSources', $field['typesettings']) && is_array($field['typesettings']['availableAssetSources'])) {
                if ($field['typesettings']['availableAssetSources'] !== '*' && $field['typesettings']['availableAssetSources'] != '') {
                    foreach ($field['typesettings']['availableAssetSources'] as $key => $value) {
                        $source = Craft::$app->assetSources->getSourceById($value);
                        if ($source) {
                            $field['typesettings']['availableAssetSources'][$key] = $source->handle;
                        }
                    }
                }
            } else {
                $field['typesettings']['availableAssetSources'] = array();
            }

            if (array_key_exists('defaultUploadLocationSource', $field['typesettings'])) {
                $source = Craft::$app->assetSources->getSourceById(intval($field['typesettings']['defaultUploadLocationSource']));
                if ($source) {
                    $field['typesettings']['defaultUploadLocationSource'] = $source->handle;
                }

            }

            if (array_key_exists('singleUploadLocationSource', $field['typesettings'])) {
                $source = Craft::$app->assetSources->getSourceById(intval($field['typesettings']['singleUploadLocationSource']));
                if ($source) {
                    $field['typesettings']['singleUploadLocationSource'] = $source->handle;
                }
            }
        }

        if ($field['type'] == 'Categories') {
            if (array_key_exists('source', $field['typesettings']) && is_string($field['typesettings']['source'])) {
                $value = $field['typesettings']['source'];
                if (substr($value, 0, 6) == 'group:') {
                    $categories = Craft::$app->categories->getAllGroupIds();
                    $categoryId = intval(substr($value, 6));
                    if (in_array($categoryId, $categories))
                    {
                        $category = Craft::$app->categories->getGroupById($categoryId);
                        if ($category) {
                            $field['typesettings']['source'] = $category->handle;
                        } else {
                            $field['typesettings']['source'] = [];
                        }
                    } else {
                        $this->addError('error', 'Can not export field: ' . $field['handle'] . ' category id: ' . $categoryId . ' does not exist in system');
                    }
                }
            }
        }

        if ($field['type'] == 'Entries') {
            if (array_key_exists('sources', $field['typesettings']) && is_array($field['typesettings']['sources'])) {
                foreach ($field['typesettings']['sources'] as $key => $value) {
                    if (substr($value, 0, 8) == 'section:') {
                        $section = Craft::$app->sections->getSectionById(intval(substr($value, 8)));
                        if ($section) {
                            $field['typesettings']['sources'][$key] = $section->handle;
                        }
                    }
                }
            } else {
                $field['typesettings']['sources'] = [];
            }
        }

        if ($field['type'] == 'Tags') {

            if (array_key_exists('source', $field['typesettings']) && is_string($field['typesettings']['source'])) {
                $value = $field['typesettings']['source'];
                //foreach ($field['typesettings']['source'] as $key => $value) {
                if (substr($value, 0, 9) == 'taggroup:') {
                    $tag = Craft::$app->tags->getTagGroupById(intval(substr($value, 9)));
                    if ($tag) {
                        $field['typesettings']['source'] = $tag->handle;
                    }
                }
                //}
            }
        }

        if ($field['type'] == 'Users') {
            if (array_key_exists('sources', $field['typesettings']) && is_array($field['typesettings']['sources'])) {
                foreach ($field['typesettings']['sources'] as $key => $value) {
                    if (substr($value, 0, 6) == 'group:') {
                        $userGroup = Craft::$app->userGroups->getGroupById(intval(substr($value, 6)));
                        if ($userGroup) {
                            $field['typesettings']['sources'][$key] = $userGroup->handle;
                        }
                    }
                }
            } else {
                $field['typesettings']['sources'] = [];
            }
        }
    }

    private function getTransformHandles(&$field)
    {
        if ($field['type'] == 'RichText') {
            if (array_key_exists('availableTransforms', $field['typesettings']) && is_array($field['typesettings']['availableTransforms'])) {
                foreach ($field['typesettings']['availableTransforms'] as $key => $value) {
                    $transform = MigrationManagerHelper::getTransformById($value);

                    if ($transform) {
                        $field['typesettings']['availableTransforms'][$key] = $transform->handle;
                    }
                }
            }
        }
    }

    private function getSettingIds(&$field)
    {
        $this->getSourceIds($field);
        $this->getTransformIds($field);
        //get ids for children items
        if ($field['type'] == 'Matrix' && key_exists('blockTypes', $field['typesettings']))
        {
            foreach ($field['typesettings']['blockTypes'] as &$blockType) {
                foreach ($blockType['fields'] as &$childField) {
                    $this->getSettingIds($childField);
                }
            }
        }

        if ($field['type'] == 'SuperTable' && key_exists('blockTypes', $field['typesettings']))
        {
            foreach ($field['typesettings']['blockTypes'] as &$blockType) {
                foreach ($blockType['fields'] as &$childField) {
                    $this->getSettingIds($childField);
                }
            }
        }

        if ($field['type'] == 'Neo' && key_exists('blockTypes', $field['typesettings']))
        {
            foreach ($field['typesettings']['blockTypes'] as &$blockType) {
                //import each neo field
                foreach($blockType['fieldLayout'] as &$fieldLayout) {
                    $fieldIds = [];
                    foreach($fieldLayout as $fieldLayoutField){
                        if($this->importItem($fieldLayoutField)){
                            $fieldIds[] = Craft::$app->fields->getFieldByHandle($fieldLayoutField['handle'])->id;
                        }
                    }
                    $fieldLayout = $fieldIds;
                }
            }
        }
    }

    private function getTransformIds(&$field)
    {
        if ($field['type'] == 'RichText') {
            if (array_key_exists('availableTransforms', $field['typesettings']) && is_array($field['typesettings']['availableTransforms'])) {
                $newTransforms = array();
                foreach ($field['typesettings']['availableTransforms'] as $value) {
                    $transform = Craft::$app->assetTransforms->getTransformByHandle($value);
                    if ($transform) {
                        $newTransforms[] = $transform->id;
                    }
                }
                $field['typesettings']['availableTransforms'] = $newTransforms;
            }
        }
    }

    private function getSourceIds(&$field)
    {
        if ($field['type'] == 'Assets') {
            $newSources = array();

            foreach ($field['typesettings']['sources'] as $source) {
                $newSource = MigrationManagerHelper::getAssetSourceByHandle($source);
                if ($newSource) {
                    $newSources[] = 'folder:' . $newSource->id;
                } else {
                    $this->addError('error', 'Asset source: ' . $source . ' is not defined in system');
                }
            }

            $field['typesettings']['sources'] = join($newSources);

            if (array_key_exists('defaultUploadLocationSource', $field['typesettings'])) {
                $source = MigrationManagerHelper::getAssetSourceByHandle($field['typesettings']['defaultUploadLocationSource']);
                if ($source) {
                    $field['typesettings']['defaultUploadLocationSource'] = $source->id;
                } else {
                    $field['typesettings']['defaultUploadLocationSource'] = '';
                }
            }
            if (array_key_exists('singleUploadLocationSource', $field['typesettings'])) {
                $source = MigrationManagerHelper::getAssetSourceByHandle($field['typesettings']['singleUploadLocationSource']);
                if ($source) {
                    $field['typesettings']['singleUploadLocationSource'] = $source->id;
                } else {
                    $field['typesettings']['singleUploadLocationSource'] = '';
                }
            }
        }

        if ($field['type'] == 'RichText') {
            $newSources = array();
            foreach ($field['typesettings']['availableAssetSources'] as $source) {
                $newSource = MigrationManagerHelper::getAssetSourceByHandle($source);
                if ($newSource) {
                    $newSources[] = 'folder:' . $newSource->id;
                } else {
                    $this->addError('error', 'Asset source: ' . $source . ' is not defined in system');
                }
            }

            $field['typesettings']['availableAssetSources'] = $newSources;

            if (array_key_exists('defaultUploadLocationSource', $field['typesettings'])) {
                $source = MigrationManagerHelper::getAssetSourceByHandle($field['typesettings']['defaultUploadLocationSource']);
                if ($source) {
                    $field['typesettings']['defaultUploadLocationSource'] = $source->id;
                } else {
                    $field['typesettings']['defaultUploadLocationSource'] = '';
                }
            }
            if (array_key_exists('singleUploadLocationSource', $field['typesettings'])) {
                $source = MigrationManagerHelper::getAssetSourceByHandle($field['typesettings']['singleUploadLocationSource']);
                if ($source) {
                    $field['typesettings']['singleUploadLocationSource'] = $source->id;
                } else {
                    $field['typesettings']['singleUploadLocationSource'] = '';
                }
            }
        }

        if ($field['type'] == 'Categories') {
            $newSource = Craft::$app->categories->getGroupByHandle($field['typesettings']['source']);
            if ($newSource) {
                $newSource = 'group:' . $newSource->id;
            } else {
                $this->addError('error', 'Category: ' . $field['typesettings']['source'] . ' is not defined in system');
            }
            $field['typesettings']['source'] = $newSource;
        }


        if ($field['type'] == 'Entries') {
            $newSources = array();
            foreach ($field['typesettings']['sources'] as $source) {
                $newSource = Craft::$app->sections->getSectionByHandle($source);
                if ($newSource)
                {
                    $newSources[] = 'section:' . $newSource->id;
                }
                elseif ($source == 'singles')
                {
                    $newSources[] = $source;
                } else {
                    $this->addError('error', 'Section : ' . $source . ' is not defined in system');
                }
            }

            $field['typesettings']['sources'] = join($newSources);
        }

        if ($field['type'] == 'Tags') {
            $newSource = Craft::$app->tags->getTagGroupByHandle($field['typesettings']['source']);
            if ($newSource) {
                $newSource = 'taggroup:' . $newSource->id;
            } else {
                $this->addError('error', 'Tag: ' . $field['typesettings']['source'] . ' is not defined in system');
            }
            $field['typesettings']['source'] = $newSource;
        }

        if ($field['type'] == 'Users') {
            $newSources = array();
            foreach ($field['typesettings']['sources'] as $source) {
                $newSource = Craft::$app->userGroups->getGroupByHandle($source);
                if ($newSource)
                {
                    $newSources[] = 'group:' . $newSource->id;
                }
                elseif ($source == 'admins')
                {
                    $newSources[] = $source;
                } else {
                    $this->addError('error', 'User Group: ' . $source . ' is not defined in system');
                }
            }

            $field['typesettings']['sources'] = join($newSources);
        }
    }


    private function mergeUpdates(&$newField, $field)
    {
        $newField['id'] = $field->id;

        if ($newField['type'] == $field->className())
        {
            if ($field->className() == 'Matrix')
            {
                $this->mergeMatrix($newField, $field);
            }

            if ($field->className() == 'SuperTable')
            {
                $this->mergeSuperTable($newField, $field);
            }
        }
    }

    private function mergeSuperTable(&$newField, $field)
    {
        $newBlockTypes = [];
        $blockTypes = $newField['typesettings']['blockTypes'];
        $existingBlockTypes = Craft::$app->superTable->getBlockTypesByFieldId($field->id);

        //there's only one blocktype in SuperTables to deal with
        $blockType = reset($blockTypes);
        if ($existingBlockTypes)
        {
            $existingBlockType = reset($existingBlockTypes);
            $this->mergeSuperTableBlockType($blockType, $existingBlockType);
            $newBlockTypes[$existingBlockType->id] = $blockType;
        } else {
            $newBlockTypes['new1'] = $blockType;
        }

        $settings = $newField['typesettings'];
        $settings['blockTypes'] = $newBlockTypes;
        $newField['typesettings'] = $settings;
    }

    private function mergeSuperTableBlockType(&$newBlockType, $existingBlockType)
    {
        $newFields = [];
        $existingFields = Craft::$app->fields->getAllFields(null, 'superTableBlockType:' . $existingBlockType->id);

        foreach($newBlockType['fields'] as $key => &$tableField)
        {
            $existingField = $this->getSuperTableFieldByHandle($tableField['handle'], $existingFields);

            if ($existingField)
            {
                if ($tableField['type'] == 'Matrix'){
                    $this->mergeMatrix($tableField, $existingField);
                }

                $newFields[$existingField->id] = $tableField;
            } else {
                $newFields[$key] = $tableField;
            }
        }
        $newBlockType['fields'] = $newFields;
    }

    private function getSuperTableFieldByHandle($handle, $fields)
    {
        foreach($fields as $field)
        {
            if ($field->handle == $handle){
                return $field;
            }
        }
        return false;
    }

    private function mergeMatrix(&$newField, $field)
    {
        if (array_key_exists('blockTypes', $newField['typesettings'])) {

            $blockTypes = $newField['typesettings']['blockTypes'];
            $newBlocks = [];

            foreach ($blockTypes as $key => &$block) {

                $existingBlock = $this->getMatrixBlockByHandle($block['handle'], $field->id);

                if ($existingBlock) {
                    $this->mergeMatrixBlock($block, $existingBlock);
                    $newBlocks[$existingBlock->id] = $block;
                } else {
                    $newBlocks[$key] = $block;
                }
            }

            $settings = $newField['typesettings'];
            $settings['blockTypes'] = $newBlocks;
            $newField['typesettings'] = $settings;

        }
    }

    private function mergeMatrixBlock(&$newBlock, $block)
    {
        $newBlock['fieldLayoutId'] = $block->fieldLayoutId;
        $newBlock['sortOrder'] = $block->sortOrder;

        $fields = $newBlock['fields'];
        $newFields = [];
        $existingFields = Craft::$app->fields->getAllFields(null, 'matrixBlockType:' . $block->id);

        foreach($fields as $key => &$field){
            $existingField = $this->getMatrixFieldByHandle($field['handle'], $existingFields);

            if ($existingField){
                $this->mergeUpdates($field, $existingField);
                $newFields[$existingField->id] = $field;
            } else {

                $newFields[$key] = $field;
            }
        }

        $newBlock['fields'] = $newFields;
    }

    private function getMatrixFieldByHandle($handle, $fields)
    {
        foreach($fields as $field)
        {
            if ($field->handle == $handle){
                return $field;
            }
        }
        return false;
    }

    private function getMatrixBlockByHandle($handle, $id)
    {
        $blocks = Craft::$app->matrix->getBlockTypesByFieldId($id);
        foreach($blocks as $block)
        {
            if ($block->handle == $handle){
                return $block;
            }
        }

        return false;
    }

}
