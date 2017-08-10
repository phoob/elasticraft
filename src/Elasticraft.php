<?php
/**
 * Elasticraft plugin for Craft CMS 3.x
 *
 * Desc.
 *
 * @link      https://dfo.no
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 */

namespace dfo\elasticraft;

use dfo\elasticraft\services\ElasticraftService as ElasticraftServiceService;
use dfo\elasticraft\models\Settings;
use dfo\elasticraft\utilities\ElasticraftUtility as ElasticraftUtilityUtility;
use dfo\elasticraft\widgets\ElasticraftWidget as ElasticraftWidgetWidget;
use dfo\elasticraft\models\ElasticDocument;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\services\Utilities;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\ElementEvent;
use craft\events\MoveElementEvent;
use craft\elements\Entry;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Peter Holme Obrestad
 * @package   Elasticraft
 * @since     1.0.0
 *
 * @property  ElasticraftServiceService $elasticraftService
 */
class Elasticraft extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Elasticraft::$plugin
     *
     * @var Elasticraft
     */
    public static $plugin;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Elasticraft::$plugin
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

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'dfo\elasticraft\console\controllers';
        }

        // Register our site routes
        Event::on(
            UrlManager::className(),
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'elasticraft/default';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::className(),
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTriggerCreateIndex'] = 'elasticraft/default/create-index';
                $event->rules['cpActionTriggerGetIndex'] = 'elasticraft/default/get-index';
                $event->rules['cpActionTriggerDeleteIndex'] = 'elasticraft/default/delete-index';
                $event->rules['cpActionTriggerIndexExists'] = 'elasticraft/default/index-exists';
            }
        );

        // Register our utilities
        Event::on(
            Utilities::className(),
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = ElasticraftUtilityUtility::class;
            }
        );

        // Register our widgets
        Event::on(
            Dashboard::className(),
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = ElasticraftWidgetWidget::class;
            }
        );

        // Register index events
        // Must use "AFTER", using "BEFORE" led to error when trying to save new entry.
        Event::on(
            Elements::className(),
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function (ElementEvent $event) {
                if ( $event->element instanceof craft\elements\Entry ) {
                    $doc = ElasticDocument::withEntry( $event->element );
                    return $this->elasticraftService->indexDocument($doc);
                }
            }
        );

        Event::on(
            Elements::className(),
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function (ElementEvent $event) {
                if ( $event->element instanceof craft\elements\Entry ) {
                    $doc = ElasticDocument::withEntry( $event->element );
                    return $this->elasticraftService->deleteDocument($doc);
                }
            }
        );

        Event::on(
            Elements::className(),
            // Structures::EVENT_AFTER_MOVE_ELEMENT,
            // ref https://github.com/craftcms/cms/issues/1828
            Elements::EVENT_AFTER_UPDATE_SLUG_AND_URI,
            function (ElementEvent $event) {
                if ( $event->element instanceof craft\elements\Entry ) {
                    // Not working: $doc = ElasticDocument::withEntry( $event->element );
                    // Fetch entry again to get URI:
                    $doc = ElasticDocument::withEntry( 
                        Entry::find()->id( $event->element->id )->one() 
                    );
                    return $this->elasticraftService->indexDocument($doc);
                }
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::className(),
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'elasticraft',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'elasticraft/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
