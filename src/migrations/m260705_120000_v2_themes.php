<?php

namespace transom\craftsitetint\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use transom\craftsitetint\models\Settings;

/**
 * m260705_120000_v2_themes migration.
 *
 * Migrates v1 flat per-site color overrides (`background`, `accent`,
 * `accent-color`, `accent-color-hover`, `accent-hover`) to the v2 grouped
 * theme shape (`sidebar`, `header`, `content`, `controls`), and drops the
 * deprecated hash-based `palette` fallback from project config.
 *
 * @author transom <accounts@transom.design>
 */
class m260705_120000_v2_themes extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $path = 'plugins.site-tint.settings';
        $rawSettings = $projectConfig->get($path, true);

        if (!is_array($rawSettings)) {
            // Nothing to migrate: a fresh install.
            return true;
        }

        // Project config stores site-UID-keyed arrays in a git-diff-friendly
        // packed form (`{__assoc__: [[key, value], ...]}`); get() returns
        // that raw form as-is, so it must be unpacked before use.
        $settings = ProjectConfigHelper::unpackAssociativeArray($rawSettings);

        if (empty($settings['overrides']) || !is_array($settings['overrides'])) {
            // Already on v2, or never had legacy overrides.
            return true;
        }

        $themes = [];
        foreach ($settings['overrides'] as $uid => $flat) {
            if (!is_array($flat)) {
                continue;
            }

            $theme = Settings::themeFromLegacyOverrides($flat);
            if (!empty($theme)) {
                $themes[$uid] = $theme;
            }
        }

        $settings['themes'] = $themes;
        unset($settings['overrides'], $settings['palette']);

        $projectConfig->muteEvents = true;
        $projectConfig->set($path, ProjectConfigHelper::packAssociativeArrays($settings), 'Migrate Site Tint settings to v2 grouped themes');
        $projectConfig->muteEvents = false;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
