<?php
/**
 * Elasticraft plugin for Craft CMS 3.x
 *
 * Desc.
 *
 * @link      https://dfo.no
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 */

namespace dfo\elasticraft\jobs;

use dfo\elasticraft\Elasticraft;
use dfo\elasticraft\models\ElasticDocument as ElasticDocument;

use Craft;
use craft\queue\BaseJob;
use craft\elements\Entry;
use craft\elements\GlobalSet;

/**
 * ElasticIndex Task
 *
 * @author    Peter Holme Obrestad
 * @package   Elasticraft
 * @since     1.0.0
 */
class ElasticJob extends BaseJob
{
    // Properties
    // =========================================================================

    public $elements = [];
    public $action = 'index';
    public $deleteStale = false;

    // Public Methods
    // =========================================================================

    public function execute($queue)
    {
        $elementQueries = array_filter($this->elements, function($v) {
            return $v instanceof craft\elements\db\ElementQueryInterface ?: false;
        });
        $this->elements = array_filter($this->elements, function($v) {
            return $v instanceof craft\base\Element ?: false;
        });

        foreach ($elementQueries as $query) {
            $this->elements = array_merge(
                $this->elements, 
                $query->all()
            );
        }

        $elementCount = count($this->elements);
        $service = Elasticraft::$plugin->elasticraftService;

        // Create index if it does not exist yet
        if( !$service->indexExists() )
            $service->createIndex();

        // Save time for in case we need it after processing
        $now = time();

        // Process elements and set progress
        for( $i=0; $i < $elementCount; $i++ ) { 
            // Set progress counter
            $this->setProgress($queue, $i / $elementCount);
            // Process element
            $service->processElement(
                $this->elements[$i], 
                $this->action
            );
        }

        // Delete documents in index that weren't indexed in this job
        if( $this->deleteStale )
            $service->deleteDocumentsOlderThan($now);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('elasticraft', 'Indexing to Elasticsearch');
    }

}
