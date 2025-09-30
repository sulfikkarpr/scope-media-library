# Installation & Setup Guide

## Scoped Media Library - Filter Images by Dimensions

This guide will walk you through the complete installation and configuration process.

---

## 📦 Installation Methods

### Method 1: WordPress Admin Panel (Recommended)

1. **Download the Plugin**
   - Download the `scoped-media-library.zip` file

2. **Upload via WordPress**
   - Log in to your WordPress admin panel
   - Navigate to **Plugins → Add New**
   - Click **Upload Plugin** at the top
   - Choose the ZIP file and click **Install Now**
   - Click **Activate Plugin**

### Method 2: Manual Upload via FTP

1. **Extract the ZIP file**
   - Extract `scoped-media-library.zip` to your computer

2. **Upload via FTP**
   - Connect to your server via FTP
   - Navigate to `/wp-content/plugins/`
   - Upload the `scoped-media-library` folder
   - Go to WordPress admin → **Plugins**
   - Activate **Scoped Media Library**

### Method 3: WordPress.org Repository (Future)

```bash
# Once published on WordPress.org
wp plugin install scoped-media-library --activate
```

---

## ⚙️ Initial Configuration

### Step 1: Access Settings

1. Log in to WordPress admin
2. Navigate to **Settings → Scoped Media Library**
3. You'll see the configuration page

### Step 2: Enable Filtering

1. Check **"Enable Filtering"**
2. This activates dimension-based filtering

### Step 3: Set Dimension Rules

Configure your dimension constraints based on your needs:

#### Example 1: Banner Images (1920×600 to 3840×1200)
```
Minimum Width: 1920
Maximum Width: 3840
Minimum Height: 600
Maximum Height: 1200
```

#### Example 2: Square Icons (100×100 to 200×200)
```
Minimum Width: 100
Maximum Width: 200
Minimum Height: 100
Maximum Height: 200
```

#### Example 3: Only Minimum Requirements
```
Minimum Width: 1920
Maximum Width: (leave empty)
Minimum Height: 1080
Maximum Height: (leave empty)
```

### Step 4: Configure Fallback Mode (Optional)

1. Check **"Enable Fallback Mode"** if you want certain users to see all images
2. Select user roles that should have fallback access
3. Recommended: Keep **Administrator** selected

### Step 5: Sync Existing Images

1. Click **"Sync All Image Dimensions"** button
2. Wait for the process to complete
3. You'll see a success message with the count of synced images

### Step 6: Save Settings

1. Click **"Save Settings"**
2. Your configuration is now active!

---

## 🧪 Testing the Plugin

### Test 1: Media Modal

1. Create or edit a post/page
2. Click **Add Media** or any image selector
3. The media library should now show only images matching your dimension rules

### Test 2: ACF Image Field

1. Edit a post with an ACF image field
2. Click to select an image
3. Only dimension-matching images should appear

### Test 3: Page Builder

1. Open Beaver Builder, Elementor, or your page builder
2. Add an image module
3. Click to select an image
4. Verify filtered results

---

## 🔧 Advanced Configuration

### For Developers: Custom Filters

#### Filter dimension rules programmatically:

```php
add_filter( 'sml_filter_dimensions', function( $dimensions, $context ) {
    // Override for specific ACF field
    if ( $context === 'acf_field_banner_image' ) {
        $dimensions['min_width'] = 1920;
        $dimensions['max_width'] = 3840;
        $dimensions['min_height'] = 600;
        $dimensions['max_height'] = 1200;
    }
    return $dimensions;
}, 10, 2 );
```

#### Custom fallback access logic:

```php
add_filter( 'sml_fallback_access', function( $has_access, $user ) {
    // Give fallback access to users with specific capability
    if ( user_can( $user, 'edit_theme_options' ) ) {
        return true;
    }
    return $has_access;
}, 10, 2 );
```

---

## 📊 Database Optimization

The plugin creates custom meta fields for efficient filtering:

- `_sml_width` - Image width in pixels
- `_sml_height` - Image height in pixels  
- `_sml_synced` - Last sync timestamp

These are automatically indexed by WordPress for fast queries.

---

## 🚨 Troubleshooting

### Issue: Images not filtering

**Solution:**
1. Make sure filtering is **enabled** in settings
2. Run the **"Sync All Image Dimensions"** tool
3. Check that your dimension values are valid numbers
4. Clear WordPress object cache if using caching plugins

### Issue: Too many/few images showing

**Solution:**
1. Review your min/max values in settings
2. Check if fallback mode is enabled for your user role
3. Verify image dimensions in the media library columns

### Issue: Sync button not working

**Solution:**
1. Check browser console for JavaScript errors
2. Verify AJAX is enabled in WordPress
3. Ensure you have administrator permissions
4. Try disabling other plugins temporarily

### Issue: Performance slow

**Solution:**
1. Ensure images are synced (run sync tool)
2. Update to latest WordPress version
3. Check database for corruption
4. Consider upgrading hosting if library is very large (10,000+ images)

---

## 🔄 Migration & Updates

### Updating the Plugin

1. Backup your site first
2. Update via WordPress admin or FTP
3. Existing settings are preserved
4. No need to re-sync unless advised in changelog

### Migrating Settings

Settings are stored in the database option: `sml_settings`

Export settings:
```php
$settings = get_option( 'sml_settings' );
echo json_encode( $settings );
```

Import settings:
```php
$settings = json_decode( $json_string, true );
update_option( 'sml_settings', $settings );
```

---

## 🗑️ Uninstallation

### Complete Removal

1. Navigate to **Plugins → Installed Plugins**
2. Deactivate **Scoped Media Library**
3. Click **Delete**
4. All settings and custom meta fields will be removed automatically

### Manual Cleanup (if needed)

```sql
-- Remove plugin options
DELETE FROM wp_options WHERE option_name LIKE 'sml_%';

-- Remove custom meta fields
DELETE FROM wp_postmeta WHERE meta_key IN ('_sml_width', '_sml_height', '_sml_synced');
```

---

## 📞 Support

If you encounter issues:

1. Check this guide first
2. Review the [README.md](README.md) documentation
3. Visit the [WordPress Support Forum](https://wordpress.org/support/plugin/scoped-media-library/)
4. Submit an issue on [GitHub](https://github.com/yourname/scoped-media-library/issues)

---

## ✅ Checklist

- [ ] Plugin installed and activated
- [ ] Settings page accessible
- [ ] Filtering enabled
- [ ] Dimension rules configured
- [ ] Fallback mode configured (if needed)
- [ ] Existing images synced
- [ ] Settings saved
- [ ] Tested with media modal
- [ ] Tested with ACF/page builder (if applicable)
- [ ] Verified filtered results

---

**Congratulations!** Your Scoped Media Library is now configured and ready to streamline your media workflow. 🎉