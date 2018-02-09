<?php

namespace firstborn\migrationmanager;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;

use yii\base\Event;
use craft\web\View;
use firstborn\migrationmanager\assetbundles\cpsidebar\CpSideBarAssetBundle;
use firstborn\migrationmanager\assetbundles\cpglobals\CpGlobalsAssetBundle;
use firstborn\migrationmanager\services\MigrationsService as MigrationsService;



/**
 * Migration Manager plugin for Craft CMS
 *
 * Create Craft migrations to easily migrate settings and content between website environments.
 *
 * @author    Derrick Grigg
 * @copyright Copyright (c) 2017 Firstborn
 * @link      https://firstborn.com
 * @package   MigrationManager
 * @since     1.0.0
 */



class MigrationManager extends Plugin
{
    /**
     * {@inheritdoc}
     */
    /*public function getName()
    {
        return Craft::t('Migration Manager');
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function getVersion()
    {
        return '1.0.8.6';
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function getDeveloper()
    {
        return 'Derrick Grigg';
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function getDeveloperUrl()
    {
        return 'https://www.firstborn.com';
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/Firstborn/Craft-CMS-Migration-Manager/master/releases.json';
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function getDocumentationUrl()
    {
        return 'https://github.com/Firstborn/Craft-CMS-Migration-Manager/tree/master/README.md';
    }*/

    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Test::$plugin
     *
     * @var Test
     */
    public static $plugin;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Test::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'migrations' => \firstborn\migrationmanager\services\MigrationsService::class,
            'locales' => \firstborn\migrationmanager\services\LocalesService::class,
            'fields' => \firstborn\migrationmanager\services\FieldsService::class,
            'sections' => \firstborn\migrationmanager\services\SectionsService::class,
            'assetVolumes' => \firstborn\migrationmanager\services\AssetVolumesService::class,
            'assetTransforms' => \firstborn\migrationmanager\services\AssetTransformsService::class,
            'globals' => \firstborn\migrationmanager\services\GlobalsService::class,
            'tags' => \firstborn\migrationmanager\services\TagsService::class,
            'categories' => \firstborn\migrationmanager\services\CategoriesService::class,
            'routes' => \firstborn\migrationmanager\services\RoutesService::class,
            'userGroups' => \firstborn\migrationmanager\services\UserGroupsService::class,
            'emailMessages' => \firstborn\migrationmanager\services\EmailMessagesService::class,
            'categoriesContent' => \firstborn\migrationmanager\services\CategoriesContentService::class,
            'entriesContent' => \firstborn\migrationmanager\services\EntriesContentService::class,
            'globalsContent' => \firstborn\migrationmanager\services\GlobalsContentService::class,
            'usersContent' => \firstborn\migrationmanager\services\UsersContentService::class,

        ]);



        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['migrationmanager'] = 'migrationmanager/cp/index';
               
            }
        );


        $view = Craft::$app->getView();
        $view->registerAssetBundle(CpSideBarAssetBundle::class);

        $request = Craft::$app->getRequest();
        if ($request->getSegment(1) == 'globals'){
            $view = Craft::$app->getView();
            $view->registerAssetBundle(CpGlobalsAssetBundle::class);
            $view->registerJs('new Craft.MigrationManagerGlobalsExport();', View::POS_END);
        }





        /*Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['plugin-handle'] = 'migrationmanager/index';
        });*/

        /*Craft::import('plugins.migrationmanager.helpers.MigrationManagerHelper');
        Craft::import('plugins.migrationmanager.services.MigrationManager_IMigrationService');
        Craft::import('plugins.migrationmanager.service.MigrationManager_BaseMigrationService');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateCategoryElementAction');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateEntryElementAction');
        Craft::import('plugins.migrationmanager.actions.MigrationManager_MigrateUserElementAction');

         // check we have a cp request as we don't want to this js to run anywhere but the cp
        // and while we're at it check for a logged in user as well
        if ( craft()->request->isCpRequest() && craft()->userSession->isLoggedIn()) {

            // add a Create Migration button to the globals screen
            if (craft()->request->getSegment(1) == 'globals' ) {
                // the includeJsResource method will add a js file to the bottom of the page
                craft()->templates->includeJsResource('migrationmanager/js/MigrationManagerGlobalsExport.js');
                craft()->templates->includeJs("new Craft.MigrationManagerGlobalsExport();");
            }

            //show alert on sidebar if migrations are pending
            $pendingMigrations = count(craft()->migrationManager_migrations->getNewMigrations());
            if ($pendingMigrations > 0) {
                craft()->templates->includeCssResource('migrationmanager/css/styles.css');
                craft()->templates->includeJsResource('migrationmanager/js/MigrationManagerSideBar.js');
                craft()->templates->includeJs("new Craft.MigrationManagerSideBar({$pendingMigrations});");
            }
        }*/

    }

    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['subnav'] = [
            'create' => ['label' => 'Create', 'url' => 'migrationmanager/create'],
            'pending' => ['label' => 'Pending', 'url' => 'migrationmanager/pending'],
            'logs' => ['label' => 'Logs', 'url' => 'migrationmanager/logs'],
        ];
        return $item;
    }

    /**
     * {@inheritdoc}
     */
   /* public function hasCpSection()
    {
        return true;
    }*/

    /**
     * {@inheritdoc}
     */
   /*/ public function registerCpRoutes()
    {
        return array(
            'migrationmanager' => array('action' => 'migrationManager/index'),
            'migrationmanager/create' => array('action' => 'migrationManager/create'),
            'migrationmanager/pending' => array('action' => 'migrationManager/pending'),
            'migrationmanager/applied' => array('action' => 'migrationManager/applied'),
            'migrationmanager/logs' => array('action' => 'migrationManager/logs'),
        );
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function addEntryActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateEntryElementAction(),
        );
    }*/

    /**
     * {@inheritdoc}
     */
   /* public function addCategoryActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateCategoryElementAction(),
        );
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function addUserActions($source)
    {
        return array(
            'Migrate',
            new MigrationManager_MigrateUserElementAction(),
        );
    }*/
}
