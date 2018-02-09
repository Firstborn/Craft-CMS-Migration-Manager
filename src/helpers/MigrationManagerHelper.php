<?php

namespace Craft;

/**
 * Class MigrationManagerHelper
 */
class MigrationManagerHelper
{
    /**
     * @param $id
     *
     * @return bool|AssetTransformModel
     */
    public static function getTransformById($id)
    {
        $transforms = craft()->assetTransforms->getAllTransforms();
        foreach ($transforms as $key => $transform) {
            if ($transform->id == $id) {
                return $transform;
            }
        }

        return false;
    }

    /**
     * @param int $id
     *
     * @return bool|AssetSourceModel
     */
    public static function getAssetSourceByFolderId($id)
    {
        $folder = craft()->assets->getFolderById($id);
        if ($folder) {
            $source = craft()->assetSources->getSourceById($folder->sourceId);
            if ($source) {
                return $source;
            }
        }

        return false;
    }

    /**
     * @param string $handle
     *
     * @return bool|AssetTransformModel
     */
    public static function getAssetSourceByHandle($handle)
    {
        $sources = craft()->assetSources->getAllSources();
        foreach ($sources as $source) {
            if ($source->handle == $handle) {
                return $source;
            }
        }

        return false;
    }

    /**
     * @param string            $handle
     * @param string|array|null $context
     *
     * @return bool|FieldModel
     */
    public static function getFieldByHandleContext($handle, $context)
    {
        $fields = craft()->fields->getAllFields(null, $context);
        foreach ($fields as $field) {
            if ($field->handle == $handle) {
                return $field;
            }
        }

        return false;
    }

    /**
     * @param $handle
     * @param $fieldId
     *
     * @return bool|MatrixBlockTypeModel
     */
    public static function getMatrixBlockType($handle, $fieldId)
    {
        $blockTypes = craft()->matrix->getBlockTypesByFieldId($fieldId);
        foreach ($blockTypes as $block) {
            if ($block->handle == $handle) {
                return $block;
            }
        }

        return false;
    }

    /**
     * @param $handle
     * @param $fieldId
     *
     * @return bool|NeoBlockTypeModel
     */
    public static function getNeoBlockType($handle, $fieldId)
    {
        $blockTypes = craft()->neo->getBlockTypesByFieldId($fieldId);
        foreach ($blockTypes as $block) {
            if ($block->handle == $handle) {
                return $block;
            }
        }

        return false;
    }

    /**
     * @param array $element
     *
     * @return bool|BaseElementModel|null
     * @throws Exception
     */
    public static function getAssetByHandle($element)
    {
        $source = MigrationManagerHelper::getAssetSourceByHandle($element['source']);
        if ($source) {

            $folderCriteria = new FolderCriteriaModel();
            $folderCriteria->name = $element['folder'];
            $folderCriteria->sourceId = $source->id;

            $folder = craft()->assets->findFolder($folderCriteria);
            if ($folder) {

                $criteria = craft()->elements->getCriteria(ElementType::Asset);
                $criteria->sourceId = $source->id;
                $criteria->folderId = $folder->id;
                $criteria->filename = $element['filename'];

                $asset = $criteria->first();
                if ($asset) {
                    return $asset;
                }
            }
        }

        return false;
    }

    /**
     * @param array $element
     *
     * @return bool|BaseElementModel|null
     * @throws Exception
     */
    public static function getCategoryByHandle($element)
    {
        $categoryGroup = craft()->categories->getGroupByHandle($element['category']);
        if ($categoryGroup) {

            $criteria = craft()->elements->getCriteria(ElementType::Category);
            $criteria->groupId = $categoryGroup->id;
            $criteria->slug = $element['slug'];

            $category = $criteria->first();
            if ($category) {
                return $category;
            }
        }

        return false;
    }

    /**
     * @param array $element
     *
     * @return bool|BaseElementModel|null
     * @throws Exception
     */
    public static function getEntryByHandle($element)
    {
        $section = craft()->sections->getSectionByHandle($element['section']);
        if ($section) {

            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->slug = $element['slug'];
            $criteria->sectionId = $section->id;

            $entry = $criteria->first();
            if ($entry) {
                return $entry;
            }
        }

        return false;
    }

    /**
     * @param array $element
     *
     * @return bool|UserModel|null
     */
    public static function getUserByHandle($element)
    {
        $user = craft()->users->getUserByUsernameOrEmail($element['username']);
        if ($user) {
            return $user;
        }

        return false;
    }

    /**
     * @param array $element
     *
     * @return BaseElementModel|null
     * @throws Exception
     */
    public static function getTagByHandle($element)
    {
        $group = craft()->tags->getTagGroupByHandle($element['group']);
        if ($group) {

            $criteria = craft()->elements->getCriteria(ElementType::Tag);
            $criteria->groupId = $group->id;
            $criteria->slug = 'tag1';

            $tag = $criteria->first();
            if ($tag) {
                return $tag;
            }
        }
    }

    /**
     * @param array $permissions
     *
     * @return array
     */
    public static function getPermissionIds($permissions)
    {
        foreach ($permissions as &$permission) {
            //determine if permission references element, get id if it does
            if (preg_match('/(:)/', $permission)) {
                $permissionParts = explode(":", $permission);
                $element = null;

                if (preg_match('/entries|entrydrafts/', $permissionParts[0])) {
                    $element = craft()->sections->getSectionByHandle($permissionParts[1]);
                } elseif (preg_match('/assetsource/', $permissionParts[0])) {
                    $element = MigrationManagerHelper::getAssetSourceByHandle($permissionParts[1]);
                } elseif (preg_match('/categories/', $permissionParts[0])) {
                    $element = craft()->categories->getGroupByHandle($permissionParts[1]);
                }

                if ($element != null) {
                    $permission = $permissionParts[0].':'.$element->id;
                }
            }
        }

        return $permissions;
    }

    /**
     * @param array $permissions
     *
     * @return array
     */
    public static function getPermissionHandles($permissions)
    {
        foreach ($permissions as &$permission) {
            //determine if permission references element, get handle if it does
            if (preg_match('/(:\d)/', $permission)) {
                $permissionParts = explode(":", $permission);
                $element = null;

                if (preg_match('/entries|entrydrafts/', $permissionParts[0])) {
                    $element = craft()->sections->getSectionById($permissionParts[1]);
                } elseif (preg_match('/assetsource/', $permissionParts[0])) {
                    $element = craft()->assetSources->getSourceById($permissionParts[1]);
                } elseif (preg_match('/categories/', $permissionParts[0])) {
                    $element = craft()->categories->getGroupById($permissionParts[1]);
                }

                if ($element != null) {
                    $permission = $permissionParts[0].':'.$element->handle;
                }
            }
        }

        return $permissions;
    }
}