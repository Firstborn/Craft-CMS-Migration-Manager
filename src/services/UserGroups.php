<?php

namespace firstborn\migrationmanager\services;

use firstborn\migrationmanager\helpers\MigrationManagerHelper;
use Craft;
use craft\elements\User;
use craft\models\UserGroup;
use firstborn\migrationmanager\events\ExportEvent;

class UserGroups extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'userGroup';

    /**
     * @var string
     */
    protected $destination = 'userGroups';

    /**
     * {@inheritdoc}
     */
    public function exportItem($id, $fullExport = false)
    {
        $group = Craft::$app->userGroups->getGroupById($id);

        if (!$group) {
            return false;
        }

        $newGroup = [
            'name' => $group->name,
            'handle' => $group->handle,
        ];

        $this->addManifest($group->handle);

        if ($fullExport) {
            $newGroup['fieldLayout'] = array();
            $newGroup['requiredFields'] = array();
            $fieldLayout = Craft::$app->fields->getLayoutByType(User::class);

            foreach ($fieldLayout->getTabs() as $tab) {
                $newGroup['fieldLayout'][$tab->name] = array();
                foreach ($tab->getFields() as $tabField) {

                    $newGroup['fieldLayout'][$tab->name][] = $tabField->handle;
                    if ($tabField->required) {
                        $newGroup['requiredFields'][] = $tabField->handle;
                    }
                }
            }
            $newGroup['permissions'] = $this->getGroupPermissionHandles($id);
            $newGroup['settings'] = Craft::$app->systemSettings->getSettings('users');

            if ($newGroup['settings']['defaultGroup'] != null) {
                $group = Craft::$app->userGroups->getGroupById($newGroup['settings']['defaultGroup']);
                $newGroup['settings']['defaultGroup'] = $group->handle;
            }
        }

        if ($fullExport) {
            $newGroup = $this->onBeforeExport($group, $newGroup);
        }

        return $newGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(array $data)
    {
        $existing = Craft::$app->userGroups->getGroupByHandle($data['handle']);

        if ($existing) {
            $this->mergeUpdates($data, $existing);
        }

        $userGroup = $this->createModel($data);
        $event = $this->onBeforeImport($userGroup, $data);

        if ($event->isValid) {
            $result = Craft::$app->userGroups->saveGroup($event->element);
            if ($result) {

                if (array_key_exists('permissions', $data)) {
                    $permissions = MigrationManagerHelper::getPermissionIds($data['permissions']);
                    if (Craft::$app->userPermissions->saveGroupPermissions($userGroup->id, $permissions)) {

                    } else {
                        $this->addError('error', 'Could not save user group permissions');
                    }
                }

                if (array_key_exists('settings', $data)) {

                    if ($data['settings']['defaultGroup'] != null) {
                        $group = Craft::$app->userGroups->getGroupByHandle($data['settings']['defaultGroup']);
                        $data['settings']['defaultGroup'] = $group->id;
                    }

                    if (Craft::$app->systemSettings->saveSettings('users', $data['settings'])) {

                    } else {
                        $this->addError('error', 'Could not save user group settings');
                    }
                }

                $this->onAfterImport($event->element, $data);
            } else {
                $this->addError('error', 'Could not save the ' . $data['handle'] . ' user group.');
            }
        } else {
            $this->addError('error', 'Error importing ' . $data['handle'] . ' user group.');
            $this->addError('error', $event->error);
            return false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createModel(Array $data)
    {
        $userGroup = new UserGroup();
        if (array_key_exists('id', $data)) {
            $userGroup->id = $data['id'];
        }

        $userGroup->name = $data['name'];
        $userGroup->handle = $data['handle'];

        if (array_key_exists('fieldLayout', $data)) {
            $requiredFields = array();
            if (array_key_exists('requiredFields', $data)) {
                foreach ($data['requiredFields'] as $handle) {
                    $field = Craft::$app->fields->getFieldByHandle($handle);
                    if ($field) {
                        $requiredFields[] = $field->id;
                    }
                }
            }

            $layout = array();
            foreach ($data['fieldLayout'] as $key => $fields) {
                $fieldIds = array();
                foreach ($fields as $field) {
                    $existingField = Craft::$app->fields->getFieldByHandle($field);
                    if ($existingField) {
                        $fieldIds[] = $existingField->id;
                    } else {
                        $this->addError('error', 'Missing field: ' . $field . ' can not add to field layout for User Group: ' . $userGroup->handle);
                    }
                }
                $layout[$key] = $fieldIds;
            }

            $fieldLayout = Craft::$app->fields->assembleLayout($layout, $requiredFields);
            $fieldLayout->type = User::class;

            Craft::$app->fields->deleteLayoutsByType(User::class);

            if (Craft::$app->fields->saveLayout($fieldLayout)) {

            } else {
                $this->addError('error', Craft::t('Couldn’t save user fields.'));
            }
        }

        return $userGroup;
    }

    /**
     * @param array $newSource
     * @param UserGroupModel$source
     */
    private function mergeUpdates(&$newSource, $source)
    {
        $newSource['id'] = $source->id;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    private function getGroupPermissionHandles($id)
    {
        $permissions = Craft::$app->userPermissions->getPermissionsByGroupId($id);
        $permissions = MigrationManagerHelper::getPermissionHandles($permissions);
        return $permissions;
    }
}
