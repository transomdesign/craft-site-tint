<?php

namespace transom\craftsitetint\assets\settings;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for the Site Tint plugin settings page: preset galleries,
 * live mockup previews, and the per-site revert-to-native control.
 *
 * @author transom <accounts@transom.design>
 */
class SettingsAsset extends AssetBundle
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'settings.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'settings.js',
    ];
}
