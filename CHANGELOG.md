# Release Notes for site-tint

## 2.0.0
### Added
- Per-area control panel theming: sidebar, header, content, and controls each get their own background/text/hover colors (17 fields total, all optional).
- A curated preset gallery (Vintage Burgundy, Forest, Slate, Ocean, Terracotta) per site — clicking a preset fills the color fields, which stay fully editable afterward.
- A live mockup preview and swatch strip on the settings page that update as fields change.
- A "Revert to native" button per site that clears its theme so it renders with stock Craft CP styling.
- A migration (`m260705_120000_v2_themes`) that converts existing v1 overrides to the new grouped shape automatically on `craft up`.

### Changed
- Settings are now stored as `themes` (grouped per site UID) instead of `overrides` (flat per site UID). The settings page UI has been fully redesigned around this shape.

### Removed
- **Breaking:** the v1 hash-based color palette that auto-assigned a theme to any site without saved overrides. A site with no saved theme now renders with native Craft styling instead of an assigned color.

## 1.0.0
- Initial release
