<?php
namespace firstborn\migrationmanager\assetbundles\cpsidebar;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CpSideBarAssetBundle extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = __DIR__.'/dist';
        //$this->sourcePath = '@firstborn/migrationmanager/assetbundles/';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/MigrationManagerSideBar.js'
        ];

        $this->css = [
            'css/migration-manager-sidebar.css'
        ];



        parent::init();
    }
}