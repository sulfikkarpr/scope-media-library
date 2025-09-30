## Scoped Media Library – Filter Images by Dimensions

Scoped Media Library allows you to control which images appear in the WordPress media library selector (used by ACF, Beaver Builder, and other plugins). Define dimension rules (min/max width and height) so only matching images display.

### Features
- Filter by dimensions
- Scoped media modal (ACF, Beaver Builder compatible)
- Fallback mode for admins
- Automatic metadata sync on upload
- Optional extensibility via filters

### Installation
1. Upload the `scoped-media-library` folder to `wp-content/plugins/`
2. Activate in Plugins → Installed Plugins
3. Go to Settings → Scoped Media Library to configure rules

### Filters
- `scoped_media_library/get_dimension_rules`: Override rules dynamically per context
- `scoped_media_library/user_can_bypass`: Control who can see all images

