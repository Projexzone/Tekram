<?php
/**
 * Refund Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Refunds {
    
    /**
     * Create refunds table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lt_refunds';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            refund_percentage decimal(5,2) DEFAULT NULL,
            reason text,
            status enum('pending','approved','processed','rejected') DEFAULT 'pending',
            requested_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            processed_by bigint(20) DEFAULT NULL,
            notes text,
            PRIMARY KEY  (id),
            KEY booking_id (booking_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Calculate refund amount based on cancellation policy
     */
    public static function calculate_refund($booking_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) {
            return false;
        }
        
        // Get cancellation policy from event settings
        $policy = get_post_meta($booking->event_id, '_lt_cancellation_policy', true);
        
        if (empty($policy)) {
            // Default policy
            $policy = array(
                array('days' => 30, 'percentage' => 100),
                array('days' => 14, 'percentage' => 50),
                array('days' => 7, 'percentage' => 25),
                array('days' => 0, 'percentage' => 0)
            );
        }
        
        // Calculate days until event
        $days_until = floor((strtotime($booking->booking_date) - time()) / (60 * 60 * 24));
        
        // Find applicable refund percentage
        $refund_percentage = 0;
        foreach ($policy as $tier) {
            if ($days_until >= $tier['days']) {
                $refund_percentage = $tier['percentage'];
                break;
            }
        }
        
        $refund_amount = ($booking->amount * $refund_percentage) / 100;
        
        return array(
            'amount' => $refund_amount,
            'percentage' => $refund_percentage,
            'days_until' => $days_until,
            'original_amount' => $booking->amount
        );
    }
    
    /**
     * Request refund
     */
    public static function request_refund($booking_id, $reason = '') {
        global $wpdb;
        $refunds_table = $wpdb->prefix . 'lt_refunds';
        
        // Check if refund already requested
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $refunds_table 
             WHERE booking_id = %d AND status IN ('pending', 'approved')",
            $booking_id
        ));
        
        if ($existing) {
            return array(
                'success' => false,
                'message' => __('A refund request already exists for this booking.', 'tekram')
            );
        }
        
        $refund_calc = self::calculate_refund($booking_id);
        
        if (!$refund_calc) {
            return array(
                'success' => false,
                'message' => __('Unable to calculate refund.', 'tekram')
            );
        }
        
        $result = $wpdb->insert(
            $refunds_table,
            array(
                'booking_id' => $booking_id,
                'amount' => $refund_calc['amount'],
                'refund_percentage' => $refund_calc['percentage'],
                'reason' => sanitize_textarea_field($reason),
                'status' => 'pending'
            ),
            array('%d', '%f', '%f', '%s', '%s')
        );
        
        if ($result) {
            // Update booking status
            $bookings_table = $wpdb->prefix . 'lt_bookings';
            $wpdb->update(
                $bookings_table,
                array('status' => 'cancelled'),
                array('id' => $booking_id)
            );
            
            // Send notification to admin
            self::notify_admin_refund_request($wpdb->insert_id);
            
            return array(
                'success' => true,
                'message' => sprintf(
                    __('Refund of %s requested successfully.', 'tekram'),
                    get_option('lt_currency_symbol', '$') . number_format($refund_calc['amount'], 2)
                ),
                'refund_id' => $wpdb->insert_id,
                'refund_amount' => $refund_calc['amount']
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Failed to request refund.', 'tekram')
        );
    }
    
    /**
     * Approve refund
     */
    public static function approve_refund($refund_id, $notes = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_refunds';
        
        $result = $wpdb->update(
            $table,
            array(
                'status' => 'approved',
                'notes' => sanitize_textarea_field($notes),
                'processed_by' => get_current_user_id()
            ),
            array('id' => $refund_id),
            array('%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result) {
            self::notify_vendor_refund_approved($refund_id);
        }
        
        return $result;
    }
    
    /**
     * Process refund (mark as completed)
     */
    public static function process_refund($refund_id, $notes = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_refunds';
        
        $result = $wpdb->update(
            $table,
            array(
                'status' => 'processed',
                'processed_at' => current_time('mysql'),
                'notes' => sanitize_textarea_field($notes),
                'processed_by' => get_current_user_id()
            ),
            array('id' => $refund_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result) {
            self::notify_vendor_refund_processed($refund_id);
        }
        
        return $result;
    }
    
    /**
     * Reject refund
     */
    public static function reject_refund($refund_id, $notes = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_refunds';
        
        $result = $wpdb->update(
            $table,
            array(
                'status' => 'rejected',
                'notes' => sanitize_textarea_field($notes),
                'processed_by' => get_current_user_id()
            ),
            array('id' => $refund_id),
            array('%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result) {
            // Restore booking status
            $refund = self::get_refund($refund_id);
            $bookings_table = $wpdb->prefix . 'lt_bookings';
            $wpdb->update(
                $bookings_table,
                array('status' => 'confirmed'),
                array('id' => $refund->booking_id)
            );
            
            self::notify_vendor_refund_rejected($refund_id);
        }
        
        return $result;
    }
    
    /**
     * Get refund by ID
     */
    public static function get_refund($refund_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_refunds';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $refund_id
        ));
    }
    
    /**
     * Get refunds for booking
     */
    public static function get_booking_refunds($booking_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_refunds';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE booking_id = %d ORDER BY requested_at DESC",
            $booking_id
        ));
    }
    
    /**
     * Get all pending refunds
     */
    public static function get_pending_refunds() {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_refunds';
        
        return $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'pending' ORDER BY requested_at ASC"
        );
    }
    
    /**
     * Get cancellation policy for event
     */
    public static function get_cancellation_policy($event_id) {
        $policy = get_post_meta($event_id, '_lt_cancellation_policy', true);
        
        if (empty($policy)) {
            // Default policy
            return array(
                array('days' => 30, 'percentage' => 100),
                array('days' => 14, 'percentage' => 50),
                array('days' => 7, 'percentage' => 25),
                array('days' => 0, 'percentage' => 0)
            );
        }
        
        return $policy;
    }
    
    /**
     * Set cancellation policy for event
     */
    public static function set_cancellation_policy($event_id, $policy) {
        return update_post_meta($event_id, '_lt_cancellation_policy', $policy);
    }
    
    /**
     * Notify admin of refund request
     */
    private static function notify_admin_refund_request($refund_id) {
        $refund = self::get_refund($refund_id);
        $booking = LT_Booking::get_by_id($refund->booking_id);
        $vendor_data = LT_Vendor::get_data($booking['vendor_id']);
        $event = get_post($booking['event_id']);
        
        $subject = __('New Refund Request', 'tekram');
        $message = sprintf(
            __('A new refund request has been submitted.

Vendor: %s
Event: %s
Date: %s
Amount: %s
Refund Amount: %s (%d%%)
Reason: %s

Please review the request in the admin panel.', 'tekram'),
            $vendor_data['business_name'],
            $event->post_title,
            date('F j, Y', strtotime($booking['booking_date'])),
            get_option('lt_currency_symbol', '$') . number_format($booking['amount'], 2),
            get_option('lt_currency_symbol', '$') . number_format($refund->amount, 2),
            $refund->refund_percentage,
            $refund->reason
        );
        
        wp_mail(get_option('admin_email'), $subject, $message);
    }
    
    /**
     * Notify vendor of refund approval
     */
    private static function notify_vendor_refund_approved($refund_id) {
        $refund = self::get_refund($refund_id);
        $booking = LT_Booking::get_by_id($refund->booking_id);
        $vendor_data = LT_Vendor::get_data($booking['vendor_id']);
        
        $subject = __('Refund Approved', 'tekram');
        $message = sprintf(
            __('Hi %s,

Your refund request has been approved.

Refund Amount: %s
Processing Time: 5-7 business days

The refund will be processed to your original payment method.

Best regards,
%s', 'tekram'),
            $vendor_data['first_name'],
            get_option('lt_currency_symbol', '$') . number_format($refund->amount, 2),
            get_bloginfo('name')
        );
        
        wp_mail($vendor_data['email'], $subject, $message);
    }
    
    /**
     * Notify vendor of refund processed
     */
    private static function notify_vendor_refund_processed($refund_id) {
        $refund = self::get_refund($refund_id);
        $booking = LT_Booking::get_by_id($refund->booking_id);
        $vendor_data = LT_Vendor::get_data($booking['vendor_id']);
        
        $subject = __('Refund Processed', 'tekram');
        $message = sprintf(
            __('Hi %s,

Your refund has been processed!

Refund Amount: %s
Date Processed: %s

You should see the refund in your account within 5-7 business days.

Best regards,
%s', 'tekram'),
            $vendor_data['first_name'],
            get_option('lt_currency_symbol', '$') . number_format($refund->amount, 2),
            date('F j, Y', strtotime($refund->processed_at)),
            get_bloginfo('name')
        );
        
        wp_mail($vendor_data['email'], $subject, $message);
    }
    
    /**
     * Notify vendor of refund rejection
     */
    private static function notify_vendor_refund_rejected($refund_id) {
        $refund = self::get_refund($refund_id);
        $booking = LT_Booking::get_by_id($refund->booking_id);
        $vendor_data = LT_Vendor::get_data($booking['vendor_id']);
        
        $subject = __('Refund Request Declined', 'tekram');
        $message = sprintf(
            __('Hi %s,

Your refund request has been declined.

Reason: %s

If you have questions, please contact us.

Best regards,
%s', 'tekram'),
            $vendor_data['first_name'],
            $refund->notes,
            get_bloginfo('name')
        );
        
        wp_mail($vendor_data['email'], $subject, $message);
    }
}



