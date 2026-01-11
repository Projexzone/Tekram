<?php
/**
 * Waitlist Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Waitlist {
    
    /**
     * Create waitlist table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lt_waitlist';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            vendor_id bigint(20) NOT NULL,
            booking_date date NOT NULL,
            position int(11) NOT NULL,
            status enum('waiting','offered','accepted','declined','expired') DEFAULT 'waiting',
            offered_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_date (event_id, booking_date),
            KEY vendor_id (vendor_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add vendor to waitlist
     */
    public static function add_to_waitlist($event_id, $vendor_id, $booking_date) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        // Check if already on waitlist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table 
             WHERE event_id = %d AND vendor_id = %d AND booking_date = %s 
             AND status = 'waiting'",
            $event_id, $vendor_id, $booking_date
        ));
        
        if ($existing) {
            return array(
                'success' => false,
                'message' => __('You are already on the waitlist for this date.', 'tekram')
            );
        }
        
        // Get next position
        $position = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(position), 0) + 1 FROM $table 
             WHERE event_id = %d AND booking_date = %s AND status = 'waiting'",
            $event_id, $booking_date
        ));
        
        // Insert waitlist entry
        $result = $wpdb->insert(
            $table,
            array(
                'event_id' => $event_id,
                'vendor_id' => $vendor_id,
                'booking_date' => $booking_date,
                'position' => $position,
                'status' => 'waiting'
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );
        
        if ($result) {
            // Send confirmation email
            self::send_waitlist_confirmation($wpdb->insert_id);
            
            return array(
                'success' => true,
                'message' => sprintf(__('You have been added to the waitlist at position #%d', 'tekram'), $position),
                'position' => $position
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Failed to join waitlist. Please try again.', 'tekram')
        );
    }
    
    /**
     * Offer spot to next person on waitlist
     */
    public static function offer_next_spot($event_id, $booking_date) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        // Get next person on waitlist
        $waitlist_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE event_id = %d AND booking_date = %s AND status = 'waiting'
             ORDER BY position ASC LIMIT 1",
            $event_id, $booking_date
        ));
        
        if (!$waitlist_entry) {
            return false;
        }
        
        // Set expiry time (24 hours from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update status
        $wpdb->update(
            $table,
            array(
                'status' => 'offered',
                'offered_at' => current_time('mysql'),
                'expires_at' => $expires_at
            ),
            array('id' => $waitlist_entry->id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        // Send notification
        self::send_spot_available($waitlist_entry->id);
        
        return true;
    }
    
    /**
     * Accept waitlist offer
     */
    public static function accept_offer($waitlist_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $waitlist_id));
        
        if (!$entry || $entry->status !== 'offered') {
            return array(
                'success' => false,
                'message' => __('Invalid waitlist offer.', 'tekram')
            );
        }
        
        // Check if expired
        if (strtotime($entry->expires_at) < time()) {
            $wpdb->update($table, array('status' => 'expired'), array('id' => $waitlist_id));
            
            // Offer to next person
            self::offer_next_spot($entry->event_id, $entry->booking_date);
            
            return array(
                'success' => false,
                'message' => __('This offer has expired.', 'tekram')
            );
        }
        
        // Mark as accepted
        $wpdb->update($table, array('status' => 'accepted'), array('id' => $waitlist_id));
        
        return array(
            'success' => true,
            'event_id' => $entry->event_id,
            'booking_date' => $entry->booking_date
        );
    }
    
    /**
     * Get waitlist position
     */
    public static function get_position($event_id, $vendor_id, $booking_date) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT position, status FROM $table 
             WHERE event_id = %d AND vendor_id = %d AND booking_date = %s 
             AND status IN ('waiting', 'offered')
             ORDER BY created_at DESC LIMIT 1",
            $event_id, $vendor_id, $booking_date
        ));
    }
    
    /**
     * Get waitlist count
     */
    public static function get_count($event_id, $booking_date) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE event_id = %d AND booking_date = %s AND status = 'waiting'",
            $event_id, $booking_date
        ));
    }
    
    /**
     * Send waitlist confirmation email
     */
    private static function send_waitlist_confirmation($waitlist_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $waitlist_id));
        $vendor_data = LT_Vendor::get_data($entry->vendor_id);
        $event = get_post($entry->event_id);
        
        $subject = __('Waitlist Confirmation', 'tekram');
        $message = sprintf(
            __('Hi %s,

You have been added to the waitlist for:

Event: %s
Date: %s
Your Position: #%d

We will notify you immediately if a spot becomes available. You will have 24 hours to accept the booking.

Thank you for your patience!

Best regards,
%s', 'tekram'),
            $vendor_data['first_name'],
            $event->post_title,
            date('F j, Y', strtotime($entry->booking_date)),
            $entry->position,
            get_bloginfo('name')
        );
        
        wp_mail($vendor_data['email'], $subject, $message);
    }
    
    /**
     * Send spot available notification
     */
    private static function send_spot_available($waitlist_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        $entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $waitlist_id));
        $vendor_data = LT_Vendor::get_data($entry->vendor_id);
        $event = get_post($entry->event_id);
        
        $booking_url = home_url('/booking-page/?waitlist=' . $waitlist_id);
        
        $subject = __('Spot Available - Book Now!', 'tekram');
        $message = sprintf(
            __('Hi %s,

GREAT NEWS! A spot has become available for:

Event: %s
Date: %s

You have 24 hours to accept this booking. After that, the spot will be offered to the next person on the waitlist.

To book this spot, click here:
%s

Or go to the booking page and use your vendor reference: %s

Hurry - this offer expires at: %s

Best regards,
%s', 'tekram'),
            $vendor_data['first_name'],
            $event->post_title,
            date('F j, Y', strtotime($entry->booking_date)),
            $booking_url,
            $vendor_data['vendor_reference'],
            date('F j, Y g:i a', strtotime($entry->expires_at)),
            get_bloginfo('name')
        );
        
        wp_mail($vendor_data['email'], $subject, $message);
    }
    
    /**
     * Check and expire old offers
     */
    public static function check_expired_offers() {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        // Get expired offers
        $expired = $wpdb->get_results(
            "SELECT * FROM $table 
             WHERE status = 'offered' AND expires_at < NOW()"
        );
        
        foreach ($expired as $entry) {
            // Mark as expired
            $wpdb->update($table, array('status' => 'expired'), array('id' => $entry->id));
            
            // Offer to next person
            self::offer_next_spot($entry->event_id, $entry->booking_date);
        }
    }
}



