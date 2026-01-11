<?php
/**
 * Admin Check-In Page
 * Mobile-friendly interface for iPad check-in on market day
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin_Checkin {
    
    public static function render() {
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
        
        $events = LT_Event::get_all();
        
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        if ($event_id && $date) {
            $bookings = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $bookings_table 
                 WHERE event_id = %d AND booking_date = %s
                 ORDER BY status ASC, created_at ASC",
                $event_id, $date
            ));
        } else {
            $bookings = array();
        }
        
        ?>
        <div class="wrap lt-checkin-wrap">
            <h1><?php _e('Market Check-In', 'tekram'); ?></h1>
            
            <!-- Filter Form -->
            <div class="lt-checkin-filters">
                <form method="get" class="lt-filter-form">
                    <input type="hidden" name="page" value="tekram-checkin">
                    
                    <div class="lt-filter-field">
                        <label for="event_id"><?php _e('Select Market:', 'tekram'); ?></label>
                        <select name="event_id" id="event_id" required>
                            <option value=""><?php _e('-- Select Market --', 'tekram'); ?></option>
                            <?php foreach ($events as $event) { ?>
                                <option value="<?php echo $event->ID; ?>" <?php selected($event_id, $event->ID); ?>>
                                    <?php echo esc_html($event->post_title); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="lt-filter-field">
                        <label for="date"><?php _e('Date:', 'tekram'); ?></label>
                        <input type="date" name="date" id="date" value="<?php echo esc_attr($date); ?>" required>
                    </div>
                    
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Load Vendors', 'tekram'); ?>
                    </button>
                </form>
            </div>
            
            <?php if ($event_id && $date) { ?>
                
                <!-- Summary Stats -->
                <div class="lt-checkin-stats">
                    <?php
                    $total = count($bookings);
                    $checked_in = 0;
                    $paid = 0;
                    $unpaid = 0;
                    
                    foreach ($bookings as $b) {
                        if ($b->status === 'confirmed') $checked_in++;
                        if ($b->payment_status === 'paid') $paid++;
                        if ($b->payment_status === 'unpaid') $unpaid++;
                    }
                    ?>
                    
                    <div class="lt-stat-card">
                        <div class="lt-stat-number"><?php echo $total; ?></div>
                        <div class="lt-stat-label"><?php _e('Total Bookings', 'tekram'); ?></div>
                    </div>
                    
                    <div class="lt-stat-card lt-stat-success">
                        <div class="lt-stat-number"><?php echo $checked_in; ?></div>
                        <div class="lt-stat-label"><?php _e('Checked In', 'tekram'); ?></div>
                    </div>
                    
                    <div class="lt-stat-card lt-stat-warning">
                        <div class="lt-stat-number"><?php echo $unpaid; ?></div>
                        <div class="lt-stat-label"><?php _e('Unpaid', 'tekram'); ?></div>
                    </div>
                </div>
                
                <!-- Vendor List -->
                <div class="lt-checkin-list">
                    <?php if (empty($bookings)) { ?>
                        <p class="lt-no-bookings"><?php _e('No vendors booked for this date.', 'tekram'); ?></p>
                    <?php } else { ?>
                        
                        <?php foreach ($bookings as $booking) {
                            $vendor = get_post($booking->vendor_id);
                            $vendor_data = LT_Vendor::get_data($booking->vendor_id);
                            $site = $booking->site_id ? get_post($booking->site_id) : null;
                            
                            $is_checked_in = ($booking->status === 'confirmed');
                            $is_paid = ($booking->payment_status === 'paid');
                            $card_class = $is_checked_in ? 'lt-checked-in' : '';
                            ?>
                            
                            <div class="lt-vendor-card <?php echo $card_class; ?>" data-booking-id="<?php echo $booking->id; ?>">
                                <div class="lt-vendor-main">
                                    <div class="lt-vendor-details">
                                        <h3 class="lt-vendor-name">
                                            <?php echo $vendor ? esc_html($vendor->post_title) : '-'; ?>
                                        </h3>
                                        <div class="lt-vendor-meta">
                                            <span><?php _e('Ref:', 'tekram'); ?> <?php echo esc_html($booking->booking_reference); ?></span>
                                            <?php if ($site) { ?>
                                                <span>• <?php _e('Site:', 'tekram'); ?> <?php echo esc_html($site->post_title); ?></span>
                                            <?php } ?>
                                        </div>
                                        <div class="lt-vendor-products">
                                            <?php echo esc_html(wp_trim_words($vendor_data['products_description'], 15)); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="lt-vendor-status">
                                        <div class="lt-payment-badge lt-payment-<?php echo esc_attr($booking->payment_status); ?>">
                                            <?php echo ucfirst($booking->payment_status); ?>
                                        </div>
                                        <div class="lt-amount">
                                            <?php echo get_option('lt_currency_symbol', '$') . number_format($booking->amount, 2); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="lt-vendor-actions">
                                    <?php if (!$is_checked_in) { ?>
                                        
                                        <?php if (!$is_paid) { ?>
                                            <button class="button button-large lt-record-payment-btn" data-booking-id="<?php echo $booking->id; ?>" data-amount="<?php echo $booking->amount; ?>">
                                                💰 <?php _e('Collect Payment', 'tekram'); ?>
                                            </button>
                                        <?php } ?>
                                        
                                        <button class="button button-primary button-large lt-checkin-btn" data-booking-id="<?php echo $booking->id; ?>">
                                            ✓ <?php _e('Check In', 'tekram'); ?>
                                        </button>
                                        
                                    <?php } else { ?>
                                        <div class="lt-checked-badge">
                                            ✓ <?php _e('Checked In', 'tekram'); ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            
                        <?php } ?>
                        
                    <?php } ?>
                </div>
                
            <?php } ?>
        </div>
        
        <!-- Payment Modal -->
        <div id="lt-payment-modal" class="lt-modal" style="display:none;">
            <div class="lt-modal-content">
                <span class="lt-modal-close">&times;</span>
                <h2><?php _e('Record Payment', 'tekram'); ?></h2>
                <form id="lt-payment-form">
                    <input type="hidden" id="payment-booking-id" name="booking_id">
                    
                    <div class="lt-form-field">
                        <label><?php _e('Amount:', 'tekram'); ?></label>
                        <div class="lt-amount-display" id="payment-amount-display"></div>
                        <input type="hidden" id="payment-amount" name="amount">
                    </div>
                    
                    <div class="lt-form-field">
                        <label><?php _e('Payment Method:', 'tekram'); ?></label>
                        <select name="payment_method" required>
                            <option value="cash"><?php _e('Cash', 'tekram'); ?></option>
                            <option value="card"><?php _e('Card', 'tekram'); ?></option>
                            <option value="bank_transfer"><?php _e('Bank Transfer', 'tekram'); ?></option>
                        </select>
                    </div>
                    
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Record Payment & Check In', 'tekram'); ?>
                    </button>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Check In Button
            $('.lt-checkin-btn').on('click', function() {
                var $btn = $(this);
                var bookingId = $btn.data('booking-id');
                var $card = $btn.closest('.lt-vendor-card');
                
                if (!confirm('Check in this vendor?')) return;
                
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ltAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lt_quick_checkin',
                        nonce: ltAdmin.nonce,
                        booking_id: bookingId,
                        checkin_action: 'confirm'
                    },
                    success: function(response) {
                        if (response.success) {
                            $card.addClass('lt-checked-in');
                            $btn.closest('.lt-vendor-actions').html(
                                '<div class="lt-checked-badge">✓ Checked In</div>'
                            );
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Error checking in vendor');
                        $btn.prop('disabled', false).text('✓ Check In');
                    }
                });
            });
            
            // Record Payment Button
            $('.lt-record-payment-btn').on('click', function() {
                var bookingId = $(this).data('booking-id');
                var amount = $(this).data('amount');
                
                $('#payment-booking-id').val(bookingId);
                $('#payment-amount').val(amount);
                $('#payment-amount-display').text('<?php echo get_option('lt_currency_symbol', '$'); ?>' + parseFloat(amount).toFixed(2));
                $('#lt-payment-modal').fadeIn();
            });
            
            // Close Modal
            $('.lt-modal-close').on('click', function() {
                $(this).closest('.lt-modal').fadeOut();
            });
            
            // Payment Form Submit
            $('#lt-payment-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $btn = $form.find('button[type="submit"]');
                var bookingId = $('#payment-booking-id').val();
                
                $btn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ltAdmin.ajaxurl,
                    type: 'POST',
                    data: $form.serialize() + '&action=lt_quick_checkin&nonce=' + ltAdmin.nonce + '&checkin_action=record_payment',
                    success: function(response) {
                        if (response.success) {
                            $('#lt-payment-modal').fadeOut();
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                            $btn.prop('disabled', false).text('Record Payment & Check In');
                        }
                    },
                    error: function() {
                        alert('Error recording payment');
                        $btn.prop('disabled', false).text('Record Payment & Check In');
                    }
                });
            });
        });
        </script>
        
        <style>
        .lt-checkin-wrap {
            max-width: 1200px;
            margin: 20px auto;
        }
        
        .lt-checkin-filters {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .lt-filter-form {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .lt-filter-field {
            flex: 1;
            min-width: 200px;
        }
        
        .lt-filter-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .lt-filter-field select,
        .lt-filter-field input {
            width: 100%;
            padding: 8px;
            font-size: 16px;
        }
        
        .lt-checkin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .lt-stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #0073aa;
        }
        
        .lt-stat-card.lt-stat-success {
            border-left-color: #46b450;
        }
        
        .lt-stat-card.lt-stat-warning {
            border-left-color: #dc3232;
        }
        
        .lt-stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 10px;
        }
        
        .lt-stat-success .lt-stat-number {
            color: #46b450;
        }
        
        .lt-stat-warning .lt-stat-number {
            color: #dc3232;
        }
        
        .lt-stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .lt-checkin-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .lt-vendor-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #dc3232;
        }
        
        .lt-vendor-card.lt-checked-in {
            border-left-color: #46b450;
            background: #f0f9f0;
        }
        
        .lt-vendor-main {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .lt-vendor-name {
            margin: 0 0 8px;
            font-size: 20px;
        }
        
        .lt-vendor-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .lt-vendor-products {
            font-size: 14px;
            color: #555;
            font-style: italic;
        }
        
        .lt-vendor-status {
            text-align: right;
        }
        
        .lt-payment-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .lt-payment-unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .lt-payment-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .lt-payment-partial {
            background: #fff3cd;
            color: #856404;
        }
        
        .lt-amount {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .lt-vendor-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .lt-vendor-actions .button {
            font-size: 16px;
            padding: 10px 20px;
        }
        
        .lt-checked-badge {
            display: inline-block;
            padding: 12px 30px;
            background: #46b450;
            color: #fff;
            border-radius: 4px;
            font-size: 18px;
            font-weight: 600;
        }
        
        /* Modal */
        .lt-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lt-modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            position: relative;
        }
        
        .lt-modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 30px;
            cursor: pointer;
            color: #666;
        }
        
        .lt-modal-content h2 {
            margin-top: 0;
        }
        
        .lt-form-field {
            margin-bottom: 20px;
        }
        
        .lt-form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .lt-form-field select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
        }
        
        .lt-amount-display {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .lt-vendor-main {
                flex-direction: column;
            }
            
            .lt-vendor-status {
                text-align: left;
                margin-top: 15px;
            }
            
            .lt-vendor-actions {
                flex-direction: column;
            }
            
            .lt-vendor-actions .button {
                width: 100%;
            }
        }
        </style>
        <?php
    }
}



