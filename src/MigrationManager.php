<?php

namespace firstborn\migrationmanager;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\User;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterElementActionsEvent;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;

use firstborn\migrationmanager\assetbundles\cpsidebar\CpSideBarAssetBundle;
use firstborn\migrationmanager\assetbundles\cpglobals\CpGlobalsAssetBundle;
use firstborn\migrationmanager\actions\MigrateCategoryElementAction;
use firstborn\migrationmanager\actions\MigrateEntryElementAction;
use firstborn\migrationmanager\actions\MigrateUserElementAction;


/**
 * Migration Manager plugin for Craft CMS
 *
 * Create Craft migrations to easily migrate settings and content between website environments.
 *
 * @author    Derrick Grigg
 * @copyright Copyright (c) 2018 Firstborn
 * @link      https://firstborn.com
 * @package   MigrationManager
 * @since     1.0.0
 */



class MigrationManager extends Plugin
{

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

    public function label(){
        return 'hi';
    }
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'migrations' => \firstborn\migrationmanager\services\Migrations::class,
            'sites' => \firstborn\migrationmanager\services\Sites::class,
            'fields' => \firstborn\migrationmanager\services\Fields::class,
            'sections' => \firstborn\migrationmanager\services\Sections::class,
            'assetVolumes' => \firstborn\migrationmanager\services\AssetVolumes::class,
            'assetTransforms' => \firstborn\migrationmanager\services\AssetTransforms::class,
            'globals' => \firstborn\migrationmanager\services\Globals::class,
            'tags' => \firstborn\migrationmanager\services\Tags::class,
            'categories' => \firstborn\migrationmanager\services\Categories::class,
            'routes' => \firstborn\migrationmanager\services\Routes::class,
            'userGroups' => \firstborn\migrationmanager\services\UserGroups::class,
            'systemMessages' => \firstborn\migrationmanager\services\SystemMessages::class,
            'categoriesContent' => \firstborn\migrationmanager\services\CategoriesContent::class,
            'entriesContent' => \firstborn\migrationmanager\services\EntriesContent::class,
            'globalsContent' => \firstborn\migrationmanager\services\GlobalsContent::class,
            'usersContent' => \firstborn\migrationmanager\services\UsersContent::class,
        ]);

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['migrationmanager/migrations'] = 'migrationmanager/cp/migrations';
                $event->rules['migrationmanager/create'] = 'migrationmanager/cp/index';
                $event->rules['migrationmanager'] = 'migrationmanager/cp/index';
            }
        );

        // Register Element Actions
        Event::on(Entry::class, Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event) {
                $event->actions[] = MigrateEntryElementAction::class;
            }
        );

        Event::on(Category::class, Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event) {
                $event->actions[] = MigrateCategoryElementAction::class;
            }
        );

        Event::on(User::class, Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event) {
                $event->actions[] = MigrateUserElementAction::class;
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
    }

    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['badgeCount'] = $this->getBadgeCount();
        $item['subnav'] = [
            'create' => ['label' => 'Create', 'url' => 'migrationmanager'],
            'migrations' => ['label' => 'Migrations', 'url' => 'migrationmanager/migrations']
        ];
        return $item;
    }

    public function getBadgeCount(){
        $count =  count($this->migrations->getNewMigrations());
        return $count;
    }

}
