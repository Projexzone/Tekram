<?php
/**
 * Admin Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin_Dashboard {
    
    public static function render() {
        global $wpdb;
        
        // Get statistics
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        $payments_table = $wpdb->prefix . 'lt_payments';
        
        $total_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
        $pending_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table WHERE status = 'pending'");
        $confirmed_bookings = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table WHERE status = 'confirmed'");
        
        $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $payments_table WHERE status = 'completed'");
        $total_revenue = $total_revenue ? $total_revenue : 0;
        
        $total_vendors = wp_count_posts('lt_vendor');
        $pending_applications = $total_vendors->pending;
        
        $total_events = wp_count_posts('lt_event');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Tekram Dashboard', 'tekram'); ?></h1>
            
            <div class="smp-dashboard-stats">
                <div class="smp-stat-box">
                    <h3><?php echo number_format($total_bookings); ?></h3>
                    <p><?php _e('Total Bookings', 'tekram'); ?></p>
                </div>
                
                <div class="smp-stat-box">
                    <h3><?php echo number_format($pending_bookings); ?></h3>
                    <p><?php _e('Pending Bookings', 'tekram'); ?></p>
                </div>
                
                <div class="smp-stat-box">
                    <h3><?php echo number_format($confirmed_bookings); ?></h3>
                    <p><?php _e('Confirmed Bookings', 'tekram'); ?></p>
                </div>
                
                <div class="smp-stat-box">
                    <h3><?php echo get_option('lt_currency_symbol', '$') . number_format($total_revenue, 2); ?></h3>
                    <p><?php _e('Total Revenue', 'tekram'); ?></p>
                </div>
                
                <div class="smp-stat-box">
                    <h3><?php echo number_format($total_vendors->publish); ?></h3>
                    <p><?php _e('Active Vendors', 'tekram'); ?></p>
                </div>
                
                <div class="smp-stat-box smp-pending">
                    <h3><?php echo number_format($pending_applications); ?></h3>
                    <p><?php _e('Pending Applications', 'tekram'); ?></p>
                </div>
                
                <div class="smp-stat-box">
                    <h3><?php echo number_format($total_events->publish); ?></h3>
                    <p><?php _e('Active Events', 'tekram'); ?></p>
                </div>
            </div>
            
            <div class="smp-dashboard-content">
                <div class="smp-recent-bookings">
                    <h2><?php _e('Recent Bookings', 'tekram'); ?></h2>
                    <?php
                    $recent_bookings = $wpdb->get_results("
                        SELECT * FROM $bookings_table 
                        ORDER BY created_at DESC 
                        LIMIT 10
                    ");
                    
                    if ($recent_bookings) {
                        ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Reference', 'tekram'); ?></th>
                                    <th><?php _e('Vendor', 'tekram'); ?></th>
                                    <th><?php _e('Event', 'tekram'); ?></th>
                                    <th><?php _e('Date', 'tekram'); ?></th>
                                    <th><?php _e('Status', 'tekram'); ?></th>
                                    <th><?php _e('Amount', 'tekram'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking) {
                                    $vendor = get_post($booking->vendor_id);
                                    $event = get_post($booking->event_id);
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($booking->booking_reference); ?></td>
                                        <td><?php echo $vendor ? esc_html($vendor->post_title) : '-'; ?></td>
                                        <td><?php echo $event ? esc_html($event->post_title) : '-'; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></td>
                                        <td>
                                            <span class="smp-status smp-status-<?php echo esc_attr($booking->status); ?>">
                                                <?php echo ucfirst($booking->status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo get_option('lt_currency_symbol', '$') . number_format($booking->amount, 2); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php
                    } else {
                        echo '<p>' . __('No bookings yet.', 'tekram') . '</p>';
                    }
                    ?>
                </div>
                
                <?php if ($pending_applications > 0) { ?>
                <div class="smp-pending-applications">
                    <h2><?php _e('Pending Applications', 'tekram'); ?></h2>
                    <p><?php printf(__('You have %d pending vendor applications.', 'tekram'), $pending_applications); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=tekram-vendors&status=pending'); ?>" class="button button-primary">
                        <?php _e('Review Applications', 'tekram'); ?>
                    </a>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php
    }
}



