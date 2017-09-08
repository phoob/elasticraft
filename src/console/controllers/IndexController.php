<?php
/**
 * Elasticraft plugin for Craft CMS 3.x
 *
 * Desc.
 *
 * @link      https://dfo.no
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 */

namespace dfo\elasticraft\console\controllers;

use dfo\elasticraft\Elasticraft;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;

use craft\elements\Entry;
use craft\elements\GlobalSet;

/**
 * Elasticsearch index utilities
 *
 * The first line of this class docblock is displayed as the description
 * of the Console Command in ./craft help
 *
 * Craft can be invoked via commandline console by using the `./craft` command
 * from the project root.
 *
 * Console Commands are just controllers that are invoked to handle console
 * actions. The segment routing is plugin/controller-name/action-name
 *
 * The actionIndex() method is what is executed if no sub-commands are supplied, e.g.:
 *
 * ./craft elasticraft/default
 *
 * Actions must be in 'kebab-case' so actionDoSomething() maps to 'do-something',
 * and would be invoked via:
 *
 * ./craft elasticraft/default/do-something
 *
 * @author    Peter Holme Obrestad
 * @package   Elasticraft
 * @since     1.0.0
 */
class IndexController extends Controller
{
    public $chunk_size = 20;

    // Public Methods
    // =========================================================================

    /**
     * Recreate Elasticsearch index
     *
     * @return mixed
     */
    public function actionRecreate()
    {
        echo "Deleting old index...";
        $result = Elasticraft::$plugin->elasticraftService->deleteIndex();
        if ( isset( $result['acknowledged'] ) )
            echo " done!";
        else echo " not found.";
        echo "\nCreating new index...";
        $result = Elasticraft::$plugin->elasticraftService->createIndex();
        if ( isset( $result['acknowledged'] ) ) {
            echo " done!";
        } else {
            echo " Index could not be created\n";
            foreach ($result as $k => $v) {
                echo "  " . $k . ": " . $v;
            }
            echo "\n";
            die();
        }
        $elements = array_merge(
            Entry::find()->all(),
            GlobalSet::find()->all()
        );
        echo "\nIndexing " . count($elements) . " elements.";

        $elements_chunks = array_chunk( $elements, $this->chunk_size );

        foreach ($elements_chunks as $elements) {
            foreach ($elements as $element) {
                Elasticraft::$plugin->elasticraftService->processElement( $element );
            }
            echo ".";
        }
        echo " done!\n";
    }

}
