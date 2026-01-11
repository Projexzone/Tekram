<?php
/**
 * Admin Reports Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin_Reports {
    
    public static function render() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        
        ?>
        <div class="wrap">
            <h1><?php _e('Reports & Analytics', 'tekram'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=tekram-reports&tab=overview" class="nav-tab <?php echo $tab === 'overview' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Overview', 'tekram'); ?>
                </a>
                <a href="?page=tekram-reports&tab=revenue" class="nav-tab <?php echo $tab === 'revenue' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Revenue', 'tekram'); ?>
                </a>
                <a href="?page=tekram-reports&tab=vendors" class="nav-tab <?php echo $tab === 'vendors' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Vendors', 'tekram'); ?>
                </a>
                <a href="?page=tekram-reports&tab=bookings" class="nav-tab <?php echo $tab === 'bookings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Bookings', 'tekram'); ?>
                </a>
            </h2>
            
            <?php
            switch ($tab) {
                case 'revenue':
                    self::render_revenue_tab();
                    break;
                case 'vendors':
                    self::render_vendors_tab();
                    break;
                case 'bookings':
                    self::render_bookings_tab();
                    break;
                default:
                    self::render_overview_tab();
                    break;
            }
            ?>
        </div>
        <?php
    }
    
    private static function render_overview_tab() {
        $summary = LT_Reports::get_dashboard_summary();
        $payment_stats = LT_Reports::get_payment_stats();
        
        ?>
        <div class="lt-reports-dashboard">
            <h2><?php _e('Dashboard Overview', 'tekram'); ?></h2>
            
            <div class="lt-stats-grid">
                <div class="lt-stat-box">
                    <h3><?php _e('Total Vendors', 'tekram'); ?></h3>
                    <p class="lt-stat-number"><?php echo number_format($summary['total_vendors']); ?></p>
                    <small><?php echo $summary['pending_vendors']; ?> <?php _e('pending', 'tekram'); ?></small>
                </div>
                
                <div class="lt-stat-box">
                    <h3><?php _e('Total Bookings', 'tekram'); ?></h3>
                    <p class="lt-stat-number"><?php echo number_format($summary['total_bookings']); ?></p>
                    <small><?php echo $summary['upcoming_bookings']; ?> <?php _e('upcoming', 'tekram'); ?></small>
                </div>
                
                <div class="lt-stat-box">
                    <h3><?php _e('Total Revenue', 'tekram'); ?></h3>
                    <p class="lt-stat-number"><?php echo get_option('lt_currency_symbol', '$') . number_format($summary['total_revenue'], 2); ?></p>
                    <small><?php echo get_option('lt_currency_symbol', '$') . number_format($summary['collected_revenue'], 2); ?> <?php _e('collected', 'tekram'); ?></small>
                </div>
                
                <div class="lt-stat-box">
                    <h3><?php _e('Average Booking', 'tekram'); ?></h3>
                    <p class="lt-stat-number"><?php echo get_option('lt_currency_symbol', '$') . number_format($summary['average_booking_value'], 2); ?></p>
                </div>
            </div>
            
            <h3><?php _e('Payment Statistics', 'tekram'); ?></h3>
            <div class="lt-payment-stats">
                <div class="lt-stat-bar">
                    <div class="lt-stat-label"><?php _e('Paid', 'tekram'); ?>: <?php echo $payment_stats->paid_percentage; ?>%</div>
                    <div class="lt-stat-progress">
                        <div class="lt-stat-progress-bar lt-paid" style="width: <?php echo $payment_stats->paid_percentage; ?>%"></div>
                    </div>
                </div>
                <div class="lt-stat-bar">
                    <div class="lt-stat-label"><?php _e('Partial', 'tekram'); ?>: <?php echo $payment_stats->partial_percentage; ?>%</div>
                    <div class="lt-stat-progress">
                        <div class="lt-stat-progress-bar lt-partial" style="width: <?php echo $payment_stats->partial_percentage; ?>%"></div>
                    </div>
                </div>
                <div class="lt-stat-bar">
                    <div class="lt-stat-label"><?php _e('Unpaid', 'tekram'); ?>: <?php echo $payment_stats->unpaid_percentage; ?>%</div>
                    <div class="lt-stat-progress">
                        <div class="lt-stat-progress-bar lt-unpaid" style="width: <?php echo $payment_stats->unpaid_percentage; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .lt-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .lt-stat-box {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .lt-stat-box h3 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #666;
        }
        .lt-stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
            color: #0073aa;
        }
        .lt-stat-bar {
            margin: 15px 0;
        }
        .lt-stat-progress {
            height: 30px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .lt-stat-progress-bar {
            height: 100%;
            transition: width 0.3s;
        }
        .lt-paid { background: #46b450; }
        .lt-partial { background: #ffb900; }
        .lt-unpaid { background: #dc3232; }
        </style>
        <?php
    }
    
    private static function render_revenue_tab() {
        $revenue = LT_Reports::get_revenue_by_event();
        
        ?>
        <h2><?php _e('Revenue by Event', 'tekram'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Event', 'tekram'); ?></th>
                    <th><?php _e('Bookings', 'tekram'); ?></th>
                    <th><?php _e('Revenue', 'tekram'); ?></th>
                    <th><?php _e('Collected', 'tekram'); ?></th>
                    <th><?php _e('Collection Rate', 'tekram'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue as $row) { 
                    $rate = $row->revenue > 0 ? round(($row->collected / $row->revenue) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?php echo esc_html($row->event_name); ?></td>
                        <td><?php echo number_format($row->bookings); ?></td>
                        <td><?php echo get_option('lt_currency_symbol', '$') . number_format($row->revenue, 2); ?></td>
                        <td><?php echo get_option('lt_currency_symbol', '$') . number_format($row->collected, 2); ?></td>
                        <td><?php echo $rate; ?>%</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
    }
    
    private static function render_vendors_tab() {
        $vendors = LT_Reports::get_top_vendors(20);
        
        ?>
        <h2><?php _e('Top Vendors', 'tekram'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Rank', 'tekram'); ?></th>
                    <th><?php _e('Vendor', 'tekram'); ?></th>
                    <th><?php _e('Business Name', 'tekram'); ?></th>
                    <th><?php _e('Bookings', 'tekram'); ?></th>
                    <th><?php _e('Total Revenue', 'tekram'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($vendors as $vendor) { ?>
                    <tr>
                        <td><?php echo $rank++; ?></td>
                        <td><?php echo esc_html($vendor->vendor_name); ?></td>
                        <td><?php echo esc_html($vendor->business_name); ?></td>
                        <td><?php echo number_format($vendor->bookings); ?></td>
                        <td><?php echo get_option('lt_currency_symbol', '$') . number_format($vendor->revenue, 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
    }
    
    private static function render_bookings_tab() {
        $trends = LT_Reports::get_booking_trends(30);
        
        ?>
        <h2><?php _e('Booking Trends (Last 30 Days)', 'tekram'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'tekram'); ?></th>
                    <th><?php _e('Bookings', 'tekram'); ?></th>
                    <th><?php _e('Revenue', 'tekram'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trends as $day) { ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($day->date)); ?></td>
                        <td><?php echo number_format($day->bookings); ?></td>
                        <td><?php echo get_option('lt_currency_symbol', '$') . number_format($day->revenue, 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php
    }
}



