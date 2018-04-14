<?php

namespace firstborn\migrationmanager\services;

use craft\base\Component;
use craft\base\Element;
use firstborn\migrationmanager\events\ExportEvent;
use firstborn\migrationmanager\events\ImportEvent;

/**
 * Class MigrationManager_BaseMigrationService
 */
abstract class BaseMigration extends Component implements IMigrationService
{
    /**
     * @event ElementEvent The event that is triggered before an element is exported
     */

    const EVENT_BEFORE_EXPORT_ELEMENT = 'beforeExport';

    /**
     * @event ElementEvent The event that is triggered before an element is imported, can be cancelled
     */
    const EVENT_BEFORE_IMPORT_ELEMENT = 'beforeImport';

    /**
     * @event ElementEvent The event that is triggered before an element is imported
     */
    const EVENT_AFTER_IMPORT_ELEMENT = 'afterImport';


    /**
     * @var array
     */
    //protected $errors = array();

    /**
     * @var
     */
    protected $source;

    /**
     * @var
     */
    protected $destination;

    /**
     * @var
     */
    protected $manifest;

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }


    /**
     * @return void
     */
    public function resetManifest()
    {
        $this->manifest = array();
    }

    /**
     * @param mixed $value
     */
    public function addManifest($value)
    {
        $this->manifest[] = $value;
    }

    /**
     * @return array
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * @param array $data
     *
     * @return BaseModel|null
     */
    public function createModel(array $data)
    {
        return null;
    }

    /**
     * @param array $ids        array of fields ids to export
     * @param bool  $fullExport flag to export all element data including extending settings and field tabs
     * @return array
     */
    public function export(array $ids, $fullExport = false)
    {
        $this->resetManifest();
        $items = array();

        foreach ($ids as $id) {
            $obj = $this->exportItem($id, $fullExport);
            if ($obj) {
                $items[] = $obj;
            }
        }

        return $items;
    }

    /**
     * @param array $data of data to import
     *
     * @return bool
     */
    public function import(array $data)
    {
        $this->clearErrors();
        $result = true;

        foreach ($data as $section) {
            if ($this->importItem($section) === false) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @param int  $id
     * @param bool $fullExport
     *
     * @return mixed
     */
    abstract public function exportItem($id, $fullExport = false);

    /**
     * @param array $data
     *
     * @return mixed
     */
    abstract public function importItem(array $data);

    /**
     * Fires an 'onBeforeExport' event.
     *
     * @param Event $event
     *          $event->params['element'] - element being exported via migration
     *          $event->params['value'] - current element value, change this value in the event handler to migrate a different value
     *
     * @return null
     */
    public function onBeforeExport($element , array $newElement)
    {
        $event = new ExportEvent(array(
            'element' => $element,
            'value' => $newElement
        ));

        $this->trigger($this::EVENT_BEFORE_EXPORT_ELEMENT, $event);
        return $event->value;
    }

    /**
     * Fires an 'onBeforeImport' event.
     *
     * @param Event $event
     *          $event->params['element'] - model to be imported, manipulate this to change the model before it is saved
     *          $event->params['value'] - data used to create the element model
     *
     * @return null
     */
    public function onBeforeImport($element, array $data)
    {
        $event = new ImportEvent(array(
            'element' => $element,
            'value' => $data
        ));
        $this->trigger($this::EVENT_BEFORE_IMPORT_ELEMENT, $event);
        return $event;
    }

    /**
     * Fires an 'onAfterImport' event.
     *
     * @param Event $event
     *          $event->params['element'] - model that was imported
     *          $event->params['value'] - data used to create the element model
     *
     * @return null
     */
    public function onAfterImport($element, array $data)
    {
        $event = new ImportEvent(array(
            'element' => $element,
            'value' => $data
        ));

        $this->trigger($this::EVENT_AFTER_IMPORT_ELEMENT, $event);
    }


}