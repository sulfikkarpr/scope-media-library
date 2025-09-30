# Scoped Media Library – Filter Images by Dimensions

A WordPress plugin that allows you to control which images appear in the WordPress media library selector by defining dimension rules. Perfect for ACF, Beaver Builder, and other plugins that require specific image sizes.

## Features

- 🔎 **Filter by dimensions**: Set minimum and maximum width/height for images
- 🎯 **Scoped media modal**: The WordPress media modal only displays matching images
- ⚡ **Faster workflows**: No need to search through thousands of unrelated images
- 🧩 **ACF & Beaver Builder compatible**: Works with custom fields and builders that use the media selector
- 🔓 **Fallback mode**: Allow users (admins) to see all images if needed
- 🛠️ **Automatic metadata sync**: Image dimensions are stored on upload for accurate filtering
- 🎨 **Configurable per field** (optional): Developers can extend rules to match different fields with different size requirements

## Installation

### Via WordPress Admin (Recommended)

1. Go to your WordPress admin dashboard
2. Navigate to Plugins → Add New
3. Search for "Scoped Media Library"
4. Click "Install Now" and then "Activate"

### Manual Installation

1. Upload the plugin files to the `/wp-content/plugins/scoped-media-library/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Scoped Media Library to configure your dimension rules

### Via Composer

```bash
composer require yourname/scoped-media-library
```

## Configuration

1. Navigate to **Settings → Scoped Media Library** in your WordPress admin
2. Configure your dimension rules:
   - **Minimum Width**: Set the minimum image width in pixels
   - **Maximum Width**: Set the maximum image width in pixels
   - **Minimum Height**: Set the minimum image height in pixels
   - **Maximum Height**: Set the maximum image height in pixels
3. Enable **Fallback Mode** if you want administrators to toggle between scoped and all images
4. Click **Save Changes**

## Use Cases

- **ACF image fields** that require icons of exactly 150×60 pixels
- **Page builder rows** that require background images larger than 1920px wide
- **Restricting content editors** to only upload/select properly scaled banner graphics
- **Improving site performance** by preventing oversized or undersized image selection

## Compatibility

This plugin works with any tool that uses the standard WordPress media modal:

- Advanced Custom Fields (ACF)
- Beaver Builder
- Elementor
- Gutenberg blocks
- Custom themes and plugins

## Developer Documentation

### Hooks and Filters

#### Actions

- `sml_metadata_synced` - Fired when image metadata is synced
  ```php
  add_action('sml_metadata_synced', function($attachment_id, $width, $height) {
      // Custom logic after metadata sync
  }, 10, 3);
  ```

- `sml_batch_sync_complete` - Fired when batch sync completes
  ```php
  add_action('sml_batch_sync_complete', function($synced, $failed, $remaining) {
      // Custom logic after batch sync
  }, 10, 3);
  ```

#### Filters

- `sml_dimension_rules` - Filter the dimension rules before applying
  ```php
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
  ```

- `sml_should_filter_query` - Control whether a specific query should be filtered
  ```php
  add_filter('sml_should_filter_query', function($should_filter, $query_args) {
      // Skip filtering for specific contexts
      if (isset($_GET['skip_sml_filter'])) {
          return false;
      }
      return $should_filter;
  }, 10, 2);
  ```

### API Functions

#### Get Attachment Dimensions

```php
$metadata_sync = new SML_Metadata_Sync();
$dimensions = $metadata_sync->get_attachment_dimensions($attachment_id);
// Returns: array('width' => 1920, 'height' => 1080)
```

#### Check if Attachment Needs Sync

```php
$metadata_sync = new SML_Metadata_Sync();
$needs_sync = $metadata_sync->needs_sync($attachment_id);
// Returns: boolean
```

#### Get Plugin Statistics

```php
$options = get_option('sml_options', array());
$media_filter = new SML_Media_Filter($options);
$stats = $media_filter->get_dimension_stats();
// Returns: array with total_images, scoped_images, without_metadata counts
```

### JavaScript API

The plugin provides JavaScript events for frontend integration:

```javascript
// Listen for fallback toggle
$(document).on('sml:fallback_toggled', function(event, data) {
    console.log('Fallback mode:', data.active);
});

// Listen for filter changes
$(document).on('sml:filter_changed', function(event, data) {
    console.log('New filter rules:', data.rules);
});
```

## File Structure

```
scoped-media-library/
├── scoped-media-library.php    # Main plugin file
├── uninstall.php               # Cleanup on uninstall
├── readme.txt                  # WordPress.org readme
├── README.md                   # Developer documentation
├── includes/                   # Core plugin classes
│   ├── class-sml-admin.php
│   ├── class-sml-media-filter.php
│   ├── class-sml-metadata-sync.php
│   └── class-sml-ajax-handler.php
├── assets/                     # Frontend assets
│   ├── css/
│   │   ├── admin.css
│   │   └── media.css
│   └── js/
│       ├── admin.js
│       └── media.js
└── languages/                  # Translation files
    └── scoped-media-library.pot
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
# Clone the repository
git clone https://github.com/yourname/scoped-media-library.git

# Install dependencies (if using Composer)
composer install

# Install npm dependencies (if using build tools)
npm install
```

### Coding Standards

This plugin follows WordPress coding standards:

- PHP: [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- JavaScript: [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- CSS: [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)

## Testing

The plugin includes unit tests and integration tests:

```bash
# Run PHP unit tests
phpunit

# Run JavaScript tests
npm test

# Run WordPress integration tests
./bin/install-wp-tests.sh wordpress_test root '' localhost latest
phpunit
```

## Changelog

### 1.0.0
- Initial release
- Dimension-based filtering for media library
- Fallback mode for administrators
- Automatic metadata synchronization
- ACF and page builder compatibility
- Statistics and preview functionality

## Support

- **Documentation**: [Plugin Documentation](https://github.com/yourname/scoped-media-library/wiki)
- **Issues**: [GitHub Issues](https://github.com/yourname/scoped-media-library/issues)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/scoped-media-library/)

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Credits

- Developed by [Your Name](https://yourwebsite.com)
- Icons by [Feather Icons](https://feathericons.com/)
- Inspired by the WordPress community's need for better media management

---

**Made with ❤️ for the WordPress community**