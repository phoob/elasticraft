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
    // Public Properties
    // =========================================================================

    public $index = [];
    public $delete = [];

    // Public Methods
    // =========================================================================

    public function execute($queue)
    {
        $params = Elasticraft::$plugin->elasticraftService->createBulkParams();

        foreach ( $this->index as $element ) {
            if ( $doc = ElasticDocument::withElement( $element ) ) {
                $params = Elasticraft::$plugin->elasticraftService->addDocToBulkParams($params, $doc, 'index' );
            }
        }

        foreach ( $this->delete as $element ) {
            if ( $doc = ElasticDocument::withElement( $element ) ) {
                $params = Elasticraft::$plugin->elasticraftService->addDocToBulkParams($params, $doc, 'delete' );
            }
        }

        Elasticraft::$plugin->elasticraftService->bulkProcess($params);

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
