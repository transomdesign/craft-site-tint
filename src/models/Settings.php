<?php

namespace transom\craftsitetint\models;

use Craft;
use craft\base\Model;
use yii\validators\InlineValidator;

/**
 * site-tint settings
 *
 * @author transom <accounts@transom.design>
 */
class Settings extends Model
{
    // Const Properties
    // =========================================================================

    /**
     * Color groups and their field keys. Each field is an optional hex color;
     * an unset field falls back to Craft's native control panel styling.
     */
    public const GROUPS = [
        'sidebar' => ['bg', 'text', 'textHover', 'hoverBg', 'activeBg', 'activeText'],
        'header' => ['bg', 'text', 'textHover'],
        'content' => ['bg', 'paneBg', 'text'],
        'controls' => ['accent', 'accentText', 'accentHover', 'link', 'focusRing'],
    ];

    // Public Properties
    // =========================================================================

    /**
     * @var array Site themes, keyed by site UID. Each value is an associative
     * array of color groups (see {@see self::GROUPS}), e.g.:
     * [
     *   'site-uid-string' => [
     *     'sidebar' => ['bg' => '#4a1d24', 'text' => '#f4e9ea'],
     *     'controls' => ['accent' => '#8c2f3f'],
     *   ],
     * ]
     * An optional 'preset' string key records which preset a theme was seeded
     * from, for informational display only.
     */
    public array $themes = [];

    /**
     * @var array Deprecated v1 flat overrides, keyed by site UID. Retained so
     * project config authored before 2.0.0 still validates; migrated to
     * {@see self::$themes} by the plugin's install migration and never
     * persisted again.
     * @deprecated in 2.0.0. Use {@see self::$themes} instead.
     */
    public array $overrides = [];

    /**
     * @var array Deprecated v1 hash-based color palette. Retained as an inert
     * property so old project config referencing it still validates.
     * @deprecated in 2.0.0. Removed with no replacement — sites without a
     * saved theme now render with native Craft styling.
     */
    public array $palette = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            ['themes'],
            function(string $attribute, mixed $params, InlineValidator $validator): void {
                $this->validateThemes($attribute, $validator);
            },
        ];

        return $rules;
    }

    /**
     * Normalizes a raw theme array: strips unknown groups/keys, normalizes
     * hex color strings, and drops empty values and empty groups.
     *
     * @param array $raw The raw theme array, as posted from the settings form.
     * @return array The normalized theme, containing only non-empty, known
     * color values.
     */
    public static function normalizeTheme(array $raw): array
    {
        $theme = [];

        foreach (self::GROUPS as $group => $keys) {
            if (!isset($raw[$group]) || !is_array($raw[$group])) {
                continue;
            }

            $normalizedGroup = [];
            foreach ($keys as $key) {
                $value = self::normalizeColorValue($raw[$group][$key] ?? null);
                if ($value !== null) {
                    $normalizedGroup[$key] = $value;
                }
            }

            if (!empty($normalizedGroup)) {
                $theme[$group] = $normalizedGroup;
            }
        }

        if (is_string($raw['preset'] ?? null) && $raw['preset'] !== '') {
            $theme['preset'] = $raw['preset'];
        }

        return $theme;
    }

    /**
     * Maps a v1 flat overrides array onto the v2 grouped theme shape.
     *
     * @param array $flat The legacy overrides array for a single site, with
     * keys 'background', 'accent', 'accent-color', 'accent-color-hover', and
     * 'accent-hover'.
     * @return array The equivalent v2 theme array.
     */
    public static function themeFromLegacyOverrides(array $flat): array
    {
        $background = self::normalizeColorValue($flat['background'] ?? null);
        $accent = self::normalizeColorValue($flat['accent'] ?? null);
        $accentText = self::normalizeColorValue($flat['accent-color'] ?? null);
        $accentTextHover = self::normalizeColorValue($flat['accent-color-hover'] ?? null);
        $accentHover = self::normalizeColorValue($flat['accent-hover'] ?? null);

        $raw = [
            'content' => [
                'bg' => $background,
            ],
            'sidebar' => [
                'bg' => $accent,
                'text' => $accentText,
                'textHover' => $accentTextHover,
                'hoverBg' => $accentHover,
            ],
            'header' => [
                'bg' => $accent,
                'text' => $accentText,
                'textHover' => $accentTextHover,
            ],
            'controls' => [
                'accent' => $accent,
                'accentText' => $accentText,
                'accentHover' => $accentHover,
                'link' => $accent,
            ],
        ];

        return self::normalizeTheme($raw);
    }

    /**
     * Resolves the theme to apply for a given site UID, preferring a saved
     * v2 theme and falling back to an in-memory mapping of any legacy v1
     * override (so tinting survives the window between a deploy and the
     * migration running).
     *
     * @param string $uid The site UID.
     * @return array The resolved theme, empty if the site has no theme.
     */
    public function resolvedThemeForSite(string $uid): array
    {
        if (!empty($this->themes[$uid]) && is_array($this->themes[$uid])) {
            return self::normalizeTheme($this->themes[$uid]);
        }

        if (!empty($this->overrides[$uid]) && is_array($this->overrides[$uid])) {
            return self::themeFromLegacyOverrides($this->overrides[$uid]);
        }

        return [];
    }

    // Private Methods
    // =========================================================================

    /**
     * Validates the `themes` attribute leniently: unknown groups/keys are
     * silently ignored (never a validation error) so that project config
     * carrying forward-compatible or partially-migrated data never fails to
     * apply. Only non-empty color values are checked against the hex format.
     *
     * @param string $attribute The attribute being validated.
     * @param InlineValidator $validator The active validator.
     */
    private function validateThemes(string $attribute, InlineValidator $validator): void
    {
        if (!is_array($this->$attribute)) {
            return;
        }

        foreach ($this->$attribute as $uid => $theme) {
            if (!is_array($theme)) {
                $this->addError($attribute, Craft::t('site-tint', 'Theme for site "{uid}" must be an array.', [
                    'uid' => $uid,
                ]));
                continue;
            }

            foreach (self::GROUPS as $group => $keys) {
                if (!isset($theme[$group]) || !is_array($theme[$group])) {
                    continue;
                }

                foreach ($keys as $key) {
                    $value = $theme[$group][$key] ?? null;
                    if ($value === null || $value === '') {
                        continue;
                    }

                    if (!is_string($value) || !preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
                        $this->addError($attribute, Craft::t('site-tint', '"{value}" is not a valid hex color.', [
                            'value' => $value,
                        ]));
                    }
                }
            }
        }
    }

    /**
     * Normalizes a color value to a lowercase `#rrggbb` hex string. Bare hex
     * (no leading `#`, as posted by the color form field) gets one added;
     * 3-digit shorthand is expanded to 6; non-hex strings (e.g. legacy
     * oklch values) and empty/non-string values are dropped.
     *
     * @param mixed $value The raw value.
     * @return string|null The normalized `#rrggbb` color, or null if not a
     * valid, non-empty hex color.
     */
    private static function normalizeColorValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $bare = strtolower(ltrim($value, '#'));
        if (!preg_match('/^[0-9a-f]{3}([0-9a-f]{3})?$/', $bare)) {
            return null;
        }

        if (strlen($bare) === 3) {
            $bare = $bare[0] . $bare[0] . $bare[1] . $bare[1] . $bare[2] . $bare[2];
        }

        return '#' . $bare;
    }
}
