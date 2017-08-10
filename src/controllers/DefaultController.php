<?php
/**
 * Elasticraft plugin for Craft CMS 3.x
 *
 * Desc.
 *
 * @link      https://dfo.no
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 */

namespace dfo\elasticraft\controllers;

use dfo\elasticraft\Elasticraft;
use dfo\elasticraft\models\ElasticDocument;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use yii\helpers\Json;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Peter Holme Obrestad
 * @package   Elasticraft
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = [
        'index'
    ];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to:
     * actions/elasticraft/default
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the DefaultController actionIndex() method';

        return $result;
    }

    /**
     * Handle requests going to:
     * actions/elasticraft/default/ping
     * actions/elasticraft/default/create-index
     * etc.
     *
     * @return mixed
     */
    public function actionPing() { return Elasticraft::$plugin->elasticraftService->ping(); }
    public function actionCreateIndex() { return Elasticraft::$plugin->elasticraftService->createIndex(); }
    public function actionGetIndex() { return Elasticraft::$plugin->elasticraftService->getIndex(); }
    public function actionDeleteIndex() { return Elasticraft::$plugin->elasticraftService->deleteIndex(); }
    public function actionIndexExists() { return Elasticraft::$plugin->elasticraftService->indexExists(); }
    public function actionIndexStats() { return Elasticraft::$plugin->elasticraftService->indexStats(); }
    public function actionReindex() { return Elasticraft::$plugin->elasticraftService->indexAllDocuments(); }

    public function actionGetTransformedEntries($limit = 10)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $entries = $this->actionGetEntries($limit);
        $entries = array_map(function($entry){
            return ElasticDocument::withEntry( $entry );
        }, $entries);
        return $entries;
    }

    public function actionGetEntries($limit = 10)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $entries = Entry::find()
            ->all();
        return $entries;
    }

}
