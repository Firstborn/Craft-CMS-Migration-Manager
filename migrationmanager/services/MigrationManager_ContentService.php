<?php

namespace Craft;

class MigrationManager_ContentService extends BaseApplicationComponent
{

    /**
     * Fires an 'onExportField' event. Event handlers can prevent the default field handling by setting $event->performAction to false.
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['parent'] - field parent
     *          $event->params['value'] - current field value, change this value in the event handler to output a different value
     *
     * @return null
     */
    public function onExportField(Event $event)
    {
        $this->raiseEvent('onExportField', $event);
    }


}