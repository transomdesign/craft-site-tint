# Site Tint plugin for Craft CMS 5

Site Tint colors the Craft control panel based on the active site. Useful for multi-site setups: give each site its own look and you'll never confuse one dashboard for another.

## Requirements

This plugin requires Craft CMS 5.9.0 or later.

## Installation

```bash
composer require transom/craft-site-tint
./craft plugin/install site-tint
```

## Settings

Go to Settings → Site Tint in the control panel. The primary site always keeps native Craft styling; every other site gets its own panel.

Each site has nine presets to start from. Clicking one fills the color fields, and you can tweak any of them before saving. A live mockup and swatch strip show the result as you edit.

Colors are grouped into four areas, and every field is optional. Anything you leave empty stays native:

- Sidebar: background, text, text (hover), background (hover), background (active), text (active)
- Header: background, text, text (hover)
- Content: background, pane background, text
- Controls: button background, button text, button background (hover), link, focus ring

The "Revert to native" button clears every field for that site. Nothing is applied until you save.

Brought to you by [Transom](https://transom.design)
