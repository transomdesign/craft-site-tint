<?php

namespace transom\craftsitetint\helpers;

/**
 * Builds the control panel tint stylesheet for a resolved site theme.
 *
 * Pure, side-effect-free: it never touches the request or calls
 * `registerCss()` — callers own delivering the returned string. Every block
 * is emitted only when its theme group has at least one set value, so a
 * partial theme (e.g. sidebar colors only) leaves the rest of the control
 * panel on native Craft styling.
 *
 * @author transom <accounts@transom.design>
 */
final class TintCss
{
    // Public Methods
    // =========================================================================

    /**
     * Builds the tint CSS for a resolved theme.
     *
     * @param array $theme A theme array shaped like
     * {@see \transom\craftsitetint\models\Settings::GROUPS}, already
     * normalized (hex colors, unknown keys stripped).
     * @return string The CSS to register, or an empty string if the theme
     * has no color values set.
     */
    public static function build(array $theme): string
    {
        $sidebar = $theme['sidebar'] ?? [];
        $header = $theme['header'] ?? [];
        $content = $theme['content'] ?? [];
        $controls = $theme['controls'] ?? [];

        $blocks = array_filter([
            self::rootTokens($sidebar, $content, $controls),
            self::sidebarCss($sidebar),
            self::headerCss($header),
        ], fn(string $block) => $block !== '');

        return implode("\n", $blocks);
    }

    // Private Methods
    // =========================================================================

    /**
     * Builds the `:root` custom-property block covering sidebar structural
     * tokens, content-area semantic tokens, and control accent tokens.
     *
     * @param array $sidebar The sidebar color group.
     * @param array $content The content color group.
     * @param array $controls The controls color group.
     * @return string The `:root { ... }` block, or an empty string if no
     * token in it has a value.
     */
    private static function rootTokens(array $sidebar, array $content, array $controls): string
    {
        $declarations = [];

        if (isset($sidebar['bg'])) {
            $declarations[] = "--sidebar-bg: {$sidebar['bg']};";
        }
        if (isset($sidebar['hoverBg'])) {
            $declarations[] = "--nav-item-bg-hover: {$sidebar['hoverBg']};";
        }
        if (isset($sidebar['textHover'])) {
            $declarations[] = "--nav-item-fg-hover: {$sidebar['textHover']};";
        }
        if (isset($sidebar['activeBg'])) {
            $declarations[] = "--nav-item-bg-active: {$sidebar['activeBg']};";
        }
        if (isset($sidebar['activeText'])) {
            $declarations[] = "--nav-item-fg-active: {$sidebar['activeText']};";
        }

        $badgeBg = $controls['accent'] ?? $sidebar['activeBg'] ?? null;
        if ($badgeBg !== null) {
            $declarations[] = "--nav-item-badge-bg: {$badgeBg};";
            $declarations[] = '--nav-item-badge-fg: #fff;';
        }

        if (isset($content['bg'])) {
            $declarations[] = "--body-bg: {$content['bg']};";
        }
        if (isset($content['paneBg'])) {
            $paneBg = $content['paneBg'];
            $declarations[] = "--pane-bg: {$paneBg};";
            $mixWith = $content['bg'] ?? '#000000';
            $declarations[] = "--secondary-pane-bg: color-mix(in srgb, {$paneBg} 88%, {$mixWith});";
        }
        // A custom background with no explicit text color would otherwise
        // fall back to Craft's default dark-gray --text-color, which can be
        // unreadable against a dark custom background (this is exactly the
        // gap a migrated v1 theme has, since v1 never captured a text
        // color) — derive one from the background's luminance instead.
        $text = $content['text'] ?? (isset($content['bg']) ? self::contrastingTextColor($content['bg']) : null);
        if ($text !== null) {
            $declarations[] = "--text-color: {$text};";
            $declarations[] = "--light-text-color: color-mix(in srgb, {$text} 70%, transparent);";
        }

        if (isset($controls['link'])) {
            $declarations[] = "--link-color: {$controls['link']};";
        }
        if (isset($controls['focusRing'])) {
            $declarations[] = "--focus-ring-color: {$controls['focusRing']};";
        }
        if (isset($controls['accent'])) {
            $declarations[] = "--primary-button-bg: {$controls['accent']};";
        }
        if (isset($controls['accentText'])) {
            $declarations[] = "--primary-button-text-color: {$controls['accentText']};";
        }
        if (isset($controls['accentHover'])) {
            $accentHover = $controls['accentHover'];
            $declarations[] = "--primary-button-bg--hover: {$accentHover};";
            $declarations[] = "--primary-button-bg--active: color-mix(in srgb, {$accentHover} 85%, #000000);";
        }

        if (empty($declarations)) {
            return '';
        }

        return ":root {\n  " . implode("\n  ", $declarations) . "\n}";
    }

