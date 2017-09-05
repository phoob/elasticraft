<?php
/**
 * Elasticraft plugin for Craft CMS 3.x
 *
 * Desc.
 *
 * @link      https://dfo.no
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 */

namespace dfo\elasticraft\services;

use dfo\elasticraft\Elasticraft;
use dfo\elasticraft\models\ElasticDocument;
use Elasticsearch\ClientBuilder;
use yii\helpers\Json;

use Craft;
use craft\base\Component;
use craft\elements\Entry;

/**
 * ElasticraftService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Peter Holme Obrestad
 * @package   Elasticraft
 * @since     1.0.0
 */
class ElasticraftService extends Component
{

    public $client;
    public $indexName;
    public $indexOptions;

    public function init()
    {
        parent::init();

        $this->client =  $this->_getClient();
        $this->indexName = $this->_getIndexName();
        $this->indexOptions = $this->_getIndexOptions();
    }

    // Public Methods
    // =========================================================================
    // From any other plugin file, call them like this:
    //     Elasticraft::$plugin->elasticraftService->method()

    /**
     * Tries to contact Elasticsearch server.
     *
     * @return bool
     */
    public function ping(): bool
    {
        $params = [ ];
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $response = $this->client->ping($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        return $response;
    }

    /**
     * Checks if the index exists on the Elasticsearch server.
     *
     * @return bool
     */
    public function indexExists(): bool
    {
        $params = [ 'index' => $this->indexName ];
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $response = $this->client->indices()->exists($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        return $response;
    }

    /**
     * Creates a new index on the Elasticsearch server with a simple mapping.
     *
     * @return array
     */
    public function createIndex(): array
    {
        $params  = [ 
            'index' => $this->indexName,
            'body' => $this->indexOptions,
        ];
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $response = $this->client->indices()->create($params);
        } catch (\Exception $e) {
            return  Json::decode($e->getMessage());
        }
        return $response;
    }

    /**
     * Recreates the index and indexes all elements
     *
     * @return array
     */
    public function recreateIndex(): array
    {
        $this->_deleteIndex();
        $this->createIndex();
        return $this->_indexAllElements();
    }

    /**
     * Get basic info about the index.
     *
     * @return array
     */
    public function getIndex(): array
    {
        $params = ['index' => $this->indexName];
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try { 
            $response = $this->client->indices()->get($params); 
        } catch (\Exception $e) { 
            return Json::decode($e->getMessage()); 
        }
        return $response;
    }

    /**
     * Get number of documents aggregated by document type
     *
     * @return array
     */
    public function getDocumentCount(): array 
    {
        $params = [
            'index' => $this->indexName,
            'body' => [
                'size' => 0,
                'aggregations' => [
                    'count_by_type' => [
                        'terms' => [
                            'field' => '_type'
                        ]
                    ]
                ]
            ]
        ];
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $response = $this->client->indices()->refresh(['index' => $this->indexName ]);
            $response = $this->client->search($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        return [
            'total' => $response['hits']['total'],
            'count_by_type' => $response['aggregations']['count_by_type']['buckets'],
        ];
    }

    /**
     * Gets a doc from Elasticsearch based on an entry
     *
     * @param Entry $entry
     *
     * @return int
     */
    public function getDocWithEntry(Entry $entry)
    {
        $params = [
            'index' => $this->indexName,
            'type' => $entry->section->handle,
            'id' => $entry->id
        ];
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $response = $this->client->get($params);
        } catch (\Exception $e) {
            return false;
        }

        return $response;
    }

    /**
     * Perform a bulk request to the Elasticsearch server.
     *
     * @param array $params Parameters for the bulk request
     *
     * @return array
     */
    public function bulkProcess(array $params): array
    {
        if( !$this->indexExists() ) 
            $this->createIndex();
        try {
            $response = $this->client->bulk($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $response;
    }

    /**
     * Process many documents with the same action.
     *
     * @param array  $docs   Array of ElasticDocuments
     * @param string $action Name of action
     *
     * @return array
     */
    public function processDocuments(array $docs, string $action='index'): array
    {
        $params = $this->createBulkParams();
        $responses = [];

        // send in batches of 1000 docs
        foreach ($docs as $i => $doc) {
            $params = $this->addDocToBulkParams($params, $doc, $action);
            if ($i % 1000 == 0) {
                $responses[] = $this->bulkProcess($params);
                $params['body'] = [];
            }
        }

        // send the rest
        if (!empty($params['body'])) {
            $responses[] = $this->bulkProcess($params);
        }

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $responses;
    }

    /**
     * Process one document.
     *
     * @param ElasticDocument $doc    Document to process
     * @param string          $action Name of action
     *
     * @return array
     */
    public function processDocument(ElasticDocument $doc, string $action = 'index'): array
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->processDocuments([$doc], $action);
    }


    // Helper methods

    /**
     * Create array with params for a bulk request.
     *
     * @return array
     */
    public function createBulkParams(): array
    {
        $params = [
            'index' => $this->indexName,
            'body' => []
        ];
        return $params;
    }

    /**
     * Add document to process to a params array.
     *
     * @param array           $params Array created using createBulkParams()
     * @param ElasticDocument $doc    Document to add
     * @param string          $action Name of action
     *
     * @return array
     */
    public function addDocToBulkParams(array $params, ElasticDocument $doc, string $action = 'index'): array
    {
        $params['body'][] = [
            $action => [
                '_type' => $doc->type,
                '_id' => $doc->id,
            ]
        ];
        if($action == 'index') {
            $params['body'][] = $doc->body;
        }
        return $params;
    }

    // Private methods
    // =========================================================================

    private function _getClient(): \Elasticsearch\Client
    {
        try {
            $client = ClientBuilder::create()
                ->setHosts( $this->_getElasticHosts() )
                ->build();
        } catch (\Exception $e) {
            throw $e;
        }
        return $client;
    }

    private function _getElasticHosts(): array
    {
        $uris = array_filter(
            explode( ',', Elasticraft::$plugin->getSettings()->hosts ), 
            function($uri){ return filter_var($uri, FILTER_VALIDATE_URL); }
        );
        if (empty($uris))
            return ['http://localhost:9200'];
        return $uris;
    }

    private function _getIndexName(): string
    {
        return Elasticraft::$plugin->getSettings()->indexName;
    }

    private function _getIndexOptions(): array
    {
        return Elasticraft::$plugin->getSettings()->indexOptions;
    }

    private function _deleteIndex(): array
    {
        $params = ['index' => $this->indexName];
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $response = $this->client->indices()->delete($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        return $response;
    }

    private function _indexAllElements(): array
    {
        $elements = Entry::find()
            ->all();
        $docs = array_map( function($element) {
            return ElasticDocument::withElement( $element );
        }, $elements );
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->processDocuments($docs);
    }


}
