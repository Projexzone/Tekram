<?php
/**
 * Admin Extras Management Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin_Extras {
    
    public static function render() {
        // Handle form submissions
        if (isset($_POST['lt_add_extra']) && check_admin_referer('lt_extras_action')) {
            self::handle_add_extra();
        }
        
        if (isset($_POST['lt_delete_extra']) && check_admin_referer('lt_extras_action')) {
            self::handle_delete_extra();
        }
        
        $extras = self::get_all_extras();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Extras & Add-ons Management', 'tekram'); ?></h1>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2><?php _e('Add New Extra', 'tekram'); ?></h2>
                
                <form method="post">
                    <?php wp_nonce_field('lt_extras_action'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="name"><?php _e('Name', 'tekram'); ?> *</label></th>
                            <td><input type="text" name="name" id="name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="description"><?php _e('Description', 'tekram'); ?></label></th>
                            <td><textarea name="description" id="description" class="large-text" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="price"><?php _e('Price', 'tekram'); ?> *</label></th>
                            <td>
                                <?php echo get_option('lt_currency_symbol', '$'); ?>
                                <input type="number" name="price" id="price" step="0.01" min="0" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="quantity_available"><?php _e('Quantity Available', 'tekram'); ?></label></th>
                            <td>
                                <input type="number" name="quantity_available" id="quantity_available" min="0">
                                <p class="description"><?php _e('Leave empty for unlimited', 'tekram'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="event_id"><?php _e('Applicable To', 'tekram'); ?></label></th>
                            <td>
                                <select name="event_id" id="event_id">
                                    <option value=""><?php _e('All Events (Global)', 'tekram'); ?></option>
                                    <?php
                                    $events = LT_Event::get_all();
                                    foreach ($events as $event) {
                                        echo '<option value="' . $event->ID . '">' . esc_html($event->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="lt_add_extra" class="button button-primary"><?php _e('Add Extra', 'tekram'); ?></button>
                    </p>
                </form>
            </div>
            
            <h2><?php _e('Existing Extras', 'tekram'); ?></h2>
            
            <?php if (empty($extras)) { ?>
                <p><?php _e('No extras created yet.', 'tekram'); ?></p>
            <?php } else { ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'tekram'); ?></th>
                            <th><?php _e('Description', 'tekram'); ?></th>
                            <th><?php _e('Price', 'tekram'); ?></th>
                            <th><?php _e('Quantity', 'tekram'); ?></th>
                            <th><?php _e('Used', 'tekram'); ?></th>
                            <th><?php _e('Applicable To', 'tekram'); ?></th>
                            <th><?php _e('Actions', 'tekram'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($extras as $extra) {
                            $used = LT_Extras::get_quantity_used($extra->id);
                            $available = $extra->quantity_available !== null ? $extra->quantity_available - $used : '∞';
                            $event_name = $extra->event_id ? get_the_title($extra->event_id) : __('All Events', 'tekram');
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($extra->name); ?></strong></td>
                                <td><?php echo esc_html($extra->description); ?></td>
                                <td><?php echo get_option('lt_currency_symbol', '$') . number_format($extra->price, 2); ?></td>
                                <td><?php echo $extra->quantity_available !== null ? $extra->quantity_available : '∞'; ?></td>
                                <td><?php echo $used; ?> (<?php echo $available; ?> available)</td>
                                <td><?php echo esc_html($event_name); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('lt_extras_action'); ?>
                                        <input type="hidden" name="extra_id" value="<?php echo $extra->id; ?>">
                                        <button type="submit" name="lt_delete_extra" class="button button-small button-link-delete" 
                                                onclick="return confirm('<?php _e('Are you sure?', 'tekram'); ?>');">
                                            <?php _e('Delete', 'tekram'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
        <?php
    }
    
    private static function handle_add_extra() {
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'quantity_available' => !empty($_POST['quantity_available']) ? intval($_POST['quantity_available']) : null,
            'event_id' => !empty($_POST['event_id']) ? intval($_POST['event_id']) : null
        );
        
        $result = LT_Extras::create_extra($data);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>' . __('Extra added successfully.', 'tekram') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to add extra.', 'tekram') . '</p></div>';
        }
    }
    
    private static function handle_delete_extra() {
        $extra_id = intval($_POST['extra_id']);
        $result = LT_Extras::delete_extra($extra_id);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>' . __('Extra deleted successfully.', 'tekram') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to delete extra.', 'tekram') . '</p></div>';
        }
    }
    
    private static function get_all_extras() {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_extras';
        
        return $wpdb->get_results("SELECT * FROM $table WHERE status = 'active' ORDER BY name ASC");
    }
}



