# Installation Guide - Scoped Media Library

This guide will help you install and configure the Scoped Media Library plugin for WordPress.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher (or MariaDB equivalent)

## Installation Methods

### Method 1: WordPress Admin Dashboard (Recommended)

1. **Login to your WordPress admin dashboard**
2. **Navigate to Plugins → Add New**
3. **Search for "Scoped Media Library"**
4. **Click "Install Now"** next to the plugin
5. **Click "Activate"** after installation completes

### Method 2: Manual Upload via WordPress Admin

1. **Download the plugin ZIP file** from the WordPress.org repository
2. **Login to your WordPress admin dashboard**
3. **Navigate to Plugins → Add New → Upload Plugin**
4. **Choose the downloaded ZIP file** and click "Install Now"
5. **Click "Activate Plugin"** after installation completes

### Method 3: FTP/SFTP Upload

1. **Download and extract the plugin ZIP file**
2. **Upload the `scoped-media-library` folder** to your `/wp-content/plugins/` directory via FTP/SFTP
3. **Login to your WordPress admin dashboard**
4. **Navigate to Plugins → Installed Plugins**
5. **Find "Scoped Media Library"** and click "Activate"

### Method 4: WP-CLI (Command Line)

```bash
# Install the plugin
wp plugin install scoped-media-library

# Activate the plugin
wp plugin activate scoped-media-library
```

## Initial Configuration

### Step 1: Access Plugin Settings

1. **Navigate to Settings → Scoped Media Library** in your WordPress admin
2. You'll see the plugin configuration page with several sections

### Step 2: Configure Basic Settings

**General Settings:**
- ✅ **Enable Filtering**: Check this box to activate dimension-based filtering

**Dimension Rules:**
- **Minimum Width**: Enter the smallest width (in pixels) for images you want to show
- **Maximum Width**: Enter the largest width (in pixels) for images you want to show  
- **Minimum Height**: Enter the smallest height (in pixels) for images you want to show
- **Maximum Height**: Enter the largest height (in pixels) for images you want to show

### Step 3: Configure Advanced Settings

**Fallback Mode:**
- ✅ **Enable Fallback Mode**: Allows toggling between scoped and all images
- ✅ **Admin Only Fallback**: Restricts fallback toggle to administrators only

### Step 4: Sync Existing Images

1. **Click the "Sync Now" button** to process existing images in your media library
2. **Wait for the sync to complete** - this may take a few minutes for large libraries
3. The sync will run in batches to prevent timeouts

## Common Configuration Examples

### Example 1: Icon Images Only
```
Minimum Width: 100px
Maximum Width: 200px
Minimum Height: 100px
Maximum Height: 200px
```

### Example 2: Banner Images Only
```
Minimum Width: 1920px
Maximum Width: 9999px
Minimum Height: 400px
Maximum Height: 800px
```

### Example 3: Thumbnail Images
```
Minimum Width: 300px
Maximum Width: 600px
Minimum Height: 200px
Maximum Height: 400px
```

## Testing Your Configuration

### Test the Media Selector

1. **Open any page/post editor**
2. **Add an image block** or use ACF image field
3. **Click "Select Image"** to open the media modal
4. **Verify** that only images matching your criteria are shown

### Use the Preview Feature

1. **Go to Settings → Scoped Media Library**
2. **Adjust your dimension settings**
3. **Look for the preview section** that shows matching images
4. **Fine-tune your settings** based on the preview results

## Troubleshooting

### No Images Appear in Media Modal

**Possible causes:**
- Dimension rules are too restrictive
- Images haven't been synced yet
- Plugin is not activated

**Solutions:**
1. **Relax your dimension rules** (increase max values, decrease min values)
2. **Run the metadata sync** from Settings → Scoped Media Library
3. **Check that the plugin is activated** and filtering is enabled

### Some Images Are Missing

**Possible causes:**
- Images don't have synced metadata
- Images don't meet dimension criteria

**Solutions:**
1. **Run the metadata sync** to process all images
2. **Review your dimension rules** to ensure they're not too restrictive
3. **Check individual image dimensions** in the media library

### Fallback Mode Not Working

**Possible causes:**
- Fallback mode is disabled
- User doesn't have admin privileges
- JavaScript conflicts

**Solutions:**
1. **Enable fallback mode** in plugin settings
2. **Check user permissions** - only admins can use fallback by default
3. **Check browser console** for JavaScript errors

### Plugin Conflicts

**Common conflicts:**
- Other media library plugins
- Page builder plugins with custom media selectors
- Caching plugins

**Solutions:**
1. **Deactivate other media plugins** temporarily to test
2. **Clear all caches** after configuration changes
3. **Check plugin compatibility** in the documentation

## Performance Considerations

### Large Media Libraries

For sites with thousands of images:
- **Run metadata sync during low-traffic periods**
- **Use smaller batch sizes** if you experience timeouts
- **Consider using WP-CLI** for bulk operations

### Caching

- **Clear object cache** after configuration changes
- **Exclude admin pages** from page caching
- **Test with caching disabled** if experiencing issues

## Getting Help

### Documentation
- **Plugin Settings Page**: Built-in help text and examples
- **GitHub Repository**: [Link to repository]
- **WordPress.org Support**: [Link to support forum]

### Debug Information

If you need to report an issue, include:
- WordPress version
- PHP version
- Plugin version
- Active plugins list
- Theme name
- Error messages (if any)

### Support Channels

1. **WordPress.org Support Forum**: For general questions
2. **GitHub Issues**: For bug reports and feature requests
3. **Plugin Settings Page**: Built-in documentation and examples

## Next Steps

After successful installation:

1. **Configure your dimension rules** based on your needs
2. **Test with different content types** (ACF fields, page builders, etc.)
3. **Train your content editors** on the new workflow
4. **Monitor performance** and adjust settings as needed
5. **Consider advanced configurations** for complex use cases

## Advanced Setup

For developers and advanced users:

### Custom Configurations
- Review the `examples/config-examples.php` file for advanced setups
- Use hooks and filters to customize behavior
- Integrate with custom post types and fields

### WP-CLI Commands
```bash
# Get plugin statistics
wp sml stats

# Sync metadata in batches
wp sml sync --batch-size=100
```

### REST API Endpoints
- `GET /wp-json/sml/v1/stats` - Get plugin statistics
- `POST /wp-json/sml/v1/sync` - Trigger metadata sync

---

**Need more help?** Check the plugin's documentation or reach out to the support community!