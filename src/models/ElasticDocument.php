<?php
/**
 * Elasticraft plugin for Craft CMS 3.x
 *
 * Desc.
 *
 * @link      https://dfo.no
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 */

namespace dfo\elasticraft\models;

use dfo\elasticraft\Elasticraft;

use Craft;
use craft\base\Model;
use craft\elements\Entry;

/**
 * ElasticDocument Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Peter Holme Obrestad
 * @package   Elasticraft
 * @since     1.0.0
 */
class ElasticDocument extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some model attribute
     *
     * @var string
     */
    public $id;
    public $type;
    public $body = [];
    public $dateCreated;
    public $dateUpdated;
    // parent children relationships requires different document types in Elasticsearch for now. 
    //public $parent;
    //public $routing;

    protected $transformers = []; 

    public function init()
    {
        parent::init();

        $this->transformers = Elasticraft::$plugin
            ->getInstance()
            ->getSettings()
            ->transformers;
    }
    // Public Methods
    // =========================================================================

    public static function withEntry( craft\elements\Entry $entry )
    {
        $instance = new self();
        $instance->loadByEntry( $entry );
        return $instance;
    }

    protected function loadByEntry( craft\elements\Entry $entry )
    {
        $this->type = $entry->getType()->handle;
        $this->id = $entry->id;
        if ( isset( $this->transformers[$this->type] ) ) {
            $this->body = $this->transformers[$this->type]->transform($entry);
        }
        $this->body['elastic']['dateCreated'] = $entry->dateCreated->format('U');
        $this->body['elastic']['dateUpdated'] = $entry->dateUpdated->format('U');
        $this->body['elastic']['dateIndexed'] = time();
    }

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['id', 'type'], 'string'],
            [['body'], 'array'],
            [['id', 'type'], 'required'],
        ];
    }
}
