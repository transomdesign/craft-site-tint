<?php

namespace transom\craftsitetint\tests\unit;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\models\Settings;

/**
 * Unit tests for site deletion cleanup and beforeSaveSettings() filtering.
 *
 * Tests exercise the array manipulation that underpins
 * EVENT_AFTER_DELETE_SITE cleanup and SiteTint::beforeSaveSettings(),
 * without requiring a running Craft instance.
 */
class SiteTintDeletionTest extends TestCase
{
    private const UID_A = 'aaaaaaaa-1111-2222-3333-aaaaaaaaaaaa';
    private const UID_B = 'bbbbbbbb-1111-2222-3333-bbbbbbbbbbbb';

    /**
     * When one UID is removed from a two-entry themes array, only the
     * other UID remains.
     */
    public function testRemovingUidFromThemes(): void
    {
        $model = new Settings();
        $model->themes = [
            self::UID_A => ['sidebar' => ['bg' => '#ff0000']],
            self::UID_B => ['sidebar' => ['bg' => '#00ff00']],
        ];

        unset($model->themes[self::UID_A]);

        $this->assertArrayNotHasKey(self::UID_A, $model->themes, 'Removed UID should not be present');
        $this->assertArrayHasKey(self::UID_B, $model->themes, 'Remaining UID should still be present');
        $this->assertCount(1, $model->themes, 'Themes should have exactly one entry after removal');
    }

    /**
     * After removing the sole UID from themes, the array is empty.
     */
    public function testEmptyThemesAfterRemoval(): void
    {
        $model = new Settings();
        $model->themes = [
            self::UID_A => ['sidebar' => ['bg' => '#ff0000']],
        ];

        unset($model->themes[self::UID_A]);

        $this->assertEmpty($model->themes, 'Themes should be empty after removing the only UID');
    }

    /**
     * After removing a UID, the remaining settings still pass validation.
     */
    public function testThemesValidAfterRemoval(): void
    {
        $model = new Settings();
        $model->themes = [
            self::UID_A => ['sidebar' => ['bg' => '#ff0000']],
            self::UID_B => ['sidebar' => ['bg' => '#00ff00']],
        ];

        unset($model->themes[self::UID_A]);
        $model->validate(['themes']);

        $this->assertFalse($model->hasErrors('themes'), 'Remaining themes should pass validation after UID removal');
    }

    /**
     * SiteTint::beforeSaveSettings() runs Settings::normalizeTheme() over
     * every non-primary site and drops sites left with no color values.
     */
    public function testNormalizingLeavesOnlyNonEmptySites(): void
    {
        $rawThemes = [
            self::UID_A => [
                'sidebar' => ['bg' => '#ff0000', 'text' => ''],
            ],
            self::UID_B => [
                'sidebar' => ['bg' => ''],
            ],
        ];

        $cleaned = [];
        foreach ($rawThemes as $uid => $theme) {
            $normalized = Settings::normalizeTheme($theme);
            if (!empty($normalized)) {
                $cleaned[$uid] = $normalized;
            }
        }

        $this->assertSame([self::UID_A => ['sidebar' => ['bg' => '#ff0000']]], $cleaned);
        $this->assertArrayNotHasKey(self::UID_B, $cleaned, 'Site with only empty values should be removed entirely');
    }
}
