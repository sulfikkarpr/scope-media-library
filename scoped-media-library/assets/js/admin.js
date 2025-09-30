/**
 * Admin JavaScript for Scoped Media Library
 */

(function($) {
    'use strict';
    
    var SMLAdmin = {
        
        init: function() {
            this.bindEvents();
            this.loadStats();
        },
        
        bindEvents: function() {
            // Sync metadata button
            $('#sml-sync-metadata').on('click', this.syncMetadata);
            
            // Test query functionality
            $('.sml-test-query-btn').on('click', this.testQuery);
            
            // Preview filter changes
            $('.sml-dimension-input').on('input', this.debounce(this.previewFilter, 500));
            
            // Import/Export functionality
            $('.sml-export-settings').on('click', this.exportSettings);
            $('.sml-import-settings').on('click', this.importSettings);
            
            // Reset settings
            $('.sml-reset-settings').on('click', this.resetSettings);
            
            // Real-time validation
            $('input[name*="min_width"], input[name*="max_width"]').on('input', this.validateDimensions);
            $('input[name*="min_height"], input[name*="max_height"]').on('input', this.validateDimensions);
        },
        
        syncMetadata: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $status = $('#sml-sync-status');
            
            $button.prop('disabled', true).text(sml_ajax.strings.syncing);
            $status.removeClass('success error').addClass('syncing').html('<span class="sml-spinner"></span>');
            
            $.ajax({
                url: sml_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sml_sync_metadata',
                    nonce: sml_ajax.nonce,
                    batch_size: 50
                },
                success: function(response) {
                    if (response.success) {
                        $status.removeClass('syncing').addClass('success').text(response.data.message);
                        
                        // Continue syncing if there are remaining images
                        if (response.data.remaining > 0) {
                            setTimeout(function() {
                                $('#sml-sync-metadata').trigger('click');
                            }, 2000);
                        } else {
                            $button.prop('disabled', false).text(sml_ajax.strings.sync_complete);
                            SMLAdmin.loadStats(); // Refresh stats
                        }
                    } else {
                        $status.removeClass('syncing').addClass('error').text(sml_ajax.strings.sync_error);
                        $button.prop('disabled', false).text('Sync Now');
                    }
                },
                error: function() {
                    $status.removeClass('syncing').addClass('error').text(sml_ajax.strings.sync_error);
                    $button.prop('disabled', false).text('Sync Now');
                }
            });
        },
        
        testQuery: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $results = $('.sml-test-results');
            
            var data = {
                action: 'sml_test_query',
                nonce: sml_ajax.nonce,
                min_width: $('input[name*="min_width"]').val(),
                max_width: $('input[name*="max_width"]').val(),
                min_height: $('input[name*="min_height"]').val(),
                max_height: $('input[name*="max_height"]').val()
            };
            
            $button.prop('disabled', true).addClass('sml-loading');
            $results.html('<div class="sml-spinner"></div>');
            
            $.ajax({
                url: sml_ajax.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        SMLAdmin.displayTestResults(response.data);
                    } else {
                        $results.html('<div class="sml-notice error">Test failed</div>');
                    }
                },
                error: function() {
                    $results.html('<div class="sml-notice error">Test failed</div>');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('sml-loading');
                }
            });
        },
        
        displayTestResults: function(data) {
            var $results = $('.sml-test-results');
            var html = '<h4>Test Results: ' + data.count + ' images found</h4>';
            
            if (data.results.length > 0) {
                html += '<div class="sml-preview-grid">';
                $.each(data.results, function(i, item) {
                    html += '<div class="sml-preview-item">';
                    html += '<img src="' + item.url + '" alt="' + item.title + '">';
                    html += '<div class="sml-preview-info">' + item.width + 'x' + item.height + '</div>';
                    html += '</div>';
                });
                html += '</div>';
            } else {
                html += '<p>No images match the current criteria.</p>';
            }
            
            $results.html(html);
        },
        
        previewFilter: function() {
            var data = {
                action: 'sml_preview_filter',
                nonce: sml_ajax.nonce,
                min_width: $('input[name*="min_width"]').val(),
                max_width: $('input[name*="max_width"]').val(),
                min_height: $('input[name*="min_height"]').val(),
                max_height: $('input[name*="max_height"]').val()
            };
            
            $.ajax({
                url: sml_ajax.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        SMLAdmin.updatePreview(response.data);
                    }
                }
            });
        },
        
        updatePreview: function(data) {
            var $preview = $('.sml-dimension-preview');
            if ($preview.length === 0) {
                // Create preview container if it doesn't exist
                $preview = $('<div class="sml-dimension-preview"><h4>Preview</h4><div class="sml-preview-content"></div></div>');
                $('.form-table').after($preview);
            }
            
            var html = '<p><strong>' + data.total_found + '</strong> images match these criteria</p>';
            
            if (data.results.length > 0) {
                html += '<div class="sml-preview-grid">';
                $.each(data.results, function(i, item) {
                    html += '<div class="sml-preview-item">';
                    html += '<img src="' + item.thumbnail + '" alt="' + item.title + '">';
                    html += '<div class="sml-preview-info">' + item.width + 'x' + item.height + '</div>';
                    html += '</div>';
                });
                html += '</div>';
            }
            
            $preview.find('.sml-preview-content').html(html);
        },
        
        validateDimensions: function() {
            var minWidth = parseInt($('input[name*="min_width"]').val()) || 0;
            var maxWidth = parseInt($('input[name*="max_width"]').val()) || 9999;
            var minHeight = parseInt($('input[name*="min_height"]').val()) || 0;
            var maxHeight = parseInt($('input[name*="max_height"]').val()) || 9999;
            
            var $widthError = $('.sml-width-error');
            var $heightError = $('.sml-height-error');
            
            // Remove existing error messages
            $widthError.remove();
            $heightError.remove();
            
            // Validate width
            if (minWidth > maxWidth) {
                $('input[name*="max_width"]').after('<div class="sml-notice error sml-width-error">Maximum width must be greater than minimum width</div>');
            }
            
            // Validate height
            if (minHeight > maxHeight) {
                $('input[name*="max_height"]').after('<div class="sml-notice error sml-height-error">Maximum height must be greater than minimum height</div>');
            }
        },
        
        exportSettings: function(e) {
            e.preventDefault();
            
            $.ajax({
                url: sml_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sml_export_settings',
                    nonce: sml_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SMLAdmin.downloadFile(response.data.data, response.data.filename);
                    }
                }
            });
        },
        
        importSettings: function(e) {
            e.preventDefault();
            
            var settingsData = $('.sml-import-textarea').val();
            
            if (!settingsData.trim()) {
                alert('Please paste settings data first.');
                return;
            }
            
            $.ajax({
                url: sml_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sml_import_settings',
                    nonce: sml_ajax.nonce,
                    settings_data: settingsData
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Import failed: ' + response.data);
                    }
                }
            });
        },
        
        resetSettings: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to reset all settings to defaults?')) {
                return;
            }
            
            $.ajax({
                url: sml_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sml_reset_settings',
                    nonce: sml_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    }
                }
            });
        },
        
        loadStats: function() {
            $.ajax({
                url: sml_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sml_get_stats',
                    nonce: sml_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SMLAdmin.displayStats(response.data);
                    }
                }
            });
        },
        
        displayStats: function(stats) {
            var $statsContainer = $('.sml-stats-container');
            if ($statsContainer.length === 0) {
                $statsContainer = $('<div class="sml-stats-container"><h3>Statistics</h3><div class="sml-stats-grid"></div></div>');
                $('.sml-admin-header').after($statsContainer);
            }
            
            var html = '';
            html += '<div class="sml-stat-card"><div class="sml-stat-number">' + stats.dimension_stats.total_images + '</div><div class="sml-stat-label">Total Images</div></div>';
            html += '<div class="sml-stat-card"><div class="sml-stat-number">' + stats.dimension_stats.scoped_images + '</div><div class="sml-stat-label">Scoped Images</div></div>';
            html += '<div class="sml-stat-card"><div class="sml-stat-number">' + stats.sync_stats.synced_images + '</div><div class="sml-stat-label">Synced Images</div></div>';
            html += '<div class="sml-stat-card"><div class="sml-stat-number">' + stats.sync_stats.unsynced_images + '</div><div class="sml-stat-label">Unsynced Images</div></div>';
            
            $statsContainer.find('.sml-stats-grid').html(html);
        },
        
        downloadFile: function(content, filename) {
            var element = document.createElement('a');
            element.setAttribute('href', 'data:application/json;charset=utf-8,' + encodeURIComponent(content));
            element.setAttribute('download', filename);
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        },
        
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        SMLAdmin.init();
    });
    
})(jQuery);