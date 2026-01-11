<?php
/**
 * Vendor Class
 * Handles all vendor operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Vendor {
    
    /**
     * Create vendor application
     */
    public static function create_application($data) {
        // Capture file attachments FIRST
        $pdf_attachments = array();
        $doc_fields = array(
            'doc_insurance' => 'Public Liability Insurance',
            'doc_food_licence' => 'Food Licence',
            'doc_food_handling' => 'Food Handling Certificate',
            'doc_food_safety' => 'Food Safety Certificate',
            'doc_business_license' => 'Business License'
        );
        
        foreach ($doc_fields as $field => $label) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK && file_exists($_FILES[$field]['tmp_name'])) {
                $pdf_attachments[$field] = array(
                    'path' => $_FILES[$field]['tmp_name'],
                    'name' => $_FILES[$field]['name'],
                    'label' => $label
                );
            }
        }
        
        // Sanitize input
        $first_name = sanitize_text_field($data['first_name']);
        $last_name = sanitize_text_field($data['last_name']);
        $email = sanitize_email($data['email']);
        $phone = sanitize_text_field($data['phone']);
        $business_name = sanitize_text_field($data['business_name']);
        $abn = sanitize_text_field($data['abn']);
        $address = sanitize_textarea_field($data['address']);
        $city = sanitize_text_field($data['city']);
        $state = sanitize_text_field($data['state']);
        $postcode = sanitize_text_field($data['postcode']);
        $products_description = sanitize_textarea_field($data['products_description']);
        $website = esc_url_raw($data['website']);
        $facebook = esc_url_raw($data['facebook']);
        $instagram = esc_url_raw($data['instagram']);
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            return array(
                'success' => false,
                'message' => __('Please fill in all required fields.', 'tekram')
            );
        }
        
        if (!is_email($email)) {
            return array(
                'success' => false,
                'message' => __('Please enter a valid email address.', 'tekram')
            );
        }
        
        // Check if email already exists in vendor applications
        $existing_vendor = get_posts(array(
            'post_type' => 'lt_vendor',
            'meta_query' => array(
                array(
                    'key' => '_lt_email',
                    'value' => $email,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'post_status' => 'any'
        ));
        
        if (!empty($existing_vendor)) {
            return array(
                'success' => false,
                'message' => __('This email address is already registered.', 'tekram')
            );
        }
        
        // Generate unique vendor reference ID
        $vendor_reference = self::generate_vendor_reference();
        
        // Create vendor post
        $post_data = array(
            'post_title' => $business_name ? $business_name : $first_name . ' ' . $last_name,
            'post_type' => 'lt_vendor',
            'post_status' => 'pending',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array(
                'success' => false,
                'message' => __('Failed to create application. Please try again.', 'tekram')
            );
        }
        
        // Save meta data
        update_post_meta($post_id, '_lt_vendor_reference', $vendor_reference);
        update_post_meta($post_id, '_lt_first_name', $first_name);
        update_post_meta($post_id, '_lt_last_name', $last_name);
        update_post_meta($post_id, '_lt_email', $email);
        update_post_meta($post_id, '_lt_phone', $phone);
        update_post_meta($post_id, '_lt_business_name', $business_name);
        update_post_meta($post_id, '_lt_abn', $abn);
        update_post_meta($post_id, '_lt_address', $address);
        update_post_meta($post_id, '_lt_city', $city);
        update_post_meta($post_id, '_lt_state', $state);
        update_post_meta($post_id, '_lt_postcode', $postcode);
        update_post_meta($post_id, '_lt_products_description', $products_description);
        update_post_meta($post_id, '_lt_website', $website);
        update_post_meta($post_id, '_lt_facebook', $facebook);
        update_post_meta($post_id, '_lt_instagram', $instagram);
        update_post_meta($post_id, '_lt_application_status', 'pending');
        update_post_meta($post_id, '_lt_application_date', current_time('mysql'));
        
        // Save all new comprehensive fields
        if (isset($data['emergency_name'])) update_post_meta($post_id, '_lt_emergency_name', sanitize_text_field($data['emergency_name']));
        if (isset($data['emergency_phone'])) update_post_meta($post_id, '_lt_emergency_phone', sanitize_text_field($data['emergency_phone']));
        if (isset($data['emergency_relationship'])) update_post_meta($post_id, '_lt_emergency_relationship', sanitize_text_field($data['emergency_relationship']));
        
        // Product information
        if (isset($data['product_categories'])) update_post_meta($post_id, '_lt_product_categories', $data['product_categories']);
        if (isset($data['product_type'])) update_post_meta($post_id, '_lt_product_type', $data['product_type']);
        if (isset($data['products_full_description'])) update_post_meta($post_id, '_lt_products_full_description', sanitize_textarea_field($data['products_full_description']));
        if (isset($data['country_of_origin'])) update_post_meta($post_id, '_lt_country_of_origin', sanitize_text_field($data['country_of_origin']));
        if (isset($data['price_range'])) update_post_meta($post_id, '_lt_price_range', sanitize_text_field($data['price_range']));
        if (isset($data['sustainability_practices'])) update_post_meta($post_id, '_lt_sustainability_practices', sanitize_textarea_field($data['sustainability_practices']));
        if (isset($data['accessibility_practices'])) update_post_meta($post_id, '_lt_accessibility_practices', sanitize_textarea_field($data['accessibility_practices']));
        
        // Stall requirements
        if (isset($data['stall_type'])) update_post_meta($post_id, '_lt_stall_type', sanitize_text_field($data['stall_type']));
        if (isset($data['require_electricity'])) update_post_meta($post_id, '_lt_require_electricity', sanitize_text_field($data['require_electricity']));
        if (isset($data['require_water'])) update_post_meta($post_id, '_lt_require_water', sanitize_text_field($data['require_water']));
        if (isset($data['tables_needed'])) update_post_meta($post_id, '_lt_tables_needed', intval($data['tables_needed']));
        if (isset($data['chairs_needed'])) update_post_meta($post_id, '_lt_chairs_needed', intval($data['chairs_needed']));
        if (isset($data['vehicle_rego'])) update_post_meta($post_id, '_lt_vehicle_rego', sanitize_text_field($data['vehicle_rego']));
        if (isset($data['special_requirements'])) update_post_meta($post_id, '_lt_special_requirements', sanitize_textarea_field($data['special_requirements']));
        
        // Power requirements
        if (isset($data['power_type'])) update_post_meta($post_id, '_lt_power_type', sanitize_text_field($data['power_type']));
        if (isset($data['power_usage'])) update_post_meta($post_id, '_lt_power_usage', sanitize_text_field($data['power_usage']));
        if (isset($data['power_equipment'])) update_post_meta($post_id, '_lt_power_equipment', sanitize_textarea_field($data['power_equipment']));
        if (isset($data['extension_cord'])) update_post_meta($post_id, '_lt_extension_cord', sanitize_text_field($data['extension_cord']));
        
        // Document details
        if (isset($data['insurance_policy_number'])) update_post_meta($post_id, '_lt_insurance_policy_number', sanitize_text_field($data['insurance_policy_number']));
        if (isset($data['insurance_issuer'])) update_post_meta($post_id, '_lt_insurance_issuer', sanitize_text_field($data['insurance_issuer']));
        if (isset($data['insurance_expiry'])) update_post_meta($post_id, '_lt_insurance_expiry', sanitize_text_field($data['insurance_expiry']));
        
        if (isset($data['food_licence_number'])) update_post_meta($post_id, '_lt_food_licence_number', sanitize_text_field($data['food_licence_number']));
        if (isset($data['food_licence_issuer'])) update_post_meta($post_id, '_lt_food_licence_issuer', sanitize_text_field($data['food_licence_issuer']));
        if (isset($data['food_licence_expiry'])) update_post_meta($post_id, '_lt_food_licence_expiry', sanitize_text_field($data['food_licence_expiry']));
        
        if (isset($data['food_handling_number'])) update_post_meta($post_id, '_lt_food_handling_number', sanitize_text_field($data['food_handling_number']));
        if (isset($data['food_handling_issuer'])) update_post_meta($post_id, '_lt_food_handling_issuer', sanitize_text_field($data['food_handling_issuer']));
        if (isset($data['food_handling_expiry'])) update_post_meta($post_id, '_lt_food_handling_expiry', sanitize_text_field($data['food_handling_expiry']));
        
        if (isset($data['food_safety_number'])) update_post_meta($post_id, '_lt_food_safety_number', sanitize_text_field($data['food_safety_number']));
        if (isset($data['food_safety_issuer'])) update_post_meta($post_id, '_lt_food_safety_issuer', sanitize_text_field($data['food_safety_issuer']));
        if (isset($data['food_safety_expiry'])) update_post_meta($post_id, '_lt_food_safety_expiry', sanitize_text_field($data['food_safety_expiry']));
        
        if (isset($data['business_licence_number'])) update_post_meta($post_id, '_lt_business_licence_number', sanitize_text_field($data['business_licence_number']));
        if (isset($data['business_licence_issuer'])) update_post_meta($post_id, '_lt_business_licence_issuer', sanitize_text_field($data['business_licence_issuer']));
        if (isset($data['business_licence_expiry'])) update_post_meta($post_id, '_lt_business_licence_expiry', sanitize_text_field($data['business_licence_expiry']));
        
        // Additional information
        if (isset($data['referral_source'])) update_post_meta($post_id, '_lt_referral_source', sanitize_text_field($data['referral_source']));
        if (isset($data['previous_experience'])) update_post_meta($post_id, '_lt_previous_experience', sanitize_text_field($data['previous_experience']));
        if (isset($data['additional_comments'])) update_post_meta($post_id, '_lt_additional_comments', sanitize_textarea_field($data['additional_comments']));
        
        // Terms acceptance
        if (isset($data['accept_rules'])) update_post_meta($post_id, '_lt_accept_rules', sanitize_text_field($data['accept_rules']));
        if (isset($data['accept_cancellation'])) update_post_meta($post_id, '_lt_accept_cancellation', sanitize_text_field($data['accept_cancellation']));
        if (isset($data['marketing_consent'])) update_post_meta($post_id, '_lt_marketing_consent', sanitize_text_field($data['marketing_consent']));
        if (isset($data['privacy_consent'])) update_post_meta($post_id, '_lt_privacy_consent', sanitize_text_field($data['privacy_consent']));
        
        // Handle logo upload if present
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $upload = wp_handle_upload($_FILES['logo'], array('test_form' => false));
            
            if (isset($upload['file'])) {
                $attachment_id = wp_insert_attachment(array(
                    'post_mime_type' => $upload['type'],
                    'post_title' => sanitize_file_name($upload['file']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                ), $upload['file'], $post_id);
                
                if ($attachment_id) {
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                    wp_update_attachment_metadata($attachment_id, $attach_data);
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }
        }
        
        // Handle multiple product photos
        if (isset($_FILES['product_photos']) && !empty($_FILES['product_photos']['name'][0])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $product_photo_ids = array();
            $files = $_FILES['product_photos'];
            
            foreach ($files['name'] as $key => $value) {
                if ($files['error'][$key] === UPLOAD_ERR_OK) {
                    $file = array(
                        'name' => $files['name'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error' => $files['error'][$key],
                        'size' => $files['size'][$key]
                    );
                    
                    $_FILES = array('upload' => $file);
                    $upload = wp_handle_upload($file, array('test_form' => false));
                    
                    if (isset($upload['file'])) {
                        $attachment_id = wp_insert_attachment(array(
                            'post_mime_type' => $upload['type'],
                            'post_title' => sanitize_file_name($upload['file']),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        ), $upload['file'], $post_id);
                        
                        if ($attachment_id) {
                            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                            wp_update_attachment_metadata($attachment_id, $attach_data);
                            $product_photo_ids[] = $attachment_id;
                        }
                    }
                }
            }
            
            if (!empty($product_photo_ids)) {
                update_post_meta($post_id, '_lt_product_photos', $product_photo_ids);
            }
        }
        
        // Document metadata is already saved above
        // PDFs will be attached to admin notification email (handled below)
        
        // Send notification email to vendor
        $subject = __('Application Received - Your Vendor Reference ID', 'tekram');
        $message = sprintf(
            __('Hi %s,

Thank you for submitting your vendor application. We have received your application and will review it shortly.

YOUR VENDOR REFERENCE ID: %s

IMPORTANT: Save this reference ID! You will need it to:
• Make bookings for markets
• Access your vendor dashboard
• Manage your market attendance

Once your application is approved, you can use this reference ID at:
%s

If you have any questions, please contact us.

Best regards,
%s', 'tekram'),
            $first_name,
            $vendor_reference,
            home_url('/booking-page/'),
            get_bloginfo('name')
        );
        
        $vendor_email_sent = self::send_vendor_email($email, $subject, $message);
        
        // Log if vendor email failed
        if (!$vendor_email_sent) {
            error_log('LT: Vendor confirmation email FAILED to send to: ' . $email);
        }
        
        // Send notification to admin with PDF attachments
        $admin_email = get_option('admin_email');
        $admin_subject = __('New Vendor Application', 'tekram');
        
        // Build document list
        $doc_list = array();
        foreach ($pdf_attachments as $field => $file_info) {
            $doc_list[] = '- ' . $file_info['label'] . ': ' . $file_info['name'];
        }
        $doc_list_text = !empty($doc_list) ? implode("\n", $doc_list) : '- No documents attached';
        
        $admin_message = sprintf(
            __('A new vendor application has been submitted.

Name: %s %s
Business: %s
Email: %s
Phone: %s
Reference ID: %s

Please review the application in the admin panel.

Attached documents:
%s', 'tekram'),
            $first_name,
            $last_name,
            $business_name,
            $email,
            $phone,
            $vendor_reference,
            $doc_list_text
        );
        
        // Prepare email attachments - copy temp files with .pdf extension
        $attachments = array();
        $temp_files_to_cleanup = array();
        
        foreach ($pdf_attachments as $file_info) {
            if (file_exists($file_info['path'])) {
                // Get file extension from original filename
                $ext = pathinfo($file_info['name'], PATHINFO_EXTENSION);
                if (empty($ext)) {
                    $ext = 'pdf'; // Default to PDF if no extension
                }
                
                // Create new temp file with proper extension
                $new_temp = $file_info['path'] . '.' . $ext;
                
                // Copy temp file to new temp file with extension
                if (@copy($file_info['path'], $new_temp)) {
                    $attachments[] = $new_temp;
                    $temp_files_to_cleanup[] = $new_temp;
                } else {
                    // Fallback: use original (won't have extension)
                    $attachments[] = $file_info['path'];
                }
            }
        }
        
        // Send email with attachments
        self::send_vendor_email($admin_email, $admin_subject, $admin_message, $attachments);
        
        // Clean up temp files we created
        foreach ($temp_files_to_cleanup as $temp_file) {
            if (file_exists($temp_file)) {
                @unlink($temp_file);
            }
        }
        
        return array(
            'success' => true,
            'message' => __('Application submitted successfully! Please check your email for your Vendor Reference ID.', 'tekram'),
            'post_id' => $post_id,
            'vendor_reference' => $vendor_reference
        );
    }
    
    /**
     * Generate unique vendor reference ID
     */
    private static function generate_vendor_reference() {
        global $wpdb;
        
        do {
            // Generate VEN-XXXXXXXX format (8 random digits)
            $reference = 'VEN-' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            
            // Check if it exists
            $exists = get_posts(array(
                'post_type' => 'lt_vendor',
                'meta_query' => array(
                    array(
                        'key' => '_lt_vendor_reference',
                        'value' => $reference,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1,
                'post_status' => 'any'
            ));
        } while (!empty($exists));
        
        return $reference;
    }
    
    /**
     * Get vendor by reference ID
     */
    public static function get_by_reference($reference) {
        $vendors = get_posts(array(
            'post_type' => 'lt_vendor',
            'meta_query' => array(
                array(
                    'key' => '_lt_vendor_reference',
                    'value' => sanitize_text_field($reference),
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'post_status' => 'publish' // Only approved vendors
        ));
        
        return !empty($vendors) ? $vendors[0] : false;
    }
    
    /**
     * Verify vendor by reference and email
     */
    public static function verify_vendor($reference, $email) {
        $vendor = self::get_by_reference($reference);
        
        if (!$vendor) {
            return false;
        }
        
        $vendor_email = get_post_meta($vendor->ID, '_lt_email', true);
        
        return (strtolower($vendor_email) === strtolower($email)) ? $vendor : false;
    }
    
    /**
     * Approve vendor
     */
    public static function approve($post_id) {
        $vendor_reference = get_post_meta($post_id, '_lt_vendor_reference', true);
        $email = get_post_meta($post_id, '_lt_email', true);
        $first_name = get_post_meta($post_id, '_lt_first_name', true);
        
        // Update post status
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish'
        ));
        
        // Update meta
        update_post_meta($post_id, '_lt_application_status', 'approved');
        update_post_meta($post_id, '_lt_approved_date', current_time('mysql'));
        
        // Send notification
        $subject = __('Application Approved - Start Booking!', 'tekram');
        $message = sprintf(
            __('Hi %s,

Congratulations! Your vendor application has been approved.

YOUR VENDOR REFERENCE ID: %s

You can now use this reference ID to:
✓ Make bookings for upcoming markets
✓ Access your vendor dashboard
✓ View your booking history

Booking Page: %s
Dashboard: %s

IMPORTANT: Save your reference ID! You will need it along with your email address to book markets and access your dashboard.

We look forward to seeing you at our markets!

Best regards,
%s', 'tekram'),
            $first_name,
            $vendor_reference,
            home_url('/booking-page/'),
            home_url('/dashboard/'),
            get_bloginfo('name')
        );
        
        return self::send_vendor_email($email, $subject, $message);
    }
    
    /**
     * Decline vendor
     */
    public static function decline($post_id, $reason = '') {
        $email = get_post_meta($post_id, '_lt_email', true);
        $first_name = get_post_meta($post_id, '_lt_first_name', true);
        
        // Update post status
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));
        
        // Update meta
        update_post_meta($post_id, '_lt_application_status', 'declined');
        update_post_meta($post_id, '_lt_decline_reason', $reason);
        update_post_meta($post_id, '_lt_declined_date', current_time('mysql'));
        
        // Send notification
        $subject = __('Application Status Update', 'tekram');
        $message = sprintf(
            __('Hi %s,

Thank you for your interest. Unfortunately, we are unable to approve your application at this time.

%s

If you have any questions, please contact us.

Best regards,
%s', 'tekram'),
            $first_name,
            $reason ? "\nReason: " . $reason : '',
            get_bloginfo('name')
        );
        
        return self::send_vendor_email($email, $subject, $message);
    }
    
    /**
     * Get vendor by user ID
     */
    public static function get_by_user_id($user_id) {
        $args = array(
            'post_type' => 'lt_vendor',
            'post_status' => 'any',
            'meta_key' => '_lt_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        );
        
        $posts = get_posts($args);
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Get vendor data
     */
    public static function get_data($post_id) {
        $data = array(
            'id' => $post_id,
            'vendor_reference' => get_post_meta($post_id, '_lt_vendor_reference', true),
            'first_name' => get_post_meta($post_id, '_lt_first_name', true),
            'last_name' => get_post_meta($post_id, '_lt_last_name', true),
            'email' => get_post_meta($post_id, '_lt_email', true),
            'phone' => get_post_meta($post_id, '_lt_phone', true),
            'business_name' => get_post_meta($post_id, '_lt_business_name', true),
            'abn' => get_post_meta($post_id, '_lt_abn', true),
            'address' => get_post_meta($post_id, '_lt_address', true),
            'city' => get_post_meta($post_id, '_lt_city', true),
            'state' => get_post_meta($post_id, '_lt_state', true),
            'postcode' => get_post_meta($post_id, '_lt_postcode', true),
            'products_description' => get_post_meta($post_id, '_lt_products_description', true),
            'website' => get_post_meta($post_id, '_lt_website', true),
            'facebook' => get_post_meta($post_id, '_lt_facebook', true),
            'instagram' => get_post_meta($post_id, '_lt_instagram', true),
            'application_status' => get_post_meta($post_id, '_lt_application_status', true),
            'application_date' => get_post_meta($post_id, '_lt_application_date', true),
            
            // Emergency contact
            'emergency_name' => get_post_meta($post_id, '_lt_emergency_name', true),
            'emergency_phone' => get_post_meta($post_id, '_lt_emergency_phone', true),
            'emergency_relationship' => get_post_meta($post_id, '_lt_emergency_relationship', true),
            
            // Product information
            'product_categories' => get_post_meta($post_id, '_lt_product_categories', true),
            'product_type' => get_post_meta($post_id, '_lt_product_type', true),
            'products_full_description' => get_post_meta($post_id, '_lt_products_full_description', true),
            'country_of_origin' => get_post_meta($post_id, '_lt_country_of_origin', true),
            'price_range' => get_post_meta($post_id, '_lt_price_range', true),
            'sustainability_practices' => get_post_meta($post_id, '_lt_sustainability_practices', true),
            'accessibility_practices' => get_post_meta($post_id, '_lt_accessibility_practices', true),
            
            // Stall requirements
            'stall_type' => get_post_meta($post_id, '_lt_stall_type', true),
            'require_electricity' => get_post_meta($post_id, '_lt_require_electricity', true),
            'require_water' => get_post_meta($post_id, '_lt_require_water', true),
            'tables_needed' => get_post_meta($post_id, '_lt_tables_needed', true),
            'chairs_needed' => get_post_meta($post_id, '_lt_chairs_needed', true),
            'vehicle_rego' => get_post_meta($post_id, '_lt_vehicle_rego', true),
            'special_requirements' => get_post_meta($post_id, '_lt_special_requirements', true),
            
            // Power requirements
            'power_type' => get_post_meta($post_id, '_lt_power_type', true),
            'power_usage' => get_post_meta($post_id, '_lt_power_usage', true),
            'power_equipment' => get_post_meta($post_id, '_lt_power_equipment', true),
            'extension_cord' => get_post_meta($post_id, '_lt_extension_cord', true),
            
            // Document details
            'insurance_policy_number' => get_post_meta($post_id, '_lt_insurance_policy_number', true),
            'insurance_issuer' => get_post_meta($post_id, '_lt_insurance_issuer', true),
            'insurance_expiry' => get_post_meta($post_id, '_lt_insurance_expiry', true),
            
            'food_licence_number' => get_post_meta($post_id, '_lt_food_licence_number', true),
            'food_licence_issuer' => get_post_meta($post_id, '_lt_food_licence_issuer', true),
            'food_licence_expiry' => get_post_meta($post_id, '_lt_food_licence_expiry', true),
            
            'food_handling_number' => get_post_meta($post_id, '_lt_food_handling_number', true),
            'food_handling_issuer' => get_post_meta($post_id, '_lt_food_handling_issuer', true),
            'food_handling_expiry' => get_post_meta($post_id, '_lt_food_handling_expiry', true),
            
            'food_safety_number' => get_post_meta($post_id, '_lt_food_safety_number', true),
            'food_safety_issuer' => get_post_meta($post_id, '_lt_food_safety_issuer', true),
            'food_safety_expiry' => get_post_meta($post_id, '_lt_food_safety_expiry', true),
            
            'business_licence_number' => get_post_meta($post_id, '_lt_business_licence_number', true),
            'business_licence_issuer' => get_post_meta($post_id, '_lt_business_licence_issuer', true),
            'business_licence_expiry' => get_post_meta($post_id, '_lt_business_licence_expiry', true),
            
            // Additional information
            'referral_source' => get_post_meta($post_id, '_lt_referral_source', true),
            'previous_experience' => get_post_meta($post_id, '_lt_previous_experience', true),
            'additional_comments' => get_post_meta($post_id, '_lt_additional_comments', true),
            
            // Terms
            'accept_rules' => get_post_meta($post_id, '_lt_accept_rules', true),
            'accept_cancellation' => get_post_meta($post_id, '_lt_accept_cancellation', true),
            'marketing_consent' => get_post_meta($post_id, '_lt_marketing_consent', true),
            'privacy_consent' => get_post_meta($post_id, '_lt_privacy_consent', true),
        );
        
        return $data;
    }
    
    /**
     * Send email with proper headers
     */
    private static function send_vendor_email($to, $subject, $message, $attachments = array()) {
        $from_name = get_option('lt_email_from_name', get_bloginfo('name'));
        $from_email = get_option('lt_email_from_address', get_option('admin_email'));
        
        // Sanitize and validate from email
        $from_email = sanitize_email($from_email);
        if (!is_email($from_email)) {
            $from_email = get_option('admin_email');
        }
        
        // Build comprehensive headers to prevent spam
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'X-Mailer: WordPress/' . get_bloginfo('version')
        );
        
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }
    
    /**
     * Get list of attached documents for email
     */
    
    /**
     * Update vendor data
     */
    public static function update($post_id, $data) {
        foreach ($data as $key => $value) {
            update_post_meta($post_id, '_lt_' . $key, sanitize_text_field($value));
        }
        
        return true;
    }
    
    /**
     * Get all vendors
     */
    public static function get_all($status = 'publish') {
        $args = array(
            'post_type' => 'lt_vendor',
            'post_status' => $status,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
}



