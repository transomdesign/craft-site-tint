<?php

namespace transom\craftsitetint\tests\unit;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\models\Settings;

/**
 * Unit tests for site deletion cleanup logic (SETT-03).
 *
 * Tests exercise the array manipulation that underpins the
 * EVENT_AFTER_DELETE_SITE cleanup and beforeSaveSettings() filtering,
 * without requiring a running Craft instance.
 */
class SiteTintDeletionTest extends TestCase
{
    private const UID_A = 'aaaaaaaa-1111-2222-3333-aaaaaaaaaaaa';
    private const UID_B = 'bbbbbbbb-1111-2222-3333-bbbbbbbbbbbb';

    /**
     * When one UID is removed from a two-entry overrides array, only
     * the other UID remains.
     */
    public function testRemovingUidFromOverrides(): void
    {
        $model = new Settings();
        $model->overrides = [
            self::UID_A => ['background' => '#ff0000'],
            self::UID_B => ['background' => '#00ff00'],
        ];

        unset($model->overrides[self::UID_A]);

        $this->assertArrayNotHasKey(self::UID_A, $model->overrides, 'Removed UID should not be present');
        $this->assertArrayHasKey(self::UID_B, $model->overrides, 'Remaining UID should still be present');
        $this->assertCount(1, $model->overrides, 'Overrides should have exactly one entry after removal');
    }

    /**
     * After removing the sole UID from overrides, the array is empty.
     */
    public function testEmptyOverridesAfterRemoval(): void
    {
        $model = new Settings();
        $model->overrides = [
            self::UID_A => ['background' => '#ff0000'],
        ];

        unset($model->overrides[self::UID_A]);

        $this->assertEmpty($model->overrides, 'Overrides should be empty after removing the only UID');
    }

    /**
     * After removing a UID, the remaining settings still pass validation.
     */
    public function testOverridesValidAfterRemoval(): void
    {
        $model = new Settings();
        $model->overrides = [
            self::UID_A => ['background' => '#ff0000'],
            self::UID_B => ['background' => '#00ff00'],
        ];

        unset($model->overrides[self::UID_A]);
        $model->validate(['overrides']);

        $this->assertFalse($model->hasErrors('overrides'), 'Remaining overrides should pass validation after UID removal');
    }

    /**
     * The beforeSaveSettings() filtering logic strips empty strings and null
     * values from site override sub-arrays, and removes entirely empty site entries.
     *
     * This mirrors the exact logic in SiteTint::beforeSaveSettings():
     *   $filtered = array_filter($siteOverrides, fn($v) => is_string($v) && $v !== '');
     *   if (!empty($filtered)) { $cleaned[$uid] = $filtered; }
     */
    public function testStrippingEmptyValuesLeavesValidEntries(): void
    {
        $rawOverrides = [
            self::UID_A => [
                'background' => '#ff0000',
                'accent' => '',             // empty string — should be stripped
            ],
            self::UID_B => [
                'background' => '',         // only empty values — entire entry removed
            ],
        ];

        // Apply the same filtering logic as SiteTint::beforeSaveSettings()
        $cleaned = [];
        foreach ($rawOverrides as $uid => $siteOverrides) {
            if (!is_array($siteOverrides)) {
                continue;
            }
            $filtered = array_filter($siteOverrides, fn($v) => is_string($v) && $v !== '');
            if (!empty($filtered)) {
                $cleaned[$uid] = $filtered;
            }
        }

        $expected = [
            self::UID_A => ['background' => '#ff0000'],
        ];

        $this->assertSame($expected, $cleaned, 'Filtering should strip empty strings and remove empty site entries');
        $this->assertArrayNotHasKey(self::UID_B, $cleaned, 'Site with only empty values should be removed entirely');
    }
}
