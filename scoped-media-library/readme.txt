=== Scoped Media Library – Filter Images by Dimensions ===
Contributors: yourname
Tags: media, library, images, dimensions, acf, beaver builder, filter
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Control which images appear in the WordPress media library selector by defining dimension rules. Perfect for ACF, Beaver Builder, and other plugins.

== Description ==

Scoped Media Library allows you to control which images appear in the WordPress media library selector (used by ACF, Beaver Builder, and other plugins). Instead of loading all media items, you can define dimension rules (height and width ranges) so that only images within those limits are displayed in the media modal.

This plugin helps site editors quickly find the right images without scrolling through irrelevant files. It's especially useful for themes or page builders that require specific image sizes (e.g., banners, icons, thumbnails).

You can also enable a fallback option that shows all images alongside scoped results, ensuring administrators and designers still have full access when needed.

= Features =

🔎 **Filter by dimensions**: Set minimum and maximum width/height for images.
🎯 **Scoped media modal**: The WordPress media modal only displays matching images.
⚡ **Faster workflows**: No need to search through thousands of unrelated images.
🧩 **ACF & Beaver Builder compatible**: Works with custom fields and builders that use the media selector.
🔓 **Fallback mode**: Allow users (admins) to see all images if needed.
🛠️ **Automatic metadata sync**: Image dimensions are stored on upload for accurate filtering.
🎨 **Configurable per field** (optional): Developers can extend rules to match different fields with different size requirements.

= Use Cases =

* ACF image fields that require icons of exactly 150×60 pixels.
* Page builder rows that require background images larger than 1920px wide.
* Restricting content editors to only upload/select properly scaled banner graphics.
* Improving site performance by preventing oversized or undersized image selection.

= How It Works =

1. Install and activate the plugin
2. Go to Settings → Scoped Media Library
3. Set your dimension rules (min/max width and height)
4. Open any media selector (ACF, Beaver Builder, etc.)
5. Only images within your defined scope will appear

= Compatibility =

* Advanced Custom Fields (ACF)
* Beaver Builder
* Elementor
* Gutenberg blocks
* Any plugin using the WordPress media modal

== Installation ==

= Automatic Installation =

1. Go to your WordPress admin dashboard
2. Navigate to Plugins → Add New
3. Search for "Scoped Media Library"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Upload the plugin files to the `/wp-content/plugins/scoped-media-library/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Scoped Media Library to configure your dimension rules

== Frequently Asked Questions ==

= Does this plugin delete or modify my images? =

No, this plugin only filters which images are displayed in the media selector. Your original images remain untouched.

= Can I still access all images if needed? =

Yes, if you enable "Fallback Mode" in the settings, administrators can toggle between scoped and all images in the media modal.

= Will this work with my page builder? =

The plugin works with any tool that uses the standard WordPress media modal, including ACF, Beaver Builder, Elementor, and Gutenberg.

= What happens to images uploaded before installing the plugin? =

The plugin will automatically sync dimension metadata for existing images. You can also manually trigger a sync from the settings page.

= Can I set different rules for different fields? =

The current version applies global rules to all media selectors. Field-specific rules are planned for a future version.

== Screenshots ==

1. Settings page with dimension rules configuration
2. Media modal showing only scoped images
3. Fallback toggle in media modal
4. Statistics dashboard showing scoped vs total images

== Changelog ==

= 1.0.0 =
* Initial release
* Dimension-based filtering for media library
* Fallback mode for administrators
* Automatic metadata synchronization
* ACF and page builder compatibility
* Statistics and preview functionality

== Upgrade Notice ==

= 1.0.0 =
Initial release of Scoped Media Library. Configure your dimension rules and start filtering your media library today!

== Developer Notes ==

= Hooks and Filters =

The plugin provides several hooks for developers:

**Actions:**
* `sml_metadata_synced` - Fired when image metadata is synced
* `sml_batch_sync_complete` - Fired when batch sync completes

**Filters:**
* `sml_dimension_rules` - Filter the dimension rules before applying
* `sml_should_filter_query` - Control whether a specific query should be filtered

= Extending the Plugin =

You can extend the plugin's functionality by hooking into the provided actions and filters. For example:

`
// Custom dimension rules based on context
add_filter('sml_dimension_rules', function($rules, $context) {
    if ($context === 'acf_field_banner') {
        return array(
            'min_width' => 1920,
            'max_width' => 9999,
            'min_height' => 400,
            'max_height' => 800
        );
    }
    return $rules;
}, 10, 2);
`