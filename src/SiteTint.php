<?php

namespace transom\craftsitetint;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\DeleteSiteEvent;
use craft\models\Site;
use craft\services\Sites;
use craft\web\View;
use transom\craftsitetint\models\Settings;
use yii\base\Event;

/**
 * site-tint plugin
 *
 * @method static SiteTint getInstance()
 * @method Settings getSettings()
 * @author transom <accounts@transom.design>
 * @copyright transom
 * @license https://craftcms.github.io/license/ Craft License
 */
class SiteTint extends Plugin
{
    public string $schemaVersion = '1.1.0';
    public bool $hasCpSettings = true;

    public function init(): void
    {
        parent::init();

        // Migrate legacy handle-keyed overrides to UID-keyed on first CP load
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->migrateHandleKeysToUid();
        }

        $this->attachEventHandlers();
    }

    private function migrateHandleKeysToUid(): void
    {
        $settings = $this->getSettings();
        $migrated = [];
        $didMigrate = false;
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

        foreach ($settings->overrides as $key => $value) {
            if (preg_match($uuidPattern, (string)$key)) {
                // Already UID-keyed, keep as-is
                $migrated[$key] = $value;
            } else {
                // Legacy handle key — attempt to resolve to UID
                $site = Craft::$app->getSites()->getSiteByHandle((string)$key);
                if ($site !== null) {
                    $migrated[$site->uid] = $value;
                    $didMigrate = true;
                }
                // If site not found, discard the orphaned entry
            }
        }

        if ($didMigrate) {
            Craft::$app->getPlugins()->savePluginSettings($this, ['overrides' => $migrated]);
        }
    }

    public static function displayName(): string
    {
        return Craft::t('site-tint', 'Site Tint');
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        $allSites = Craft::$app->getSites()->getAllSites();
        // Exclude primary site from the overrides table
        $sites = array_filter($allSites, fn(Site $s) => !$s->primary);
        $settings = $this->getSettings();

        // Pass raw saved overrides for form values (not resolved defaults)
        // so cleared fields show as empty, not backfilled with palette colors
        $siteColors = [];
        foreach ($sites as $site) {
            $siteColors[$site->uid] = $settings->overrides[$site->uid] ?? [];
        }

        // Build resolved colors (palette fallback OR override) for preview swatches
        $resolvedColors = [];
        foreach ($sites as $site) {
            $resolvedColors[$site->uid] = $this->resolveSiteColors($site, $settings);
        }

        return Craft::$app->view->renderTemplate('site-tint/_settings.twig', [
            'plugin' => $this,
            'settings' => $settings,
            'sites' => $sites,
            'siteColors' => $siteColors,
            'resolvedColors' => $resolvedColors,
        ]);
    }

    public static function applyNavHook(array &$context, ?Site $site): void
    {
        if ($site === null || $site->primary) {
            return;
        }
        $siteUrl = $site->getBaseUrl();
        if ($siteUrl !== null) {
            $context['siteUrl'] = $siteUrl;
        }
    }

    private function attachEventHandlers(): void
    {
        // Site deletion cleanup runs unconditionally (not behind CP guard)
        // so orphaned UID entries are removed even from console/queue contexts
        Event::on(
            Sites::class,
            Sites::EVENT_AFTER_DELETE_SITE,
            function(DeleteSiteEvent $event): void {
                $uid = $event->site->uid;
                $settings = $this->getSettings();

                if (array_key_exists($uid, $settings->overrides)) {
                    unset($settings->overrides[$uid]);
                    Craft::$app->getPlugins()->savePluginSettings($this, ['overrides' => $settings->overrides]);
                }
            }
        );

        // Only attach CP-specific event handlers if it's a control panel request
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            return;
        }

        Event::on(
            View::class,
            View::EVENT_BEGIN_PAGE,
            function(Event $event): void {
                $site = $this->resolveActiveCpSite();

                if (!$site || $site->primary) {
                    return;
                }

                $settings = $this->getSettings();
                $colors = $this->resolveSiteColors($site, $settings);
                $css = self::buildTintCss($colors);

                if ($css !== '') {
                    Craft::$app->getView()->registerCss($css, [], 'site-tint-cp');
                }
            }
        );

        Craft::$app->getView()->hook('cp.layouts.base', function(array &$context): void {
            $site = $this->resolveActiveCpSite();
            self::applyNavHook($context, $site);
        });
    }

    private function resolveActiveCpSite(): ?Site
    {
        $req = Craft::$app->getRequest();
        $sites = Craft::$app->getSites();

        $qSite = $req->getParam('site');
        if ($qSite !== null && $qSite !== '') {
            return is_numeric($qSite) ? $sites->getSiteById((int)$qSite) : $sites->getSiteByHandle((string)$qSite);
        }

        $qSiteId = $req->getParam('siteId');
        if ($qSiteId !== null && $qSiteId !== '') {
            return $sites->getSiteById((int)$qSiteId);
        }

        $session = Craft::$app->getSession();
        if ($session && $session->has('siteId')) {
            $sid = (int)$session->get('siteId');
            if ($sid) {
                return $sites->getSiteById($sid);
            }
        }

        return $sites->getCurrentSite();
    }

    private function colorsFromHandle(string $handle, array $palette): array
    {
        $defaultBackgroundColor = 'oklch(95.4% 0.038 75.164)';
        $defaultAccentColor = 'oklch(50% 0.1 75.164)';
        $defaultAccentTextColor = 'oklch(99% 0 0)';
        $defaultAccentTextHoverColor = 'oklch(99% 0 0)';
        $defaultAccentHoverColor = $defaultAccentColor;

        if (empty($palette)) {
            return [
                'background' => $defaultBackgroundColor,
                'accent' => $defaultAccentColor,
                'accent-color' => $defaultAccentTextColor,
                'accent-color-hover' => $defaultAccentTextHoverColor,
                'accent-hover' => $defaultAccentHoverColor,
            ];
        }

        $hashInput = $handle . '-site-color-v2';
        $hash = hash('fnv1a32', $hashInput);
        $index = hexdec(substr($hash, 0, 8)) % count($palette);

        $selectedColors = $palette[$index];

        if (!is_array($selectedColors) || !isset($selectedColors['background']) || !isset($selectedColors['accent'])) {
            return [
                'background' => $defaultBackgroundColor,
                'accent' => $defaultAccentColor,
                'accent-color' => $defaultAccentTextColor,
                'accent-color-hover' => $defaultAccentTextHoverColor,
                'accent-hover' => $defaultAccentHoverColor,
            ];
        }

        return [
            'background' => $selectedColors['background'],
            'accent' => $selectedColors['accent'],
            'accent-color' => $selectedColors['accent-color'] ?? $defaultAccentTextColor,
            'accent-color-hover' => $selectedColors['accent-color-hover'] ?? $defaultAccentTextHoverColor,
            'accent-hover' => $selectedColors['accent-hover'] ?? $defaultAccentHoverColor,
        ];
    }

    public function beforeSaveSettings(): bool
    {
        $settings = $this->getSettings();
        $primaryUid = Craft::$app->getSites()->getPrimarySite()->uid;
        $cleaned = [];

        foreach ($settings->overrides as $uid => $siteOverrides) {
            // Always exclude primary site UID
            if ($uid === $primaryUid) {
                continue;
            }

            if (!is_array($siteOverrides)) {
                continue;
            }

            // Strip empty strings and null values
            $filtered = array_filter($siteOverrides, fn($v) => is_string($v) && $v !== '');

            // Only keep non-empty site entries
            if (!empty($filtered)) {
                $cleaned[$uid] = $filtered;
            }
        }

        $settings->overrides = $cleaned;

        return parent::beforeSaveSettings();
    }

    protected function resolveSiteColors(Site $site, Settings $settings): array
    {
        $overrides = $settings->overrides[$site->uid] ?? [];
        $palette = $settings->palette;
        $base = $this->colorsFromHandle($site->handle, $palette);

        $keys = [
            'background',
            'accent',
            'accent-color',
            'accent-color-hover',
            'accent-hover',
        ];

        $resolved = [];
        foreach ($keys as $key) {
            $value = array_key_exists($key, $overrides) ? $overrides[$key] : ($base[$key] ?? null);
            $resolved[$key] = $this->normalizeColorValue($value);
        }

        $resolved['background'] = $resolved['background'] ?: 'oklch(95.4% 0.038 75.164)';
        $resolved['accent'] = $resolved['accent'] ?: 'oklch(50% 0.1 75.164)';
        $resolved['accent-color'] = $resolved['accent-color'] ?: 'oklch(99% 0 0)';
        $resolved['accent-color-hover'] = $resolved['accent-color-hover'] ?: 'oklch(99% 0 0)';
        $resolved['accent-hover'] = $resolved['accent-hover'] ?: $resolved['accent'];

        return $resolved;
    }

    private function normalizeColorValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if ($value[0] === '#') {
            return $value;
        }

        if (preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value)) {
            return '#' . $value;
        }

        return $value;
    }

    /**
     * Build the CSS string for site tinting.
     *
     * Pure function — no side effects, no registerCss() call.
     * Returns an empty string when $colors is empty (primary site guard).
     *
     * @param array{background?: string, accent?: string, accent-color?: string, accent-color-hover?: string, accent-hover?: string} $colors
     */
    public static function buildTintCss(array $colors): string
    {
        if (empty($colors)) {
            return '';
        }

        $background = $colors['background'] ?? '';
        $accent = $colors['accent'] ?? '';
        $accentColor = $colors['accent-color'] ?? '';
        $accentColorHover = $colors['accent-color-hover'] ?? '';
        $accentHover = $colors['accent-hover'] ?? '';

        return <<<CSS
            :root {
              --cp-site-background: {$background};
              --cp-site-accent: {$accent};
              --cp-site-accent-color: {$accentColor};
              --cp-site-accent-color-hover: {$accentColorHover};
              --cp-site-accent-hover: {$accentHover};
              --body-bg: var(--cp-site-background);
              --sidebar-bg: var(--cp-site-accent);
              --link-color: var(--cp-site-accent);
              --primary-button-bg: var(--cp-site-accent);
              --primary-button-bg--hover: var(--cp-site-accent-hover);
              --nav-item-fg-active: var(--cp-site-accent-color);
            }
            #global-header {
              background-color: var(--cp-site-accent) !important;
            }
            #global-header a,
            #global-header button {
              color: var(--cp-site-accent-color) !important;
            }
            #global-header a:hover,
            #global-header a:focus,
            #global-header button:hover,
            #global-header button:focus {
              color: var(--cp-site-accent-color-hover) !important;
              background-color: var(--cp-site-accent-hover) !important;
            }
            .global-sidebar {
              background-color: var(--cp-site-accent) !important;
            }
            .global-sidebar a,
            .global-sidebar .nav a {
              color: var(--cp-site-accent-color) !important;
            }
            .global-sidebar a:hover,
            .global-sidebar a:focus,
            .global-sidebar .nav a:hover,
            .global-sidebar .nav a:focus {
              color: var(--cp-site-accent-color-hover) !important;
              background-color: var(--cp-site-accent-hover) !important;
            }
            #site-icon svg path {
              fill: var(--cp-site-accent-color);
            }
            CSS;
    }
}
