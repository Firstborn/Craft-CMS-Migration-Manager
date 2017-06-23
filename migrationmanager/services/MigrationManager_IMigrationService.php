<?php

namespace Craft;

interface MigrationManager_IMigrationService {

    public function import(Array $data);

    public function importItem(Array $data);

    public function export(Array $ids);

    public function exportItem($id);

    public function createModel(Array $data);

    public function hasErrors();

    public function getErrors();

    public function addError($value);

    public function resetErrors();





}