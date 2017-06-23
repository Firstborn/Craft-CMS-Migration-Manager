<?php

namespace Craft;
class MigrationManager_FieldsService extends MigrationManager_BaseMigrationService
{

    public function exportItem($id){
        $includeID = false;
        $field = craft()->fields->getFieldById($id);
        if (!$field){
            return false;
        }

        $newField = [
            'group' => $field->group->name,
            'name' => $field->name,
            'handle' => $field->handle,
            'instructions' => $field->instructions,
            'translatable' => $field->translatable,
            'required' => $field->required,
            'type' => $field->type,
            'typesettings' => $field->settings
        ];

        if ($field->type == 'PositionSelect')
        {
            $options = [];
            foreach ($newField['typesettings']['options'] as $value) {
                $options[$value] = true;
            }
            $newField['typesettings']['options'] = $options;
        }

        if ($field->type == 'Matrix')
        {
            $this->getMatrixField($newField, $field->id, $includeID);
        }

        if ($field->type == 'SuperTable')
        {
            $this->getSuperTableField($newField, $field->id, $includeID);
        }

        $this->getSettingHandles($newField);

        return $newField;
    }


    public function importItem(Array $data)
    {

        $existing = craft()->fields->getFieldByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        } else {
            $data['id'] = 'new';
        }

        $field = $this->createModel($data);

        $result = craft()->fields->saveField($field);
        if ($result) {

        } else {
            $this->addError('Could not save the ' . $data['handle'] . ' field.');
        }

