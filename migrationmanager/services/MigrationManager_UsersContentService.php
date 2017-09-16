<?php
namespace Craft;

class MigrationManager_UsersContentService extends MigrationManager_BaseContentMigrationService
{
    protected $source = 'user';
    protected $destination = 'users';

    public function exportItem($id, $fullExport)
    {
        $user = craft()->users->getUserById($id);

        $this->addManifest($id);

        if ($user)
        {
            $attributes = $user->getAttributes();
            unset($attributes['id']);
            $content = array();
            $this->getContent($content, $user);
            $content = array_merge($content, $attributes);
        } else {
            return false;
        }


        return $content;
    }

    public function importItem(Array $data)
    {
         $user = craft()->users->getUserByUsernameOrEmail($data['username']);

        if ($user) {
            $data['id'] = $user->id;
        }
        $user = $this->createModel($data);

        // save user
        if (craft()->users->saveUser($user)) {
            $groups = $this->getUserGroupIds($data['groups']);
            craft()->userGroups->assignUserToGroups($user->id, $groups);
            $permissions = MigrationManagerHelper::getPermissionIds($data['permissions']);
            craft()->userPermissions->saveUserPermissions($user->id, $permissions);
        } else {
            throw new Exception(print_r($user->getErrors(), true));
        }

        return true;

    }



    public function createModel(Array $data)
    {
        $user = new UserModel();

        if (array_key_exists('id', $data)){
            $user->id = $data['id'];
        }

        $user->setAttributes($data);
        $this->getSourceIds($data);
        $user->setContentFromPost($data);
        return $user;
    }

    protected function getContent(&$content, $element)
    {
        parent::getContent($content, $element);
        $this->getUserGroupHandles($content, $element);
        $this->getUserPermissionHandles($content, $element);
    }


    private function getUserGroupIds($groups)
    {
        $usergroups = [];
        foreach ($groups as $group)
        {
            $usergroup = craft()->userGroups->getGroupByHandle($group);
            $usergroups[] = $usergroup->id;
        }
        return $usergroups;
    }
    private function getUserGroupHandles(&$content, $element)
    {
        $groups = $element->getGroups();
        $content['groups'] = array();
        foreach($groups as $group)
        {
            $content['groups'][] = $group->handle;
        }
    }

    private function getUserPermissionHandles(&$content, $element)
    {
        $permissions = craft()->userPermissions->getPermissionsByUserId($element->id);
        $permissions = MigrationManagerHelper::getPermissionHandles($permissions);
        $content['permissions'] = $permissions;
    }




}