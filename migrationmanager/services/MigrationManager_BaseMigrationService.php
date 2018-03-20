<?php

namespace Craft;

/**
 * Class MigrationManager_BaseMigrationService
 */
abstract class MigrationManager_BaseMigrationService extends BaseApplicationComponent implements MigrationManager_IMigrationService
{
    /**
     * @var array
     */
    protected $errors = array();

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
     * @param $value
     */
    public function addError($value)
    {
        $this->errors[] = $value;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * @return void
     */
    public function resetErrors()
    {
        $this->errors = array();
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
        $this->resetErrors();
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
     * Fires an 'onExportFieldContent' event. Event handlers can prevent the default field handling by setting $event->performAction to false.
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['parent'] - field parent
     *          $event->params['value'] - current field value, change this value in the event handler to output a different value
     *
     * @return null
     */
    public function onExportFieldContent(Event $event)
    {
        //route this through fields service for simplified event listening
        craft()->migrationManager_fields->onExportFieldContent($event);
    }

    /**
     * Fires an 'onImportFieldContent' event. Event handlers can prevent the default field handling by setting $event->performAction to false.
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['parent'] - field parent
     *          $event->params['value'] - current field value, change this value in the event handler to import a different value
     *
     * @return null
     */
    public function onImportFieldContent(Event $event)
    {
        //route this through fields service for simplified event listening
        craft()->migrationManager_fields->onImportFieldContent($event);
    }

    /**
     * Fires an 'onExport' event after an element has been prepped for export.
     *
     * @param Event $event
     *          $event->params['element'] - element being exported
     *          $event->params['value'] - data to be exported, change this value in the event handler to export a different value
     *
     * @return null
     */
    public function onExport(Event &$event)
    {
        $this->raiseEvent('onExport', $event);
    }

    /**
     * Fires an 'onImport' event after an element has been imported into the CMS
     *
     * @param Event $event
     *          $event->params['element'] - element being imported
     *          $event->params['value'] - the data that was used to construct the imported object
     *
     * @return null
     */
    public function onImport(Event $event)
    {
        $this->raiseEvent('onImport', $event);
    }
}