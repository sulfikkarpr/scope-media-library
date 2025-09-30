# Scoped Media Library - Filter Images by Dimensions

![WordPress Plugin](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)

Control which images appear in the WordPress media library selector by defining dimension rules. Perfect for ACF, Beaver Builder, and other page builders.

## 📋 Description

**Scoped Media Library** allows you to control which images appear in the WordPress media library selector (used by ACF, Beaver Builder, and other plugins). Instead of loading all media items, you can define dimension rules (height and width ranges) so that only images within those limits are displayed in the media modal.

This plugin helps site editors quickly find the right images without scrolling through irrelevant files. It's especially useful for themes or page builders that require specific image sizes (e.g., banners, icons, thumbnails).

You can also enable a fallback option that shows all images alongside scoped results, ensuring administrators and designers still have full access when needed.

## ✨ Features

- 🔎 **Filter by dimensions**: Set minimum and maximum width/height for images
- 🎯 **Scoped media modal**: The WordPress media modal only displays matching images
- ⚡ **Faster workflows**: No need to search through thousands of unrelated images
- 🧩 **ACF & Beaver Builder compatible**: Works with custom fields and builders that use the media selector
- 🔓 **Fallback mode**: Allow users (admins) to see all images if needed
- 🛠️ **Automatic metadata sync**: Image dimensions are stored on upload for accurate filtering
- 🎨 **Configurable per role**: Define which user roles can bypass filters
- 📊 **Dimensions column**: View image dimensions directly in the media library
- 🚀 **Performance optimized**: Efficient database queries for fast filtering

## 🎯 Use Cases

- ACF image fields that require icons of exactly 150×60 pixels
- Page builder rows that require background images larger than 1920px wide
- Restricting content editors to only upload/select properly scaled banner graphics
- Improving site performance by preventing oversized or undersized image selection
- Managing large media libraries with thousands of images
- Enforcing brand guidelines for image dimensions

## 📥 Installation

### Automatic Installation

1. Log in to your WordPress admin panel
2. Navigate to **Plugins → Add New**
3. Search for "Scoped Media Library"
4. Click "Install Now" and then "Activate"

### Manual Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/scoped-media-library/` directory
3. Activate through the WordPress Plugins screen
4. Go to **Settings → Scoped Media Library** to configure

## ⚙️ Configuration

1. Navigate to **Settings → Scoped Media Library**
2. Enable filtering by checking **"Enable Filtering"**
3. Set your dimension constraints:
   - **Minimum Width (px)**: e.g., 1200
   - **Maximum Width (px)**: e.g., 1920
   - **Minimum Height (px)**: e.g., 600
   - **Maximum Height (px)**: e.g., 1080
4. Configure **Fallback Mode** if needed (allow admins to see all images)
5. Click **"Sync All Image Dimensions"** to process existing images
6. Save settings

## 🔧 Usage Examples

### Example 1: Banner Images
```
Minimum Width: 1920px
Maximum Width: 3840px
Minimum Height: 600px
Maximum Height: 1200px
```
Perfect for header banners and hero sections.

### Example 2: Thumbnail Icons
```
Minimum Width: 100px
Maximum Width: 200px
Minimum Height: 100px
Maximum Height: 200px
```
Ideal for icon libraries and thumbnail grids.

### Example 3: Large Backgrounds
```
Minimum Width: 1920px
Minimum Height: 1080px
(No maximum constraints)
```
Ensures only high-resolution background images are selectable.

## 🔌 Compatibility

Works seamlessly with:

- ✅ ACF (Advanced Custom Fields)
- ✅ Beaver Builder
- ✅ Elementor
- ✅ Gutenberg (WordPress Block Editor)
- ✅ WPBakery Page Builder
- ✅ Divi Builder
- ✅ Any plugin using the standard WordPress media modal

## 👨‍💻 Developer Hooks

### Filters

#### `sml_filter_dimensions`
Modify dimension constraints programmatically:

```php
add_filter( 'sml_filter_dimensions', function( $dimensions, $context ) {
    if ( $context === 'acf_field_123' ) {
        $dimensions['min_width'] = 500;
        $dimensions['max_width'] = 1000;
    }
    return $dimensions;
}, 10, 2 );
```

#### `sml_fallback_access`
Control fallback access logic:

```php
add_filter( 'sml_fallback_access', function( $has_access, $user ) {
    // Custom logic for fallback access
    return $has_access;
}, 10, 2 );
```

#### `sml_query_args`
Customize media query arguments:

```php
add_filter( 'sml_query_args', function( $args ) {
    // Modify query arguments
    return $args;
} );
```

## 📊 Database Structure

The plugin stores image dimensions in custom post meta fields:

- `_sml_width`: Image width in pixels
- `_sml_height`: Image height in pixels
- `_sml_synced`: Timestamp of last sync

These fields enable efficient database queries without performance impact.

## 🚀 Performance

- **Optimized queries**: Uses indexed meta fields for fast filtering
- **No frontend impact**: Filtering only occurs in admin/media modal
- **Minimal overhead**: Dimension sync happens only on upload
- **Cached results**: WordPress object cache compatible

## ❓ FAQ

**Q: Does this work with ACF (Advanced Custom Fields)?**  
A: Yes! The plugin filters the WordPress media modal used by ACF.

**Q: Does this work with Beaver Builder?**  
A: Absolutely! Beaver Builder uses the standard WordPress media modal.

**Q: What happens to images that don't match my rules?**  
A: They remain in your media library but won't appear in the filtered media selector.

**Q: Will this slow down my site?**  
A: No! The plugin uses efficient database queries and only filters when opening the media modal.

**Q: Can I set different rules for different fields?**  
A: The current version applies global rules. Field-specific rules can be implemented using developer hooks.

**Q: What if I have existing images?**  
A: Use the "Sync All Image Dimensions" button to process your existing media library.

## 🗺️ Roadmap

- [ ] Field-specific dimension rules
- [ ] Image aspect ratio filtering
- [ ] File size filtering
- [ ] MIME type filtering
- [ ] Multiple dimension rule sets
- [ ] Import/export settings
- [ ] Bulk actions for unscoped images
- [ ] REST API endpoints
- [ ] WP-CLI commands

## 📝 Changelog

### 1.0.0 - 2025-09-30
- Initial release
- Filter media library by width and height dimensions
- Support for min/max constraints on both dimensions
- Fallback mode for administrators
- Automatic dimension sync on upload
- Bulk sync tool for existing images
- ACF and Beaver Builder compatibility
- Dimensions column in media library
- Performance optimized queries

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 💬 Support

For support, feature requests, or bug reports:

- [WordPress Plugin Support Forum](https://wordpress.org/support/plugin/scoped-media-library/)
- [GitHub Issues](https://github.com/yourname/scoped-media-library/issues)

## 🙏 Credits

Developed with ❤️ for the WordPress community.

---

**Note**: Replace `yourname` in URLs with your actual username/organization name before publishing.