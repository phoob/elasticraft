# Elasticraft plugin for Craft CMS 3.x

Elasticsearch plugin for Craft 3

## Installation

To install Elasticraft, follow these steps:

1. Download & unzip the file and place the `elasticraft` directory into your `craft/plugins` directory
2.  -OR- do a `git clone https://github.com/phoob/elasticraft.git` directly into your `craft/plugins` folder.  You can then update it with `git pull`
3.  -OR- install with Composer via `composer require phoob/elasticraft`
4. Install plugin in the Craft Control Panel under Settings > Plugins
5. The plugin folder should be named `elasticraft` for Craft to see it.  GitHub recently started appending `-master` (the branch name) to the name of the folder for zip file downloads.

Elasticraft works on Craft 3.x.

## Elasticraft Overview

Provides basic functionality to index entries and other craft elements to an elasticsearch server.

The plugin uses the [Elasticesearch PHP Client](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html). 

## Configuring Elasticraft

Copy `src/config.php` to `craft/config/elasticraft.php` and configure hosts and pagetransformers for the elements you wish to index to Elasticsearch.

Pagetransformers should inherit `League\Fractal\TransformerAbstract` – see examples in `./transformers/`.

If you want, put the server details in your `.env`.:

```
ELASTIC_HOSTS=localhost:9200
ELASTIC_INDEX_NAME=craftdev
```

## Using Elasticraft

Elasticraft indexes elements (and their descendants and ancestors) when you save or move an element. It also deletes the element from elasticsearch when the element is deleted in Craft.

## Elasticraft Roadmap

* Make it more stable and test it. It has not been used in a production environment yet.
* Please note that the settings page and the widget is not in use currently. All settings are configured in the config file.

Brought to you by [Peter Holme Obrestad](https://dfo.no)
