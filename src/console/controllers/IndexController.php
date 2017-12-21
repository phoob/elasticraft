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
  * Index all elements, globalsets and drafts to Elasticsearch
  *
  * @return mixed
  */
  public function actionIndex()
  {
    $service = Elasticraft::$plugin->elasticraftService;

    // Create index if it does not exist yet
    if( !$service->indexExists() )
      $service->createIndex();

    // Index entries
    $entries = Entry::find()->all();
    printf("Indexing %d entries\n", count($entries));
    $this->indexElementsInChunks($entries);
    unset($entries);

    // Index globalsets
    $globalsets = GlobalSet::find()->all();
    printf("Indexing %d globalsets\n", count($globalsets));
    $this->indexElementsInChunks($globalsets);
    unset($globalsets);

    // Index entry drafts
    $drafts = $service->getCraftDrafts();
    printf("Indexing %d entry drafts\n", count($drafts));
    $this->indexElementsInChunks($drafts);
    unset($drafts);
    printf("Done!\n");

  }

  /**
  * Delete and recreate Elasticsearch index
  *
  * @return mixed
  */
  public function actionRecreate()
  {
    echo "Deleting old index...";
    if ( $result = Elasticraft::$plugin->elasticraftService->deleteIndex() )
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

    $this->indexElementsInChunks($elements);

    echo " done!\n";
  }

  private function indexElementsInChunks($elements, $chunk_size = null, $print_dots = true)
  {
    $service = Elasticraft::$plugin->elasticraftService;

    if ($chunk_size == null) $chunk_size = $this->chunk_size;
    $elements_chunks = array_chunk( $elements, $this->chunk_size );
    foreach ($elements_chunks as $elements) {
      foreach ($elements as $element) {
        if (is_a($element, 'craft\base\Element'))
          $service->processElement( $element );
        elseif (is_a($element, 'craft\models\EntryDraft'))
          $service->processEntryDraft( $element );
        elseif ($print_dots) echo "+";
      }
      if ($print_dots) echo ".";
    }
    if ($print_dots) echo "\n";
  }

}
