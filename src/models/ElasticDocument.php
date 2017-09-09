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

    const ELEMENT_DOCUMENT_TYPE = 'element';

    // Public Properties
    // =========================================================================

    /**
     * Some model attribute
     *
     * @var string
     */
    public $type;
    public $id;
    public $body = [];

    protected $transformers = [];

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        $this->transformers = Elasticraft::$plugin
            ->getInstance()
            ->getSettings()
            ->transformers;
    }

    public static function withElement( Element $element )
    {
        $instance = new self();
        $instance->_loadByElement( $element );
        return $instance;
    }

    // Helper methods
    // =========================================================================

    public static function elementHasTransformer( Element $element): bool
    {
        $instance = new self();
        $transformer = $instance->_getTransformerForElement( $element );
        if( isset( $instance->transformers[$transformer] ) ) 
            return true;
        return false;
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
            [['id', 'type'], 'required'],
        ];
    }

    // Private Methods
    // =========================================================================

    private function _loadByElement( Element $element )
    {
        $this->type = self::ELEMENT_DOCUMENT_TYPE;
        $this->id = $element->id;

        $transformer = $this->_getTransformerForElement( $element );

        // set document body if one is defined in the config.
        if( isset( $this->transformers[$transformer] ) ) 
            $this->body = $this->transformers[$transformer]->transform($element);

        // set body['type'] if it is not alreade defined in transformer
        if (!isset($this->body['type']))
            $this->body['type'] = $transformer;
    }

    private function _getTransformerForElement( Element $element ): string
    {
        switch (get_class($element)) {
            case 'craft\elements\Entry':
                return $element->section->handle;
            case 'craft\elements\GlobalSet':
                return $element->handle;
            default:
                return 'default';
        }
    }

}
