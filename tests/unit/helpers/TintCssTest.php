<?php

namespace transom\craftsitetint\tests\unit\helpers;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\helpers\TintCss;

/**
 * Unit tests for TintCss::build().
 *
 * Verifies the pure CSS-generation contract: an empty theme produces no
 * output, partial themes only emit the blocks their values drive, and full
 * themes cover every documented token and selector.
 */
class TintCssTest extends TestCase
{
    public function testEmptyThemeProducesNoCss(): void
    {
        $this->assertSame('', TintCss::build([]));
    }

    public function testSidebarOnlyThemeEmitsOnlySidebarAndRelatedTokens(): void
    {
        $css = TintCss::build([
            'sidebar' => ['bg' => '#4a1d24'],
        ]);

        $this->assertStringContainsString('--sidebar-bg: #4a1d24;', $css);
        $this->assertStringContainsString('.global-sidebar {', $css);
        $this->assertStringNotContainsString('#global-header', $css);
        $this->assertStringNotContainsString('--body-bg', $css);
        $this->assertStringNotContainsString('--primary-button-bg', $css);
    }

    public function testHeaderTextRequiresImportantOnAnchorColor(): void
    {
        $css = TintCss::build([
            'header' => ['text' => '#f4e4e6'],
        ]);

        $this->assertStringContainsString('#global-header #crumbs a', $css);
        $this->assertStringContainsString('color: #f4e4e6 !important;', $css);
    }

    public function testSidebarBackgroundHasNoImportant(): void
    {
        $css = TintCss::build([
            'sidebar' => ['bg' => '#123456'],
        ]);

        $this->assertStringContainsString('.global-sidebar {', $css);
        $this->assertStringNotContainsString('!important', $css);
    }

    public function testContentGroupDrivesDerivedTokens(): void
    {
        $css = TintCss::build([
            'content' => ['bg' => '#faf6f2', 'paneBg' => '#ffffff', 'text' => '#3a2226'],
        ]);

        $this->assertStringContainsString('--body-bg: #faf6f2;', $css);
        $this->assertStringContainsString('--pane-bg: #ffffff;', $css);
        $this->assertStringContainsString('--secondary-pane-bg: color-mix(in srgb, #ffffff 88%, #faf6f2);', $css);
        $this->assertStringContainsString('--text-color: #3a2226;', $css);
        $this->assertStringContainsString('--light-text-color: color-mix(in srgb, #3a2226 70%, transparent);', $css);
    }

    public function testContentBackgroundWithoutTextDerivesContrastingColor(): void
    {
        // A migrated v1 theme only ever sets content.bg, never content.text
        // — without a fallback, --text-color would stay Craft's default
        // dark gray, unreadable against a dark custom background.
        $darkBgCss = TintCss::build(['content' => ['bg' => '#7b3634']]);
        $this->assertStringContainsString('--text-color: #ffffff;', $darkBgCss);

        $lightBgCss = TintCss::build(['content' => ['bg' => '#faf6f2']]);
        $this->assertStringContainsString('--text-color: #1f2833;', $lightBgCss);
    }

    public function testExplicitContentTextOverridesDerivedContrast(): void
    {
        $css = TintCss::build(['content' => ['bg' => '#7b3634', 'text' => '#f4e4e6']]);

        $this->assertStringContainsString('--text-color: #f4e4e6;', $css);
        $this->assertStringNotContainsString('--text-color: #ffffff;', $css);
    }

    public function testNoContentBackgroundLeavesTextColorUnset(): void
    {
        $css = TintCss::build(['sidebar' => ['bg' => '#4a1d24']]);

        $this->assertStringNotContainsString('--text-color', $css);
    }

    public function testControlsGroupDrivesButtonAndLinkTokens(): void
    {
        $css = TintCss::build([
            'controls' => [
                'accent' => '#8c2f3f',
                'accentText' => '#ffffff',
                'accentHover' => '#732632',
                'link' => '#8c2f3f',
                'focusRing' => '#c26a78',
            ],
        ]);

        $this->assertStringContainsString('--primary-button-bg: #8c2f3f;', $css);
        $this->assertStringContainsString('--primary-button-text-color: #ffffff;', $css);
        $this->assertStringContainsString('--primary-button-bg--hover: #732632;', $css);
        $this->assertStringContainsString('--primary-button-bg--active: color-mix(in srgb, #732632 85%, #000000);', $css);
        $this->assertStringContainsString('--link-color: #8c2f3f;', $css);
        $this->assertStringContainsString('--focus-ring-color: #c26a78;', $css);
    }

    public function testBadgeBackgroundFallsBackToSidebarActiveWhenNoAccent(): void
    {
        $css = TintCss::build([
            'sidebar' => ['activeBg' => '#6b2b35'],
        ]);

        $this->assertStringContainsString('--nav-item-badge-bg: #6b2b35;', $css);
    }

    public function testBadgeBackgroundPrefersControlsAccent(): void
    {
        $css = TintCss::build([
            'sidebar' => ['activeBg' => '#6b2b35'],
            'controls' => ['accent' => '#8c2f3f'],
        ]);

        $this->assertStringContainsString('--nav-item-badge-bg: #8c2f3f;', $css);
    }

    public function testFullThemeNeverLeavesUnsetTokenPlaceholders(): void
    {
        $css = TintCss::build([
            'sidebar' => [
                'bg' => '#4a1d24',
                'text' => '#f4e4e6',
                'textHover' => '#ffffff',
                'hoverBg' => '#5e252e',
                'activeBg' => '#6b2b35',
                'activeText' => '#ffffff',
            ],
            'header' => [
                'bg' => '#4a1d24',
                'text' => '#f4e4e6',
                'textHover' => '#ffffff',
            ],
            'content' => [
                'bg' => '#faf6f2',
                'paneBg' => '#ffffff',
                'text' => '#3a2226',
            ],
            'controls' => [
                'accent' => '#8c2f3f',
                'accentText' => '#ffffff',
                'accentHover' => '#732632',
                'link' => '#8c2f3f',
                'focusRing' => '#c26a78',
            ],
        ]);

        $this->assertStringNotContainsString(': ;', $css, 'No declaration should have an empty value');
        $this->assertStringContainsString('#site-icon svg path', $css);
    }
}
