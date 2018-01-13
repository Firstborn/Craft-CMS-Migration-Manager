<?php

namespace Craft;

interface MigrationManager_IMigrationService {

    public function import(Array $data);

    public function importItem(Array $data);

    public function export(Array $ids, $fullExport);

    public function exportItem($id, $fullExport);

    public function createModel(Array $data);

    public function hasErrors();

    public function getErrors();

    public function addError($value);

    public function resetErrors();

    /**
     * @return string the post field to pull export ids from
     */
    public function getSource();

    /**
     * @return string the property to write export data to
     */
    public function getDestination();





}