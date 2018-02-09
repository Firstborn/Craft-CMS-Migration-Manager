<?php
namespace firstborn\migrationmanager\assetbundles\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CpAssetBundle extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        //$this->sourcePath = '@firstborn/migrationmanager/assetbundles/cp/';
        $this->sourcePath = __DIR__.'/dist';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/MigrationManagerExport.js',
            'js/MigrationManagerRunner.js'
        ];

        $this->css = [
            'css/migration-manager.css',
        ];

        parent::init();
    }
}