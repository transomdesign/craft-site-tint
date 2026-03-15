<?php

namespace transom\craftsitetint\tests\unit;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\SiteTint;

class SiteTintNavTest extends TestCase
{
    public function testNavHookSetsSiteUrl(): void
    {
        // Non-primary site with a baseUrl — context['siteUrl'] should be updated
        $site = $this->createMock(\craft\models\Site::class);
        $site->primary = false;
        $site->method('getBaseUrl')->willReturn('https://example.com/');

        $context = ['siteUrl' => 'https://primary.com/'];
        SiteTint::applyNavHook($context, $site);

        $this->assertSame('https://example.com/', $context['siteUrl']);
    }

    public function testNavHookSkipsPrimarySite(): void
    {
        // Primary site — context['siteUrl'] must remain unchanged
        $site = $this->createMock(\craft\models\Site::class);
        $site->primary = true;
        $site->method('getBaseUrl')->willReturn('https://primary.com/');

        $context = ['siteUrl' => 'https://primary.com/'];
        SiteTint::applyNavHook($context, $site);

        $this->assertSame('https://primary.com/', $context['siteUrl']);
    }

    public function testNavHookSkipsNullBaseUrl(): void
    {
        // Non-primary site but getBaseUrl() returns null — context must remain unchanged
        $site = $this->createMock(\craft\models\Site::class);
        $site->primary = false;
        $site->method('getBaseUrl')->willReturn(null);

        $context = ['siteUrl' => 'https://primary.com/'];
        SiteTint::applyNavHook($context, $site);

        $this->assertSame('https://primary.com/', $context['siteUrl']);
    }
}
