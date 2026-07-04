<?php

namespace transom\craftsitetint;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\DeleteSiteEvent;
use craft\models\Site;
use craft\services\Sites;
use craft\web\View;
use transom\craftsitetint\helpers\TintCss;
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
    // Public Properties
    // =========================================================================

    public string $schemaVersion = '2.0.0';
    public bool $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->attachEventHandlers();
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('site-tint', 'Site Tint');
    }

    /**
     * Applies the active site's base URL to the sidebar nav hook context, so
     * nav links stay scoped to that site. No-op for the primary site or when
     * no site is active.
     *
     * @param array $context The `cp.layouts.base` template hook context.
     * @param Site|null $site The active control panel site.
     */
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

    /**
     * @inheritdoc
     */
    public function beforeSaveSettings(): bool
    {
        $settings = $this->getSettings();
        $primaryUid = Craft::$app->getSites()->getPrimarySite()->uid;
        $cleaned = [];

        foreach ($settings->themes as $uid => $theme) {
            if ($uid === $primaryUid || !is_array($theme)) {
                continue;
            }

            $normalized = Settings::normalizeTheme($theme);
            if (!empty($normalized)) {
                $cleaned[$uid] = $normalized;
            }
        }

        $settings->themes = $cleaned;

        // Legacy properties never persist past the migration; keep them
        // empty so a stray post from an old form can't resurrect them.
        $settings->overrides = [];
        $settings->palette = [];

        return parent::beforeSaveSettings();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        $allSites = Craft::$app->getSites()->getAllSites();
        // Exclude primary site — it always renders with native Craft styling.
        $sites = array_filter($allSites, fn(Site $s) => !$s->primary);
        $settings = $this->getSettings();

        // Pass raw saved themes for form values (not resolved fallbacks) so
        // cleared fields show as empty rather than backfilled.
        $siteThemes = [];
        foreach ($sites as $site) {
            $siteThemes[$site->uid] = $settings->themes[$site->uid] ?? [];
        }

        return Craft::$app->getView()->renderTemplate('site-tint/_settings.twig', [
            'plugin' => $this,
            'settings' => $settings,
            'sites' => $sites,
            'siteThemes' => $siteThemes,
            'presets' => Presets::all(),
            'groups' => $this->groupLabels(),
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the color group and field labels used to render the settings
     * form's fieldsets, in display order.
     *
     * @return array<string, array{label: string, fields: array<string, string>}>
     */
    private function groupLabels(): array
    {
        return [
            'sidebar' => [
                'label' => Craft::t('site-tint', 'Sidebar'),
                'fields' => [
                    'bg' => Craft::t('site-tint', 'Background'),
                    'text' => Craft::t('site-tint', 'Text'),
                    'textHover' => Craft::t('site-tint', 'Text (hover)'),
                    'hoverBg' => Craft::t('site-tint', 'Background (hover)'),
                    'activeBg' => Craft::t('site-tint', 'Background (active)'),
                    'activeText' => Craft::t('site-tint', 'Text (active)'),
                ],
            ],
            'header' => [
                'label' => Craft::t('site-tint', 'Header'),
                'fields' => [
                    'bg' => Craft::t('site-tint', 'Background'),
                    'text' => Craft::t('site-tint', 'Text'),
                    'textHover' => Craft::t('site-tint', 'Text (hover)'),
                ],
            ],
            'content' => [
                'label' => Craft::t('site-tint', 'Content'),
                'fields' => [
                    'bg' => Craft::t('site-tint', 'Background'),
                    'paneBg' => Craft::t('site-tint', 'Pane background'),
                    'text' => Craft::t('site-tint', 'Text'),
                ],
            ],
            'controls' => [
                'label' => Craft::t('site-tint', 'Controls'),
                'fields' => [
                    'accent' => Craft::t('site-tint', 'Button background'),
                    'accentText' => Craft::t('site-tint', 'Button text'),
                    'accentHover' => Craft::t('site-tint', 'Button background (hover)'),
                    'link' => Craft::t('site-tint', 'Link'),
                    'focusRing' => Craft::t('site-tint', 'Focus ring'),
                ],
            ],
        ];
    }

    /**
     * Registers the plugin's event handlers: control panel CSS injection,
     * the sidebar nav hook, and site-deletion cleanup.
     */
    private function attachEventHandlers(): void
    {
        // Site deletion cleanup runs unconditionally (not behind the CP
        // guard below) so orphaned UID entries are removed even from
        // console/queue contexts.
        Event::on(
            Sites::class,
            Sites::EVENT_AFTER_DELETE_SITE,
            function(DeleteSiteEvent $event): void {
                $uid = $event->site->uid;
                $settings = $this->getSettings();

                if (array_key_exists($uid, $settings->themes)) {
                    unset($settings->themes[$uid]);
                    Craft::$app->getPlugins()->savePluginSettings($this, ['themes' => $settings->themes]);
                }
            }
        );

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

                $theme = $this->getSettings()->resolvedThemeForSite($site->uid);
                $css = TintCss::build($theme);

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

    /**
     * Resolves the site whose theme should apply to the current control
     * panel request, preferring an explicit `site`/`siteId` request param
     * over the session's stored site and falling back to Craft's current
     * site.
     *
     * @return Site|null The resolved site, or null if none could be
     * determined.
     */
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
}
