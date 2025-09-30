/**
 * Media modal JavaScript for Scoped Media Library
 */

(function($, wp) {
    'use strict';
    
    if (!wp || !wp.media) {
        return;
    }
    
    var SMLMedia = {
        
        init: function() {
            this.extendMediaViews();
            this.bindEvents();
        },
        
        extendMediaViews: function() {
            // Extend the media library view
            var originalAttachmentsBrowser = wp.media.view.AttachmentsBrowser;
            wp.media.view.AttachmentsBrowser = originalAttachmentsBrowser.extend({
                
                initialize: function() {
                    originalAttachmentsBrowser.prototype.initialize.apply(this, arguments);
                    this.addSMLControls();
                },
                
                addSMLControls: function() {
                    if (!sml_media.options.fallback_mode) {
                        return;
                    }
                    
                    // Add fallback toggle
                    this.addFallbackToggle();
                    
                    // Add filter status
                    this.addFilterStatus();
                    
                    // Add media info
                    this.addMediaInfo();
                },
                
                addFallbackToggle: function() {
                    var self = this;
                    var template = wp.template('sml-fallback-toggle');
                    
                    var toggleData = {
                        mode: sml_media.fallback_active ? 'all' : 'scoped',
                        text: sml_media.fallback_active ? sml_media.strings.scoped_mode : sml_media.strings.all_images,
                        dimension_info: sml_media.strings.dimension_info
                    };
                    
                    this.$el.append(template(toggleData));
                    
                    // Bind toggle event
                    this.$el.on('click', '.sml-toggle-btn', function(e) {
                        e.preventDefault();
                        self.toggleFallback($(this));
                    });
                },
                
                addFilterStatus: function() {
                    var statusClass = sml_media.fallback_active ? 'fallback-mode' : '';
                    var statusText = sml_media.fallback_active ? 
                        sml_media.strings.all_images : 
                        'Filtered: ' + sml_media.strings.dimension_info;
                    
                    var $status = $('<div class="sml-filter-status ' + statusClass + '">' + statusText + '</div>');
                    this.$el.append($status);
                    
                    // Auto-hide after 3 seconds
                    setTimeout(function() {
                        $status.addClass('hidden');
                    }, 3000);
                },
                
                addMediaInfo: function() {
                    var self = this;
                    this.getMediaCounts(function(counts) {
                        var template = wp.template('sml-media-info');
                        var infoData = {
                            scoped_count: counts.scoped_images,
                            total_count: counts.total_images
                        };
                        
                        self.$el.append(template(infoData));
                    });
                },
                
                toggleFallback: function($button) {
                    var currentMode = $button.data('mode');
                    var newMode = currentMode === 'scoped' ? 'all' : 'scoped';
                    
                    $button.prop('disabled', true).addClass('sml-loading');
                    this.$el.find('.attachments').addClass('sml-loading');
                    
                    var self = this;
                    $.ajax({
                        url: sml_media.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sml_toggle_fallback',
                            nonce: sml_media.nonce,
                            mode: newMode
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update button
                                $button.data('mode', newMode)
                                       .removeClass('sml-loading')
                                       .prop('disabled', false);
                                
                                var buttonText = newMode === 'all' ? 
                                    sml_media.strings.scoped_mode : 
                                    sml_media.strings.all_images;
                                $button.find('.sml-toggle-text').text(buttonText);
                                
                                // Update filter status
                                self.updateFilterStatus(newMode === 'all');
                                
                                // Refresh the collection
                                self.collection.props.set({sml_refresh: Date.now()});
                                
                                // Update global state
                                sml_media.fallback_active = response.data.active;
                            }
                        },
                        error: function() {
                            $button.removeClass('sml-loading').prop('disabled', false);
                        },
                        complete: function() {
                            self.$el.find('.attachments').removeClass('sml-loading');
                        }
                    });
                },
                
                updateFilterStatus: function(fallbackActive) {
                    var $status = this.$el.find('.sml-filter-status');
                    var statusText = fallbackActive ? 
                        sml_media.strings.all_images : 
                        'Filtered: ' + sml_media.strings.dimension_info;
                    
                    $status.removeClass('hidden fallback-mode')
                           .text(statusText);
                    
                    if (fallbackActive) {
                        $status.addClass('fallback-mode');
                    }
                    
                    // Auto-hide again
                    setTimeout(function() {
                        $status.addClass('hidden');
                    }, 3000);
                },
                
                getMediaCounts: function(callback) {
                    $.ajax({
                        url: sml_media.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sml_get_media_counts',
                            nonce: sml_media.nonce
                        },
                        success: function(response) {
                            if (response.success && callback) {
                                callback(response.data);
                            }
                        }
                    });
                }
            });
            
            // Extend attachment view to show dimension info
            var originalAttachment = wp.media.view.Attachment;
            wp.media.view.Attachment = originalAttachment.extend({
                
                initialize: function() {
                    originalAttachment.prototype.initialize.apply(this, arguments);
                    this.addDimensionInfo();
                },
                
                addDimensionInfo: function() {
                    if (!this.model.get('width') || !this.model.get('height')) {
                        return;
                    }
                    
                    var width = this.model.get('width');
                    var height = this.model.get('height');
                    var inScope = this.isInScope(width, height);
                    
                    // Add CSS classes
                    if (inScope) {
                        this.$el.addClass('sml-scoped');
                    } else {
                        this.$el.addClass('sml-out-of-scope');
                    }
                },
                
                isInScope: function(width, height) {
                    var options = sml_media.options;
                    
                    if (width < options.min_width || width > options.max_width) {
                        return false;
                    }
                    
                    if (height < options.min_height || height > options.max_height) {
                        return false;
                    }
                    
                    return true;
                }
            });
            
            // Extend attachment details view
            var originalAttachmentDetails = wp.media.view.Attachment.Details;
            wp.media.view.Attachment.Details = originalAttachmentDetails.extend({
                
                render: function() {
                    originalAttachmentDetails.prototype.render.apply(this, arguments);
                    this.addDimensionDetails();
                    return this;
                },
                
                addDimensionDetails: function() {
                    if (!this.model.get('width') || !this.model.get('height')) {
                        return;
                    }
                    
                    var width = this.model.get('width');
                    var height = this.model.get('height');
                    var inScope = this.isInScope(width, height);
                    
                    var html = '<div class="sml-dimensions">';
                    html += '<div class="sml-dimensions-title">Scoped Media Library</div>';
                    html += '<div class="sml-dimensions-value">Dimensions: ' + width + ' × ' + height + ' pixels</div>';
                    html += '<span class="sml-dimensions-status ' + (inScope ? 'in-scope' : 'out-of-scope') + '">';
                    html += inScope ? 'Within scope' : 'Outside scope';
                    html += '</span>';
                    html += '</div>';
                    
                    this.$el.find('.details').append(html);
                },
                
                isInScope: function(width, height) {
                    var options = sml_media.options;
                    
                    if (width < options.min_width || width > options.max_width) {
                        return false;
                    }
                    
                    if (height < options.min_height || height > options.max_height) {
                        return false;
                    }
                    
                    return true;
                }
            });
        },
        
        bindEvents: function() {
            // Listen for media modal open
            $(document).on('wp-media-frame-ready', this.onMediaFrameReady);
            
            // Listen for collection refresh
            wp.media.model.Query.prototype.on('change:props', this.onPropsChange);
        },
        
        onMediaFrameReady: function() {
            // Additional setup when media frame is ready
            console.log('SML: Media frame ready');
        },
        
        onPropsChange: function(model, props) {
            // Handle collection property changes
            if (props.sml_refresh) {
                // Collection is being refreshed due to fallback toggle
                console.log('SML: Collection refreshed');
            }
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        SMLMedia.init();
    });
    
    // Also initialize when media scripts are loaded
    wp.media.view.MediaFrame.Post.prototype.on('ready', function() {
        SMLMedia.init();
    });
    
})(jQuery, wp);