# Changelog

All notable changes to the Scoped Media Library plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-09-30

### Added
- Initial release of Scoped Media Library
- Filter media library by width and height dimensions
- Support for minimum and maximum width constraints
- Support for minimum and maximum height constraints
- Fallback mode for administrators and custom user roles
- Automatic dimension sync on image upload
- Bulk sync tool for existing images in media library
- ACF (Advanced Custom Fields) compatibility
- Beaver Builder compatibility
- Elementor compatibility
- Gutenberg (WordPress Block Editor) compatibility
- Settings page with intuitive configuration interface
- Dimensions column in media library list view
- Performance optimized database queries using custom meta fields
- Admin notice in media modal showing active filters
- Form validation for dimension constraints
- AJAX-powered bulk sync with progress feedback
- Developer hooks and filters for extensibility
- Comprehensive documentation (README.md and readme.txt)
- WordPress coding standards compliance
- Security best practices (nonce verification, capability checks)
- Internationalization support (i18n ready)
- Uninstall cleanup routine

### Security
- Nonce verification for AJAX requests
- Capability checks for admin functions
- Input sanitization and validation
- Output escaping for XSS prevention
- SQL query preparation using wpdb

### Performance
- Indexed meta fields for fast queries
- Efficient database queries with proper joins
- No frontend performance impact
- Cached dimension data on upload
- Optimized for large media libraries

## [Unreleased]

### Planned Features
- Field-specific dimension rules
- Image aspect ratio filtering
- File size filtering
- MIME type filtering
- Multiple dimension rule sets
- Import/export settings functionality
- Bulk actions for unscoped images
- REST API endpoints
- WP-CLI commands
- Advanced reporting dashboard
- Integration with popular page builders
- Custom dimension presets library