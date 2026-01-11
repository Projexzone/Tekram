<?php
/**
 * Admin Class
 * Main admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Tekram', 'tekram'),
            __('Tekram', 'tekram'),
            'manage_options',
            'tekram',
            array('LT_Admin_Dashboard', 'render'),
            'dashicons-store',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'tekram',
            __('Dashboard', 'tekram'),
            __('Dashboard', 'tekram'),
            'manage_options',
            'tekram',
            array('LT_Admin_Dashboard', 'render')
        );
        
        // Vendors
        add_submenu_page(
            'tekram',
            __('Vendors', 'tekram'),
            __('Vendors', 'tekram'),
            'manage_options',
            'tekram-vendors',
            array('LT_Admin_Vendors', 'render')
        );
        
        // Events
        add_submenu_page(
            'tekram',
            __('Events', 'tekram'),
            __('Events', 'tekram'),
            'manage_options',
            'tekram-events',
            array('LT_Admin_Events', 'render')
        );
        
        // Bookings
        add_submenu_page(
            'tekram',
            __('Bookings', 'tekram'),
            __('Bookings', 'tekram'),
            'manage_options',
            'tekram-bookings',
            array('LT_Admin_Bookings', 'render')
        );
        
        // Extras & Add-ons
        add_submenu_page(
            'tekram',
            __('Extras & Add-ons', 'tekram'),
            __('Extras & Add-ons', 'tekram'),
            'manage_options',
            'tekram-extras',
            array('LT_Admin_Extras', 'render')
        );
        
        // Reports & Analytics
        add_submenu_page(
            'tekram',
            __('Reports & Analytics', 'tekram'),
            __('📊 Reports', 'tekram'),
            'manage_options',
            'tekram-reports',
            array('LT_Admin_Reports', 'render')
        );
        
        // Check-In (iPad View)
        add_submenu_page(
            'tekram',
            __('Check-In', 'tekram'),
            __('📱 Check-In', 'tekram'),
            'manage_options',
            'tekram-checkin',
            array('LT_Admin_Checkin', 'render')
        );
        
        // Settings
        add_submenu_page(
            'tekram',
            __('Settings', 'tekram'),
            __('Settings', 'tekram'),
            'manage_options',
            'tekram-settings',
            array('LT_Admin_Settings', 'render')
        );
    }
    
    public function register_settings() {
        // General settings
        register_setting('lt_general_settings', 'lt_currency');
        register_setting('lt_general_settings', 'lt_currency_symbol');
        register_setting('lt_general_settings', 'lt_date_format');
        register_setting('lt_general_settings', 'lt_time_format');
        
        // Payment settings
        register_setting('lt_payment_settings', 'lt_enable_stripe');
        register_setting('lt_payment_settings', 'lt_stripe_public_key');
        register_setting('lt_payment_settings', 'lt_stripe_secret_key');
        register_setting('lt_payment_settings', 'lt_enable_paypal');
        register_setting('lt_payment_settings', 'lt_paypal_client_id');
        register_setting('lt_payment_settings', 'lt_paypal_secret');
        
        // Email settings
        register_setting('lt_email_settings', 'lt_email_from_name');
        register_setting('lt_email_settings', 'lt_email_from_address');
    }
}



