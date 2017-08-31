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
use craft\base\Element;

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

    const UNKNOWN_TYPE = 'unknown';
    const GLOBALSET_PREFIX = 'global-';

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

    public static function withElement( craft\base\Element $element )
    {
        $instance = new self();
        $instance->loadByElement( $element );
        return $instance;
    }

    protected function loadByElement( craft\base\Element $element )
    {
        // set type and id based on the type of element
        switch ( true ) {
            case $element instanceof craft\elements\Entry:
                $this->type = $element->section->handle;
                $this->id = $element->id;
                break;
            
            case $element instanceof craft\elements\GlobalSet:
                $this->type = self::GLOBALSET_PREFIX . $element->handle;
                $this->id = $element->handle;
                break;

            // to do: add more element types.

            default:
                return false;
        }

        // set document body if one is defined in the config.
        if( isset( $this->transformers[$this->type] ) ) {
            $this->body = $this->transformers[$this->type]->transform($element);
        }

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
