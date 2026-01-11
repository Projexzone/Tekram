/**
 * TEKRAM Market stall manager - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Confirmation Dialogs
        $('.lt-delete-action').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Bulk Actions
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).siblings('select').val();
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete the selected items?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Status Update
        $('.lt-status-select').on('change', function() {
            var $select = $(this);
            var bookingId = $select.data('booking-id');
            var newStatus = $select.val();
            
            if (confirm('Update booking status to ' + newStatus + '?')) {
                $.ajax({
                    url: ltAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'smp_update_booking_status',
                        nonce: ltAdmin.nonce,
                        booking_id: bookingId,
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error updating status: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error updating status. Please try again.');
                    }
                });
            } else {
                // Reset select to original value
                $select.val($select.data('original-value'));
            }
        });
        
        // Date Range Picker (if needed in future)
        if ($.fn.datepicker) {
            $('.lt-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }
        
        // Add Event Modal/Form Toggle
        $('.lt-add-event').on('click', function(e) {
            e.preventDefault();
            // Open modal or navigate to add event page
            window.location.href = $(this).attr('href');
        });
        
        // Payment Recording
        $('#lt-record-payment-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: ltAdmin.ajaxurl,
                type: 'POST',
                data: $form.serialize() + '&action=smp_record_payment&nonce=' + ltAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        alert('Payment recorded successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error recording payment. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Export Functionality
        $('.lt-export-btn').on('click', function(e) {
            e.preventDefault();
            
            var exportType = $(this).data('export-type');
            var $button = $(this);
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Exporting...');
            
            // Build query parameters
            var params = new URLSearchParams(window.location.search);
            params.append('action', 'smp_export_data');
            params.append('type', exportType);
            params.append('nonce', ltAdmin.nonce);
            
            // Trigger download
            window.location.href = ltAdmin.ajaxurl + '?' + params.toString();
            
            setTimeout(function() {
                $button.prop('disabled', false).text(originalText);
            }, 2000);
        });
        
        // Toggle Advanced Options
        $('.lt-toggle-advanced').on('click', function(e) {
            e.preventDefault();
            $('.lt-advanced-options').slideToggle();
        });
        
        // Character Counter
        $('.lt-char-counter').each(function() {
            var $input = $(this);
            var maxLength = $input.attr('maxlength');
            var $counter = $('<div class="lt-counter"></div>').insertAfter($input);
            
            function updateCounter() {
                var remaining = maxLength - $input.val().length;
                $counter.text(remaining + ' characters remaining');
            }
            
            $input.on('input', updateCounter);
            updateCounter();
        });
        
        // Email Preview
        $('.lt-preview-email').on('click', function(e) {
            e.preventDefault();
            
            var templateType = $(this).data('template');
            
            $.ajax({
                url: ltAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'smp_preview_email',
                    nonce: ltAdmin.nonce,
                    template: templateType
                },
                success: function(response) {
                    if (response.success) {
                        // Open preview in modal or new window
                        var previewWindow = window.open('', 'Email Preview', 'width=800,height=600');
                        previewWindow.document.write(response.data.html);
                    }
                }
            });
        });
        
        // Site Map Drag and Drop (basic implementation)
        if ($('.lt-site-map-container').length) {
            $('.lt-site').draggable({
                containment: '.lt-site-map-container',
                stop: function(event, ui) {
                    var siteId = $(this).data('site-id');
                    var position = ui.position;
                    
                    // Save position
                    $.ajax({
                        url: ltAdmin.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'smp_update_site_position',
                            nonce: ltAdmin.nonce,
                            site_id: siteId,
                            x: position.left,
                            y: position.top
                        }
                    });
                }
            });
        }
        
        // Tabs
        $('.lt-tabs a').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            $('.lt-tabs a').removeClass('active');
            $(this).addClass('active');
            
            $('.lt-tab-content').hide();
            $(target).show();
        });
        
        // Select All Checkbox
        $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('tbody input[type="checkbox"]').prop('checked', isChecked);
        });
        
        // Quick Edit
        $('.editinline').on('click', function(e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            $row.addClass('editing');
            // Show inline edit form
        });
        
        // Tooltips (if using a tooltip library)
        if ($.fn.tooltip) {
            $('[data-tooltip]').tooltip();
        }
        
        // Auto-save (for long forms)
        var autoSaveTimer;
        $('.lt-autosave-form input, .lt-autosave-form textarea, .lt-autosave-form select').on('change', function() {
            clearTimeout(autoSaveTimer);
            var $form = $(this).closest('form');
            
            autoSaveTimer = setTimeout(function() {
                $.ajax({
                    url: ltAdmin.ajaxurl,
                    type: 'POST',
                    data: $form.serialize() + '&action=smp_autosave&nonce=' + ltAdmin.nonce,
                    success: function(response) {
                        if (response.success) {
                            $('.lt-autosave-notice').fadeIn().delay(2000).fadeOut();
                        }
                    }
                });
            }, 3000);
        });
        
        // Image Upload
        $('.lt-upload-image').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $input = $button.siblings('input[type="hidden"]');
            var $preview = $button.siblings('.lt-image-preview');
            
            var frame = wp.media({
                title: 'Select or Upload Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.id);
                $preview.html('<img src="' + attachment.url + '" style="max-width:200px;">');
            });
            
            frame.open();
        });
        
        // Remove Image
        $('.lt-remove-image').on('click', function(e) {
            e.preventDefault();
            $(this).siblings('input[type="hidden"]').val('');
            $(this).siblings('.lt-image-preview').empty();
        });
    });
    
})(jQuery);
