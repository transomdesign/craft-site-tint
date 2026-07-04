# Release Notes for site-tint

## 2.0.0
- Colors are now set per area of the control panel. Sidebar, header, content, and controls each have their own background, text, and hover fields (17 per site, all optional; empty fields keep native Craft styling).
- Nine preset themes, light and dark, all checked against WCAG AA contrast. Clicking a preset fills the fields, and you can tweak them before saving.
- The settings page has a live mockup preview and a "Revert to native" button per site.
- Existing v1 overrides are migrated to the new format on `craft up`.
- Breaking: the hash-based auto palette is gone. Sites without a saved theme now render with native Craft styling.

## 1.0.0
- Initial release
