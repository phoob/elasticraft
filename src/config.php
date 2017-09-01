<?php
/**
 * Elasticraft plugin for Craft CMS 3.x
 *
 * Craft integration with Elasticsearch
 *
 * @link      https://dfo.no
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 */

/**
 * Elasticraft config.php
 *
 * This file exists only as a template for the Elasticraft settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'elasticraft.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

// Require transformers to be used
# require_once __DIR__ . '/../src/transformers/page.php';

return [

  // Comma separated list of Elasticsearch hosts, eg. "http://localhost:9200,https://user:pass@elastic.domain.com:443"
  'hosts' => getenv('ELASTIC_HOSTS'),

  // The name of the Elasticsearch index to be used
  'indexName' => getenv('ELASTIC_INDEX_NAME'),

  // IndexOptions to pass to Elasticsearch when creating index
  'indexOptions' => [
    'mappings' => [
      '_default_' => [
        'properties' => [
          'date' => [
            'properties' => [
              'indexed' => [
                'type' => 'date',
                'format' => 'epoch_second'
              ],
              'created' => [
                'type' => 'date',
                'format' => 'epoch_second'
              ],
              'updated' => [
                'type' => 'date',
                'format' => 'epoch_second'
              ],
            ],
          ],
        ],
      ],
    ],
  ],

  // Path to date indexed for entries in elasticsearch, use a dot notated string
  'entryDateIndexedFieldPath' => '_source.date.indexed',

  // Mapping of which page transformers should be used for which element types
  'transformers' => [

  // 'page' => new PageTransformer(),
  ],
];
