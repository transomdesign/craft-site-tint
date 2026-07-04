<?php

namespace transom\craftsitetint;

/**
 * Curated Site Tint preset themes.
 *
 * Presets are seed data only: the settings UI copies a preset's colors into
 * the per-site form fields when clicked, and the concrete values are what
 * gets saved. Nothing at render time depends on a preset's continued
 * existence, so presets can be freely edited or added to without touching
 * saved settings.
 *
 * @author transom <accounts@transom.design>
 */
final class Presets
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the full set of built-in preset themes, keyed by handle.
     *
     * @return array<string, array{name: string, theme: array}> Presets keyed
     * by handle, each with a display `name` and a full `theme` array shaped
     * like {@see \transom\craftsitetint\models\Settings::GROUPS}.
     */
    public static function all(): array
    {
        return [
            'burgundy' => [
                'name' => 'Vintage Burgundy',
                'theme' => [
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
                ],
            ],
            'forest' => [
                'name' => 'Forest',
                'theme' => [
                    'sidebar' => [
                        'bg' => '#1e3a2a',
                        'text' => '#e3ede6',
                        'textHover' => '#ffffff',
                        'hoverBg' => '#274a35',
                        'activeBg' => '#2f5940',
                        'activeText' => '#ffffff',
                    ],
                    'header' => [
                        'bg' => '#1e3a2a',
                        'text' => '#e3ede6',
                        'textHover' => '#ffffff',
                    ],
                    'content' => [
                        'bg' => '#f4f7f3',
                        'paneBg' => '#ffffff',
                        'text' => '#22301f',
                    ],
                    'controls' => [
                        'accent' => '#2f6b46',
                        'accentText' => '#ffffff',
                        'accentHover' => '#26573a',
                        'link' => '#2f6b46',
                        'focusRing' => '#6fa787',
                    ],
                ],
            ],
            'slate' => [
                'name' => 'Slate',
                'theme' => [
                    'sidebar' => [
                        'bg' => '#26303a',
                        'text' => '#e6eaee',
                        'textHover' => '#ffffff',
                        'hoverBg' => '#303d49',
                        'activeBg' => '#3a4a58',
                        'activeText' => '#ffffff',
                    ],
                    'header' => [
                        'bg' => '#26303a',
                        'text' => '#e6eaee',
                        'textHover' => '#ffffff',
                    ],
                    'content' => [
                        'bg' => '#f2f4f6',
                        'paneBg' => '#ffffff',
                        'text' => '#1f2833',
                    ],
                    'controls' => [
                        'accent' => '#41586e',
                        'accentText' => '#ffffff',
                        'accentHover' => '#33465a',
                        'link' => '#41586e',
                        'focusRing' => '#8098b0',
                    ],
                ],
            ],
            'ocean' => [
                'name' => 'Ocean',
                'theme' => [
                    'sidebar' => [
                        'bg' => '#123a4c',
                        'text' => '#dcedf3',
                        'textHover' => '#ffffff',
                        'hoverBg' => '#194a60',
                        'activeBg' => '#205a73',
                        'activeText' => '#ffffff',
                    ],
                    'header' => [
                        'bg' => '#123a4c',
                        'text' => '#dcedf3',
                        'textHover' => '#ffffff',
                    ],
                    'content' => [
                        'bg' => '#f2f7f9',
                        'paneBg' => '#ffffff',
                        'text' => '#12262e',
                    ],
                    'controls' => [
                        'accent' => '#1d6a8a',
                        'accentText' => '#ffffff',
                        'accentHover' => '#175571',
                        'link' => '#1d6a8a',
                        'focusRing' => '#6cb3d1',
                    ],
                ],
            ],
            'terracotta' => [
                'name' => 'Terracotta',
                'theme' => [
                    'sidebar' => [
                        'bg' => '#5c3226',
                        'text' => '#f4e6de',
                        'textHover' => '#ffffff',
                        'hoverBg' => '#6f3e2f',
                        'activeBg' => '#834938',
                        'activeText' => '#ffffff',
                    ],
                    'header' => [
                        'bg' => '#5c3226',
                        'text' => '#f4e6de',
                        'textHover' => '#ffffff',
                    ],
                    'content' => [
                        'bg' => '#faf5f0',
                        'paneBg' => '#ffffff',
                        'text' => '#3a2419',
                    ],
                    'controls' => [
                        'accent' => '#b05c3a',
                        'accentText' => '#ffffff',
                        'accentHover' => '#8f4a2e',
                        'link' => '#b05c3a',
                        'focusRing' => '#d69a7c',
                    ],
                ],
            ],
        ];
    }
}
