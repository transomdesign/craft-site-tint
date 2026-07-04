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

Go to **Settings → Site Tint**. The primary site always renders with native Craft styling and isn't themeable. Every other site gets its own section with:

- A **preset gallery** — click a preset to fill that site's fields; the values are copied in and remain fully editable.
- A **live mockup** and swatch strip that update as you edit fields.
- Four color groups, all optional — an unset field falls back to native Craft styling:
  - **Sidebar** — background, text, text (hover), background (hover), background (active), text (active)
  - **Header** — background, text, text (hover)
  - **Content** — background, pane background, text
  - **Controls** — button background, button text, button background (hover), link, focus ring
- A **Revert to native** button that clears every field for that site.

Nothing takes effect until you click Save.

Brought to you by [Transom](https://transom.design)
