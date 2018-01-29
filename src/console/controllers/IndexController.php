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

    $elements = array_merge(
      Entry::find()->all(),
      GlobalSet::find()->all(),
      $service->getCraftDrafts()
    );
    $this->stdout(sprintf("Indexing %d entries, globalsets and drafts\n", count($elements)), Console::BOLD);

    // Save time in case we need it after processing
    $now = microtime(true);
    $slow = [];
    Console::startProgress(0,count($elements));
    foreach ($elements as $index => $element) {
      $n = microtime(true);
      if (is_a($element, 'craft\models\EntryDraft')) {
        $service->processEntryDraft( $element );
      } elseif (is_a($element, 'craft\base\Element')) {
        $service->processElement( $element );
      }
      $d = (microtime(true) - $n);
      if ($d > 0.5) $slow[] = [
        'time' => $d,
        'title' => $element->title,
      ];
      Console::updateProgress($index + 1, count($elements));
    }
    Console::endProgress();
    
    $this->stdout("Done in " . round((microtime(true) - $now),3) . " seconds.\n");
    if ($slow) {
      $this->stdout("These elements indexed slowly:\n");
      foreach ($slow as $e) {
        $this->stdout(round($e['time'],3) . "\t" . $e['title'] . "\n");
      }
    }
  }

  /**
  * Delete and recreate Elasticsearch index
  *
  * @return mixed
  */
  public function actionRecreate()
  {
    // Delete old index
    Elasticraft::$plugin->elasticraftService->deleteIndex();
    $this->actionIndex();
  }

}
