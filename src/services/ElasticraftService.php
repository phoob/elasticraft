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
use craft\base\Element;

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
        try {
            $response = $this->client->ping($params);
        } catch (\Exception $e) {
            throw $e;
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
        try {
            $response = $this->client->indices()->exists($params);
        } catch (\Exception $e) {
            throw $e;
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
        try {
            $response = $this->client->indices()->create($params);
        } catch (\Exception $e) {
            throw $e;
        }
        return $response;
    }

    /**
     * Get basic info about the index.
     *
     * @return array
     */
    public function getIndex(): array
    {
        $params = ['index' => $this->indexName];
        try { 
            $response = $this->client->indices()->get($params); 
        } catch (\Exception $e) { 
            if ($e instanceof \Elasticsearch\Common\Exceptions\Missing404Exception)
                return [];
            throw $e;
        }
        return $response;
    }

    /**
     * Delete index.
     *
     * @return bool
     */
    public function deleteIndex(): bool
    {
        $params = ['index' => $this->indexName];
        try {
            $response = $this->client->indices()->delete($params);
        } catch (\Exception $e) {
            if ($e instanceof \Elasticsearch\Common\Exceptions\Missing404Exception)
                return false;
            throw $e;
        }
        return true;
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
                            'field' => 'type.keyword'
                        ]
                    ]
                ]
            ]
        ];
        try {
            $response = $this->client->indices()->refresh(['index' => $this->indexName ]);
            $response = $this->client->search($params);
        } catch (\Exception $e) {
            if ($e instanceof \Elasticsearch\Common\Exceptions\Missing404Exception)
                return [];
            throw $e;
        }
        return $response;
    }

    /**
     * Gets a doc from Elasticsearch based on an entry
     *
     * @param Entry $entry
     *
     * @return int
     */
    public function getDocWithElement(Element $element)
    {
        $params = [
            'index' => $this->indexName,
            'type' => ElasticDocument::ELEMENT_DOCUMENT_TYPE,
            'id' => $element->id
        ];
        try {
            $response = $this->client->get($params);
        } catch (\Exception $e) {
            if ($e instanceof \Elasticsearch\Common\Exceptions\Missing404Exception)
                return [];
            throw $e;
        }
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
        $params = $this->_createBulkParams();
        $responses = [];

        // send in batches of 1000 docs
        foreach ($docs as $i => $doc) {
            $params = $this->addDocToBulkParams($params, $doc, $action);
            if ($i % 1000 == 0) {
                $responses[] = $this->_bulkProcess($params);
                $params['body'] = [];
            }
        }

        // send the rest
        if (!empty($params['body'])) {
            $responses[] = $this->_bulkProcess($params);
        }

        return $responses;
    }

    /**
     * Process one document.
     *
     * @param ElasticDocument $doc    Document to process
     * @param string          $action Name of action ('index' or 'delete')
     *
     * @return array
     */
    public function processDocument(ElasticDocument $doc, string $action = 'index'): array
    {
        $params = [
            'index' => $this->indexName,
            'type' => $doc->type,
            'id' => $doc->id,
        ];
        try {
            switch ($action) {
                case 'index':
                    $params['body'] = $doc->body;
                    $response = $this->client->index($params);
                    break;
                case 'delete':
                    $response = $this->client->delete($params);
                    break;
                default:
                    throw new \Exception("Action must be either 'index' or 'delete'.", 1);
                    break;
            }
        } catch (\Exception $e) {
            if ($e instanceof \Elasticsearch\Common\Exceptions\Missing404Exception)
                return [];
            throw $e;
        }
        return $response;
    }

    /**
     * Process one element.
     *
     * @param Element $doc    Document to process
     * @param string          $action Name of action
     *
     * @return array
     */
    public function processElement(craft\base\Element $element, string $action = 'index'): array
    {
        if ( $doc = ElasticDocument::withElement( $element ) ) {
            return $this->processDocument($doc, $action);
        }
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

    /**
     * Create array with params for a bulk request.
     *
     * @return array
     */
    private function _createBulkParams(): array
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
    private function _addDocToBulkParams(array $params, ElasticDocument $doc, string $action = 'index'): array
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

    /**
     * Perform a bulk request to the Elasticsearch server.
     *
     * @param array $params Parameters for the bulk request
     *
     * @return array
     */
    private function _bulkProcess(array $params): array
    {
        if( !$this->indexExists() ) 
            $this->createIndex();
        try {
            $response = $this->client->bulk($params);
        } catch (\Exception $e) {
            throw $e;
        }
        return $response;
    }


}
