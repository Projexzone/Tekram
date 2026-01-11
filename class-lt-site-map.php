<?php
/**
 * Site Map Class
 * Handles site map and visual layout operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Site_Map {
    
    /**
     * Save visual layout
     */
    public static function save_layout($event_id, $layout_data) {
        // Save layout as JSON
        update_post_meta($event_id, '_lt_visual_layout', $layout_data);
        
        return array('success' => true);
    }
    
    /**
     * Get visual layout
     */
    public static function get_layout($event_id) {
        $layout = get_post_meta($event_id, '_lt_visual_layout', true);
        
        if (empty($layout)) {
            return array(
                'sites' => array(),
                'facilities' => array(),
                'paths' => array(),
                'labels' => array()
            );
        }
        
        return json_decode($layout, true);
    }
    
    /**
     * Assign vendor to site
     */
    public static function assign_vendor($event_id, $date, $site_id, $vendor_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_bookings';
        
        // Check if site already booked
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table 
             WHERE event_id = %d 
             AND booking_date = %s 
             AND site_id = %s 
             AND status != 'cancelled'",
            $event_id, $date, $site_id
        ));
        
        if ($existing) {
            return array(
                'success' => false,
                'message' => __('This site is already booked.', 'tekram')
            );
        }
        
        return array('success' => true);
    }
    
    /**
     * Get site assignments for event/date
     */
    public static function get_assignments($event_id, $date) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_bookings';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT b.site_id, b.vendor_id, b.booking_reference, v.post_title as vendor_name
             FROM $table b
             LEFT JOIN {$wpdb->posts} v ON b.vendor_id = v.ID
             WHERE b.event_id = %d 
             AND b.booking_date = %s 
             AND b.status != 'cancelled'",
            $event_id, $date
        ));
        
        $assignments = array();
        foreach ($results as $row) {
            $vendor_data = LT_Vendor::get_data($row->vendor_id);
            $assignments[$row->site_id] = array(
                'vendor_id' => $row->vendor_id,
                'vendor_name' => $row->vendor_name,
                'business_name' => $vendor_data['business_name'],
                'reference' => $row->booking_reference
            );
        }
        
        return $assignments;
    }
    
    /**
     * Export layout to PDF
     */
    public static function export_to_pdf($event_id, $date) {
        $event = get_post($event_id);
        $layout = self::get_layout($event_id);
        $assignments = self::get_assignments($event_id, $date);
        
        // Generate HTML for PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                h1 { text-align: center; }
                .map-container { border: 2px solid #333; padding: 20px; }
                .site { border: 1px solid #666; padding: 5px; margin: 5px; }
                .vendor { font-weight: bold; color: #0073aa; }
            </style>
        </head>
        <body>
            <h1>' . esc_html($event->post_title) . '</h1>
            <h3>Date: ' . date('F j, Y', strtotime($date)) . '</h3>
            <div class="map-container">
                <h2>Site Assignments</h2>';
        
        foreach ($assignments as $site_id => $vendor) {
            $html .= '<div class="site">';
            $html .= '<strong>Site ' . esc_html($site_id) . ':</strong> ';
            $html .= '<span class="vendor">' . esc_html($vendor['business_name']) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Create site
     */
    public static function create($data) {
        $post_data = array(
            'post_title' => sanitize_text_field($data['name']),
            'post_type' => 'lt_site',
            'post_status' => 'publish',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array('success' => false, 'message' => $post_id->get_error_message());
        }
        
        // Save meta data
        update_post_meta($post_id, '_lt_event_id', intval($data['event_id']));
        update_post_meta($post_id, '_lt_site_number', sanitize_text_field($data['site_number']));
        update_post_meta($post_id, '_lt_width', floatval($data['width']));
        update_post_meta($post_id, '_lt_length', floatval($data['length']));
        update_post_meta($post_id, '_lt_position_x', floatval($data['position_x']));
        update_post_meta($post_id, '_lt_position_y', floatval($data['position_y']));
        update_post_meta($post_id, '_lt_description', sanitize_textarea_field($data['description']));
        
        return array('success' => true, 'post_id' => $post_id);
    }
    
    /**
     * Update site
     */
    public static function update($post_id, $data) {
        $post_data = array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field($data['name']),
        );
        
        wp_update_post($post_data);
        
        // Update meta
        foreach ($data as $key => $value) {
            if ($key !== 'name') {
                update_post_meta($post_id, '_lt_' . $key, $value);
            }
        }
        
        return array('success' => true);
    }
    
    /**
     * Delete site
     */
    public static function delete($post_id) {
        // Check if site has bookings
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $has_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table WHERE site_id = %d AND status != 'cancelled'",
            $post_id
        ));
        
        if ($has_bookings > 0) {
            return array(
                'success' => false,
                'message' => __('Cannot delete site with active bookings.', 'tekram')
            );
        }
        
        wp_delete_post($post_id, true);
        
        return array('success' => true);
    }
    
    /**
     * Get site data
     */
    public static function get_data($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return null;
        }
        
        $data = array(
            'id' => $post_id,
            'name' => $post->post_title,
            'event_id' => get_post_meta($post_id, '_lt_event_id', true),
            'site_number' => get_post_meta($post_id, '_lt_site_number', true),
            'width' => get_post_meta($post_id, '_lt_width', true),
            'length' => get_post_meta($post_id, '_lt_length', true),
            'position_x' => get_post_meta($post_id, '_lt_position_x', true),
            'position_y' => get_post_meta($post_id, '_lt_position_y', true),
            'description' => get_post_meta($post_id, '_lt_description', true),
        );
        
        return $data;
    }
    
    /**
     * Get sites by event
     */
    public static function get_by_event($event_id) {
        $args = array(
            'post_type' => 'lt_site',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_lt_event_id',
            'meta_value' => $event_id,
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
    
    /**
     * Get map layout
     */
    public static function get_map_layout($event_id) {
        $sites = self::get_by_event($event_id);
        $layout = array();
        
        foreach ($sites as $site) {
            $layout[] = array(
                'id' => $site->ID,
                'name' => $site->post_title,
                'site_number' => get_post_meta($site->ID, '_lt_site_number', true),
                'width' => get_post_meta($site->ID, '_lt_width', true),
                'length' => get_post_meta($site->ID, '_lt_length', true),
                'position_x' => get_post_meta($site->ID, '_lt_position_x', true),
                'position_y' => get_post_meta($site->ID, '_lt_position_y', true),
            );
        }
        
        return $layout;
    }
    
    /**
     * Update site positions (bulk update)
     */
    public static function update_positions($positions) {
        foreach ($positions as $site_id => $position) {
            update_post_meta($site_id, '_lt_position_x', floatval($position['x']));
            update_post_meta($site_id, '_lt_position_y', floatval($position['y']));
        }
        
        return array('success' => true);
    }
    
    /**
     * Clone sites from another event
     */
    public static function clone_from_event($source_event_id, $target_event_id) {
        $sites = self::get_by_event($source_event_id);
        $cloned = 0;
        
        foreach ($sites as $site) {
            $data = self::get_data($site->ID);
            $data['event_id'] = $target_event_id;
            
            $result = self::create($data);
            if ($result['success']) {
                $cloned++;
            }
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('%d sites cloned successfully.', 'tekram'), $cloned)
        );
    }
}