    /**
     * Builds the sidebar element-rule block. Element rules are required
     * because core hardcodes `.global-sidebar`'s background and relies on
     * `currentcolor` for link text, neither of which a custom property alone
     * can override.
     *
     * @param array $sidebar The sidebar color group.
     * @return string The CSS block, or an empty string if bg/text are unset.
     */
    private static function sidebarCss(array $sidebar): string
    {
        $rules = [];

        if (isset($sidebar['bg'])) {
            $rules[] = ".global-sidebar {\n  background-color: {$sidebar['bg']};\n}";
        }

        if (isset($sidebar['text'])) {
            $text = $sidebar['text'];
            $rules[] = ".global-sidebar,\n.global-sidebar a,\n.global-sidebar .sidebar-action {\n  color: {$text};\n}";
            $rules[] = "#site-icon svg path {\n  fill: {$text};\n}";
        }

        return implode("\n", $rules);
    }

    /**
     * Builds the `#global-header` block. `!important` is required here (and
     * only here) because core's header buttons set inline-specificity
     * `--ui-control-color` custom properties that otherwise win over a
     * cascaded color rule.
     *
     * @param array $header The header color group.
     * @return string The CSS block, or an empty string if bg/text are unset.
     */
    private static function headerCss(array $header): string
    {
        $rules = [];

        if (isset($header['bg'])) {
            $rules[] = "#global-header {\n  background-color: {$header['bg']};\n}";
        }

        if (isset($header['text'])) {
            $text = $header['text'];
            $rules[] = "#global-header #crumbs a,\n#global-header a,\n#global-header button {\n  color: {$text} !important;\n}";
            $rules[] = "#global-header .btn {\n  --ui-control-color: {$text};\n}";
        }

        if (isset($header['textHover'])) {
            $textHover = $header['textHover'];
            $rules[] = "#global-header a:hover,\n#global-header a:focus,\n#global-header button:hover,\n#global-header button:focus {\n  color: {$textHover} !important;\n}";
            $rules[] = "#global-header .btn {\n  --ui-control-hover-color: {$textHover};\n  --ui-control-active-color: {$textHover};\n}";
        }

        return implode("\n", $rules);
    }

    /**
     * Picks white or a dark neutral to sit legibly on top of the given
     * background color, using the WCAG relative luminance formula.
     *
     * @param string $hexBackground A `#rrggbb` color.
     * @return string `#ffffff` for a dark background, or a dark neutral for
     * a light one.
     */
    private static function contrastingTextColor(string $hexBackground): string
    {
        $hex = ltrim($hexBackground, '#');
        $red = hexdec(substr($hex, 0, 2)) / 255;
        $green = hexdec(substr($hex, 2, 2)) / 255;
        $blue = hexdec(substr($hex, 4, 2)) / 255;

        $linearize = static fn(float $channel): float => $channel <= 0.03928
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;

        $luminance = 0.2126 * $linearize($red) + 0.7152 * $linearize($green) + 0.0722 * $linearize($blue);

        return $luminance <= 0.5 ? '#ffffff' : '#1f2833';
    }
}
