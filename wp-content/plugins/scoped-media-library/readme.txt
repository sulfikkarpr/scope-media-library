=== Scoped Media Library – Filter Images by Dimensions ===
Contributors: scopingtools
Tags: media library, images, dimensions, ACF, Beaver Builder, admin
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scoped Media Library allows you to control which images appear in the WordPress media library selector.

== Description ==

Scoped Media Library lets you define dimension rules (min/max width and height) so that only images within those limits are displayed in the media modal. It stores image dimensions on upload and filters the AJAX/REST queries used by the media modal, ACF, Beaver Builder, and other tools.

Features

* Filter by dimensions: Set minimum and maximum width/height for images.
* Scoped media modal: The WordPress media modal only displays matching images.
* Faster workflows: Avoid scrolling through irrelevant files.
* ACF & Beaver Builder compatible.
* Fallback mode: Allow specific users to see all images.
* Automatic metadata sync: Dimensions are stored on upload.
* Developer hooks for per-field overrides via `sml_current_rules`.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/scoped-media-library/` or install via the Plugins screen.
2. Activate the plugin through the Plugins screen.
3. Go to Settings → Scoped Media Library to define your dimension rules.
4. Open any media selector. Only images within the defined scope will appear.

== Frequently Asked Questions ==

**How does fallback work?**

Administrators (or another capability you choose) can bypass the scope and see all images. Configure this under Settings → Scoped Media Library.

**Can I set different rules for different fields?**

Yes. Developers can hook `sml_current_rules` and inspect request context (e.g., field keys) to return different min/max values.

== Changelog ==

= 1.0.0 =
* Initial release.

