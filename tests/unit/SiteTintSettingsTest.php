<?php

namespace transom\craftsitetint\tests\unit;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\models\Settings;
use transom\craftsitetint\SiteTint;

class SiteTintSettingsTest extends TestCase
{
    private function callResolveSiteColors(object $site, Settings $settings): array
    {
        // Create SiteTint instance without Plugin constructor (avoids Craft bootstrap)
        $plugin = (new \ReflectionClass(SiteTint::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(SiteTint::class, 'resolveSiteColors');
        return $method->invoke($plugin, $site, $settings);
    }

    public function testResolveSiteColorsReturnsAllKeys(): void
    {
        $site = $this->createMock(\craft\models\Site::class);
        $site->handle = 'french';
        $site->uid = 'test-uid-all-keys';

        $settings = new Settings();

        $result = $this->callResolveSiteColors($site, $settings);

        $expectedKeys = ['background', 'accent', 'accent-color', 'accent-color-hover', 'accent-hover'];
        $this->assertSame($expectedKeys, array_keys($result));

        foreach ($expectedKeys as $key) {
            $this->assertIsString($result[$key], "Key '$key' should be a string");
            $this->assertNotEmpty($result[$key], "Key '$key' should not be empty");
        }
    }

    public function testResolveSiteColorsOverrideTakesPrecedence(): void
    {
        $site = $this->createMock(\craft\models\Site::class);
        $site->handle = 'french';
        $site->uid = 'test-uid-123';

        $settings = new Settings();
        $settings->overrides = ['test-uid-123' => ['accent' => '#ff0000']];

        $result = $this->callResolveSiteColors($site, $settings);

        $this->assertSame('#ff0000', $result['accent']);
    }

    public function testResolveSiteColorsFallsBackToDefaults(): void
    {
        $site = $this->createMock(\craft\models\Site::class);
        $site->handle = 'empty-site';
        $site->uid = 'test-uid-empty';

        $settings = new Settings();
        $settings->palette = [];
        $settings->overrides = [];

        $result = $this->callResolveSiteColors($site, $settings);

        $this->assertSame('oklch(95.4% 0.038 75.164)', $result['background']);
        $this->assertSame('oklch(50% 0.1 75.164)', $result['accent']);
        $this->assertSame('oklch(99% 0 0)', $result['accent-color']);
        $this->assertSame('oklch(99% 0 0)', $result['accent-color-hover']);
        $this->assertSame('oklch(50% 0.1 75.164)', $result['accent-hover']);
    }
}
