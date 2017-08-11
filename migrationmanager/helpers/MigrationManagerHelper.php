<?php

namespace Craft;

class MigrationManagerHelper
{
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

    public static function getAssetSourceByHandle($handle)
    {
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

    public static function getFieldByHandleContext($handle, $context){
        $fields = craft()->fields->getAllFields(null, $context);
        foreach($fields as $field){
            if ($field->handle == $handle){
                return $field;
            }
        }
        return false;
    }

    public static function getAssetByHandle($element)
    {
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

                    return $asset;
                    //Craft::log('found asset: ' . serialize($asset), LogLevel::Error);
                } else {
                    Craft::log('no asset: ' . $element['filename'], LogLevel::Error);
                }
            } else {
                Craft::log('no folder: ' . $element['folder'], LogLevel::Error);
            }
        } else {
            Craft::log('no source:' . $element['source'], LogLevel::Error);
        }

        return false;

    }

    public static function getCategoryByHandle($element)
    {

        Craft::log('find category: ' . $element['slug'], LogLevel::Error);
        Craft::log(JsonHelper::encode($element), LogLevel::Error);
        $categoryGroup = craft()->categories->getGroupByHandle($element['category']);
        if ($categoryGroup) {
            $criteria = craft()->elements->getCriteria(ElementType::Category);
            $criteria->groupId = $categoryGroup->id;
            $criteria->slug = $element['slug'];
            $category = $criteria->first();
            if ($category)
            {
                return $category;
            }
        } else {
            Craft::log('no category group: ' . $element['category'], LogLevel::Error);
        }

        return false;
    }

    public static function getEntryByHandle($element)
    {

        $section = craft()->sections->getSectionByHandle($element['section']);
        if ($section) {
            $criteria = craft()->elements->getCriteria(ElementType::Entry);
            $criteria->slug = $element['slug'];
            $criteria->sectionId = $section->id;
            $entry = $criteria->first();
            if ($entry){
                return $entry;
            } else {
                Craft::log('entry not found: ' . $element['slug'], LogLevel::Error);
            }
        } else {
            Craft::log('section not found ' . $element['section'], LogLevel::Error);
        }

        return false;
    }

    public static function getUserByHandle($element)
    {
        $user = craft()->users->getUserByUsernameOrEmail($element['username']);
        if ($user)
        {
            return $user;
        }

        return false;

    }

    public static function getTagByHandle($element)
    {
        $group = craft()->tags->getTagGroupByHandle($element['group']);
        if ($group)
        {
            $criteria = craft()->elements->getCriteria(ElementType::Tag);
            $criteria->groupId = $group->id;
            $criteria->slug = 'tag1';

            $tag = $criteria->first();
            if ($tag)
            {
                return $tag;
            }
        } else {
            Craft::log('group not found');
        }
    }



}