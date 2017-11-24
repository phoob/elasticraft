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
use dfo\elasticraft\models\ElasticDocument as ElasticDocument;
use dfo\elasticraft\jobs\ElasticJob as ElasticJob;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\models\EntryDraft;
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
        $result = 'Welcome to Elasticraft.';

        return $this->asJson($result);
    }

    /**
     * Handle requests going to:
     * actions/elasticraft/default/ping
     * actions/elasticraft/default/create-index
     * etc.
     *
     * @return mixed
     */
    public function actionPing() 
    { 
        $result = Elasticraft::$plugin->elasticraftService->ping();
        return $this->asJson($result); 
    }

    public function actionIndexExists() 
    { 
        $result = Elasticraft::$plugin->elasticraftService->indexExists();
        return $this->asJson($result);
    }

    public function actionReindex()
    {
        Craft::$app->queue->push(new ElasticJob([
            'elements' => [
                Entry::find(),
                GlobalSet::find(),
                $this->_getDraftsQuery()
            ],
            'deleteStale' => true,
            'description' => 'Reindexing all entries and globals and deleting stale',
        ]));
        return $this->asJson(true);
    }

    public function actionRecreateIndex() 
    { 
        Elasticraft::$plugin->elasticraftService->deleteIndex();
        Craft::$app->queue->push(new ElasticJob([
            'elements' => [
                Entry::find(),
                GlobalSet::find(),
                $this->_getDraftsQuery()
            ],
            'description' => 'Indexing all entries and globals',
        ]));
        return $this->asJson(true);
    }

    public function actionGetDocumentCount() 
    { 
        $response = Elasticraft::$plugin->elasticraftService->getDocumentCount(); 
        return $this->asJson([
            'total' => $response['hits']['total'],
            'count_by_type' => $response['aggregations']['count_by_type']['buckets'],
        ]);
    }

    public function actionReleaseStaleJobs()
    {
        $response = Craft::$app->queue->getJobInfo();
        $jobs = [
            'kept' => [],
            'released' => []
        ];
        foreach ($response as $job) {
            if ($job['status'] > 1) {
                Craft::$app->queue->release($job['id']);
                $jobs['released'][] = $job;
            } else {
                $jobs['kept'][] = $job;
            }
        }
        return $this->asJson($jobs);
    }

    private function _getDraftsQuery($siteId = null): craft\db\Query
    {
        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->getPrimarySite()->id;
        }

        return (new craft\db\Query())
            ->select([
                'id',
                'entryId',
                'sectionId',
                'creatorId',
                'siteId',
                'name',
                'notes',
                'data',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from(['{{%entrydrafts}}'])
            ->where(['siteId' => $siteId]);
    }

}
