<?php

namespace transom\craftsitetint\tests\unit;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\models\Settings;

/**
 * Unit tests for Settings::resolvedThemeForSite().
 *
 * Covers the three resolution paths: a saved v2 theme, an in-memory
 * fallback to legacy v1 overrides (the window between deploy and the
 * plugin's install migration running), and no theme at all.
 */
class SiteTintSettingsTest extends TestCase
{
    private const UID = 'test-uid-123';

    public function testReturnsSavedV2Theme(): void
    {
        $settings = new Settings();
        $settings->themes = [self::UID => ['sidebar' => ['bg' => '#8c2f3f']]];

        $theme = $settings->resolvedThemeForSite(self::UID);

        $this->assertSame(['sidebar' => ['bg' => '#8c2f3f']], $theme);
    }

    public function testFallsBackToLegacyOverridesWhenNoV2Theme(): void
    {
        $settings = new Settings();
        $settings->overrides = [self::UID => ['accent' => '#ff0000']];

        $theme = $settings->resolvedThemeForSite(self::UID);

        $this->assertSame('#ff0000', $theme['sidebar']['bg']);
        $this->assertSame('#ff0000', $theme['controls']['accent']);
    }

    public function testV2ThemeTakesPrecedenceOverLegacyOverrides(): void
    {
        $settings = new Settings();
        $settings->themes = [self::UID => ['sidebar' => ['bg' => '#111111']]];
        $settings->overrides = [self::UID => ['accent' => '#ff0000']];

        $theme = $settings->resolvedThemeForSite(self::UID);

        $this->assertSame('#111111', $theme['sidebar']['bg']);
    }

    public function testReturnsEmptyThemeForUnknownSite(): void
    {
        $settings = new Settings();

        $this->assertSame([], $settings->resolvedThemeForSite(self::UID));
    }
}
