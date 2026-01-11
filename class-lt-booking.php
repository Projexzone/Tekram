<?php
/**
 * Booking Class
 * Handles all booking operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Booking {
    
    /**
     * Create booking
     */
    public static function create($data) {
        $vendor_reference = sanitize_text_field($data['vendor_reference']);
        $email_verify = sanitize_email($data['email_verify']);
        
        // Verify vendor
        $vendor = LT_Vendor::verify_vendor($vendor_reference, $email_verify);
        
        if (!$vendor) {
            return array(
                'success' => false,
                'message' => __('Invalid Vendor Reference ID or email address. Please check and try again.', 'tekram')
            );
        }
        
        $event_id = intval($data['event_id']);
        $booking_date = sanitize_text_field($data['booking_date']);
        $site_id = !empty($data['site_id']) ? sanitize_text_field($data['site_id']) : null;
        
        // Validate event
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'lt_event') {
            return array(
                'success' => false,
                'message' => __('Invalid market.', 'tekram')
            );
        }
        
        // Check if booking is open
        if (!LT_Event::is_booking_open($event_id)) {
            return array(
                'success' => false,
                'message' => __('Booking is not open for this market.', 'tekram')
            );
        }
        
        // Check availability
        if ($site_id) {
            if (!LT_Database::is_site_available($event_id, $site_id, $booking_date)) {
                return array(
                    'success' => false,
                    'message' => __('This site is not available.', 'tekram')
                );
            }
        } else {
            if (!LT_Event::check_availability($event_id, $booking_date)) {
                return array(
                    'success' => false,
                    'message' => __('No sites available for this date.', 'tekram')
                );
            }
        }
        
        // Generate booking reference
        $reference = self::generate_reference();
        
        // Get site fee (check if custom site pricing exists)
        $sites_config = get_post_meta($event_id, '_lt_sites_config', true);
        $amount = get_post_meta($event_id, '_lt_site_fee', true);
        
        // If site_id is provided and custom config exists, check for custom pricing
        if ($site_id && !empty($sites_config) && is_array($sites_config)) {
            foreach ($sites_config as $site) {
                if ($site['name'] == $site_id && isset($site['price']) && $site['price'] > 0) {
                    $amount = $site['price'];
                    break;
                }
            }
        }
        
        // Create booking
        $booking_data = array(
            'vendor_id' => $vendor->ID,
            'event_id' => $event_id,
            'site_id' => $site_id,
            'booking_date' => $booking_date,
            'status' => 'pending',
            'amount' => $amount,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'booking_reference' => $reference,
        );
        
        $booking_id = LT_Database::insert_booking($booking_data);
        
        if (!$booking_id) {
            return array(
                'success' => false,
                'message' => __('Failed to create booking.', 'tekram')
            );
        }
        
        // Send notifications
        LT_Notifications::send_booking_confirmation($booking_id);
        
        return array(
            'success' => true,
            'message' => __('Booking created successfully!', 'tekram'),
            'booking_id' => $booking_id,
            'reference' => $reference
        );
    }
    
    /**
     * Update booking status
     */
    public static function update_status($booking_id, $status, $notes = '') {
        $valid_statuses = array('pending', 'confirmed', 'cancelled', 'completed');
        
        if (!in_array($status, $valid_statuses)) {
            return array(
                'success' => false,
                'message' => __('Invalid status.', 'tekram')
            );
        }
        
        $data = array('status' => $status);
        
        if ($notes) {
            $data['notes'] = sanitize_textarea_field($notes);
        }
        
        $result = LT_Database::update_booking($booking_id, $data);
        
        if ($result) {
            // Send notification
            LT_Notifications::send_booking_status_update($booking_id, $status);
            
            return array('success' => true);
        }
        
        return array('success' => false, 'message' => __('Failed to update booking.', 'tekram'));
    }
    
    /**
     * Cancel booking
     */
    public static function cancel($booking_id, $reason = '') {
        $booking = LT_Database::get_booking($booking_id);
        
        if (!$booking) {
            return array(
                'success' => false,
                'message' => __('Booking not found.', 'tekram')
            );
        }
        
        // Check if can cancel
        if ($booking->status === 'completed' || $booking->status === 'cancelled') {
            return array(
                'success' => false,
                'message' => __('This booking cannot be cancelled.', 'tekram')
            );
        }
        
        // Update status
        $data = array(
            'status' => 'cancelled',
            'notes' => $reason
        );
        
        LT_Database::update_booking($booking_id, $data);
        
        // Process refund if payment was made
        if ($booking->paid_amount > 0) {
            LT_Payment::process_refund($booking_id);
        }
        
        // Send notification
        LT_Notifications::send_booking_cancellation($booking_id);
        
        return array('success' => true, 'message' => __('Booking cancelled.', 'tekram'));
    }
    
    /**
     * Get booking details
     */
    public static function get_details($booking_id) {
        $booking = LT_Database::get_booking($booking_id);
        
        if (!$booking) {
            return null;
        }
        
        // Get related data
        $event = LT_Event::get_data($booking->event_id);
        $vendor = LT_Vendor::get_data($booking->vendor_id);
        
        $site_name = '';
        if ($booking->site_id) {
            $site = get_post($booking->site_id);
            $site_name = $site ? $site->post_title : '';
        }
        
        $payments = LT_Database::get_booking_payments($booking_id);
        
        return array(
            'booking' => $booking,
            'event' => $event,
            'vendor' => $vendor,
            'site_name' => $site_name,
            'payments' => $payments
        );
    }
    
    /**
     * Generate unique booking reference
     */
    private static function generate_reference() {
        do {
            $reference = 'BK-' . strtoupper(wp_generate_password(8, false));
            
            global $wpdb;
            $table = $wpdb->prefix . 'lt_bookings';
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE booking_reference = %s",
                $reference
            ));
        } while ($exists);
        
        return $reference;
    }
    
    /**
     * Get vendor bookings
     */
    public static function get_vendor_bookings($vendor_id, $status = null) {
        return LT_Database::get_vendor_bookings($vendor_id, $status);
    }
    
    /**
     * Get event bookings
     */
    public static function get_event_bookings($event_id, $date = null) {
        return LT_Database::get_event_bookings($event_id, $date);
    }
    
    /**
     * Get user bookings
     */
    public static function get_user_bookings($user_id, $status = null) {
        $vendor = LT_Vendor::get_by_user_id($user_id);
        
        if (!$vendor) {
            return array();
        }
        
        return self::get_vendor_bookings($vendor->ID, $status);
    }
}



