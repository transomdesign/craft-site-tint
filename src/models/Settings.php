<?php

namespace transom\craftsitetint\models;

use Craft;
use craft\base\Model;
use yii\validators\InlineValidator;

/**
 * site-tint settings
 */
class Settings extends Model
{
    /**
     * @var array Site-specific color overrides, keyed by site UID.
     * e.g., [
     *   'site-uid-string' => [
     *     'background' => '#ff0000',
     *     'accent' => '#cc0000',
     *     'accent-color' => '#ffffff',
     *     'accent-color-hover' => '#ffffff',
     *     'accent-hover' => '#aa0000',
     *   ]
     * ]
     */
    public array $overrides = [];

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            ['overrides'],
            function(string $attribute, mixed $params, InlineValidator $validator): void {
                if (!is_array($this->$attribute)) {
                    return;
                }

                foreach ($this->$attribute as $uid => $siteOverrides) {
                    if (!is_array($siteOverrides)) {
                        $this->addError($attribute, Craft::t('site-tint', 'Overrides for site "{uid}" must be an array.', ['uid' => $uid]));
                        continue;
                    }

                    foreach ($siteOverrides as $key => $value) {
                        if ($value === null || $value === '') {
                            continue;
                        }

                        if (!is_string($value) || !preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
                            $this->addError($attribute, Craft::t('site-tint', '"{value}" is not a valid hex color.', ['value' => $value]));
                        }
                    }
                }
            },
        ];

        return $rules;
    }

    /**
     * @var array The palette of available color pairs to choose from.
     * Each entry is an associative array with 'background', 'accent', 'accent-color',
     * 'accent-color-hover', and 'accent-hover' color strings.
     */
    public array $palette = [
        [
            'background' => 'oklch(97.1% 0.013 17.38)',
            'accent' => 'oklch(50% 0.1 17.38)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 17.38)',
        ],
        [
            'background' => 'oklch(98% 0.016 73.684)',
            'accent' => 'oklch(50% 0.1 73.684)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 73.684)',
        ],
        [
            'background' => 'oklch(98.7% 0.026 102.212)',
            'accent' => 'oklch(50% 0.1 102.212)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 102.212)',
        ],
        [
            'background' => 'oklch(98.6% 0.031 120.757)',
            'accent' => 'oklch(50% 0.1 120.757)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 120.757)',
        ],
        [
            'background' => 'oklch(97.9% 0.021 166.113)',
            'accent' => 'oklch(50% 0.1 166.113)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 166.113)',
        ],
        [
            'background' => 'oklch(98.4% 0.014 180.72)',
            'accent' => 'oklch(50% 0.1 180.72)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 180.72)',
        ],
        [
            'background' => 'oklch(97.7% 0.013 236.62)',
            'accent' => 'oklch(50% 0.1 236.62)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 236.62)',
        ],
        [
            'background' => 'oklch(96.2% 0.018 272.314)',
            'accent' => 'oklch(50% 0.1 272.314)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 272.314)',
        ],
        [
            'background' => 'oklch(97.7% 0.014 308.299)',
            'accent' => 'oklch(50% 0.1 308.299)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 308.299)',
        ],
        [
            'background' => 'oklch(97.7% 0.017 320.058)',
            'accent' => 'oklch(50% 0.1 320.058)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 320.058)',
        ],
    ];
}
