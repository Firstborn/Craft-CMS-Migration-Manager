<?php

namespace Craft;

abstract class MigrationManager_BaseMigrationService extends BaseApplicationComponent implements  MigrationManager_IMigrationService
{
    protected $errors = array();

    public function __construct()
    {

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



    /**
     * @param $ids array of fields ids to export
     */
    public function export(Array $ids)
    {
        $items = array();
        foreach ($ids as $id) {
            $obj = $this->exportItem($id);
            if ($obj) {
                $items[] = $obj;
            }
        }
        return $items;
    }

    abstract public function exportItem($id);

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