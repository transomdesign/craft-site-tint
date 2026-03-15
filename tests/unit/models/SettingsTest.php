<?php

namespace transom\craftsitetint\tests\unit\models;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\models\Settings;

/**
 * Unit tests for Settings::defineRules() hex color validation (SETT-02).
 *
 * Tests exercise the InlineValidator closure directly by instantiating
 * Settings, setting overrides, and calling validate(['overrides']).
 * Craft::t() is safe without a running app (returns the message string).
 */
class SettingsTest extends TestCase
{
    private const FAKE_UID = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    // -------------------------------------------------------------------
    // Valid hex — should pass validation (no errors on 'overrides')
    // -------------------------------------------------------------------

    public function testValidHexSixDigitAccepted(): void
    {
        $model = new Settings();
        $model->overrides = [self::FAKE_UID => ['background' => '#ff0000']];
        $model->validate(['overrides']);
        $this->assertFalse($model->hasErrors('overrides'), 'Six-digit hex #ff0000 should pass validation');
    }

    public function testValidHexThreeDigitAccepted(): void
    {
        $model = new Settings();
        $model->overrides = [self::FAKE_UID => ['background' => '#f00']];
        $model->validate(['overrides']);
        $this->assertFalse($model->hasErrors('overrides'), 'Three-digit hex #f00 should pass validation');
    }

    // -------------------------------------------------------------------
    // Invalid colors — should fail validation (error on 'overrides')
    // -------------------------------------------------------------------

    public function testInvalidColorRejected(): void
    {
        $model = new Settings();
        $model->overrides = [self::FAKE_UID => ['background' => 'oklch(50% 0.1 17)']];
        $model->validate(['overrides']);
        $this->assertTrue($model->hasErrors('overrides'), 'oklch color should fail validation');
    }

    public function testInvalidNamedColorRejected(): void
    {
        $model = new Settings();
        $model->overrides = [self::FAKE_UID => ['background' => 'red']];
        $model->validate(['overrides']);
        $this->assertTrue($model->hasErrors('overrides'), 'Named color "red" should fail validation');
    }

    public function testInvalidBareHexRejected(): void
    {
        $model = new Settings();
        $model->overrides = [self::FAKE_UID => ['background' => 'ff0000']];
        $model->validate(['overrides']);
        $this->assertTrue($model->hasErrors('overrides'), 'Bare hex without # should fail validation');
    }

    // -------------------------------------------------------------------
    // Empty/null — should be skipped (no validation error)
    // -------------------------------------------------------------------

    public function testEmptyOverridesValid(): void
    {
        $model = new Settings();
        $model->overrides = [];
        $model->validate(['overrides']);
        $this->assertFalse($model->hasErrors('overrides'), 'Empty overrides array should pass validation');
    }

    public function testNullValueSkipped(): void
    {
        $model = new Settings();
        $model->overrides = [self::FAKE_UID => ['background' => null]];
        $model->validate(['overrides']);
        $this->assertFalse($model->hasErrors('overrides'), 'Null override value should be skipped (no error)');
    }

    public function testEmptyStringSkipped(): void
    {
        $model = new Settings();
        $model->overrides = [self::FAKE_UID => ['background' => '']];
        $model->validate(['overrides']);
        $this->assertFalse($model->hasErrors('overrides'), 'Empty string override value should be skipped (no error)');
    }
}
