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
    public $elementType;
    public $action = 'index';

    // Public Methods
    // =========================================================================

    public function execute($queue)
    {
        if (isset($this->elementType))
            $this->elements = $this->elementType::find();
        $elementCount = count($this->elements);
        for ($i=0; $i < $elementCount; $i++) { 
            // Set progress counter
            $this->setProgress($queue, $i / $elementCount);
            // Process element
            Elasticraft::$plugin->elasticraftService->processElement(
                $this->elements[$i], 
                $this->action
            );
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
