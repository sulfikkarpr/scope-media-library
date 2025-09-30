=== Scoped Media Library – Filter Images by Dimensions ===
Contributors: yourname
Tags: media library, images, filter, ACF, beaver builder
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scoped Media Library lets you control which images appear in the WordPress media selector by defining min/max width and height. Editors see only the images that match your rules, speeding up workflows while keeping full access with an optional fallback.

== Description ==
- Filter by dimensions: Set minimum and maximum width/height for images.
- Scoped media modal: The WordPress media modal displays only matching images.
- Faster workflows: No need to search through thousands of unrelated images.
- ACF & Beaver Builder compatible: Works with custom fields and builders that use the media selector.
- Fallback mode: Allow admins to see all images alongside scoped results.
- Automatic metadata sync: Image dimensions are stored on upload.
- Configurable per field (optional): Developers can override rules via filters.

== Installation ==
1. Upload the plugin files to `wp-content/plugins/scoped-media-library/` or install via Plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to Settings → Scoped Media Library to define dimension rules.
4. Open the media selector (e.g., via ACF or Beaver Builder). Only images within the defined scope will appear.

== Frequently Asked Questions ==
= How does fallback work? =
If enabled, users with `manage_options` capability bypass filtering and can see all images.

= Can I set different rules per field? =
Yes. Developers can hook `sml_settings_for_query` and inspect the media modal request data to return different rules.

== Developer Notes ==
Filters:
- `sml_settings` (array $settings): Override global settings.
- `sml_settings_for_query` (array $settings, array $raw_query): Override settings for a given media query. Inspect `$raw_query` (e.g., ACF field data) and return a modified settings array.

Meta Keys:
- `_sml_width` and `_sml_height` are stored on upload/metadata generation.

== Changelog ==
= 1.0.0 =
- Initial release.
