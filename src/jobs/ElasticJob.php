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

    /**
     * Some attribute
     *
     * @var string
     */
    public $elements;
    public $action = 'index';

    // Public Methods
    // =========================================================================

    public function execute($queue)
    {
        // Allow $elements to be single element
        if ( !is_array( $this->elements ) ) $this->elements = [$this->elements];

        foreach ( $this->elements as $i => $element ) {
            $this->setProgress($queue, $i / count( $this->elements ) );
            if ( $doc = ElasticDocument::withElement( $element ) ) {
                Elasticraft::$plugin->elasticraftService->processDocument( $doc, $this->action );
            }
        }

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
