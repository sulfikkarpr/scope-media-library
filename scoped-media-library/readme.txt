=== Scoped Media Library - Filter Images by Dimensions ===
Contributors: yourname
Tags: media library, image filter, dimensions, ACF, beaver builder, media, images
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Control which images appear in the WordPress media library selector by defining dimension rules. Perfect for ACF, Beaver Builder, and page builders.

== Description ==

**Scoped Media Library** allows you to control which images appear in the WordPress media library selector (used by ACF, Beaver Builder, and other plugins). Instead of loading all media items, you can define dimension rules (height and width ranges) so that only images within those limits are displayed in the media modal.

This plugin helps site editors quickly find the right images without scrolling through irrelevant files. It's especially useful for themes or page builders that require specific image sizes (e.g., banners, icons, thumbnails).

You can also enable a fallback option that shows all images alongside scoped results, ensuring administrators and designers still have full access when needed.

= Features =

* 🔎 **Filter by dimensions**: Set minimum and maximum width/height for images
* 🎯 **Scoped media modal**: The WordPress media modal only displays matching images
* ⚡ **Faster workflows**: No need to search through thousands of unrelated images
* 🧩 **ACF & Beaver Builder compatible**: Works with custom fields and builders that use the media selector
* 🔓 **Fallback mode**: Allow users (admins) to see all images if needed
* 🛠️ **Automatic metadata sync**: Image dimensions are stored on upload for accurate filtering
* 🎨 **Configurable per role**: Define which user roles can bypass filters
* 📊 **Dimensions column**: View image dimensions directly in the media library
* 🚀 **Performance optimized**: Efficient database queries for fast filtering

= Use Cases =

* ACF image fields that require icons of exactly 150×60 pixels
* Page builder rows that require background images larger than 1920px wide
* Restricting content editors to only upload/select properly scaled banner graphics
* Improving site performance by preventing oversized or undersized image selection
* Managing large media libraries with thousands of images
* Enforcing brand guidelines for image dimensions

= How It Works =

1. Install and activate the plugin
2. Go to Settings → Scoped Media Library
3. Define your dimension rules (min/max width and height)
4. Enable filtering and save settings
5. Open any media selector (ACF, Beaver Builder, Gutenberg, etc.)
6. Only images matching your rules will appear!

The plugin automatically stores image dimensions when files are uploaded, ensuring fast and accurate filtering without performance impact.

= Developer Friendly =

The plugin provides hooks and filters for developers to extend functionality:

* `sml_filter_dimensions` - Modify dimension constraints programmatically
* `sml_fallback_access` - Control fallback access logic
* `sml_query_args` - Customize media query arguments

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Scoped Media Library"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin through the Plugins screen

= Configuration =

1. Go to Settings → Scoped Media Library
2. Enable filtering by checking "Enable Filtering"
3. Set your desired dimension constraints:
   * Minimum Width (px)
   * Maximum Width (px)
   * Minimum Height (px)
   * Maximum Height (px)
4. Configure fallback mode if needed
5. Click "Sync All Image Dimensions" to process existing images
6. Save your settings

== Frequently Asked Questions ==

= Does this work with ACF (Advanced Custom Fields)? =

Yes! The plugin filters the WordPress media modal used by ACF, so any ACF image field will respect your dimension rules.

= Does this work with Beaver Builder? =

Absolutely! Beaver Builder uses the standard WordPress media modal, which this plugin filters.

= What happens to images that don't match my rules? =

They remain in your media library but won't appear in the media selector modal. Administrators with fallback access can still see all images if fallback mode is enabled.

= Can I set different rules for different fields? =

The current version applies global dimension rules. Developer hooks are available to implement field-specific rules programmatically.

= Will this slow down my site? =

No! The plugin stores dimensions as separate metadata for efficient database queries. Filtering happens only when opening the media modal and has minimal performance impact.

= What if I have existing images? =

Use the "Sync All Image Dimensions" button on the settings page to process your existing media library. This only needs to be done once.

= Can I disable filtering temporarily? =

Yes, simply uncheck "Enable Filtering" in the settings and save. Your dimension rules will be preserved.

= Does this work with Gutenberg? =

Yes! The plugin filters all instances of the WordPress media modal, including Gutenberg blocks that use image selection.

= Can editors still upload images outside the dimension rules? =

Yes, they can upload any images. The filtering only affects which images appear in the media selector, not what can be uploaded.

== Screenshots ==

1. Settings page with dimension rules configuration
2. Media modal showing only filtered images
3. Dimensions column in media library list view
4. Fallback mode settings for user roles
5. Sync dimensions interface

== Changelog ==

= 1.0.0 - 2025-09-30 =
* Initial release
* Filter media library by width and height dimensions
* Support for min/max constraints on both dimensions
* Fallback mode for administrators
* Automatic dimension sync on upload
* Bulk sync tool for existing images
* ACF and Beaver Builder compatibility
* Dimensions column in media library
* Performance optimized queries

== Upgrade Notice ==

= 1.0.0 =
Initial release of Scoped Media Library.

== Privacy Policy ==

Scoped Media Library does not collect, store, or transmit any personal data. All filtering happens locally within your WordPress installation.

== Support ==

For support, feature requests, or bug reports, please visit:
* Plugin support forum: https://wordpress.org/support/plugin/scoped-media-library/
* GitHub repository: https://github.com/yourname/scoped-media-library

== Credits ==

Developed with ❤️ for the WordPress community.

== Additional Information ==

= Requirements =
* WordPress 5.0 or higher
* PHP 7.2 or higher
* MySQL 5.6 or higher (or equivalent MariaDB version)

= Compatibility =
* ACF (Advanced Custom Fields)
* Beaver Builder
* Elementor
* Gutenberg (WordPress Block Editor)
* WPBakery Page Builder
* Divi Builder
* Any plugin using the standard WordPress media modal

= Roadmap =
* Field-specific dimension rules
* Image aspect ratio filtering
* File size filtering
* MIME type filtering
* Multiple dimension rule sets
* Import/export settings
* Bulk actions for unscoped images