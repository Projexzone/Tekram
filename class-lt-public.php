<?php
/**
 * Public Class
 * Handles public-facing functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Public {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add hooks if needed
    }
}
