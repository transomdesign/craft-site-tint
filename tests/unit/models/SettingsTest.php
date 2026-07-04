<?php

namespace transom\craftsitetint\tests\unit\models;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\models\Settings;

/**
 * Unit tests for Settings: `themes` validation and `normalizeTheme()`.
 *
 * Craft::t() is safe to call without a running app (it returns the message
 * string untranslated), so these instantiate Settings directly.
 */
class SettingsTest extends TestCase
{
    private const FAKE_UID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    // -------------------------------------------------------------------
    // Validation — valid hex passes, invalid hex fails, unknown keys ignored
    // -------------------------------------------------------------------

    public function testValidHexSixDigitAccepted(): void
    {
        $model = new Settings();
        $model->themes = [self::FAKE_UID => ['sidebar' => ['bg' => '#ff0000']]];
        $model->validate(['themes']);

        $this->assertFalse($model->hasErrors('themes'), 'Six-digit hex #ff0000 should pass validation');
    }

    public function testValidHexThreeDigitAccepted(): void
    {
        $model = new Settings();
        $model->themes = [self::FAKE_UID => ['sidebar' => ['bg' => '#f00']]];
        $model->validate(['themes']);

        $this->assertFalse($model->hasErrors('themes'), 'Three-digit hex #f00 should pass validation');
    }

    public function testInvalidColorRejected(): void
    {
        $model = new Settings();
        $model->themes = [self::FAKE_UID => ['sidebar' => ['bg' => 'oklch(50% 0.1 17)']]];
        $model->validate(['themes']);

        $this->assertTrue($model->hasErrors('themes'), 'oklch color should fail validation');
    }

    public function testInvalidNamedColorRejected(): void
    {
        $model = new Settings();
        $model->themes = [self::FAKE_UID => ['sidebar' => ['bg' => 'red']]];
        $model->validate(['themes']);

        $this->assertTrue($model->hasErrors('themes'), 'Named color "red" should fail validation');
    }

    public function testBareHexAccepted(): void
    {
        // Craft's color field posts the hex value without a leading '#'
        // (the '#' shown next to the input is a static UI prefix, not part
        // of the field value) — bare hex is the normal, expected shape of
        // a real form submission and must validate.
        $model = new Settings();
        $model->themes = [self::FAKE_UID => ['sidebar' => ['bg' => 'ff0000']]];
        $model->validate(['themes']);

        $this->assertFalse($model->hasErrors('themes'), 'Bare hex (as posted by the color field) should pass validation');
    }

    public function testUnknownGroupIsIgnoredNotRejected(): void
    {
        $model = new Settings();
        $model->themes = [self::FAKE_UID => ['notAGroup' => ['bg' => 'not-a-color']]];
        $model->validate(['themes']);

        $this->assertFalse(
            $model->hasErrors('themes'),
            'Unknown groups must be ignored so forward-compatible project config never fails to apply'
        );
    }

    public function testEmptyThemesValid(): void
    {
        $model = new Settings();
        $model->themes = [];
        $model->validate(['themes']);

        $this->assertFalse($model->hasErrors('themes'), 'Empty themes array should pass validation');
    }

    public function testNullValueSkipped(): void
    {
        $model = new Settings();
        $model->themes = [self::FAKE_UID => ['sidebar' => ['bg' => null]]];
        $model->validate(['themes']);

        $this->assertFalse($model->hasErrors('themes'), 'Null theme value should be skipped (no error)');
    }

    public function testEmptyStringSkipped(): void
    {
        $model = new Settings();
        $model->themes = [self::FAKE_UID => ['sidebar' => ['bg' => '']]];
        $model->validate(['themes']);

        $this->assertFalse($model->hasErrors('themes'), 'Empty string theme value should be skipped (no error)');
    }

    // -------------------------------------------------------------------
    // normalizeTheme()
    // -------------------------------------------------------------------

    public function testNormalizeThemeExpandsAndLowercasesBareHex(): void
    {
        $normalized = Settings::normalizeTheme(['sidebar' => ['bg' => 'FFF', 'text' => '4A1D24']]);

        $this->assertSame(['bg' => '#ffffff', 'text' => '#4a1d24'], $normalized['sidebar']);
    }

    public function testNormalizeThemeStripsUnknownGroupsAndKeys(): void
    {
        $normalized = Settings::normalizeTheme([
            'sidebar' => ['bg' => '#fff', 'notAKey' => '#000'],
            'notAGroup' => ['bg' => '#fff'],
        ]);

        $this->assertSame(['bg' => '#ffffff'], $normalized['sidebar']);
        $this->assertArrayNotHasKey('notAGroup', $normalized);
    }

    public function testNormalizeThemeDropsEmptyGroups(): void
    {
        $normalized = Settings::normalizeTheme([
            'sidebar' => ['bg' => ''],
            'header' => ['bg' => '#4a1d24'],
        ]);

        $this->assertArrayNotHasKey('sidebar', $normalized, 'A group with only empty values should be dropped');
        $this->assertSame(['bg' => '#4a1d24'], $normalized['header']);
    }

    public function testNormalizeThemeKeepsInformationalPresetKey(): void
    {
        $normalized = Settings::normalizeTheme([
            'sidebar' => ['bg' => '#4a1d24'],
            'preset' => 'burgundy',
        ]);

        $this->assertSame('burgundy', $normalized['preset']);
    }
}
