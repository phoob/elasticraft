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
        $elements = [];
        foreach ($this->elements as $v) {
            if( $v instanceof craft\base\Element ) {
                $elements[] = $v;
            } else if( $v instanceof craft\elements\db\ElementQueryInterface ) {
                $elements = array_merge( $elements, $v->all() );
            }
        }

        $elementCount = count($elements);
        $service = Elasticraft::$plugin->elasticraftService;

        // Create index if it does not exist yet
        if( !$service->indexExists() )
            $service->createIndex();

        // Save time in case we need it after processing
        $now = time();

        // Process elements and set progress
        for( $i=0; $i < $elementCount; $i++ ) { 
            // Set progress counter
            $this->setProgress($queue, $i / $elementCount);
            // Process element
            $service->processElement(
                $elements[$i], 
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