        return $result;
    }

    private function getMatrixField(&$newField, $fieldId, $includeID = false)
    {
        $blockTypes = craft()->matrix->getBlockTypesByFieldId($fieldId);
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
                    'type' => $blockField->type,
                    'typesettings' => $blockField->settings,
                ];
                if ($blockField->type == 'PositionSelect')
                {
                    $options = [];
                    foreach ($blockField->settings['options'] as $value) {
                        $options[$value] = true;
                    }
                    $newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId]['typesettings']['options'] = $options;
                }

                if ($blockField->type == 'SuperTable') {
                    $this->getSuperTableField($newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId], $blockField->id);
                }

                ++$fieldCount;
            }
            ++$blockCount;
        }
    }

    private function getSuperTableField(&$newField, $fieldId, $includeID = false)
    {
        $blockTypes = craft()->superTable->getBlockTypesByFieldId($fieldId);
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
                    'type' => $field->type,
                    'width' => isset($columns[$field - 1]['width']) ? $columns[$field - 1]['width'] : '',
                    'typesettings' => $field->settings,
                ];

                if ($field->type == 'PositionSelect') {
                    $options = [];
                    foreach ($field->settings['options'] as $value) {
                        $options[$value] = true;
                    }
                    $newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId]['typesettings']['options'] = $options;
                }
                if ($field->type == 'Matrix') {
                    $this->getMatrixField($newField['typesettings']['blockTypes'][$blockId]['fields'][$fieldId], $field->id);
                }

                ++$fieldCount;
            }
        }
        unset($newField['typesettings']['columns']);
    }

    /**
     * @param $field
     */

    private function getSettingHandles(&$field)
    {
        $this->getSourceHandles($field);
        $this->getTransformHandles($field);

        if ($field['type'] == 'Matrix')
        {
            foreach ($field['typesettings']['blockTypes'] as &$blockType) {
                foreach ($blockType['fields'] as &$childField) {
                    $this->getSettingHandles($childField);
                }
            }
        }

        if ($field['type'] == 'SuperTable')
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
                        $source = craft()->assetSources->getSourceById(intval(substr($value, 7)));
                        if ($source) {
                            $field['typesettings']['sources'][$key] = $source->handle;
                        }
                    }
                }
            } else {
                $field['typesettings']['sources'] = array();
            }

            if (array_key_exists('defaultUploadLocationSource', $field['typesettings'])) {
                $source = craft()->assetSources->getSourceById(intval($field['typesettings']['defaultUploadLocationSource']));
                if ($source) {
                    $field['typesettings']['defaultUploadLocationSource'] = $source->handle;
                }
            }

            if (array_key_exists('singleUploadLocationSource', $field['typesettings'])) {
                $source = craft()->assetSources->getSourceById(intval($field['typesettings']['singleUploadLocationSource']));
                if ($source) {
                    $field['typesettings']['singleUploadLocationSource'] = $source->handle;
                }
            }

        }

        if ($field['type'] == 'RichText') {
            if (array_key_exists('availableAssetSources', $field['typesettings']) && is_array($field['typesettings']['availableAssetSources'])) {
                if ($field['typesettings']['availableAssetSources'] !== '*' && $field['typesettings']['availableAssetSources'] != '') {
                    foreach ($field['typesettings']['availableAssetSources'] as $key => $value) {
                        $source = craft()->assetSources->getSourceById($value);
                        if ($source) {
                            $field['typesettings']['availableAssetSources'][$key] = $source->handle;
                        }
                    }
                }
            } else {
                $field['typesettings']['availableAssetSources'] = array();
            }

            if (array_key_exists('defaultUploadLocationSource', $field['typesettings'])) {
                $source = craft()->assetSources->getSourceById(intval($field['typesettings']['defaultUploadLocationSource']));
                if ($source) {
                    $field['typesettings']['defaultUploadLocationSource'] = $source->handle;
                }

            }

            if (array_key_exists('singleUploadLocationSource', $field['typesettings'])) {
                $source = craft()->assetSources->getSourceById(intval($field['typesettings']['singleUploadLocationSource']));
                if ($source) {
                    $field['typesettings']['singleUploadLocationSource'] = $source->handle;
                }
            }
        }

        if ($field['type'] == 'Categories') {
            if (array_key_exists('source', $field['typesettings'])) {
                if (substr($field['typesettings']['source'], 0, 6) == 'group:') {
                    $category = craft()->categories->getGroupById(intval(substr($field['typesettings']['source'], 6)));
                    if ($category) {
                        $field['typesettings']['source'] = $category->handle;
                    }
                }
            }
        }

        if ($field['type'] == 'Entries') {
            if (array_key_exists('sources', $field['typesettings']) && is_array($field['typesettings']['sources'])) {
                foreach ($field['typesettings']['sources'] as $key => $value) {
                    if (substr($value, 0, 8) == 'section:') {
                        $section = craft()->sections->getSectionById(intval(substr($value, 8)));
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
            if (array_key_exists('source', $field['typesettings'])) {
                if (substr($field['typesettings']['source'], 0, 9) == 'taggroup:') {
                    $tag = craft()->tags->getTagGroupById(intval(substr($field['typesettings']['source'], 9)));
                    if ($tag) {
                        $field['typesettings']['source'] = $tag->handle;
                    }
                }
            }
        }

        if ($field['type'] == 'Users') {
            if (array_key_exists('sources', $field['typesettings']) && is_array($field['typesettings']['sources'])) {
                foreach ($field['typesettings']['sources'] as $key => $value) {
                    if (substr($value, 0, 6) == 'group:') {
                        $userGroup = craft()->userGroups->getGroupById(intval(substr($value, 6)));
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
                    $transform = $this->getTransformById($value);

                    if ($transform) {
                        $field['typesettings']['availableTransforms'][$key] = $transform->handle;
                    }
                }
            }
        }
    }

    private function getAssetSourceByHandle($handle){
        $sources = craft()->assetSources->getAllSources();
        foreach($sources as $source)
        {
            if ($source->handle == $handle)
            {
                return $source;
            }
        }

        return false;
    }

    private function getFieldGroupByName($name)
    {
        // Get all field groups
        $groups = craft()->fields->getAllGroups();

        // Loop through field groups
        foreach ($groups as $group) {

            // Return matching group
            if ($group->name == $name) {
                return $group;
            }
        }
        return false;
    }

    private function getTransformById($id)
    {
        $transforms = craft()->assetTransforms->getAllTransforms();
        foreach ($transforms as $key => $transform) {
            if ($transform->id == $id) {
                return $transform;
            }
        }
    }

    private function getSettingIds(&$field)
    {
        $this->getSourceIds($field);
        $this->getTransformIds($field);
        //get ids for children items
        if ($field['type'] == 'Matrix')
        {
            foreach ($field['typesettings']['blockTypes'] as &$blockType) {
                foreach ($blockType['fields'] as &$childField) {
                     $this->getSettingIds($childField);
                }
            }
        }

        if ($field['type'] == 'SuperTable')
        {
            foreach ($field['typesettings']['blockTypes'] as &$blockType) {
                foreach ($blockType['fields'] as &$childField) {
                    $this->getSettingIds($childField);
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
                    $transform = craft()->assetTransforms->getTransformByHandle($value);
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
                $newSource = $this->getAssetSourceByHandle($source);
                if ($newSource) {
                    $newSources[] = 'folder:' . $newSource->id;
                } else {
                    $this->addError('Asset source: ' . $source . ' is not defined in system');
                }
            }

            $field['typesettings']['sources'] = join($newSources);

            if (array_key_exists('defaultUploadLocationSource', $field['typesettings'])) {
                $source = $this->getAssetSourceByHandle($field['typesettings']['defaultUploadLocationSource']);
                if ($source) {
                    $field['typesettings']['defaultUploadLocationSource'] = $source->id;
                } else {
                    $field['typesettings']['defaultUploadLocationSource'] = '';
                }
            }
            if (array_key_exists('singleUploadLocationSource', $field['typesettings'])) {
                $source = $this->getAssetSourceByHandle($field['typesettings']['singleUploadLocationSource']);
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
                $newSource = $this->getAssetSourceByHandle($source);
                if ($newSource) {
                    $newSources[] = 'folder:' . $newSource->id;
                } else {
                    $this->addError('Asset source: ' . $source . ' is not defined in system');
                }
            }

            $field['typesettings']['availableAssetSources'] = $newSources;

            if (array_key_exists('defaultUploadLocationSource', $field['typesettings'])) {
                $source = $this->getAssetSourceByHandle($field['typesettings']['defaultUploadLocationSource']);
                if ($source) {
                    $field['typesettings']['defaultUploadLocationSource'] = $source->id;
                } else {
                    $field['typesettings']['defaultUploadLocationSource'] = '';
                }
            }
            if (array_key_exists('singleUploadLocationSource', $field['typesettings'])) {
                $source = $this->getAssetSourceByHandle($field['typesettings']['singleUploadLocationSource']);
                if ($source) {
                    $field['typesettings']['singleUploadLocationSource'] = $source->id;
                } else {
                    $field['typesettings']['singleUploadLocationSource'] = '';
                }
            }
        }

        if ($field['type'] == 'Categories') {
            $newSources = array();
            $newSource = craft()->categories->getGroupByHandle($field['typesettings']['source']);
            if ($newSource) {
                $newSources[] = 'group:' . $newSource->id;
            } else {
                $this->addError('Category: ' . $field['typesettings']['source'] . ' is not defined in system');
            }
            $field['typesettings']['source'] = $newSources;
        }


        if ($field['type'] == 'Entries') {
            $newSources = array();
            foreach ($field['typesettings']['sources'] as $source) {
                $newSource = craft()->sections->getSectionByHandle($source);
                if ($newSource)
                {
                    $newSources[] = 'section:' . $newSource->id;
                }
                elseif ($source == 'singles')
                {
                    $newSources[] = $source;
                } else {
                    $this->addError('Section : ' . $source . ' is not defined in system');
                }
            }

            $field['typesettings']['sources'] = join($newSources);
        }

        if ($field['type'] == 'Tags') {
            $newSources = array();
            $newSource = craft()->tags->getTagGroupByHandle($field['typesettings']['source']);
            if ($newSource) {
                $newSources[] = 'taggroup:' . $newSource->id;
            } else {
                $this->addError('Tag: ' . $field['typesettings']['source'] . ' is not defined in system');
            }
            $field['typesettings']['source'] = $newSources;
        }

        if ($field['type'] == 'Users') {
            $newSources = array();
            foreach ($field['typesettings']['sources'] as $source) {
                $newSource = craft()->userGroups->getGroupByHandle($source);
                if ($newSource)
                {
                    $newSources[] = 'group:' . $newSource->id;
                }
                elseif ($source == 'admins')
                {
                    $newSources[] = $source;
                } else {
                    $this->addError('User Group: ' . $source . ' is not defined in system');
                }
            }

            $field['typesettings']['sources'] = join($newSources);
        }
    }

    public function createModel(Array $data)
    {
        $field = new FieldModel();
        //find group id
        $field->id = $data['id'];

        $group = $this->getFieldGroupByName($data['group']);
        if (!$group){
            $group = new FieldGroupModel();
            $group->name = $data['group'];
            craft()->fields->saveGroup($group);
        }

        //go get any extra settings that need to be set based on handles
        $this->getSettingIds($data);

        $field->groupId = $group->id;

        $field->name = $data['name'];
        $field->handle = $data['handle'];
        $field->instructions = $data['instructions'];

        if (array_key_exists('translatable', $data)) {
            $field->translatable = (bool)$data['translatable'];
        }
        $field->type = $data['type'];
        $field->settings = $data['typesettings'];


        return $field;
    }


    private function mergeUpdates(&$newField, $field)
    {
        $newField['id'] = $field->id;

        if ($newField['type'] == $field->type)
        {
            if ($field->type == 'Matrix')
            {
                $this->mergeMatrix($newField, $field);
            }

            if ($field->type == 'SuperTable')
            {
                $this->mergeSuperTable($newField, $field);
            }
        }
    }

    private function mergeSuperTable(&$newField, $field)
    {
        $newBlockTypes = [];
        $blockTypes = $newField['typesettings']['blockTypes'];
        $existingBlockTypes = craft()->superTable->getBlockTypesByFieldId($field->id);

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
        $existingFields = craft()->fields->getAllFields(null, 'superTableBlockType:' . $existingBlockType->id);

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
        $existingFields = craft()->fields->getAllFields(null, 'matrixBlockType:' . $block->id);

        foreach($fields as $key => &$field){
            $existingField = $this->getMatrixFieldByHandle($field['handle'], $existingFields);

            if ($existingField){
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
        $blocks = craft()->matrix->getBlockTypesByFieldId($id);
        foreach($blocks as $block)
        {
            if ($block->handle == $handle){
                return $block;
            }
        }

        return false;
    }

}