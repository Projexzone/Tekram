<?php
/**
 * Database Class
 * Handles all database operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Database {
    
    /**
     * Create custom database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Bookings table
        $table_bookings = $wpdb->prefix . 'lt_bookings';
        $sql_bookings = "CREATE TABLE $table_bookings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vendor_id bigint(20) NOT NULL,
            event_id bigint(20) NOT NULL,
            site_id bigint(20) DEFAULT NULL,
            booking_date date NOT NULL,
            status varchar(50) DEFAULT 'pending',
            amount decimal(10,2) DEFAULT 0.00,
            paid_amount decimal(10,2) DEFAULT 0.00,
            payment_status varchar(50) DEFAULT 'unpaid',
            booking_reference varchar(100) UNIQUE,
            notes text,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vendor_id (vendor_id),
            KEY event_id (event_id),
            KEY booking_date (booking_date),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_bookings);
        
        // Payments table
        $table_payments = $wpdb->prefix . 'lt_payments';
        $sql_payments = "CREATE TABLE $table_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            vendor_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50) NOT NULL,
            transaction_id varchar(255),
            status varchar(50) DEFAULT 'pending',
            payment_date datetime,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY vendor_id (vendor_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_payments);
        
        // Notifications table
        $table_notifications = $wpdb->prefix . 'lt_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            subject varchar(255),
            message text,
            status varchar(50) DEFAULT 'pending',
            sent_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_notifications);
        
        // Availability table
        $table_availability = $wpdb->prefix . 'lt_availability';
        $sql_availability = "CREATE TABLE $table_availability (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_id bigint(20) NOT NULL,
            site_id bigint(20) NOT NULL,
            date date NOT NULL,
            is_available tinyint(1) DEFAULT 1,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_availability (event_id, site_id, date),
            KEY event_id (event_id),
            KEY date (date)
        ) $charset_collate;";
        dbDelta($sql_availability);
        
        // Documents table
        $table_documents = $wpdb->prefix . 'lt_documents';
        $sql_documents = "CREATE TABLE $table_documents (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vendor_id bigint(20) NOT NULL,
            document_type varchar(100) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_url varchar(500) NOT NULL,
            expiry_date date,
            status varchar(50) DEFAULT 'pending',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vendor_id (vendor_id),
            KEY document_type (document_type)
        ) $charset_collate;";
        dbDelta($sql_documents);
        
        // Waitlist table
        $table_waitlist = $wpdb->prefix . 'lt_waitlist';
        $sql_waitlist = "CREATE TABLE $table_waitlist (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vendor_id bigint(20) NOT NULL,
            event_id bigint(20) NOT NULL,
            date date NOT NULL,
            position int(11) NOT NULL,
            status varchar(50) DEFAULT 'active',
            notified tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vendor_id (vendor_id),
            KEY event_id (event_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_waitlist);
        
        // Extras table
        $table_extras = $wpdb->prefix . 'lt_extras';
        $sql_extras = "CREATE TABLE $table_extras (
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
        dbDelta($sql_extras);
        
        // Booking extras junction table
        $table_booking_extras = $wpdb->prefix . 'lt_booking_extras';
        $sql_booking_extras = "CREATE TABLE $table_booking_extras (
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
        dbDelta($sql_booking_extras);
        
        // Refunds table
        $table_refunds = $wpdb->prefix . 'lt_refunds';
        $sql_refunds = "CREATE TABLE $table_refunds (
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
        dbDelta($sql_refunds);
        
        // Update database version
        update_option('lt_db_version', '1.0.0');
    }
    
    /**
     * Get booking by ID
     */
    public static function get_booking($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_bookings';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Get bookings by vendor
     */
    public static function get_vendor_bookings($vendor_id, $status = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_bookings';
        
        if ($status) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE vendor_id = %d AND status = %s ORDER BY booking_date DESC",
                $vendor_id, $status
            ));
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE vendor_id = %d ORDER BY booking_date DESC",
            $vendor_id
        ));
    }
    
    /**
     * Get bookings by event
     */
    public static function get_event_bookings($event_id, $date = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_bookings';
        
        if ($date) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE event_id = %d AND booking_date = %s",
                $event_id, $date
            ));
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE event_id = %d ORDER BY booking_date DESC",
            $event_id
        ));
    }
    
    /**
     * Insert booking
     */
    public static function insert_booking($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_bookings';
        
        $wpdb->insert($table, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update booking
     */
    public static function update_booking($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_bookings';
        
        return $wpdb->update($table, $data, array('id' => $id));
    }
    
    /**
     * Delete booking
     */
    public static function delete_booking($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_bookings';
        
        return $wpdb->delete($table, array('id' => $id));
    }
    
    /**
     * Get payment by ID
     */
    public static function get_payment($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_payments';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    /**
     * Get payments by booking
     */
    public static function get_booking_payments($booking_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_payments';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE booking_id = %d ORDER BY payment_date DESC",
            $booking_id
        ));
    }
    
    /**
     * Insert payment
     */
    public static function insert_payment($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_payments';
        
        $wpdb->insert($table, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update payment
     */
    public static function update_payment($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_payments';
        
        return $wpdb->update($table, $data, array('id' => $id));
    }
    
    /**
     * Check site availability
     */
    public static function is_site_available($event_id, $site_id, $date) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        $availability_table = $wpdb->prefix . 'lt_availability';
        
        // Check if already booked
        $booked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE event_id = %d AND site_id = %d AND booking_date = %s AND status != 'cancelled'",
            $event_id, $site_id, $date
        ));
        
        if ($booked > 0) {
            return false;
        }
        
        // Check availability settings
        $available = $wpdb->get_var($wpdb->prepare(
            "SELECT is_available FROM $availability_table 
             WHERE event_id = %d AND site_id = %d AND date = %s",
            $event_id, $site_id, $date
        ));
        
        // If no specific availability set, assume available
        return ($available === null) ? true : (bool) $available;
    }
    
    /**
     * Get available sites for event and date
     */
    public static function get_available_sites($event_id, $date) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        // Check if custom sites are configured
        $sites_config = get_post_meta($event_id, '_lt_sites_config', true);
        
        if (!empty($sites_config) && is_array($sites_config)) {
            // Use custom site configuration
            $all_sites = array();
            $site_index = 0;
            foreach ($sites_config as $site) {
                $site_index++;
                // Use site index as unique identifier if no explicit ID
                $site_id = !empty($site['id']) ? $site['id'] : $site_index;
                $all_sites[] = array(
                    'id' => strval($site_id),
                    'name' => $site['name'],
                    'price' => !empty($site['price']) ? floatval($site['price']) : 0,
                    'description' => !empty($site['description']) ? $site['description'] : ''
                );
            }
        } else {
            // Use simple numbered sites based on capacity
            $capacity = get_post_meta($event_id, '_lt_capacity', true);
            if (empty($capacity)) {
                $capacity = 20;
            }
            $all_sites = array();
            for ($i = 1; $i <= intval($capacity); $i++) {
                $all_sites[] = array(
                    'id' => strval($i),
                    'name' => strval($i),
                    'price' => 0,
                    'description' => ''
                );
            }
        }
        
        // Get booked sites for this date
        $booked_sites_sql = "SELECT site_id FROM $bookings_table 
                             WHERE event_id = %d 
                             AND booking_date = %s 
                             AND status != 'cancelled' 
                             AND site_id IS NOT NULL 
                             AND site_id != ''";
        
        $booked_sites = $wpdb->get_col($wpdb->prepare($booked_sites_sql, $event_id, $date));
        
        // Normalize booked sites - trim and convert to array for comparison
        $booked_sites_normalized = array();
        foreach ($booked_sites as $booked_site) {
            $booked_sites_normalized[] = trim(strval($booked_site));
        }
        
        // Get default price
        $default_price = get_post_meta($event_id, '_lt_site_fee', true);
        if (empty($default_price)) {
            $default_price = 45;
        }
        
        // Filter out booked sites and format for return
        $available_sites = array();
        foreach ($all_sites as $site) {
            $site_id = trim(strval($site['id']));
            $site_name = trim($site['name']);
            
            // Check if this site is booked (compare by ID)
            if (!in_array($site_id, $booked_sites_normalized, true)) {
                // Use custom price if set, otherwise use default
                $final_price = !empty($site['price']) ? floatval($site['price']) : floatval($default_price);
                
                // Build display name
                $display_name = $site_name;
                if (!empty($site['description'])) {
                    $display_name .= ' - ' . $site['description'];
                }
                
                $available_sites[] = array(
                    'id' => $site_id,
                    'name' => $display_name,
                    'price' => $final_price
                );
            }
        }
        
        return $available_sites;
    }
    
    /**
     * Add to waitlist
     */
    public static function add_to_waitlist($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        // Get next position
        $position = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(position) FROM $table WHERE event_id = %d AND date = %s",
            $data['event_id'], $data['date']
        ));
        
        $data['position'] = $position ? $position + 1 : 1;
        
        $wpdb->insert($table, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get waitlist entries
     */
    public static function get_waitlist($event_id, $date = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_waitlist';
        
        if ($date) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE event_id = %d AND date = %s AND status = 'active' ORDER BY position ASC",
                $event_id, $date
            ));
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE event_id = %d AND status = 'active' ORDER BY date ASC, position ASC",
            $event_id
        ));
    }
}
