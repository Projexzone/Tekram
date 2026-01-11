<?php
/**
 * Plugin Name: Tekram
 * Plugin URI: https://gravityprojex.com
 * Description: Complete vendor and market management system with mobile check-in and vendor directory
 * Version: 4.0.0
 * Author: Gravity Projex
 * Author URI: https://gravityprojex.com
 * Text Domain: tekram
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LT_VERSION', '1.0.0');
define('LT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Tekram Class
 */
class Tekram {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core classes
        require_once LT_PLUGIN_DIR . 'includes/class-lt-database.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-vendor.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-event.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-booking.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-payment.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-notifications.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-site-map.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-waitlist.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-documents.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-extras.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-refunds.php';
        require_once LT_PLUGIN_DIR . 'includes/class-lt-reports.php';

        // Admin classes
        if (is_admin()) {
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin.php';
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin-dashboard.php';
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin-vendors.php';
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin-events.php';
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin-bookings.php';
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin-extras.php';
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin-reports.php';
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin-settings.php';
            require_once LT_PLUGIN_DIR . 'admin/class-lt-admin-checkin.php';
        }
        
        // Public classes
        require_once LT_PLUGIN_DIR . 'public/class-lt-public.php';
        require_once LT_PLUGIN_DIR . 'public/class-lt-shortcodes.php';

    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Register custom post types
        add_action('init', array($this, 'register_post_types'));
        
        // Add custom user roles
        add_action('init', array($this, 'add_custom_roles'));
        
