<?php
/**
 * Admin Vendors
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Admin_Vendors {
    
    public static function render() {
        // Handle actions
        if (isset($_POST['action']) && check_admin_referer('lt_vendor_action')) {
            self::handle_action();
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            self::handle_delete(intval($_GET['id']));
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $vendor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'view' && $vendor_id) {
            self::render_vendor_details($vendor_id);
        } else {
            self::render_vendor_list();
        }
    }
    
    private static function render_vendor_list() {
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'publish';
        
        // Get pending count for badge
        $pending_count = wp_count_posts('lt_vendor')->pending;
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Vendors', 'tekram'); ?></h1>
            <a href="<?php echo admin_url('post-new.php?post_type=lt_vendor'); ?>" class="page-title-action">
                <?php _e('Add New', 'tekram'); ?>
            </a>
            
            <ul class="subsubsub">
                <li>
                    <a href="?page=tekram-vendors&status=publish" <?php echo $status === 'publish' ? 'class="current"' : ''; ?>>
                        <?php _e('Active', 'tekram'); ?>
                    </a> |
                </li>
                <li>
                    <a href="?page=tekram-vendors&status=pending" <?php echo $status === 'pending' ? 'class="current"' : ''; ?>>
                        <?php _e('Pending', 'tekram'); ?>
                        <?php if ($pending_count > 0) { ?>
                            <span class="count">(<?php echo $pending_count; ?>)</span>
                        <?php } ?>
                    </a> |
                </li>
                <li>
                    <a href="?page=tekram-vendors&status=draft" <?php echo $status === 'draft' ? 'class="current"' : ''; ?>>
                        <?php _e('Declined', 'tekram'); ?>
                    </a>
                </li>
            </ul>
            
            <br class="clear">
            
            <?php
            $args = array(
                'post_type' => 'lt_vendor',
                'post_status' => $status,
                'posts_per_page' => 50,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            
            $vendors = new WP_Query($args);
            
            if ($vendors->have_posts()) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Business Name', 'tekram'); ?></th>
                            <th><?php _e('Contact', 'tekram'); ?></th>
                            <th><?php _e('Email', 'tekram'); ?></th>
                            <th><?php _e('Phone', 'tekram'); ?></th>
                            <th><?php _e('Reference ID', 'tekram'); ?></th>
                            <th><?php _e('Application Date', 'tekram'); ?></th>
                            <th><?php _e('Actions', 'tekram'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($vendors->have_posts()) {
                            $vendors->the_post();
                            $post_id = get_the_ID();
                            $data = LT_Vendor::get_data($post_id);
                            ?>
                            <tr>
                                <td>
                                    <strong><a href="?page=tekram-vendors&action=view&id=<?php echo $post_id; ?>"><?php echo esc_html(get_the_title()); ?></a></strong>
                                </td>
                                <td><?php echo esc_html($data['first_name'] . ' ' . $data['last_name']); ?></td>
                                <td><?php echo esc_html($data['email']); ?></td>
                                <td><?php echo esc_html($data['phone']); ?></td>
                                <td><code><?php echo esc_html($data['vendor_reference']); ?></code></td>
                                <td><?php echo date('M j, Y', strtotime($data['application_date'])); ?></td>
                                <td>
                                    <a href="?page=tekram-vendors&action=view&id=<?php echo $post_id; ?>" class="button button-small">
                                        <?php _e('View', 'tekram'); ?>
                                    </a>
                                    <?php if ($status === 'pending') { ?>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('lt_vendor_action'); ?>
                                            <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                            <button type="submit" name="action" value="approve" class="button button-primary button-small">
                                                <?php _e('Approve', 'tekram'); ?>
                                            </button>
                                            <button type="submit" name="action" value="decline" class="button button-small">
                                                <?php _e('Decline', 'tekram'); ?>
                                            </button>
                                        </form>
                                    <?php } ?>
                                    <a href="?page=tekram-vendors&action=delete&id=<?php echo $post_id; ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('<?php _e('Are you sure you want to delete this vendor?', 'tekram'); ?>');">
                                        <?php _e('Delete', 'tekram'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        wp_reset_postdata();
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>' . __('No vendors found.', 'tekram') . '</p>';
            }
            ?>
        </div>
        <?php
    }
    
    private static function render_vendor_details($vendor_id) {
        $vendor = get_post($vendor_id);
        if (!$vendor || $vendor->post_type !== 'lt_vendor') {
            echo '<div class="wrap"><p>' . __('Vendor not found.', 'tekram') . '</p></div>';
            return;
        }
        
        $data = LT_Vendor::get_data($vendor_id);
        $bookings = LT_Booking::get_vendor_bookings($vendor_id);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Vendor Details', 'tekram'); ?></h1>
            <a href="?page=tekram-vendors" class="page-title-action"><?php _e('← Back to Vendors', 'tekram'); ?></a>
            
            <div style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccc;">
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Vendor Reference ID', 'tekram'); ?>:</th>
                        <td><code style="font-size: 16px; background: #f0f0f0; padding: 5px 10px;"><?php echo esc_html($data['vendor_reference']); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'tekram'); ?>:</th>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($vendor->post_status); ?>" style="background: <?php echo $vendor->post_status === 'pending' ? '#fff3cd' : ($vendor->post_status === 'publish' ? '#d4edda' : '#f8d7da'); ?>; color: #333; padding: 5px 15px; border-radius: 3px; display: inline-block;">
                                <?php echo ucfirst($vendor->post_status); ?>
                            </span>
                            <?php if ($vendor->post_status === 'pending') { ?>
                                <form method="post" style="display: inline; margin-left: 10px;">
                                    <?php wp_nonce_field('lt_vendor_action'); ?>
                                    <input type="hidden" name="post_id" value="<?php echo $vendor_id; ?>">
                                    <button type="submit" name="action" value="approve" class="button button-primary">
                                        <?php _e('Approve Vendor', 'tekram'); ?>
                                    </button>
                                    <button type="submit" name="action" value="decline" class="button">
                                        <?php _e('Decline Vendor', 'tekram'); ?>
                                    </button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
                
                <!-- Personal & Business Information -->
                <h2 style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #0073aa;"><?php _e('Personal & Business Information', 'tekram'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Business Name', 'tekram'); ?>:</th>
                        <td><strong><?php echo esc_html($data['business_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Contact Name', 'tekram'); ?>:</th>
                        <td><?php echo esc_html($data['first_name'] . ' ' . $data['last_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email', 'tekram'); ?>:</th>
                        <td><a href="mailto:<?php echo esc_attr($data['email']); ?>"><?php echo esc_html($data['email']); ?></a></td>
                    </tr>
                    <tr>
                        <th><?php _e('Phone', 'tekram'); ?>:</th>
                        <td><?php echo esc_html($data['phone']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('ABN', 'tekram'); ?>:</th>
                        <td><?php echo esc_html($data['abn']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Address', 'tekram'); ?>:</th>
                        <td>
                            <?php echo esc_html($data['address']); ?><br>
                            <?php echo esc_html($data['city'] . ', ' . $data['state'] . ' ' . $data['postcode']); ?>
                        </td>
                    </tr>
                    <?php if (!empty($data['website'])) { ?>
                    <tr>
                        <th><?php _e('Website', 'tekram'); ?>:</th>
                        <td><a href="<?php echo esc_url($data['website']); ?>" target="_blank"><?php echo esc_html($data['website']); ?></a></td>
                    </tr>
                    <?php } ?>
                    <?php if (!empty($data['facebook'])) { ?>
                    <tr>
                        <th><?php _e('Facebook', 'tekram'); ?>:</th>
                        <td><a href="<?php echo esc_url($data['facebook']); ?>" target="_blank"><?php echo esc_html($data['facebook']); ?></a></td>
                    </tr>
                    <?php } ?>
                    <?php if (!empty($data['instagram'])) { ?>
                    <tr>
                        <th><?php _e('Instagram', 'tekram'); ?>:</th>
                        <td><a href="<?php echo esc_url($data['instagram']); ?>" target="_blank"><?php echo esc_html($data['instagram']); ?></a></td>
                    </tr>
                    <?php } ?>
                    <?php if (has_post_thumbnail($vendor_id)) { ?>
                    <tr>
                        <th><?php _e('Logo', 'tekram'); ?>:</th>
                        <td><?php echo get_the_post_thumbnail($vendor_id, 'medium'); ?></td>
                    </tr>
                    <?php } ?>
                </table>
                
                <!-- Emergency Contact -->
                <?php if (!empty($data['emergency_name'])) { ?>
                <h2 style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #0073aa;"><?php _e('Emergency Contact', 'tekram'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Name', 'tekram'); ?>:</th>
                        <td><?php echo esc_html($data['emergency_name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Phone', 'tekram'); ?>:</th>
                        <td><?php echo esc_html($data['emergency_phone']); ?></td>
                    </tr>
                    <?php if (!empty($data['emergency_relationship'])) { ?>
                    <tr>
                        <th><?php _e('Relationship', 'tekram'); ?>:</th>
                        <td><?php echo esc_html($data['emergency_relationship']); ?></td>
                    </tr>
                    <?php } ?>
                </table>
                <?php } ?>
                
                <!-- Product Information -->
                <h2 style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #0073aa;"><?php _e('Product Information', 'tekram'); ?></h2>
                <table class="form-table">
                    <?php if (!empty($data['product_categories']) && is_array($data['product_categories'])) { ?>
                    <tr>
                        <th><?php _e('Product Categories', 'tekram'); ?>:</th>
                        <td>
                            <?php 
                            $cat_labels = array(
                                'food_beverage' => 'Food / Beverage',
                                'take_home_food' => 'Take home food',
                                'health_wellbeing' => 'Health & Wellbeing',
                                'arts_crafts' => 'Arts / Crafts',
                                'jewellery' => 'Jewellery',
                                'fashion_accessories' => 'Fashion & Accessories',
                                'homewares' => 'Homewares',
                                'promotional_charity' => 'Promotional or Charity'
                            );
                            $cats = array();
                            foreach ($data['product_categories'] as $cat) {
                                $cats[] = isset($cat_labels[$cat]) ? $cat_labels[$cat] : $cat;
                            }
                            echo '• ' . implode('<br>• ', array_map('esc_html', $cats));
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['product_type']) && is_array($data['product_type'])) { ?>
                    <tr>
                        <th><?php _e('Type of Products', 'tekram'); ?>:</th>
                        <td>
                            <?php 
                            $type_labels = array(
                                'handmade_by_me' => 'Handmade by me',
                                'service_by_me' => 'A service provided by me',
                                'grown_by_me' => 'Grown by me',
                                'designed_made_australia' => 'Designed by me and made in Australia',
                                'designed_made_overseas' => 'Designed by me and made overseas',
                                'australian_supplier' => 'Purchased from an Australian supplier',
                                'imported' => 'Imported goods'
                            );
                            $types = array();
                            foreach ($data['product_type'] as $type) {
                                $types[] = isset($type_labels[$type]) ? $type_labels[$type] : $type;
                            }
                            echo '• ' . implode('<br>• ', array_map('esc_html', $types));
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <tr>
                        <th><?php _e('Products/Services Description', 'tekram'); ?>:</th>
                        <td><?php echo nl2br(esc_html($data['products_description'])); ?></td>
                    </tr>
                    
                    <?php if (!empty($data['products_full_description'])) { ?>
                    <tr>
                        <th><?php _e('Full Description', 'tekram'); ?>:</th>
                        <td style="background: #f9f9f9; padding: 15px; border-left: 3px solid #0073aa;">
                            <?php echo nl2br(esc_html($data['products_full_description'])); ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['country_of_origin'])) { ?>
                    <tr>
                        <th><?php _e('Country of Origin', 'tekram'); ?>:</th>
                        <td><?php echo esc_html($data['country_of_origin']); ?></td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['price_range'])) { ?>
                    <tr>
                        <th><?php _e('Price Range', 'tekram'); ?>:</th>
                        <td><strong><?php echo esc_html($data['price_range']); ?></strong></td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['sustainability_practices'])) { ?>
                    <tr>
                        <th><?php _e('Sustainability Practices', 'tekram'); ?>:</th>
                        <td style="background: #e7f7e7; padding: 15px; border-left: 3px solid #28a745;">
                            <em style="color: #666; font-size: 12px;"><?php _e('(Displays on vendor public page)', 'tekram'); ?></em><br>
                            <?php echo nl2br(esc_html($data['sustainability_practices'])); ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['accessibility_practices'])) { ?>
                    <tr>
                        <th><?php _e('Accessibility Practices', 'tekram'); ?>:</th>
                        <td style="background: #e7f0ff; padding: 15px; border-left: 3px solid #007bff;">
                            <em style="color: #666; font-size: 12px;"><?php _e('(Displays on vendor public page)', 'tekram'); ?></em><br>
                            <?php echo nl2br(esc_html($data['accessibility_practices'])); ?>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
                
                <!-- Product Photos -->
                <?php 
                $product_photos = get_post_meta($vendor_id, '_lt_product_photos', true);
                ?>
                <h3><?php _e('Product Photos', 'tekram'); ?></h3>
                <?php if (!empty($product_photos)) { ?>
                    <?php if (is_array($product_photos) && count($product_photos) > 0) { ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                            <?php foreach ($product_photos as $photo_id) {
                                if (is_numeric($photo_id)) {
                                    $img = wp_get_attachment_image($photo_id, 'thumbnail', false, array('style' => 'width: 100%; height: auto;'));
                                    if ($img) {
                                        echo '<div style="border: 1px solid #ddd; padding: 5px;">';
                                        echo $img;
                                        echo '</div>';
                                    } else {
                                        // Photo ID exists but image not found
                                        echo '<div style="border: 1px solid #ffc107; padding: 10px; background: #fff3cd;">';
                                        echo '<p style="margin: 0; font-size: 12px;">⚠️ Photo ID ' . $photo_id . ' not found</p>';
                                        echo '</div>';
                                    }
                                }
                            } ?>
                        </div>
                    <?php } else { ?>
                        <p style="color: #999; font-style: italic;">
                            No product photos uploaded. 
                            <span style="font-size: 11px; color: #666;">(Data: <?php echo esc_html(print_r($product_photos, true)); ?>)</span>
                        </p>
                    <?php } ?>
                <?php } else { ?>
                    <p style="color: #999; font-style: italic;">No product photos uploaded.</p>
                <?php } ?>
                
                
                <!-- Stall Requirements -->
                <h2 style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #0073aa;"><?php _e('Stall & Equipment Requirements', 'tekram'); ?></h2>
                <table class="form-table">
                    <?php if (!empty($data['stall_type'])) { ?>
                    <tr>
                        <th><?php _e('Stall Type', 'tekram'); ?>:</th>
                        <td>
                            <?php 
                            $stall_types = array(
                                'own_gazebo' => 'I have my own gazebo/tent',
                                'food_van_truck' => '🚚 Food Van / Truck',
                                'need_hire' => 'I need to hire a gazebo',
                                'other' => 'Other'
                            );
                            echo esc_html(isset($stall_types[$data['stall_type']]) ? $stall_types[$data['stall_type']] : $data['stall_type']);
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <tr>
                        <th><?php _e('Requires Electricity', 'tekram'); ?>:</th>
                        <td><strong style="color: <?php echo $data['require_electricity'] === 'yes' ? '#dc3232' : '#46b450'; ?>;">
                            <?php echo $data['require_electricity'] === 'yes' ? '⚡ YES' : 'No'; ?>
                        </strong></td>
                    </tr>
                    
                    <?php if (!empty($data['require_water'])) { ?>
                    <tr>
                        <th><?php _e('Requires Water', 'tekram'); ?>:</th>
                        <td><strong style="color: <?php echo $data['require_water'] === 'yes' ? '#0073aa' : '#46b450'; ?>;">
                            <?php echo $data['require_water'] === 'yes' ? '💧 YES' : 'No'; ?>
                        </strong></td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['tables_needed']) && $data['tables_needed'] > 0) { ?>
                    <tr>
                        <th><?php _e('Tables Needed', 'tekram'); ?>:</th>
                        <td><?php echo intval($data['tables_needed']); ?></td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['chairs_needed']) && $data['chairs_needed'] > 0) { ?>
                    <tr>
                        <th><?php _e('Chairs Needed', 'tekram'); ?>:</th>
                        <td><?php echo intval($data['chairs_needed']); ?></td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['vehicle_rego'])) { ?>
                    <tr>
                        <th><?php _e('Vehicle Registration', 'tekram'); ?>:</th>
                        <td><code><?php echo esc_html($data['vehicle_rego']); ?></code></td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['special_requirements'])) { ?>
                    <tr>
                        <th><?php _e('Special Requirements', 'tekram'); ?>:</th>
                        <td style="background: #fff3cd; padding: 15px; border-left: 3px solid #ffc107;">
                            <?php echo nl2br(esc_html($data['special_requirements'])); ?>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
                
                <!-- Power Requirements -->
                <?php if (!empty($data['power_type']) || !empty($data['power_usage']) || !empty($data['power_equipment'])) { ?>
                <h2 style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #0073aa;"><?php _e('⚡ Access to Power', 'tekram'); ?></h2>
                <table class="form-table">
                    <?php if (!empty($data['power_type'])) { ?>
                    <tr>
                        <th><?php _e('Power Type Required', 'tekram'); ?>:</th>
                        <td>
                            <?php 
                            $power_types = array(
                                'single_phase' => 'Single Phase (240V / 10 Amp)',
                                'three_phase' => 'Three Phase (415V)',
                                'generator' => 'Own Generator',
                                'none' => 'No power required'
                            );
                            echo '<strong>' . esc_html(isset($power_types[$data['power_type']]) ? $power_types[$data['power_type']] : $data['power_type']) . '</strong>';
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['power_usage'])) { ?>
                    <tr>
                        <th><?php _e('Power Usage', 'tekram'); ?>:</th>
                        <td><code style="background: #f0f0f0; padding: 5px 10px;"><?php echo esc_html($data['power_usage']); ?></code></td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['power_equipment'])) { ?>
                    <tr>
                        <th><?php _e('Equipment Requiring Power', 'tekram'); ?>:</th>
                        <td style="background: #fff3cd; padding: 15px;">
                            <?php echo nl2br(esc_html($data['power_equipment'])); ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['extension_cord'])) { ?>
                    <tr>
                        <th><?php _e('Extension Cord & Power Board', 'tekram'); ?>:</th>
                        <td><?php echo $data['extension_cord'] === 'yes' ? '✅ Has own' : '⚠️ Needs to borrow/hire'; ?></td>
                    </tr>
                    <?php } ?>
                </table>
                <?php } ?>
                
                <!-- Documents -->
                <h2 style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #0073aa;"><?php _e('📄 Documents & Compliance', 'tekram'); ?></h2>
                <table class="form-table">
                    <!-- Public Liability Insurance -->
                    <?php if (!empty($data['insurance_policy_number'])) { ?>
                    <tr>
                        <th><?php _e('Public Liability Insurance', 'tekram'); ?>:</th>
                        <td style="background: #e7f7e7; padding: 15px;">
                            <strong>Policy Number:</strong> <?php echo esc_html($data['insurance_policy_number']); ?><br>
                            <strong>Issuer:</strong> <?php echo esc_html($data['insurance_issuer']); ?><br>
                            <strong>Expiry:</strong> <?php echo !empty($data['insurance_expiry']) ? date('d/m/Y', strtotime($data['insurance_expiry'])) : '-'; ?><br>
                            <p style="color: #666; font-style: italic; font-size: 12px; margin: 10px 0 0 0;">📧 Document was emailed as attachment to admin</p>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <!-- Food Licence -->
                    <?php if (!empty($data['food_licence_number'])) { ?>
                    <tr>
                        <th><?php _e('Food Licence', 'tekram'); ?>:</th>
                        <td style="background: #f9f9f9; padding: 15px;">
                            <strong>Licence Number:</strong> <?php echo esc_html($data['food_licence_number']); ?><br>
                            <strong>Issuer:</strong> <?php echo esc_html($data['food_licence_issuer']); ?><br>
                            <strong>Expiry:</strong> <?php echo !empty($data['food_licence_expiry']) ? date('d/m/Y', strtotime($data['food_licence_expiry'])) : '-'; ?><br>
                            <p style="color: #666; font-style: italic; font-size: 12px; margin: 10px 0 0 0;">📧 Document was emailed as attachment to admin</p>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <!-- Food Handling Certificate -->
                    <?php if (!empty($data['food_handling_number'])) { ?>
                    <tr>
                        <th><?php _e('Food Handling Certificate', 'tekram'); ?>:</th>
                        <td style="background: #f9f9f9; padding: 15px;">
                            <strong>Certificate Number:</strong> <?php echo esc_html($data['food_handling_number']); ?><br>
                            <strong>Issuer:</strong> <?php echo esc_html($data['food_handling_issuer']); ?><br>
                            <strong>Expiry:</strong> <?php echo !empty($data['food_handling_expiry']) ? date('d/m/Y', strtotime($data['food_handling_expiry'])) : '-'; ?><br>
                            <p style="color: #666; font-style: italic; font-size: 12px; margin: 10px 0 0 0;">📧 Document was emailed as attachment to admin</p>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <!-- Food Safety Certificate -->
                    <?php if (!empty($data['food_safety_number'])) { ?>
                    <tr>
                        <th><?php _e('Food Safety Certificate', 'tekram'); ?>:</th>
                        <td style="background: #f9f9f9; padding: 15px;">
                            <strong>Certificate Number:</strong> <?php echo esc_html($data['food_safety_number']); ?><br>
                            <strong>Issuer:</strong> <?php echo esc_html($data['food_safety_issuer']); ?><br>
                            <strong>Expiry:</strong> <?php echo !empty($data['food_safety_expiry']) ? date('d/m/Y', strtotime($data['food_safety_expiry'])) : '-'; ?><br>
                            <p style="color: #666; font-style: italic; font-size: 12px; margin: 10px 0 0 0;">📧 Document was emailed as attachment to admin</p>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <!-- Business License -->
                    <?php if (!empty($data['business_licence_number'])) { ?>
                    <tr>
                        <th><?php _e('Business License', 'tekram'); ?>:</th>
                        <td style="background: #f9f9f9; padding: 15px;">
                            <strong>Licence Number:</strong> <?php echo esc_html($data['business_licence_number']); ?><br>
                            <strong>Issuer:</strong> <?php echo esc_html($data['business_licence_issuer']); ?><br>
                            <strong>Expiry:</strong> <?php echo !empty($data['business_licence_expiry']) ? date('d/m/Y', strtotime($data['business_licence_expiry'])) : '-'; ?><br>
                            <p style="color: #666; font-style: italic; font-size: 12px; margin: 10px 0 0 0;">📧 Document was emailed as attachment to admin</p>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
                
                
                <!-- Additional Information -->
                <h2 style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #0073aa;"><?php _e('Additional Information', 'tekram'); ?></h2>
                <table class="form-table">
                    <?php if (!empty($data['referral_source'])) { ?>
                    <tr>
                        <th><?php _e('How They Heard About Us', 'tekram'); ?>:</th>
                        <td>
                            <?php 
                            $referral_sources = array(
                                'social_media' => 'Social Media',
                                'friend' => 'Friend / Word of Mouth',
                                'google' => 'Google Search',
                                'market_directory' => 'Market Directory Website',
                                'returning' => 'Returning Vendor',
                                'other' => 'Other'
                            );
                            echo esc_html(isset($referral_sources[$data['referral_source']]) ? $referral_sources[$data['referral_source']] : $data['referral_source']);
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['previous_experience'])) { ?>
                    <tr>
                        <th><?php _e('Previous Market Experience', 'tekram'); ?>:</th>
                        <td><?php echo $data['previous_experience'] === 'yes' ? '✅ Yes - Experienced vendor' : '🆕 No - First time vendor'; ?></td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (!empty($data['additional_comments'])) { ?>
                    <tr>
                        <th><?php _e('Additional Comments', 'tekram'); ?>:</th>
                        <td style="background: #f9f9f9; padding: 15px; border-left: 3px solid #666;">
                            <?php echo nl2br(esc_html($data['additional_comments'])); ?>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <tr>
                        <th><?php _e('Application Date', 'tekram'); ?>:</th>
                        <td><?php echo date('F j, Y g:i a', strtotime($data['application_date'])); ?></td>
                    </tr>
                </table>
                
                <!-- Booking History -->
                <h2 style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #0073aa;"><?php _e('Booking History', 'tekram'); ?></h2>
                
                <?php if ($bookings && count($bookings) > 0) { ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Reference', 'tekram'); ?></th>
                                <th><?php _e('Market', 'tekram'); ?></th>
                                <th><?php _e('Date', 'tekram'); ?></th>
                                <th><?php _e('Amount', 'tekram'); ?></th>
                                <th><?php _e('Payment', 'tekram'); ?></th>
                                <th><?php _e('Status', 'tekram'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking) {
                                $event = get_post($booking->event_id);
                                ?>
                                <tr>
                                    <td><code><?php echo esc_html($booking->booking_reference); ?></code></td>
                                    <td><?php echo $event ? esc_html($event->post_title) : '-'; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></td>
                                    <td><?php echo get_option('lt_currency_symbol', '$') . number_format($booking->amount, 2); ?></td>
                                    <td><?php echo ucfirst($booking->payment_status); ?></td>
                                    <td><?php echo ucfirst($booking->status); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p><?php _e('No bookings yet.', 'tekram'); ?></p>
                <?php } ?>
                
                <p style="margin-top: 20px;">
                    <a href="?page=tekram-vendors" class="button"><?php _e('← Back to Vendors', 'tekram'); ?></a>
                    <a href="?page=tekram-vendors&action=delete&id=<?php echo $vendor_id; ?>" 
                       class="button button-link-delete" 
                       onclick="return confirm('<?php _e('Are you sure you want to delete this vendor? This will also delete all their bookings.', 'tekram'); ?>');" 
                       style="color: #b32d2e; margin-left: 10px;">
                        <?php _e('Delete Vendor', 'tekram'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-publish {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-draft {
            background: #f8d7da;
            color: #721c24;
        }
        </style>
        <?php
    }
    
    private static function handle_delete($vendor_id) {
        // Delete vendor and all related bookings
        wp_delete_post($vendor_id, true);
        
        echo '<div class="notice notice-success"><p>' . __('Vendor deleted successfully.', 'tekram') . '</p></div>';
        echo '<script>setTimeout(function(){ window.location.href = "?page=tekram-vendors"; }, 1500);</script>';
    }
    
    private static function handle_action() {
        $action = sanitize_text_field($_POST['action']);
        $post_id = intval($_POST['post_id']);
        
        switch ($action) {
            case 'approve':
                LT_Vendor::approve($post_id);
                echo '<div class="notice notice-success"><p>' . __('Vendor approved.', 'tekram') . '</p></div>';
                break;
                
            case 'decline':
                LT_Vendor::decline($post_id);
                echo '<div class="notice notice-success"><p>' . __('Vendor declined.', 'tekram') . '</p></div>';
                break;
        }
    }
}



