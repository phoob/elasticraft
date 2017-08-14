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

    public function init()
    {
        parent::init();

        $this->client =  $this->getClient();
        $this->indexName = $this->getIndexName();

        # Since all returns are supposed to be JSON, add this here.
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    // Public Methods
    // =========================================================================
    /**
     * Elasticsearch Client
     *
     * From any other plugin file, call it like this:
     *
     *     Elasticraft::$plugin->elasticraftService->client()
     *
     * @return mixed
     */

    public function ping()
    {
        $params = [ ];
        try {
            $response = $this->client->ping($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        return $response;
    }

    public function indexExists()
    {
        $params = [ 'index' => $this->indexName ];
        try {
            $response = $this->client->indices()->exists($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        return $response;
    }

    public function createIndex()
    {
        $params  = [ 
            'index' => $this->indexName,
            'body' => [
                'mappings' => [
                    '_default_' => [
                        'properties' => [
                            'elastic.dateCreated' => [
                                'type' => 'date',
                                'format' => 'epoch_second'
                            ],
                            'elastic.dateUpdated' => [
                                'type' => 'date',
                                'format' => 'epoch_second'
                            ],
                            'elastic.dateIndexed' => [
                                'type' => 'date',
                                'format' => 'epoch_second'
                            ]
                        ],
                    ],
                ],
            ],
        ];
        try {
            $response = $this->client->indices()->create($params);
        } catch (\Exception $e) {
            return  Json::decode($e->getMessage());
        }
        return $response;
    }

    public function getIndex()
    {
        $params  = [ 'index' => $this->indexName ];
        try { 
            $response = $this->client->indices()->get($params); 
        } catch (\Exception $e) { 
            return Json::decode($e->getMessage()); 
        }
        return $response;
    }

    public function deleteIndex()
    {
        $params = [ 'index' => $this->indexName ];
        try {
            $response = $this->client->indices()->delete($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        return $response;
    }

    public function indexStats()
    {
        $params = [ 'index' => $this->indexName ];
        try {
            $response = $this->client->indices()->refresh($params);
            $response = $this->client->indices()->stats($params);
        } catch (\Exception $e) {
            return Json::decode($e->getMessage());
        }
        return $response;
    }

    public function indexDocument(ElasticDocument $doc)
    {
        return $this->bulk([$doc]);
    }

    public function indexDocuments(array $docs)
    {
        return $this->bulk($docs);
    }

    public function indexAllDocuments()
    {
        $entries = Entry::find()
            ->all();
        $docs = array_map( function($entry) {
            return ElasticDocument::withEntry( $entry );
        }, $entries );
        return $this->indexDocuments($docs);
    }

    public function deleteDocument(ElasticDocument $doc)
    {
        return $this->bulk([$doc], $action='delete');
    }

    // https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_indexing_documents.html
    protected function bulk(array $docs, string $action='index')
    {
        // filter out non-ElasticDocuments
        $docs = array_filter($docs, function($doc) {
            return is_a( $doc, 'dfo\elasticraft\models\ElasticDocument' );
        });

        if( $docs && !$this->indexExists() )
            $this->createIndex();

        $params = [ 
            'index' => $this->indexName,
            'body' => [],
        ];
        $responses = [];

        foreach ($docs as $i => $doc) {
            $params['body'][] = [
                $action => [
                    '_type' => $doc->type,
                    '_id' => $doc->id,
                ]
            ];
            if ($action == 'index') {
                $params['body'][] = $doc->body;
            }

            // send in batches of 1000 docs
            if ($i % 1000 == 0) {
                try {
                    $response = $this->client->bulk($params);
                } catch (\Exception $e) {
                    return Json::decode($e->getMessage());
                }
                $responses[] = $response;

                $params['body'] = [];

                unset($response);
            }
        }
        // send the rest
        if (!empty($params['body'])) {
            try {
                $response = $this->client->bulk($params);
            } catch (\Exception $e) {
                return Json::decode($e->getMessage());
            }
            $responses[] = $response;
        }

        return $responses;
    }

    // Private methods
    // =========================================================================

    private function getClient(): \Elasticsearch\Client
    {
        try {
            $client = ClientBuilder::create()
                ->setHosts( $this->getElasticHosts() )
                ->build();
        } catch (\Exception $e) {
            throw new \Exception("No Elasticsearch hosts are defined", 1);
        }
        return $client;
    }

    private function getElasticHosts(): array
    {
        $uris = array_filter(
            explode( ',', Elasticraft::$plugin->getSettings()->hosts ), 
            function($uri){ return filter_var($uri, FILTER_VALIDATE_URL); }
        );
        if (empty($uris))
            return ['http://localhost:9200'];
        return $uris;
    }

    private function getIndexName(): string
    {
        return Elasticraft::$plugin->getSettings()->indexName;
    }

}
