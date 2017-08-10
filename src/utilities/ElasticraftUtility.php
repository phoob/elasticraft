<?php
/**
 * Elasticraft plugin for Craft CMS 3.x
 *
 * Desc.
 *
 * @link      https://dfo.no
 * @copyright Copyright (c) 2017 Peter Holme Obrestad
 */

namespace dfo\elasticraft\utilities;

use dfo\elasticraft\Elasticraft;
use dfo\elasticraft\assetbundles\elasticraftutilityutility\ElasticraftUtilityUtilityAsset;

use Craft;
use craft\base\Utility;

/**
 * Elasticraft Utility
 *
 * Utility is the base class for classes representing Control Panel utilities.
 *
 * https://craftcms.com/docs/plugins/utilities
 *
 * @author    Peter Holme Obrestad
 * @package   Elasticraft
 * @since     1.0.0
 */
class ElasticraftUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * Returns the display name of this utility.
     *
     * @return string The display name of this utility.
     */
    public static function displayName(): string
    {
        return Craft::t('elasticraft', 'Elasticraft');
    }

    /**
     * Returns the utility’s unique identifier.
     *
     * The ID should be in `kebab-case`, as it will be visible in the URL (`admin/utilities/the-handle`).
     *
     * @return string
     */
    public static function id(): string
    {
        return 'elasticraft-utility';
    }

    /**
     * Returns the path to the utility's SVG icon.
     *
     * @return string|null The path to the utility SVG icon
     */
    public static function iconPath()
    {
        return Craft::getAlias("@dfo/elasticraft/assetbundles/elasticraftutilityutility/dist/img/ElasticraftUtility-icon.svg");
    }

    /**
     * Returns the number that should be shown in the utility’s nav item badge.
     *
     * If `0` is returned, no badge will be shown
     *
     * @return int
     */
    public static function badgeCount(): int
    {
        if (Elasticraft::$plugin->elasticraftService->ping() === false
          ||Elasticraft::$plugin->elasticraftService->indexExists() === false
        ) return 1;
        return 0;
    }

    /**
     * Returns the utility's content HTML.
     *
     * @return string
     */
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(ElasticraftUtilityUtilityAsset::class);

        return Craft::$app->getView()->renderTemplate(
            'elasticraft/_components/utilities/ElasticraftUtility_content',
            [
                'indexName'       => Elasticraft::$plugin->elasticraftService->indexName,
            ]
        );
    }
}
