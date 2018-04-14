<?php

namespace firstborn\migrationmanager\helpers;

use Craft;
use craft\models\FolderCriteria;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;

/**
 * Class MigrationManagerHelper
 */
class MigrationManagerHelper
{
    /**
     * @param string            $handle
     * @param string|array|null $context
     *
     * @return bool|FieldModel
     */
    public static function getFieldByHandleContext($handle, $context)
    {
        $fields = Craft::$app->fields->getAllFields(null, $context);
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
        $blockTypes = Craft::$app->matrix->getBlockTypesByFieldId($fieldId);
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
        $blockTypes = Craft::$app->neo->getBlockTypesByFieldId($fieldId);
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
        $volume = Craft::$app->volumes->getVolumeByHandle($element['source']);
        if ($volume) {

            $folderCriteria = new FolderCriteria();
            $folderCriteria->name = $element['folder'];
            $folderCriteria->volumeId = $volume->id;

            $folder = Craft::$app->assets->findFolder($folderCriteria);
            if ($folder) {

                $query = Asset::find();
                $query->volumeId($volume->id);
                $query->folderId($folder->id);
                $query->filename($element['filename']);
                $asset = $query->one();



                /*$criteria = Craft::$app->elements->getCriteria(Asset::class);
                $criteria->sourceId = $source->id;
                $criteria->folderId = $folder->id;
                $criteria->filename = $element['filename'];*/

                //$asset = $criteria->first();
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
        $categoryGroup = Craft::$app->categories->getGroupByHandle($element['category']);
        if ($categoryGroup) {

            $query = Category::find();
            $query->groupId($categoryGroup->id);
            $query->slug($element['slug']);
            $category = $query->one();


            /*$criteria = Craft::$app->elements->getCriteria(Category::class);
            $criteria->groupId = $categoryGroup->id;
            $criteria->slug = $element['slug'];
            $category = $criteria->first();*/
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
        $section = Craft::$app->sections->getSectionByHandle($element['section']);
        if ($section) {

            $query = Entry::find();
            $query->sectionId($section->id);
            $query->slug($element['slug']);
            $entry = $query->one();

            /*$criteria = Craft::$app->elements->getCriteria(Entry::class);
            $criteria->slug = $element['slug'];
            $criteria->sectionId = $section->id;

            $entry = $criteria->first();*/
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
        $user = Craft::$app->users->getUserByUsernameOrEmail($element['username']);
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
        $group = Craft::$app->tags->getTagGroupByHandle($element['group']);
        if ($group) {

            $query = Tag::find();
            $query->groupId($group->id);
            $query->slug($element['slug']);
            $tag = $query->one();

            /*$criteria = Craft::$app->elements->getCriteria(ElementType::Tag);
            $criteria->groupId = $group->id;
            $criteria->slug = 'tag1';

            $tag = $criteria->first();*/
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
                    $element = Craft::$app->sections->getSectionByHandle($permissionParts[1]);
                } elseif (preg_match('/assetsource/', $permissionParts[0])) {
                    $element = MigrationManagerHelper::getAssetSourceByHandle($permissionParts[1]);
                } elseif (preg_match('/categories/', $permissionParts[0])) {
                    $element = Craft::$app->categories->getGroupByHandle($permissionParts[1]);
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
                    $element = Craft::$app->sections->getSectionById($permissionParts[1]);
                } elseif (preg_match('/assetsource/', $permissionParts[0])) {
                    $element = Craft::$app->assetSources->getSourceById($permissionParts[1]);
                } elseif (preg_match('/categories/', $permissionParts[0])) {
                    $element = Craft::$app->categories->getGroupById($permissionParts[1]);
                }

                if ($element != null) {
                    $permission = $permissionParts[0].':'.$element->handle;
                }
            }
        }

        return $permissions;
    }
}