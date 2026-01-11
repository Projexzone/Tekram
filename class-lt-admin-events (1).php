<?php
/**
 * Admin Events
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin_Events {
    
    public static function render() {
        // Handle form submissions
        if (isset($_POST['lt_save_event']) && check_admin_referer('lt_event_nonce')) {
            self::save_event();
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            self::delete_event(intval($_GET['id']));
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id'])) {
            self::duplicate_event(intval($_GET['id']));
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'edit' && $event_id) {
            self::render_edit_form($event_id);
        } elseif ($action === 'add') {
            self::render_add_form();
        } else {
            self::render_list();
        }
    }
    
    private static function render_list() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Markets', 'tekram'); ?></h1>
            <a href="?page=tekram-events&action=add" class="page-title-action">
                <?php _e('Add New Market', 'tekram'); ?>
            </a>
            
            <br><br>
            
            <?php
            $events = LT_Event::get_all();
            
            if ($events) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Market Name', 'tekram'); ?></th>
                            <th><?php _e('Location', 'tekram'); ?></th>
                            <th><?php _e('Start Date', 'tekram'); ?></th>
                            <th><?php _e('Frequency', 'tekram'); ?></th>
                            <th><?php _e('Capacity', 'tekram'); ?></th>
                            <th><?php _e('Site Fee', 'tekram'); ?></th>
                            <th><?php _e('Actions', 'tekram'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event) {
                            $data = LT_Event::get_data($event->ID);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($event->post_title); ?></strong></td>
                                <td><?php echo esc_html($data['location']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($data['start_date'])); ?></td>
                                <td><?php echo ucfirst($data['frequency']); ?></td>
                                <td><?php echo $data['capacity']; ?></td>
                                <td><?php echo get_option('lt_currency_symbol', '$') . number_format($data['site_fee'], 2); ?></td>
                                <td>
                                    <a href="?page=tekram-events&action=edit&id=<?php echo $event->ID; ?>" class="button button-small">
                                        <?php _e('Edit', 'tekram'); ?>
                                    </a>
                                    <a href="?page=tekram-bookings&event_id=<?php echo $event->ID; ?>" class="button button-small">
                                        <?php _e('Bookings', 'tekram'); ?>
                                    </a>
                                    <a href="?page=tekram-events&action=duplicate&id=<?php echo $event->ID; ?>" class="button button-small" style="color: #0073aa;" title="<?php _e('Duplicate this market with all settings', 'tekram'); ?>">
                                        <?php _e('Duplicate', 'tekram'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>' . __('No markets found. Create your first market to get started!', 'tekram') . '</p>';
            }
            ?>
        </div>
        <?php
    }
    
    private static function render_add_form() {
        ?>
        <div class="wrap">
            <h1><?php _e('Add New Market', 'tekram'); ?></h1>
            <a href="?page=tekram-events" class="page-title-action"><?php _e('← Back to List', 'tekram'); ?></a>
            
            <form method="post" action="">
                <?php wp_nonce_field('lt_event_nonce'); ?>
                <input type="hidden" name="lt_save_event" value="1">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="title"><?php _e('Market Name', 'tekram'); ?> *</label></th>
                        <td><input type="text" name="title" id="title" class="regular-text" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="description"><?php _e('Description', 'tekram'); ?></label></th>
                        <td><textarea name="description" id="description" rows="5" class="large-text"></textarea></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="location"><?php _e('Location', 'tekram'); ?> *</label></th>
                        <td><input type="text" name="location" id="location" class="regular-text" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="start_date"><?php _e('Start Date', 'tekram'); ?> *</label></th>
                        <td><input type="date" name="start_date" id="start_date" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="end_date"><?php _e('End Date', 'tekram'); ?></label></th>
                        <td><input type="date" name="end_date" id="end_date"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="start_time"><?php _e('Start Time', 'tekram'); ?></label></th>
                        <td><input type="time" name="start_time" id="start_time" value="08:00"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="end_time"><?php _e('End Time', 'tekram'); ?></label></th>
                        <td><input type="time" name="end_time" id="end_time" value="14:00"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="frequency"><?php _e('Frequency', 'tekram'); ?></label></th>
                        <td>
                            <select name="frequency" id="frequency">
                                <option value="once"><?php _e('One-time', 'tekram'); ?></option>
                                <option value="weekly"><?php _e('Weekly', 'tekram'); ?></option>
                                <option value="fortnightly"><?php _e('Fortnightly', 'tekram'); ?></option>
                                <option value="monthly"><?php _e('Monthly', 'tekram'); ?></option>
                                <option value="quarterly"><?php _e('Quarterly', 'tekram'); ?></option>
                                <option value="annually"><?php _e('Annually', 'tekram'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="capacity"><?php _e('Capacity (# of stalls)', 'tekram'); ?> *</label></th>
                        <td><input type="number" name="capacity" id="capacity" min="1" max="20" value="20" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="sites"><?php _e('Site Numbers', 'tekram'); ?></label></th>
                        <td>
                            <input type="text" name="sites" id="sites" class="large-text" value="1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20">
                            <p class="description"><?php _e('Comma-separated site numbers or names (e.g., "1,2,3,4,5" or "A1,A2,B1,B2"). Max 20 sites.', 'tekram'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="site_fee"><?php _e('Site Fee', 'tekram'); ?> *</label></th>
                        <td><input type="number" name="site_fee" id="site_fee" step="0.01" min="0" value="45.00" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="booking_open_date"><?php _e('Booking Opens', 'tekram'); ?></label></th>
                        <td><input type="date" name="booking_open_date" id="booking_open_date"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="booking_close_date"><?php _e('Booking Closes', 'tekram'); ?></label></th>
                        <td><input type="date" name="booking_close_date" id="booking_close_date"></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Create Market', 'tekram'); ?>">
                    <a href="?page=tekram-events" class="button"><?php _e('Cancel', 'tekram'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private static function render_edit_form($event_id) {
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'lt_event') {
            echo '<div class="wrap"><p>' . __('Event not found.', 'tekram') . '</p></div>';
            return;
        }
        
        $data = LT_Event::get_data($event_id);
        ?>
        <div class="wrap">
            <h1><?php _e('Edit Market', 'tekram'); ?></h1>
            <a href="?page=tekram-events" class="page-title-action"><?php _e('← Back to List', 'tekram'); ?></a>
            
            <form method="post" action="">
                <?php wp_nonce_field('lt_event_nonce'); ?>
                <input type="hidden" name="lt_save_event" value="1">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="title"><?php _e('Market Name', 'tekram'); ?> *</label></th>
                        <td><input type="text" name="title" id="title" class="regular-text" value="<?php echo esc_attr($event->post_title); ?>" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="description"><?php _e('Description', 'tekram'); ?></label></th>
                        <td><textarea name="description" id="description" rows="5" class="large-text"><?php echo esc_textarea($event->post_content); ?></textarea></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="location"><?php _e('Location', 'tekram'); ?> *</label></th>
                        <td><input type="text" name="location" id="location" class="regular-text" value="<?php echo esc_attr($data['location']); ?>" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="start_date"><?php _e('Start Date', 'tekram'); ?> *</label></th>
                        <td><input type="date" name="start_date" id="start_date" value="<?php echo esc_attr($data['start_date']); ?>" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="end_date"><?php _e('End Date', 'tekram'); ?></label></th>
                        <td><input type="date" name="end_date" id="end_date" value="<?php echo esc_attr($data['end_date']); ?>"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="start_time"><?php _e('Start Time', 'tekram'); ?></label></th>
                        <td><input type="time" name="start_time" id="start_time" value="<?php echo esc_attr($data['start_time']); ?>"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="end_time"><?php _e('End Time', 'tekram'); ?></label></th>
                        <td><input type="time" name="end_time" id="end_time" value="<?php echo esc_attr($data['end_time']); ?>"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="frequency"><?php _e('Frequency', 'tekram'); ?></label></th>
                        <td>
                            <select name="frequency" id="frequency">
                                <option value="once" <?php selected($data['frequency'], 'once'); ?>><?php _e('One-time', 'tekram'); ?></option>
                                <option value="weekly" <?php selected($data['frequency'], 'weekly'); ?>><?php _e('Weekly', 'tekram'); ?></option>
                                <option value="fortnightly" <?php selected($data['frequency'], 'fortnightly'); ?>><?php _e('Fortnightly', 'tekram'); ?></option>
                                <option value="monthly" <?php selected($data['frequency'], 'monthly'); ?>><?php _e('Monthly', 'tekram'); ?></option>
                                <option value="quarterly" <?php selected($data['frequency'], 'quarterly'); ?>><?php _e('Quarterly', 'tekram'); ?></option>
                                <option value="annually" <?php selected($data['annually'], 'annually'); ?>><?php _e('Annually', 'tekram'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="capacity"><?php _e('Capacity (# of stalls)', 'tekram'); ?> *</label></th>
                        <td>
                            <input type="number" name="capacity" id="capacity" min="1" max="50" value="<?php echo esc_attr($data['capacity']); ?>" required>
                            <p class="description"><?php _e('Total number of stalls/sites available', 'tekram'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="sites_config"><?php _e('Site Configuration (Optional)', 'tekram'); ?></label></th>
                        <td>
                            <?php
                            $sites_config = get_post_meta($event_id, '_lt_sites_config', true);
                            if (empty($sites_config) || !is_array($sites_config)) {
                                $sites_config = array();
                            }
                            ?>
                            <div id="sites-config-container">
                                <p class="description" style="margin-bottom: 10px;">
                                    <?php _e('Leave empty to use simple numbered sites (1, 2, 3...). Or configure custom sites below with different prices and descriptions.', 'tekram'); ?>
                                </p>
                                
                                <div id="sites-list">
                                    <?php if (!empty($sites_config)) {
                                        foreach ($sites_config as $index => $site) { ?>
                                            <div class="site-config-row" style="margin-bottom: 10px; padding: 10px; background: #f5f5f5; border-left: 3px solid #0073aa;">
                                                <input type="text" name="site_name[]" placeholder="Site Name (e.g., A1, Corner Site)" value="<?php echo esc_attr($site['name']); ?>" style="width: 200px;">
                                                <input type="number" name="site_price[]" placeholder="Price" step="0.01" value="<?php echo esc_attr($site['price']); ?>" style="width: 100px;">
                                                <input type="text" name="site_description[]" placeholder="Description (optional)" value="<?php echo esc_attr($site['description']); ?>" style="width: 300px;">
                                                <button type="button" class="button remove-site">Remove</button>
                                            </div>
                                        <?php }
                                    } ?>
                                </div>
                                
                                <button type="button" class="button" id="add-site"><?php _e('+ Add Custom Site', 'tekram'); ?></button>
                            </div>
                            
                            <script>
                            jQuery(document).ready(function($) {
                                $('#add-site').on('click', function() {
                                    var row = $('<div class="site-config-row" style="margin-bottom: 10px; padding: 10px; background: #f5f5f5; border-left: 3px solid #0073aa;">' +
                                        '<input type="text" name="site_name[]" placeholder="Site Name (e.g., A1, Corner Site)" style="width: 200px;">' +
                                        '<input type="number" name="site_price[]" placeholder="Price" step="0.01" style="width: 100px;">' +
                                        '<input type="text" name="site_description[]" placeholder="Description (optional)" style="width: 300px;">' +
                                        '<button type="button" class="button remove-site">Remove</button>' +
                                        '</div>');
                                    $('#sites-list').append(row);
                                });
                                
                                $(document).on('click', '.remove-site', function() {
                                    $(this).closest('.site-config-row').remove();
                                });
                            });
                            </script>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="site_fee"><?php _e('Default Site Fee', 'tekram'); ?> *</label></th>
                        <td>
                            <input type="number" name="site_fee" id="site_fee" step="0.01" min="0" value="<?php echo esc_attr($data['site_fee']); ?>" required>
                            <p class="description"><?php _e('Used for simple numbered sites. Custom sites above can have different prices.', 'tekram'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="booking_open_date"><?php _e('Booking Opens', 'tekram'); ?></label></th>
                        <td><input type="date" name="booking_open_date" id="booking_open_date" value="<?php echo esc_attr($data['booking_open_date']); ?>"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="booking_close_date"><?php _e('Booking Closes', 'tekram'); ?></label></th>
                        <td><input type="date" name="booking_close_date" id="booking_close_date" value="<?php echo esc_attr($data['booking_close_date']); ?>"></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Update Market', 'tekram'); ?>">
                    <a href="?page=tekram-events" class="button"><?php _e('Cancel', 'tekram'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
    
    private static function save_event() {
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        // Process site configuration
        $sites_config = array();
        if (isset($_POST['site_name']) && is_array($_POST['site_name'])) {
            foreach ($_POST['site_name'] as $index => $name) {
                if (!empty($name)) {
                    $sites_config[] = array(
                        'name' => sanitize_text_field($name),
                        'price' => isset($_POST['site_price'][$index]) ? floatval($_POST['site_price'][$index]) : 0,
                        'description' => isset($_POST['site_description'][$index]) ? sanitize_text_field($_POST['site_description'][$index]) : ''
                    );
                }
            }
        }
        
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'event_type' => 'market',
            'location' => sanitize_text_field($_POST['location']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'start_time' => sanitize_text_field($_POST['start_time']),
            'end_time' => sanitize_text_field($_POST['end_time']),
            'frequency' => sanitize_text_field($_POST['frequency']),
            'capacity' => intval($_POST['capacity']),
            'sites_config' => $sites_config,
            'site_fee' => floatval($_POST['site_fee']),
            'booking_open_date' => sanitize_text_field($_POST['booking_open_date']),
            'booking_close_date' => sanitize_text_field($_POST['booking_close_date']),
        );
        
        if ($event_id) {
            // Update existing
            $result = LT_Event::update($event_id, $data);
            $message = __('Market updated successfully!', 'tekram');
        } else {
            // Create new
            $result = LT_Event::create($data);
            $message = __('Market created successfully!', 'tekram');
        }
        
        if ($result['success']) {
            echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
            echo '<script>setTimeout(function(){ window.location.href = "?page=tekram-events"; }, 1500);</script>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    
    private static function delete_event($event_id) {
        $result = LT_Event::delete($event_id);
        if ($result['success']) {
            echo '<div class="notice notice-success"><p>' . __('Market deleted.', 'tekram') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    
    private static function duplicate_event($event_id) {
        $original_event = get_post($event_id);
        if (!$original_event || $original_event->post_type !== 'lt_event') {
            echo '<div class="notice notice-error"><p>' . __('Event not found.', 'tekram') . '</p></div>';
            return;
        }
        
        // Get all event data
        $data = LT_Event::get_data($event_id);
        $sites_config = get_post_meta($event_id, '_lt_sites_config', true);
        
        // Create duplicate with "Copy" suffix
        $new_title = $original_event->post_title . ' (Copy)';
        
        // Calculate next date based on frequency
        $original_date = strtotime($data['start_date']);
        $new_date = $original_date;
        
        switch ($data['frequency']) {
            case 'weekly':
                $new_date = strtotime('+7 days', $original_date);
                break;
            case 'fortnightly':
                $new_date = strtotime('+14 days', $original_date);
                break;
            case 'monthly':
                $new_date = strtotime('+1 month', $original_date);
                break;
            case 'quarterly':
                $new_date = strtotime('+3 months', $original_date);
                break;
            case 'annually':
                $new_date = strtotime('+1 year', $original_date);
                break;
            default:
                $new_date = strtotime('+7 days', $original_date);
        }
        
        // Create new event post
        $new_event_data = array(
            'post_title' => $new_title,
            'post_content' => $original_event->post_content,
            'post_type' => 'lt_event',
            'post_status' => 'publish'
        );
        
        $new_event_id = wp_insert_post($new_event_data);
        
        if (is_wp_error($new_event_id)) {
            echo '<div class="notice notice-error"><p>' . __('Failed to duplicate event.', 'tekram') . '</p></div>';
            return;
        }
        
        // Copy all meta data with new date
        update_post_meta($new_event_id, '_lt_location', $data['location']);
        update_post_meta($new_event_id, '_lt_start_date', date('Y-m-d', $new_date));
        update_post_meta($new_event_id, '_lt_end_date', $data['end_date'] ? date('Y-m-d', strtotime($data['end_date']) + ($new_date - $original_date)) : '');
        update_post_meta($new_event_id, '_lt_start_time', $data['start_time']);
        update_post_meta($new_event_id, '_lt_end_time', $data['end_time']);
        update_post_meta($new_event_id, '_lt_frequency', $data['frequency']);
        update_post_meta($new_event_id, '_lt_capacity', $data['capacity']);
        update_post_meta($new_event_id, '_lt_sites_config', $sites_config);
        update_post_meta($new_event_id, '_lt_site_fee', $data['site_fee']);
        update_post_meta($new_event_id, '_lt_booking_open_date', $data['booking_open_date'] ? date('Y-m-d', strtotime($data['booking_open_date']) + ($new_date - $original_date)) : '');
        update_post_meta($new_event_id, '_lt_booking_close_date', $data['booking_close_date'] ? date('Y-m-d', strtotime($data['booking_close_date']) + ($new_date - $original_date)) : '');
        
        echo '<div class="notice notice-success is-dismissible" style="padding: 15px; margin: 20px 0; background: #d4edda; border-left: 4px solid #28a745;">';
        echo '<p style="margin: 0; font-size: 14px;">';
        echo '<strong>' . __('✅ Market Duplicated Successfully!', 'tekram') . '</strong><br>';
        echo sprintf(__('Created "%s" scheduled for %s', 'tekram'), esc_html($new_title), date('F j, Y', $new_date));
        echo '<br><a href="?page=tekram-events&action=edit&id=' . $new_event_id . '" class="button button-primary" style="margin-top: 10px;">' . __('Edit Duplicate Event →', 'tekram') . '</a> ';
        echo '<a href="?page=tekram-events" class="button" style="margin-top: 10px;">' . __('← Back to Markets', 'tekram') . '</a>';
        echo '</p>';
        echo '</div>';
    }
}



