<?php
/**
 * Event Class
 * Handles all event operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Event {
    
    /**
     * Create event
     */
    public static function create($data) {
        $post_data = array(
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['description']),
            'post_type' => 'lt_event',
            'post_status' => 'publish',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array('success' => false, 'message' => $post_id->get_error_message());
        }
        
        // Save meta data
        update_post_meta($post_id, '_lt_event_type', sanitize_text_field($data['event_type']));
        update_post_meta($post_id, '_lt_frequency', sanitize_text_field($data['frequency']));
        update_post_meta($post_id, '_lt_location', sanitize_text_field($data['location']));
        update_post_meta($post_id, '_lt_start_date', sanitize_text_field($data['start_date']));
        update_post_meta($post_id, '_lt_end_date', sanitize_text_field($data['end_date']));
        update_post_meta($post_id, '_lt_start_time', sanitize_text_field($data['start_time']));
        update_post_meta($post_id, '_lt_end_time', sanitize_text_field($data['end_time']));
        update_post_meta($post_id, '_lt_capacity', intval($data['capacity']));
        update_post_meta($post_id, '_lt_sites_config', isset($data['sites_config']) ? $data['sites_config'] : array());
        update_post_meta($post_id, '_lt_site_fee', floatval($data['site_fee']));
        update_post_meta($post_id, '_lt_booking_open_date', sanitize_text_field($data['booking_open_date']));
        update_post_meta($post_id, '_lt_booking_close_date', sanitize_text_field($data['booking_close_date']));
        
        return array('success' => true, 'post_id' => $post_id);
    }
    
    /**
     * Update event
     */
    public static function update($post_id, $data) {
        $post_data = array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['description']),
        );
        
        wp_update_post($post_data);
        
        // Update meta
        foreach ($data as $key => $value) {
            if ($key !== 'title' && $key !== 'description') {
                update_post_meta($post_id, '_lt_' . $key, $value);
            }
        }
        
        return array('success' => true);
    }
    
    /**
     * Get event data
     */
    public static function get_data($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return null;
        }
        
        $data = array(
            'id' => $post_id,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'event_type' => get_post_meta($post_id, '_lt_event_type', true),
            'frequency' => get_post_meta($post_id, '_lt_frequency', true),
            'location' => get_post_meta($post_id, '_lt_location', true),
            'start_date' => get_post_meta($post_id, '_lt_start_date', true),
            'end_date' => get_post_meta($post_id, '_lt_end_date', true),
            'start_time' => get_post_meta($post_id, '_lt_start_time', true),
            'end_time' => get_post_meta($post_id, '_lt_end_time', true),
            'capacity' => get_post_meta($post_id, '_lt_capacity', true),
            'site_fee' => get_post_meta($post_id, '_lt_site_fee', true),
            'booking_open_date' => get_post_meta($post_id, '_lt_booking_open_date', true),
            'booking_close_date' => get_post_meta($post_id, '_lt_booking_close_date', true),
        );
        
        return $data;
    }
    
    /**
     * Get all events
     */
    public static function get_all($args = array()) {
        $defaults = array(
            'post_type' => 'lt_event',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => '_lt_start_date',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return get_posts($args);
    }
    
    /**
     * Get upcoming events
     */
    public static function get_upcoming() {
        $today = current_time('Y-m-d');
        
        $args = array(
            'post_type' => 'lt_event',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_lt_start_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_lt_start_date',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
    
    /**
     * Check availability
     */
    public static function check_availability($event_id, $date) {
        $capacity = get_post_meta($event_id, '_lt_capacity', true);
        $bookings = LT_Database::get_event_bookings($event_id, $date);
        
        $confirmed_bookings = 0;
        foreach ($bookings as $booking) {
            if ($booking->status === 'confirmed') {
                $confirmed_bookings++;
            }
        }
        
        return $confirmed_bookings < $capacity;
    }
    
    /**
     * Get available slots
     */
    public static function get_available_slots($event_id, $date) {
        $capacity = get_post_meta($event_id, '_lt_capacity', true);
        $bookings = LT_Database::get_event_bookings($event_id, $date);
        
        $confirmed_bookings = 0;
        foreach ($bookings as $booking) {
            if ($booking->status === 'confirmed') {
                $confirmed_bookings++;
            }
        }
        
        return max(0, $capacity - $confirmed_bookings);
    }
    
    /**
     * Check if booking is open
     */
    public static function is_booking_open($event_id) {
        $open_date = get_post_meta($event_id, '_lt_booking_open_date', true);
        $close_date = get_post_meta($event_id, '_lt_booking_close_date', true);
        $today = current_time('Y-m-d');
        
        if ($open_date && $today < $open_date) {
            return false;
        }
        
        if ($close_date && $today > $close_date) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate event dates based on frequency
     */
    public static function generate_dates($event_id) {
        $frequency = get_post_meta($event_id, '_lt_frequency', true);
        $start_date = get_post_meta($event_id, '_lt_start_date', true);
        $end_date = get_post_meta($event_id, '_lt_end_date', true);
        
        if (!$start_date || !$end_date) {
            return array();
        }
        
        $dates = array();
        $current = strtotime($start_date);
        $end = strtotime($end_date);
        
        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            
            switch ($frequency) {
                case 'weekly':
                    $current = strtotime('+1 week', $current);
                    break;
                case 'fortnightly':
                    $current = strtotime('+2 weeks', $current);
                    break;
                case 'monthly':
                    $current = strtotime('+1 month', $current);
                    break;
                case 'quarterly':
                    $current = strtotime('+3 months', $current);
                    break;
                case 'annually':
                    $current = strtotime('+1 year', $current);
                    break;
                default:
                    // One-time event
                    break 2;
            }
        }
        
        return $dates;
    }
    
    /**
     * Delete event
     */
    public static function delete($post_id) {
        // Check if event has bookings
        $bookings = LT_Database::get_event_bookings($post_id);
        
        if (!empty($bookings)) {
            return array(
                'success' => false,
                'message' => __('Cannot delete event with existing bookings.', 'tekram')
            );
        }
        
        wp_delete_post($post_id, true);
        
        return array('success' => true);
    }
}
