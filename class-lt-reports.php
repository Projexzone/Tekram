<?php
/**
 * Reports and Analytics Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Reports {
    
    /**
     * Get revenue report
     */
    public static function get_revenue_report($start_date = null, $end_date = null) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $sql = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(amount) as total_revenue,
                    SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as paid_revenue,
                    SUM(CASE WHEN payment_status = 'partial' THEN amount ELSE 0 END) as partial_revenue,
                    SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END) as unpaid_revenue,
                    SUM(paid_amount) as collected_revenue
                FROM $bookings_table 
                WHERE status != 'cancelled'";
        
        if ($start_date) {
            $sql .= $wpdb->prepare(" AND booking_date >= %s", $start_date);
        }
        if ($end_date) {
            $sql .= $wpdb->prepare(" AND booking_date <= %s", $end_date);
        }
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Get revenue by event
     */
    public static function get_revenue_by_event($start_date = null, $end_date = null) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $sql = "SELECT 
                    event_id,
                    COUNT(*) as bookings,
                    SUM(amount) as revenue,
                    SUM(paid_amount) as collected
                FROM $bookings_table 
                WHERE status != 'cancelled'";
        
        if ($start_date) {
            $sql .= $wpdb->prepare(" AND booking_date >= %s", $start_date);
        }
        if ($end_date) {
            $sql .= $wpdb->prepare(" AND booking_date <= %s", $end_date);
        }
        
        $sql .= " GROUP BY event_id ORDER BY revenue DESC";
        
        $results = $wpdb->get_results($sql);
        
        // Add event names
        foreach ($results as &$result) {
            $event = get_post($result->event_id);
            $result->event_name = $event ? $event->post_title : 'Unknown';
        }
        
        return $results;
    }
    
    /**
     * Get booking trends
     */
    public static function get_booking_trends($days = 30) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as bookings,
                SUM(amount) as revenue
             FROM $bookings_table 
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $days
        ));
        
        return $results;
    }
    
    /**
     * Get vendor retention rate
     */
    public static function get_vendor_retention() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $sql = "SELECT 
                    vendor_id,
                    COUNT(DISTINCT event_id) as events_attended,
                    COUNT(*) as total_bookings,
                    MIN(booking_date) as first_booking,
                    MAX(booking_date) as last_booking,
                    SUM(amount) as total_spent
                FROM $bookings_table 
                WHERE status != 'cancelled'
                GROUP BY vendor_id
                ORDER BY total_bookings DESC";
        
        $results = $wpdb->get_results($sql);
        
        // Add vendor names
        foreach ($results as &$result) {
            $vendor = get_post($result->vendor_id);
            $result->vendor_name = $vendor ? $vendor->post_title : 'Unknown';
        }
        
        return $results;
    }
    
    /**
     * Get top vendors
     */
    public static function get_top_vendors($limit = 10) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vendor_id,
                COUNT(*) as bookings,
                SUM(amount) as revenue
             FROM $bookings_table 
             WHERE status != 'cancelled'
             GROUP BY vendor_id
             ORDER BY bookings DESC
             LIMIT %d",
            $limit
        ));
        
        // Add vendor names
        foreach ($results as &$result) {
            $vendor = get_post($result->vendor_id);
            $vendor_data = LT_Vendor::get_data($result->vendor_id);
            $result->vendor_name = $vendor ? $vendor->post_title : 'Unknown';
            $result->business_name = $vendor_data['business_name'];
        }
        
        return $results;
    }
    
    /**
     * Get popular sites
     */
    public static function get_popular_sites($event_id = null) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $sql = "SELECT 
                    site_id,
                    COUNT(*) as bookings
                FROM $bookings_table 
                WHERE status != 'cancelled' 
                AND site_id IS NOT NULL 
                AND site_id != ''";
        
        if ($event_id) {
            $sql .= $wpdb->prepare(" AND event_id = %d", $event_id);
        }
        
        $sql .= " GROUP BY site_id ORDER BY bookings DESC LIMIT 10";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get payment statistics
     */
    public static function get_payment_stats() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        return $wpdb->get_row(
            "SELECT 
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_count,
                COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_count,
                ROUND(COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) * 100.0 / COUNT(*), 2) as paid_percentage,
                ROUND(COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) * 100.0 / COUNT(*), 2) as partial_percentage,
                ROUND(COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) * 100.0 / COUNT(*), 2) as unpaid_percentage
             FROM $bookings_table 
             WHERE status != 'cancelled'"
        );
    }
    
    /**
     * Get cancellation rate
     */
    public static function get_cancellation_stats() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        return $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
                ROUND(COUNT(CASE WHEN status = 'cancelled' THEN 1 END) * 100.0 / COUNT(*), 2) as cancellation_rate
             FROM $bookings_table"
        );
    }
    
    /**
     * Get occupancy rate
     */
    public static function get_occupancy_rate($event_id, $date) {
        $event_data = LT_Event::get_data($event_id);
        $capacity = intval($event_data['capacity']);
        
        if ($capacity <= 0) {
            return 0;
        }
        
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        $booked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE event_id = %d 
             AND booking_date = %s 
             AND status != 'cancelled'",
            $event_id, $date
        ));
        
        return round(($booked / $capacity) * 100, 2);
    }
    
    /**
     * Get average booking value
     */
    public static function get_average_booking_value() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        return $wpdb->get_var(
            "SELECT ROUND(AVG(amount), 2) 
             FROM $bookings_table 
             WHERE status != 'cancelled'"
        );
    }
    
    /**
     * Get dashboard summary
     */
    public static function get_dashboard_summary() {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        $vendors_table = $wpdb->prefix . 'posts';
        
        $total_vendors = wp_count_posts('lt_vendor');
        
        $bookings_stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_bookings,
                SUM(amount) as total_revenue,
                SUM(paid_amount) as collected_revenue
             FROM $bookings_table 
             WHERE status != 'cancelled'"
        );
        
        $upcoming_bookings = $wpdb->get_var(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE booking_date >= CURDATE() 
             AND status != 'cancelled'"
        );
        
        $pending_payments = $wpdb->get_var(
            "SELECT SUM(amount - paid_amount) 
             FROM $bookings_table 
             WHERE payment_status IN ('unpaid', 'partial') 
             AND status != 'cancelled'"
        );
        
        return array(
            'total_vendors' => $total_vendors->publish + $total_vendors->pending,
            'pending_vendors' => $total_vendors->pending,
            'total_bookings' => $bookings_stats->total_bookings,
            'total_revenue' => $bookings_stats->total_revenue,
            'collected_revenue' => $bookings_stats->collected_revenue,
            'upcoming_bookings' => $upcoming_bookings,
            'pending_payments' => $pending_payments,
            'average_booking_value' => self::get_average_booking_value()
        );
    }
    
    /**
     * Export report to CSV
     */
    public static function export_to_csv($report_type, $params = array()) {
        $filename = 'report_' . $report_type . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($report_type) {
            case 'revenue':
                $data = self::get_revenue_by_event($params['start_date'], $params['end_date']);
                fputcsv($output, array('Event', 'Bookings', 'Revenue', 'Collected'));
                foreach ($data as $row) {
                    fputcsv($output, array(
                        $row->event_name,
                        $row->bookings,
                        $row->revenue,
                        $row->collected
                    ));
                }
                break;
                
            case 'vendors':
                $data = self::get_top_vendors(100);
                fputcsv($output, array('Vendor', 'Bookings', 'Total Spent'));
                foreach ($data as $row) {
                    fputcsv($output, array(
                        $row->vendor_name,
                        $row->bookings,
                        $row->revenue
                    ));
                }
                break;
        }
        
        fclose($output);
        exit;
    }
}
