<?php

namespace Craft;

abstract class MigrationManager_BaseMigrationService extends BaseApplicationComponent implements  MigrationManager_IMigrationService
{
    protected $errors = array();

    protected $source;
    protected $destination;
    protected $manifest;

    public function __construct()
    {

    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function addError($value){
        $this->errors[] = $value;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors(){
        return count($this->errors) > 0;
    }

    public function resetErrors(){
        $this->errors = array();
    }

    public function resetManifest(){
        $this->manifest = array();
    }

    public function addManifest($value)
    {
        $this->manifest[] = $value;
    }

    public function getManifest(){
        return $this->manifest;
    }

    /**
     * @param $ids array of fields ids to export
     * @param $fullExport flag to export all element data including extending settings and field tabs
     */
    public function export(Array $ids, $fullExport = true)
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

    abstract public function exportItem($id, $fullExport);

    /**
     * @param $data array of items to import
     */
    public function import(Array $data)
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

    abstract public function importItem(Array $data);

}