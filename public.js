/**
 * Tekram market stall manager - Public JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Application Form - NO JAVASCRIPT INTERFERENCE
        // Traditional POST submission handles everything server-side
        // DO NOT bind any events to #lt-application-form
        
        // Booking Form - Event Selection
        $('#event_id').on('change', function() {
            var $option = $(this).find('option:selected');
            var startDate = $option.data('start-date');
            var endDate = $option.data('end-date');
            var fee = $option.data('fee');
            
            if (startDate && endDate) {
                $('#booking_date').attr('min', startDate).attr('max', endDate);
                $('#summary-event').text($option.text());
                $('#summary-fee').text('$' + parseFloat(fee).toFixed(2));
                $('.lt-booking-summary').slideDown();
            } else {
                $('.lt-booking-summary').slideUp();
                $('#site-selection').slideUp();
            }
        });
        
        // Booking Form - Date Selection
        $('#booking_date').on('change', function() {
            var eventId = $('#event_id').val();
            var date = $(this).val();
            
            if (eventId && date) {
                checkAvailability(eventId, date);
                loadExtras(eventId);
                $('#summary-date').text(formatDate(date));
            }
        });
        
        // Load Extras for event
        function loadExtras(eventId) {
            $.ajax({
                url: ltAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lt_get_extras',
                    nonce: ltAjax.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success && response.data.extras && response.data.extras.length > 0) {
                        var $list = $('#extras-list');
                        $list.empty();
                        
                        $.each(response.data.extras, function(index, extra) {
                            var html = '<div class="lt-extra-item">' +
                                      '<label>' +
                                      '<input type="checkbox" name="extras[]" value="' + extra.id + '" data-price="' + extra.price + '" class="extra-checkbox">' +
                                      '<strong>' + extra.name + '</strong> - $' + parseFloat(extra.price).toFixed(2) +
                                      (extra.description ? '<br><small>' + extra.description + '</small>' : '') +
                                      '</label>' +
                                      '</div>';
                            $list.append(html);
                        });
                        
                        $('#extras-selection').slideDown();
                        
                        // Add change handler for extras
                        $('.extra-checkbox').on('change', updateTotal);
                    } else {
                        $('#extras-selection').slideUp();
                    }
                }
            });
        }
        
        // Update Total with Extras
        function updateTotal() {
            var siteFee = parseFloat($('#summary-fee').text().replace('$', '')) || 0;
            var extrasTotal = 0;
            
            $('.extra-checkbox:checked').each(function() {
                extrasTotal += parseFloat($(this).data('price')) || 0;
            });
            
            if (extrasTotal > 0) {
                $('#summary-extras').text('$' + extrasTotal.toFixed(2));
                $('#summary-extras-line').show();
            } else {
                $('#summary-extras-line').hide();
            }
            
            var total = siteFee + extrasTotal;
            $('#summary-total').text('$' + total.toFixed(2));
        }
        
        // Update total when site changes
        $('#site_id').on('change', function() {
            var $selected = $(this).find('option:selected');
            var priceAttr = $selected.attr('data-price');
            var defaultFee = parseFloat($('#event_id option:selected').data('fee')) || 45;
            
            // Parse price - if not set, use default
            var finalPrice = priceAttr !== undefined && priceAttr !== '' ? parseFloat(priceAttr) : defaultFee;
            
            console.log('Site selected:', $selected.text(), 'Price:', finalPrice);
            
            $('#summary-fee').text('$' + finalPrice.toFixed(2));
            updateTotal();
        });
        
        // Check Availability
        function checkAvailability(eventId, date) {
            var $container = $('.lt-availability-check');
            var $message = $container.find('.lt-availability-message');
            
            $container.show();
            $message.html('<span class="lt-loading"></span> Checking availability...');
            
            $.ajax({
                url: ltAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lt_check_availability',
                    nonce: ltAjax.nonce,
                    event_id: eventId,
                    date: date
                },
                success: function(response) {
                    if (response.success && response.data.available) {
                        $container.removeClass('unavailable').addClass('available');
                        $message.html('✓ Sites are available for this date!');
                        loadAvailableSites(eventId, date);
                    } else {
                        $container.removeClass('available').addClass('unavailable');
                        $message.html('✗ No sites available for this date.');
                        $('#site-selection').slideUp();
                    }
                },
                error: function() {
                    $container.hide();
                }
            });
        }
        
        // Load Available Sites
        function loadAvailableSites(eventId, date) {
            $.ajax({
                url: ltAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lt_get_available_sites',
                    nonce: ltAjax.nonce,
                    event_id: eventId,
                    date: date
                },
                success: function(response) {
                    if (response.success && response.data.sites) {
                        var $select = $('#site_id');
                        $select.find('option:not(:first)').remove();
                        
                        // Get default fee from event
                        var defaultFee = parseFloat($('#event_id option:selected').data('fee')) || 45;
                        
                        // Update "Any Available Site" option with default price
                        $select.find('option:first').attr('data-price', defaultFee);
                        
                        // Add available sites
                        $.each(response.data.sites, function(index, site) {
                            var price = site.price !== undefined && site.price !== null ? parseFloat(site.price) : defaultFee;
                            $select.append('<option value="' + site.id + '" data-price="' + price + '">' + site.name + '</option>');
                        });
                        
                        // Set initial price to default
                        $('#summary-fee').text('$' + defaultFee.toFixed(2));
                        
                        $('#site-selection').slideDown();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading sites:', error);
                }
            });
        }
        
        // Site Selection - Update Price
        $('#site_id').on('change', function() {
            var $selected = $(this).find('option:selected');
            var priceAttr = $selected.attr('data-price');
            var defaultFee = parseFloat($('#event_id option:selected').data('fee')) || 45;
            
            // Parse price - if not set, use default
            var finalPrice = priceAttr !== undefined && priceAttr !== '' ? parseFloat(priceAttr) : defaultFee;
            
            console.log('Site selected:', $selected.text(), 'Price:', finalPrice);
            
            $('#summary-fee').text('$' + finalPrice.toFixed(2));
        });
        
        // Booking Form Submission
        $('#lt-booking-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $('#lt-submit-booking');
            var $message = $form.find('.lt-form-message');
            
            // Disable button
            $button.prop('disabled', true).text('Processing...');
            $message.hide();
            
            var formData = $form.serialize();
            formData += '&action=lt_create_booking';
            formData += '&nonce=' + ltAjax.nonce;
            
            $.ajax({
                url: ltAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('error').addClass('success')
                               .html(response.data.message + '<br><strong>Booking Reference: ' + response.data.reference + '</strong>')
                               .fadeIn();
                        $form[0].reset();
                        $('.lt-booking-summary').slideUp();
                        $('#site-selection').slideUp();
                        $('.lt-availability-check').slideUp();
                        
                        // Redirect to payment or confirmation page
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 2000);
                        }
                    } else {
                        $message.removeClass('success').addClass('error')
                               .html(response.data.message).fadeIn();
                    }
                },
                error: function() {
                    $message.removeClass('success').addClass('error')
                           .html(ltAjax.strings.error).fadeIn();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Confirm Booking');
                }
            });
        });
        
        // Format Date
        function formatDate(dateString) {
            var date = new Date(dateString);
            var options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString(undefined, options);
        }
        
        // Book Now Buttons
        $('.lt-book-now').on('click', function(e) {
            e.preventDefault();
            var eventId = $(this).data('event-id');
            
            // Scroll to booking form or redirect to booking page
            if ($('#lt-booking-form').length) {
                $('#event_id').val(eventId).trigger('change');
                $('html, body').animate({
                    scrollTop: $('#lt-booking-form').offset().top - 100
                }, 500);
            } else {
                // Redirect to booking page with event ID
                var bookingUrl = $(this).data('booking-url') || '/bookings/?event_id=' + eventId;
                window.location.href = bookingUrl;
            }
        });
        
        // Form Validation
        $('input[required], select[required], textarea[required]').on('invalid', function() {
            $(this).addClass('error');
        }).on('input change', function() {
            $(this).removeClass('error');
        });
        
        // Phone Number Formatting (basic)
        $('input[type="tel"]').on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            if (value.length > 3 && value.length <= 6) {
                $(this).val('(' + value.slice(0, 3) + ') ' + value.slice(3));
            } else if (value.length > 6) {
                $(this).val('(' + value.slice(0, 3) + ') ' + value.slice(3, 6) + '-' + value.slice(6, 10));
            }
        });
    });
    
})(jQuery);
