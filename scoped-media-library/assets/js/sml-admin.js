/**
 * Scoped Media Library - Admin JavaScript
 * 
 * @package    Scoped_Media_Library
 * @subpackage Scoped_Media_Library/assets/js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        /**
         * Handle bulk sync button click
         */
        $('#sml-sync-dimensions').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $spinner = $button.siblings('.spinner');
            var $result = $('#sml-sync-result');
            
            // Disable button and show spinner
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.removeClass('success error info').hide();
            
            // Show info message
            $result.html('Syncing image dimensions... This may take a moment.')
                   .addClass('info')
                   .show();
            
            // Make AJAX request
            $.ajax({
                url: smlSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sml_bulk_sync',
                    nonce: smlSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span class="dashicons dashicons-yes"></span> ' + response.data.message)
                               .removeClass('info error')
                               .addClass('success');
                    } else {
                        $result.html('<span class="dashicons dashicons-warning"></span> ' + response.data.message)
                               .removeClass('info success')
                               .addClass('error');
                    }
                },
                error: function(xhr, status, error) {
                    $result.html('<span class="dashicons dashicons-warning"></span> An error occurred: ' + error)
                           .removeClass('info success')
                           .addClass('error');
                },
                complete: function() {
                    // Re-enable button and hide spinner
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        /**
         * Add filter notice to media modal
         */
        if (typeof wp !== 'undefined' && wp.media) {
            var originalMediaFrame = wp.media.view.MediaFrame.Select;
            
            wp.media.view.MediaFrame.Select = originalMediaFrame.extend({
                initialize: function() {
                    originalMediaFrame.prototype.initialize.apply(this, arguments);
                    
                    // Add notice if filtering is enabled
                    if (smlSettings.enabled) {
                        this.on('open', function() {
                            addFilterNotice();
                        });
                    }
                }
            });
            
            function addFilterNotice() {
                // Check if notice already exists
                if ($('.sml-filter-notice').length > 0) {
                    return;
                }
                
                var dimensions = [];
                
                if (smlSettings.minWidth) {
                    dimensions.push('min width: ' + smlSettings.minWidth + 'px');
                }
                if (smlSettings.maxWidth) {
                    dimensions.push('max width: ' + smlSettings.maxWidth + 'px');
                }
                if (smlSettings.minHeight) {
                    dimensions.push('min height: ' + smlSettings.minHeight + 'px');
                }
                if (smlSettings.maxHeight) {
                    dimensions.push('max height: ' + smlSettings.maxHeight + 'px');
                }
                
                if (dimensions.length > 0) {
                    var noticeText = '<strong>Media filtering active:</strong> Only showing images matching ' + 
                                   dimensions.join(', ');
                    
                    var $notice = $('<div class="sml-filter-notice">' +
                                  '<span class="dashicons dashicons-filter"></span>' +
                                  noticeText +
                                  '</div>');
                    
                    // Insert notice into media modal
                    setTimeout(function() {
                        var $toolbar = $('.media-toolbar');
                        if ($toolbar.length > 0) {
                            $toolbar.after($notice);
                        }
                    }, 100);
                }
            }
        }
        
        /**
         * Form validation for settings page
         */
        $('form').on('submit', function(e) {
            var minWidth = parseInt($('input[name="sml_settings[min_width]"]').val());
            var maxWidth = parseInt($('input[name="sml_settings[max_width]"]').val());
            var minHeight = parseInt($('input[name="sml_settings[min_height]"]').val());
            var maxHeight = parseInt($('input[name="sml_settings[max_height]"]').val());
            
            // Validate width constraints
            if (minWidth && maxWidth && minWidth > maxWidth) {
                e.preventDefault();
                alert('Error: Minimum width cannot be greater than maximum width.');
                return false;
            }
            
            // Validate height constraints
            if (minHeight && maxHeight && minHeight > maxHeight) {
                e.preventDefault();
                alert('Error: Minimum height cannot be greater than maximum height.');
                return false;
            }
        });
        
        /**
         * Real-time dimension summary
         */
        function updateDimensionSummary() {
            var $summary = $('#sml-dimension-summary');
            if ($summary.length === 0) {
                return;
            }
            
            var dimensions = [];
            var minWidth = $('input[name="sml_settings[min_width]"]').val();
            var maxWidth = $('input[name="sml_settings[max_width]"]').val();
            var minHeight = $('input[name="sml_settings[min_height]"]').val();
            var maxHeight = $('input[name="sml_settings[max_height]"]').val();
            
            if (minWidth || maxWidth) {
                var widthText = 'Width: ';
                if (minWidth && maxWidth) {
                    widthText += minWidth + 'px - ' + maxWidth + 'px';
                } else if (minWidth) {
                    widthText += '≥ ' + minWidth + 'px';
                } else {
                    widthText += '≤ ' + maxWidth + 'px';
                }
                dimensions.push(widthText);
            }
            
            if (minHeight || maxHeight) {
                var heightText = 'Height: ';
                if (minHeight && maxHeight) {
                    heightText += minHeight + 'px - ' + maxHeight + 'px';
                } else if (minHeight) {
                    heightText += '≥ ' + minHeight + 'px';
                } else {
                    heightText += '≤ ' + maxHeight + 'px';
                }
                dimensions.push(heightText);
            }
            
            if (dimensions.length > 0) {
                $summary.html('<strong>Active filters:</strong> ' + dimensions.join(', ')).show();
            } else {
                $summary.hide();
            }
        }
        
        // Update summary on input change
        $('input[name^="sml_settings"]').on('input change', function() {
            updateDimensionSummary();
        });
        
        // Initial update
        updateDimensionSummary();
    });

})(jQuery);