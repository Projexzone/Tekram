<?php
/**
 * Admin Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin_Settings {
    
    public static function render() {
        if (isset($_POST['submit']) && check_admin_referer('lt_settings_nonce')) {
            self::save_settings();
        }
        
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        ?>
        <div class="wrap">
            <h1><?php _e('Tekram Settings', 'tekram'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=tekram-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'tekram'); ?>
                </a>
                <a href="?page=tekram-settings&tab=payment" class="nav-tab <?php echo $active_tab === 'payment' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Payments', 'tekram'); ?>
                </a>
                <a href="?page=tekram-settings&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Email', 'tekram'); ?>
                </a>
            </h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('lt_settings_nonce'); ?>
                
                <?php if ($active_tab === 'general') { ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="lt_currency"><?php _e('Currency', 'tekram'); ?></label>
                            </th>
                            <td>
                                <select name="lt_currency" id="lt_currency">
                                    <option value="USD" <?php selected(get_option('lt_currency'), 'USD'); ?>>USD - US Dollar</option>
                                    <option value="AUD" <?php selected(get_option('lt_currency'), 'AUD'); ?>>AUD - Australian Dollar</option>
                                    <option value="EUR" <?php selected(get_option('lt_currency'), 'EUR'); ?>>EUR - Euro</option>
                                    <option value="GBP" <?php selected(get_option('lt_currency'), 'GBP'); ?>>GBP - British Pound</option>
                                    <option value="CAD" <?php selected(get_option('lt_currency'), 'CAD'); ?>>CAD - Canadian Dollar</option>
                                    <option value="NZD" <?php selected(get_option('lt_currency'), 'NZD'); ?>>NZD - New Zealand Dollar</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lt_currency_symbol"><?php _e('Currency Symbol', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="lt_currency_symbol" id="lt_currency_symbol" value="<?php echo esc_attr(get_option('lt_currency_symbol', '$')); ?>" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lt_date_format"><?php _e('Date Format', 'tekram'); ?></label>
                            </th>
                            <td>
                                <select name="lt_date_format" id="lt_date_format">
                                    <option value="Y-m-d" <?php selected(get_option('lt_date_format'), 'Y-m-d'); ?>>YYYY-MM-DD</option>
                                    <option value="m/d/Y" <?php selected(get_option('lt_date_format'), 'm/d/Y'); ?>>MM/DD/YYYY</option>
                                    <option value="d/m/Y" <?php selected(get_option('lt_date_format'), 'd/m/Y'); ?>>DD/MM/YYYY</option>
                                    <option value="F j, Y" <?php selected(get_option('lt_date_format'), 'F j, Y'); ?>>Month D, YYYY</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lt_time_format"><?php _e('Time Format', 'tekram'); ?></label>
                            </th>
                            <td>
                                <select name="lt_time_format" id="lt_time_format">
                                    <option value="H:i" <?php selected(get_option('lt_time_format'), 'H:i'); ?>>24-hour (14:30)</option>
                                    <option value="g:i A" <?php selected(get_option('lt_time_format'), 'g:i A'); ?>>12-hour (2:30 PM)</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                <?php } ?>
                
                <?php if ($active_tab === 'payment') { ?>
                    <h3><?php _e('Stripe Settings', 'tekram'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="lt_enable_stripe"><?php _e('Enable Stripe', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" name="lt_enable_stripe" id="lt_enable_stripe" value="1" <?php checked(get_option('lt_enable_stripe'), '1'); ?>>
                                <p class="description"><?php _e('Accept credit card payments via Stripe', 'tekram'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lt_stripe_public_key"><?php _e('Stripe Publishable Key', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="lt_stripe_public_key" id="lt_stripe_public_key" value="<?php echo esc_attr(get_option('lt_stripe_public_key')); ?>" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lt_stripe_secret_key"><?php _e('Stripe Secret Key', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="password" name="lt_stripe_secret_key" id="lt_stripe_secret_key" value="<?php echo esc_attr(get_option('lt_stripe_secret_key')); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('PayPal Settings', 'tekram'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="lt_enable_paypal"><?php _e('Enable PayPal', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" name="lt_enable_paypal" id="lt_enable_paypal" value="1" <?php checked(get_option('lt_enable_paypal'), '1'); ?>>
                                <p class="description"><?php _e('Accept payments via PayPal', 'tekram'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lt_paypal_client_id"><?php _e('PayPal Client ID', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="lt_paypal_client_id" id="lt_paypal_client_id" value="<?php echo esc_attr(get_option('lt_paypal_client_id')); ?>" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lt_paypal_secret"><?php _e('PayPal Secret', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="password" name="lt_paypal_secret" id="lt_paypal_secret" value="<?php echo esc_attr(get_option('lt_paypal_secret')); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                <?php } ?>
                
                <?php if ($active_tab === 'email') { ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="lt_email_from_name"><?php _e('From Name', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="lt_email_from_name" id="lt_email_from_name" value="<?php echo esc_attr(get_option('lt_email_from_name', get_bloginfo('name'))); ?>" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="lt_email_from_address"><?php _e('From Email', 'tekram'); ?></label>
                            </th>
                            <td>
                                <input type="email" name="lt_email_from_address" id="lt_email_from_address" value="<?php echo esc_attr(get_option('lt_email_from_address', get_option('admin_email'))); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                <?php } ?>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'tekram'); ?>">
                </p>
            </form>
        </div>
        <?php
    }
    
    private static function save_settings() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        if ($tab === 'general') {
            update_option('lt_currency', sanitize_text_field($_POST['lt_currency']));
            update_option('lt_currency_symbol', sanitize_text_field($_POST['lt_currency_symbol']));
            update_option('lt_date_format', sanitize_text_field($_POST['lt_date_format']));
            update_option('lt_time_format', sanitize_text_field($_POST['lt_time_format']));
        } elseif ($tab === 'payment') {
            update_option('lt_enable_stripe', isset($_POST['lt_enable_stripe']) ? '1' : '0');
            update_option('lt_stripe_public_key', sanitize_text_field($_POST['lt_stripe_public_key']));
            update_option('lt_stripe_secret_key', sanitize_text_field($_POST['lt_stripe_secret_key']));
            update_option('lt_enable_paypal', isset($_POST['lt_enable_paypal']) ? '1' : '0');
            update_option('lt_paypal_client_id', sanitize_text_field($_POST['lt_paypal_client_id']));
            update_option('lt_paypal_secret', sanitize_text_field($_POST['lt_paypal_secret']));
        } elseif ($tab === 'email') {
            update_option('lt_email_from_name', sanitize_text_field($_POST['lt_email_from_name']));
            update_option('lt_email_from_address', sanitize_email($_POST['lt_email_from_address']));
        }
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'tekram') . '</p></div>';
    }
}