        // AJAX handlers
        add_action('wp_ajax_lt_submit_application', array($this, 'handle_application'));
        add_action('wp_ajax_nopriv_lt_submit_application', array($this, 'handle_application'));
        add_action('wp_ajax_lt_check_availability', array($this, 'check_availability'));
        add_action('wp_ajax_nopriv_lt_check_availability', array($this, 'check_availability'));
        add_action('wp_ajax_lt_create_booking', array($this, 'create_booking'));
        add_action('wp_ajax_lt_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_lt_get_available_sites', array($this, 'get_available_sites'));
        add_action('wp_ajax_nopriv_lt_get_available_sites', array($this, 'get_available_sites'));
        add_action('wp_ajax_lt_get_extras', array($this, 'get_extras'));
        add_action('wp_ajax_nopriv_lt_get_extras', array($this, 'get_extras'));
        add_action('wp_ajax_lt_join_waitlist', array($this, 'join_waitlist'));
        add_action('wp_ajax_nopriv_lt_join_waitlist', array($this, 'join_waitlist'));
        add_action('wp_ajax_lt_quick_checkin', array($this, 'quick_checkin'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        LT_Database::create_tables();
        
        // Create new feature tables
        LT_Waitlist::create_table();
        LT_Documents::create_table();
        LT_Extras::create_tables();
        LT_Refunds::create_table();
        
        // Add custom roles
        $this->add_custom_roles();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('tekram', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize admin
        if (is_admin()) {
            LT_Admin::get_instance();
        }
        
        // Initialize public
        LT_Public::get_instance();
        
        // Initialize shortcodes
        LT_Shortcodes::init();
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // CSS
        wp_enqueue_style('lt-public', LT_PLUGIN_URL . 'assets/css/public.css', array(), LT_VERSION);
        
        // JavaScript
        wp_enqueue_script('lt-public', LT_PLUGIN_URL . 'assets/js/public.js', array('jquery'), LT_VERSION, true);
        
        // Localize script
        wp_localize_script('lt-public', 'ltAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lt_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'tekram'),
                'error' => __('An error occurred. Please try again.', 'tekram'),
                'success' => __('Success!', 'tekram')
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'tekram') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style('lt-admin', LT_PLUGIN_URL . 'assets/css/admin.css', array(), LT_VERSION);
        
        // JavaScript
        wp_enqueue_script('lt-admin', LT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), LT_VERSION, true);
        
        // Localize script
        wp_localize_script('lt-admin', 'ltAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lt_admin_nonce')
        ));
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register Vendor CPT
        register_post_type('lt_vendor', array(
            'labels' => array(
                'name' => __('Vendors', 'tekram'),
                'singular_name' => __('Vendor', 'tekram'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'thumbnail'),
            'has_archive' => false,
        ));
        
        // Register Event CPT
        register_post_type('lt_event', array(
            'labels' => array(
                'name' => __('Markets', 'tekram'),
                'singular_name' => __('Market', 'tekram'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'editor', 'thumbnail'),
            'has_archive' => false,
        ));
        
        // Register Site CPT
        register_post_type('lt_site', array(
            'labels' => array(
                'name' => __('Sites', 'tekram'),
                'singular_name' => __('Site', 'tekram'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title'),
            'has_archive' => false,
        ));
    }
    
    /**
     * Add custom user roles
     */
    public function add_custom_roles() {
        // Vendor role
        add_role('lt_vendor', __('Market Vendor', 'tekram'), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        ));
        
        // Market Coordinator role
        add_role('lt_coordinator', __('Market Coordinator', 'tekram'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'manage_lt_events' => true,
        ));
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'lt_currency' => 'USD',
            'lt_currency_symbol' => '$',
            'lt_date_format' => 'Y-m-d',
            'lt_time_format' => 'H:i',
            'lt_enable_stripe' => '0',
            'lt_enable_paypal' => '0',
            'lt_email_from_name' => get_bloginfo('name'),
            'lt_email_from_address' => get_bloginfo('admin_email'),
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Handle vendor application
     */
    public function handle_application() {
        check_ajax_referer('lt_nonce', 'nonce');
        
        $result = LT_Vendor::create_application($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Check availability
     */
    public function check_availability() {
        check_ajax_referer('lt_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        $date = sanitize_text_field($_POST['date']);
        
        $available = LT_Event::check_availability($event_id, $date);
        
        wp_send_json_success(array('available' => $available));
    }
    
    /**
     * Create booking
     */
    public function create_booking() {
        check_ajax_referer('lt_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to book.', 'tekram')));
        }
        
        $result = LT_Booking::create($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Process payment
     */
    public function process_payment() {
        check_ajax_referer('lt_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'tekram')));
        }
        
        $result = LT_Payment::process($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get available sites
     */
    public function get_available_sites() {
        check_ajax_referer('lt_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        $date = sanitize_text_field($_POST['date']);
        
        $sites = LT_Database::get_available_sites($event_id, $date);
        
        wp_send_json_success(array('sites' => $sites));
    }
    
    /**
     * Quick check-in (for iPad/mobile)
     */
    public function quick_checkin() {
        check_ajax_referer('lt_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'tekram')));
        }
        
        $booking_id = intval($_POST['booking_id']);
        $action = sanitize_text_field($_POST['checkin_action']);
        
        if ($action === 'confirm') {
            LT_Booking::update_status($booking_id, 'confirmed');
            wp_send_json_success(array('message' => __('Vendor checked in!', 'tekram')));
        } elseif ($action === 'record_payment') {
            $amount = floatval($_POST['amount']);
            $result = LT_Payment::record_payment($booking_id, array(
                'amount' => $amount,
                'payment_method' => 'cash',
                'status' => 'completed',
                'transaction_id' => 'cash_' . time()
            ));
            
            if ($result['success']) {
                LT_Booking::update_status($booking_id, 'confirmed');
                wp_send_json_success(array('message' => __('Payment recorded & vendor checked in!', 'tekram')));
            }
        }
        
        wp_send_json_error(array('message' => __('Invalid action.', 'tekram')));
    }
    
    /**
     * Get extras for event
     */
    public function get_extras() {
        check_ajax_referer('lt_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        $extras = LT_Extras::get_event_extras($event_id);
        
        wp_send_json_success(array('extras' => $extras));
    }
    
    /**
     * Join waitlist
     */
    public function join_waitlist() {
        check_ajax_referer('lt_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id']);
        $vendor_id = intval($_POST['vendor_id']);
        $date = sanitize_text_field($_POST['date']);
        
        $result = LT_Waitlist::add_to_waitlist($event_id, $vendor_id, $date);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}

/**
 * Initialize the plugin
 */
function lt_init() {
    return Tekram::get_instance();
}

// Start the plugin
lt_init();
