<?php
/**
 * Admin Bookings
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin_Bookings {
    
    public static function render() {
        // Handle actions
        if (isset($_POST['action']) && check_admin_referer('lt_booking_action')) {
            self::handle_action();
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'view' && $booking_id) {
            self::render_booking_details($booking_id);
        } else {
            self::render_booking_list();
        }
    }
    
    private static function render_booking_list() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        
        ?>
        <div class="wrap">
            <h1><?php _e('Bookings', 'tekram'); ?></h1>
            
            <ul class="subsubsub">
                <li>
                    <a href="?page=tekram-bookings&status=all" <?php echo $status === 'all' ? 'class="current"' : ''; ?>>
                        <?php _e('All', 'tekram'); ?>
                    </a> |
                </li>
                <li>
                    <a href="?page=tekram-bookings&status=pending" <?php echo $status === 'pending' ? 'class="current"' : ''; ?>>
                        <?php _e('Pending', 'tekram'); ?>
                    </a> |
                </li>
                <li>
                    <a href="?page=tekram-bookings&status=confirmed" <?php echo $status === 'confirmed' ? 'class="current"' : ''; ?>>
                        <?php _e('Confirmed', 'tekram'); ?>
                    </a> |
                </li>
                <li>
                    <a href="?page=tekram-bookings&status=cancelled" <?php echo $status === 'cancelled' ? 'class="current"' : ''; ?>>
                        <?php _e('Cancelled', 'tekram'); ?>
                    </a>
                </li>
            </ul>
            
            <br class="clear">
            
            <!-- Filters -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="event_filter" id="event_filter">
                        <option value=""><?php _e('All Events', 'tekram'); ?></option>
                        <?php
                        $events = LT_Event::get_all();
                        foreach ($events as $event) {
                            $selected = ($event->ID == $event_id) ? 'selected' : '';
                            echo '<option value="' . $event->ID . '" ' . $selected . '>' . esc_html($event->post_title) . '</option>';
                        }
                        ?>
                    </select>
                    <button type="button" class="button" id="filter-submit"><?php _e('Filter', 'tekram'); ?></button>
                </div>
            </div>
            
            <?php
            $sql = "SELECT * FROM $bookings_table WHERE 1=1";
            
            if ($status !== 'all') {
                $sql .= $wpdb->prepare(" AND status = %s", $status);
            }
            
            if ($event_id > 0) {
                $sql .= $wpdb->prepare(" AND event_id = %d", $event_id);
            }
            
            $sql .= " ORDER BY booking_date DESC, created_at DESC LIMIT 100";
            
            $bookings = $wpdb->get_results($sql);
            
            if ($bookings) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Reference', 'tekram'); ?></th>
                            <th><?php _e('Vendor', 'tekram'); ?></th>
                            <th><?php _e('Event', 'tekram'); ?></th>
                            <th><?php _e('Date', 'tekram'); ?></th>
                            <th><?php _e('Site', 'tekram'); ?></th>
                            <th><?php _e('Amount', 'tekram'); ?></th>
                            <th><?php _e('Payment', 'tekram'); ?></th>
                            <th><?php _e('Status', 'tekram'); ?></th>
                            <th><?php _e('Actions', 'tekram'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking) {
                            $vendor = get_post($booking->vendor_id);
                            $event = get_post($booking->event_id);
                            $site = $booking->site_id ? get_post($booking->site_id) : null;
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($booking->booking_reference); ?></strong></td>
                                <td><?php echo $vendor ? esc_html($vendor->post_title) : '-'; ?></td>
                                <td><?php echo $event ? esc_html($event->post_title) : '-'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></td>
                                <td><?php echo $site ? esc_html($site->post_title) : '-'; ?></td>
                                <td><?php echo get_option('lt_currency_symbol', '$') . number_format($booking->amount, 2); ?></td>
                                <td>
                                    <span class="smp-payment-status smp-payment-<?php echo esc_attr($booking->payment_status); ?>">
                                        <?php echo ucfirst($booking->payment_status); ?>
                                    </span>
                                    <br>
                                    <small><?php echo get_option('lt_currency_symbol', '$') . number_format($booking->paid_amount, 2); ?> / <?php echo get_option('lt_currency_symbol', '$') . number_format($booking->amount, 2); ?></small>
                                </td>
                                <td>
                                    <span class="smp-status smp-status-<?php echo esc_attr($booking->status); ?>">
                                        <?php echo ucfirst($booking->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=tekram-bookings&action=view&id=' . $booking->id); ?>" class="button button-small">
                                        <?php _e('View', 'tekram'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>' . __('No bookings found.', 'tekram') . '</p>';
            }
            ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#filter-submit').on('click', function() {
                var eventId = $('#event_filter').val();
                var url = window.location.href.split('?')[0] + '?page=tekram-bookings';
                if (eventId) {
                    url += '&event_id=' + eventId;
                }
                window.location.href = url;
            });
        });
        </script>
        <?php
    }
    
    private static function render_booking_details($booking_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $bookings_table WHERE id = %d", $booking_id));
        
        if (!$booking) {
            echo '<div class="wrap"><p>' . __('Booking not found.', 'tekram') . '</p></div>';
            return;
        }
        
        $vendor = get_post($booking->vendor_id);
        $vendor_data = LT_Vendor::get_data($booking->vendor_id);
        $event = get_post($booking->event_id);
        $event_data = LT_Event::get_data($booking->event_id);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Booking Details', 'tekram'); ?></h1>
            <a href="?page=tekram-bookings" class="page-title-action"><?php _e('← Back to Bookings', 'tekram'); ?></a>
            
            <div style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccc;">
                <h2><?php _e('Booking Information', 'tekram'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Booking Reference', 'tekram'); ?>:</th>
                        <td><code style="font-size: 16px; background: #f0f0f0; padding: 5px 10px;"><?php echo esc_html($booking->booking_reference); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'tekram'); ?>:</th>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                <?php echo ucfirst($booking->status); ?>
                            </span>
                            <form method="post" style="display: inline; margin-left: 10px;">
                                <?php wp_nonce_field('lt_booking_action'); ?>
                                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                                <select name="new_status" style="vertical-align: middle;">
                                    <option value="">-- Change Status --</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="completed">Completed</option>
                                </select>
                                <button type="submit" name="action" value="update_status" class="button">Update</button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Vendor', 'tekram'); ?>:</th>
                        <td>
                            <strong><?php echo esc_html($vendor->post_title); ?></strong><br>
                            <?php echo esc_html($vendor_data['first_name'] . ' ' . $vendor_data['last_name']); ?><br>
                            <a href="mailto:<?php echo esc_attr($vendor_data['email']); ?>"><?php echo esc_html($vendor_data['email']); ?></a><br>
                            <?php echo esc_html($vendor_data['phone']); ?><br>
                            <small>Reference: <code><?php echo esc_html($vendor_data['vendor_reference']); ?></code></small>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Market', 'tekram'); ?>:</th>
                        <td><?php echo esc_html($event->post_title); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Event Date', 'tekram'); ?>:</th>
                        <td><?php echo date('l, F j, Y', strtotime($booking->booking_date)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Site', 'tekram'); ?>:</th>
                        <td><?php echo $booking->site_id ? esc_html($booking->site_id) : __('Any Available Site', 'tekram'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Amount Due', 'tekram'); ?>:</th>
                        <td><strong style="font-size: 18px;"><?php echo get_option('lt_currency_symbol', '$') . number_format($booking->amount, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Payment Status', 'tekram'); ?>:</th>
                        <td>
                            <span class="payment-badge payment-<?php echo esc_attr($booking->payment_status); ?>">
                                <?php echo ucfirst($booking->payment_status); ?>
                            </span>
                            <br>
                            <small><?php echo get_option('lt_currency_symbol', '$') . number_format($booking->paid_amount, 2); ?> paid of <?php echo get_option('lt_currency_symbol', '$') . number_format($booking->amount, 2); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Booked On', 'tekram'); ?>:</th>
                        <td><?php echo date('F j, Y g:i a', strtotime($booking->created_at)); ?></td>
                    </tr>
                </table>
                
                <h2 style="margin-top: 30px;"><?php _e('Payment Management', 'tekram'); ?></h2>
                
                <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa;">
                    <form method="post">
                        <?php wp_nonce_field('lt_booking_action'); ?>
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        
                        <p>
                            <strong><?php _e('Mark Payment Received', 'tekram'); ?></strong>
                        </p>
                        
                        <p>
                            <label for="payment_amount"><?php _e('Amount Received:', 'tekram'); ?></label><br>
                            <input type="number" name="payment_amount" id="payment_amount" step="0.01" 
                                   value="<?php echo esc_attr($booking->amount - $booking->paid_amount); ?>" 
                                   style="width: 150px;">
                            <?php echo get_option('lt_currency_symbol', '$'); ?>
                        </p>
                        
                        <p>
                            <label for="payment_method"><?php _e('Payment Method:', 'tekram'); ?></label><br>
                            <select name="payment_method" id="payment_method" style="width: 200px;">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="paypal">PayPal</option>
                                <option value="stripe">Stripe</option>
                                <option value="other">Other</option>
                            </select>
                        </p>
                        
                        <p>
                            <label for="payment_notes"><?php _e('Notes (optional):', 'tekram'); ?></label><br>
                            <textarea name="payment_notes" id="payment_notes" rows="3" style="width: 100%; max-width: 500px;"></textarea>
                        </p>
                        
                        <p>
                            <button type="submit" name="action" value="record_payment" class="button button-primary button-large">
                                <?php _e('Record Payment', 'tekram'); ?>
                            </button>
                        </p>
                        
                        <?php if ($booking->payment_status === 'unpaid' || $booking->payment_status === 'partial') { ?>
                            <p style="margin-top: 20px;">
                                <button type="submit" name="action" value="mark_paid" class="button button-success" 
                                        onclick="return confirm('<?php _e('Mark this booking as fully paid?', 'tekram'); ?>');"
                                        style="background: #46b450; border-color: #46b450; color: #fff;">
                                    <?php _e('Mark as Fully Paid', 'tekram'); ?>
                                </button>
                            </p>
                        <?php } ?>
                    </form>
                </div>
                
                <?php
                // Get payment history if exists
                $payment_meta = get_post_meta($booking->vendor_id, '_lt_booking_' . $booking_id . '_payments', true);
                if (!empty($payment_meta) && is_array($payment_meta)) {
                    ?>
                    <h3 style="margin-top: 30px;"><?php _e('Payment History', 'tekram'); ?></h3>
                    <table class="wp-list-table widefat fixed striped" style="max-width: 600px;">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'tekram'); ?></th>
                                <th><?php _e('Amount', 'tekram'); ?></th>
                                <th><?php _e('Method', 'tekram'); ?></th>
                                <th><?php _e('Notes', 'tekram'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_meta as $payment) { ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i a', strtotime($payment['date'])); ?></td>
                                    <td><?php echo get_option('lt_currency_symbol', '$') . number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo ucfirst($payment['method']); ?></td>
                                    <td><?php echo esc_html($payment['notes']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php
                }
                ?>
                
                <p style="margin-top: 30px;">
                    <a href="?page=tekram-bookings" class="button"><?php _e('← Back to Bookings', 'tekram'); ?></a>
                    <a href="?page=tekram-bookings&action=delete&id=<?php echo $booking_id; ?>" 
                       class="button button-link-delete" 
                       onclick="return confirm('<?php _e('Are you sure you want to delete this booking?', 'tekram'); ?>');" 
                       style="color: #b32d2e; margin-left: 10px;">
                        <?php _e('Delete Booking', 'tekram'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <style>
        .status-badge, .payment-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        
        .payment-unpaid { background: #f8d7da; color: #721c24; }
        .payment-partial { background: #fff3cd; color: #856404; }
        .payment-paid { background: #d4edda; color: #155724; }
        </style>
        <?php
    }
    
    private static function handle_action() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $action = sanitize_text_field($_POST['action']);
        $booking_id = intval($_POST['booking_id']);
        
        switch ($action) {
            case 'update_status':
                $new_status = sanitize_text_field($_POST['new_status']);
                if (!empty($new_status)) {
                    $wpdb->update(
                        $bookings_table,
                        array('status' => $new_status),
                        array('id' => $booking_id),
                        array('%s'),
                        array('%d')
                    );
                    echo '<div class="notice notice-success"><p>' . __('Status updated successfully.', 'tekram') . '</p></div>';
                }
                break;
                
            case 'mark_paid':
                $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $bookings_table WHERE id = %d", $booking_id));
                $wpdb->update(
                    $bookings_table,
                    array(
                        'payment_status' => 'paid',
                        'paid_amount' => $booking->amount,
                        'status' => 'confirmed'  // Automatically change status to confirmed when fully paid
                    ),
                    array('id' => $booking_id),
                    array('%s', '%f', '%s'),
                    array('%d')
                );
                
                // Record payment
                self::record_payment_history($booking_id, $booking->vendor_id, $booking->amount, 'cash', 'Marked as fully paid');
                
                echo '<div class="notice notice-success"><p>' . __('Booking marked as paid and status changed to confirmed.', 'tekram') . '</p></div>';
                break;
                
            case 'record_payment':
                $amount = floatval($_POST['payment_amount']);
                $method = sanitize_text_field($_POST['payment_method']);
                $notes = sanitize_textarea_field($_POST['payment_notes']);
                
                $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $bookings_table WHERE id = %d", $booking_id));
                $new_paid_amount = $booking->paid_amount + $amount;
                
                // Determine payment status
                $update_data = array();
                if ($new_paid_amount >= $booking->amount) {
                    $payment_status = 'paid';
                    $new_paid_amount = $booking->amount; // Don't overpay
                    $update_data['status'] = 'confirmed'; // Auto-change to confirmed when fully paid
                } elseif ($new_paid_amount > 0) {
                    $payment_status = 'partial';
                } else {
                    $payment_status = 'unpaid';
                }
                
                $update_data['payment_status'] = $payment_status;
                $update_data['paid_amount'] = $new_paid_amount;
                
                $format = array('%s', '%f');
                if (isset($update_data['status'])) {
                    $format[] = '%s';
                }
                
                $wpdb->update(
                    $bookings_table,
                    $update_data,
                    array('id' => $booking_id),
                    $format,
                    array('%d')
                );
                
                // Record in payment history
                self::record_payment_history($booking_id, $booking->vendor_id, $amount, $method, $notes);
                
                $message = sprintf(__('Payment of %s recorded successfully.', 'tekram'), get_option('lt_currency_symbol', '$') . number_format($amount, 2));
                if ($payment_status === 'paid') {
                    $message .= ' ' . __('Booking status automatically changed to confirmed.', 'tekram');
                }
                echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
                break;
        }
    }
    
    private static function record_payment_history($booking_id, $vendor_id, $amount, $method, $notes) {
        $payment_meta = get_post_meta($vendor_id, '_lt_booking_' . $booking_id . '_payments', true);
        if (!is_array($payment_meta)) {
            $payment_meta = array();
        }
        
        $payment_meta[] = array(
            'date' => current_time('mysql'),
            'amount' => $amount,
            'method' => $method,
            'notes' => $notes
        );
        
        update_post_meta($vendor_id, '_lt_booking_' . $booking_id . '_payments', $payment_meta);
    }
}



