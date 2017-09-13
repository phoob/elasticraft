<?php

namespace dfo\elasticraft\transformers;

use League\Fractal\TransformerAbstract;
use craft\helpers\UrlHelper;
use craft\fields\data\RichTextData;
use craft\base\Element;

abstract class BaseTransformer extends TransformerAbstract {
    /**
     * Helper function that prepares the markup from a rich text field.__construct
     *
     * 1. Gets parsed content (entity references is replaced with actual URLs)
     * 2. removes domain from link hrefs to make it possible to handle on the client side.
     */
    protected function prepareRichText(RichTextData $text) {
        return str_replace(UrlHelper::siteUrl(), '/', $text->getParsedContent());
    }

}