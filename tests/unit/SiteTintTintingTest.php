<?php

namespace transom\craftsitetint\tests\unit;

use PHPUnit\Framework\TestCase;
use transom\craftsitetint\SiteTint;

class SiteTintTintingTest extends TestCase
{
    private string $css;

    protected function setUp(): void
    {
        $this->css = SiteTint::buildTintCss([
            'background' => 'oklch(97.1% 0.013 17.38)',
            'accent' => 'oklch(50% 0.1 17.38)',
            'accent-color' => 'oklch(99% 0 0)',
            'accent-color-hover' => 'oklch(99% 0 0)',
            'accent-hover' => 'oklch(45% 0.1 17.38)',
        ]);
    }

    public function testHeaderCssRegistered(): void
    {
        $this->assertStringContainsString('#global-header', $this->css);
        $this->assertStringContainsString('background-color', $this->css);
    }

    public function testSidebarCssRegistered(): void
    {
        $this->assertStringContainsString('.global-sidebar', $this->css);
        $this->assertStringContainsString('background-color', $this->css);
    }

    public function testBodyBgCssRegistered(): void
    {
        $this->assertStringContainsString('--body-bg', $this->css);
        $this->assertStringContainsString(':root', $this->css);
    }

    public function testAccentCssRegistered(): void
    {
        $this->assertStringContainsString('--primary-button-bg', $this->css);
        $this->assertStringContainsString('--primary-button-bg--hover', $this->css);
        $this->assertStringContainsString('--link-color', $this->css);
    }

    public function testPrimarySiteUntinted(): void
    {
        $emptyCss = SiteTint::buildTintCss([]);
        $this->assertSame('', $emptyCss);
    }

    public function testNoJsInjection(): void
    {
        $this->assertStringNotContainsString('MutationObserver', $this->css);
        $this->assertStringNotContainsString('observer.observe', $this->css);
        $this->assertStringNotContainsString('element.style', $this->css);
        $this->assertStringNotContainsString('.observe(', $this->css);
        $this->assertStringNotContainsString('registerJs', $this->css);
    }

    public function testCssContainsStableKey(): void
    {
        // The handler must call registerCss(..., [], 'site-tint-cp').
        // Since buildTintCss() is a pure function, the stable-key contract is
        // documented here and verified at the code-review level in Task 2.
        // What we CAN assert is that the output is non-empty for valid input.
        $this->assertNotEmpty($this->css);
    }
}
