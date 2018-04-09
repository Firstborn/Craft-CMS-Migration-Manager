<?php

namespace firstborn\migrationmanager\actions;
use firstborn\migrationmanager\MigrationManager;
use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;


class MigrateUserElementAction extends ElementAction
{


    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Create Migration');
    }


    /**
     * {@inheritdoc}
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $params['user'] = [];
        $elements = $query->all();

        foreach ($elements as $element) {
            $params['user'][] = $element->id;
        }

        if (MigrationManager::getInstance()->migrations->createContentMigration($params)) {

            $this->setMessage(Craft::t('app', 'Migration created.'));
            return true;
        } else {

            $this->setMessage(Craft::t('app', 'Migration could not be created.'));
            return false;
        }
    }
}
