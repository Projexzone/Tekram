<?php
/**
 * Extras/Add-ons Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Extras {
    
    /**
     * Create extras tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Extras table
        $extras_table = $wpdb->prefix . 'lt_extras';
        $sql1 = "CREATE TABLE $extras_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) DEFAULT NULL,
            name varchar(255) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL,
            quantity_available int(11) DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_id (event_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Booking extras junction table
        $booking_extras_table = $wpdb->prefix . 'lt_booking_extras';
        $sql2 = "CREATE TABLE $booking_extras_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            extra_id bigint(20) NOT NULL,
            quantity int(11) DEFAULT 1,
            price decimal(10,2) NOT NULL,
            total decimal(10,2) NOT NULL,
            PRIMARY KEY  (id),
            KEY booking_id (booking_id),
            KEY extra_id (extra_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }
    
    /**
     * Create extra
     */
    public static function create_extra($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_extras';
        
        $result = $wpdb->insert(
            $table,
            array(
                'event_id' => isset($data['event_id']) ? intval($data['event_id']) : null,
                'name' => sanitize_text_field($data['name']),
                'description' => sanitize_textarea_field($data['description']),
                'price' => floatval($data['price']),
                'quantity_available' => isset($data['quantity_available']) ? intval($data['quantity_available']) : null,
                'status' => 'active'
            ),
            array('%d', '%s', '%s', '%f', '%d', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get extras for event
     */
    public static function get_event_extras($event_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_extras';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE (event_id = %d OR event_id IS NULL) 
             AND status = 'active'
             ORDER BY name ASC",
            $event_id
        ));
    }
    
    /**
     * Get global extras (applicable to all events)
     */
    public static function get_global_extras() {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_extras';
        
        return $wpdb->get_results(
            "SELECT * FROM $table 
             WHERE event_id IS NULL 
             AND status = 'active'
             ORDER BY name ASC"
        );
    }
    
    /**
     * Get extra by ID
     */
    public static function get_extra($extra_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_extras';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $extra_id
        ));
    }
    
    /**
     * Update extra
     */
    public static function update_extra($extra_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_extras';
        
        $update_data = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['price'])) {
            $update_data['price'] = floatval($data['price']);
        }
        if (isset($data['quantity_available'])) {
            $update_data['quantity_available'] = intval($data['quantity_available']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }
        
        return $wpdb->update(
            $table,
            $update_data,
            array('id' => $extra_id)
        );
    }
    
    /**
     * Delete extra
     */
    public static function delete_extra($extra_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_extras';
        
        return $wpdb->delete($table, array('id' => $extra_id), array('%d'));
    }
    
    /**
     * Add extra to booking
     */
    public static function add_to_booking($booking_id, $extra_id, $quantity = 1) {
        global $wpdb;
        $booking_extras_table = $wpdb->prefix . 'lt_booking_extras';
        
        $extra = self::get_extra($extra_id);
        if (!$extra) {
            return false;
        }
        
        // Check availability
        if ($extra->quantity_available !== null) {
            $used = self::get_quantity_used($extra_id);
            if ($used + $quantity > $extra->quantity_available) {
                return false;
            }
        }
        
        $total = $extra->price * $quantity;
        
        return $wpdb->insert(
            $booking_extras_table,
            array(
                'booking_id' => $booking_id,
                'extra_id' => $extra_id,
                'quantity' => $quantity,
                'price' => $extra->price,
                'total' => $total
            ),
            array('%d', '%d', '%d', '%f', '%f')
        );
    }
    
    /**
     * Get booking extras
     */
    public static function get_booking_extras($booking_id) {
        global $wpdb;
        $booking_extras_table = $wpdb->prefix . 'lt_booking_extras';
        $extras_table = $wpdb->prefix . 'lt_extras';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT be.*, e.name, e.description 
             FROM $booking_extras_table be
             JOIN $extras_table e ON be.extra_id = e.id
             WHERE be.booking_id = %d",
            $booking_id
        ));
    }
    
    /**
     * Get total extras cost for booking
     */
    public static function get_booking_extras_total($booking_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_booking_extras';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM $table WHERE booking_id = %d",
            $booking_id
        ));
    }
    
    /**
     * Remove extra from booking
     */
    public static function remove_from_booking($booking_id, $extra_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_booking_extras';
        
        return $wpdb->delete(
            $table,
            array('booking_id' => $booking_id, 'extra_id' => $extra_id),
            array('%d', '%d')
        );
    }
    
    /**
     * Get quantity used for an extra
     */
    public static function get_quantity_used($extra_id, $event_id = null, $date = null) {
        global $wpdb;
        $booking_extras_table = $wpdb->prefix . 'lt_booking_extras';
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $sql = "SELECT COALESCE(SUM(be.quantity), 0) 
                FROM $booking_extras_table be
                JOIN $bookings_table b ON be.booking_id = b.id
                WHERE be.extra_id = %d 
                AND b.status != 'cancelled'";
        
        $params = array($extra_id);
        
        if ($event_id) {
            $sql .= " AND b.event_id = %d";
            $params[] = $event_id;
        }
        
        if ($date) {
            $sql .= " AND b.booking_date = %s";
            $params[] = $date;
        }
        
        return $wpdb->get_var($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get available quantity for an extra
     */
    public static function get_available_quantity($extra_id, $event_id = null, $date = null) {
        $extra = self::get_extra($extra_id);
        
        if (!$extra || $extra->quantity_available === null) {
            return null; // Unlimited
        }
        
        $used = self::get_quantity_used($extra_id, $event_id, $date);
        return max(0, $extra->quantity_available - $used);
    }
}
