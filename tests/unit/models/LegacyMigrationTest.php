<?php

namespace transom\craftsitetint\tests\unit\models;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\models\Settings;

/**
 * Unit tests for Settings::themeFromLegacyOverrides().
 *
 * Verifies the v1 flat-overrides-to-v2-grouped-theme mapping, including
 * against the real values previously saved in project config for Matthews
 * Winery and Tenor Wines.
 */
class LegacyMigrationTest extends TestCase
{
    public function testMapsEachV1KeyToItsDocumentedV2Destinations(): void
    {
        $theme = Settings::themeFromLegacyOverrides([
            'background' => '#111111',
            'accent' => '#222222',
            'accent-color' => '#333333',
            'accent-color-hover' => '#444444',
            'accent-hover' => '#555555',
        ]);

        $this->assertSame('#111111', $theme['content']['bg']);

        $this->assertSame('#222222', $theme['sidebar']['bg']);
        $this->assertSame('#333333', $theme['sidebar']['text']);
        $this->assertSame('#444444', $theme['sidebar']['textHover']);
        $this->assertSame('#555555', $theme['sidebar']['hoverBg']);

        $this->assertSame('#222222', $theme['header']['bg']);
        $this->assertSame('#333333', $theme['header']['text']);
        $this->assertSame('#444444', $theme['header']['textHover']);

        $this->assertSame('#222222', $theme['controls']['accent']);
        $this->assertSame('#333333', $theme['controls']['accentText']);
        $this->assertSame('#555555', $theme['controls']['accentHover']);
        $this->assertSame('#222222', $theme['controls']['link']);
    }

    public function testMigratesMatthewsWineryOverridesFromProjectConfig(): void
    {
        // Real values from config/project/project.yaml prior to migration.
        $theme = Settings::themeFromLegacyOverrides([
            'background' => 'efebe2',
            'accent' => '7b3634',
            'accent-color' => 'fff',
            'accent-color-hover' => 'efebe2',
            'accent-hover' => '7b3634',
        ]);

        $this->assertSame('#efebe2', $theme['content']['bg']);
        $this->assertSame('#7b3634', $theme['sidebar']['bg']);
        $this->assertSame('#ffffff', $theme['sidebar']['text']);
        $this->assertSame('#efebe2', $theme['sidebar']['textHover']);
        $this->assertSame('#7b3634', $theme['sidebar']['hoverBg']);
        $this->assertSame('#7b3634', $theme['controls']['accent']);
    }

    public function testMigratesTenorWinesOverridesFromProjectConfig(): void
    {
        // Real values from config/project/project.yaml prior to migration.
        $theme = Settings::themeFromLegacyOverrides([
            'background' => '7b3634',
            'accent' => '64a70b',
            'accent-color' => 'fff',
            'accent-color-hover' => 'fff',
            'accent-hover' => 'fff',
        ]);

        $this->assertSame('#7b3634', $theme['content']['bg']);
        $this->assertSame('#64a70b', $theme['sidebar']['bg']);
        $this->assertSame('#ffffff', $theme['sidebar']['text']);
        $this->assertSame('#ffffff', $theme['sidebar']['textHover']);
        $this->assertSame('#ffffff', $theme['sidebar']['hoverBg']);
        $this->assertSame('#64a70b', $theme['controls']['accent']);
    }

    public function testEmptyOverridesProduceEmptyTheme(): void
    {
        $this->assertSame([], Settings::themeFromLegacyOverrides([]));
    }

    public function testPartialOverridesOnlyPopulateDrivenFields(): void
    {
        $theme = Settings::themeFromLegacyOverrides(['background' => '#efebe2']);

        $this->assertSame(['bg' => '#efebe2'], $theme['content']);
        $this->assertArrayNotHasKey('sidebar', $theme);
        $this->assertArrayNotHasKey('header', $theme);
        $this->assertArrayNotHasKey('controls', $theme);
    }
}
