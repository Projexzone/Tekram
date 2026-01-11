<?php
/**
 * Document Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Documents {
    
    /**
     * Create documents table
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lt_documents';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vendor_id bigint(20) NOT NULL,
            document_type varchar(50) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_url varchar(500) NOT NULL,
            expiry_date date DEFAULT NULL,
            status enum('pending','approved','expired','rejected') DEFAULT 'pending',
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            approved_at datetime DEFAULT NULL,
            notes text,
            PRIMARY KEY  (id),
            KEY vendor_id (vendor_id),
            KEY document_type (document_type),
            KEY status (status),
            KEY expiry_date (expiry_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Upload document
     */
    public static function upload_document($vendor_id, $document_type, $expiry_date = null) {
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            return array('success' => false, 'message' => __('No file uploaded.', 'tekram'));
        }
        
        $file = $_FILES['document'];
        
        // Validate file type
        $allowed_types = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx');
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            return array('success' => false, 'message' => __('Invalid file type.', 'tekram'));
        }
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return array('success' => false, 'message' => __('File too large. Maximum 5MB.', 'tekram'));
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            return array('success' => false, 'message' => $upload['error']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'lt_documents';
        
        $result = $wpdb->insert(
            $table,
            array(
                'vendor_id' => $vendor_id,
                'document_type' => $document_type,
                'file_name' => basename($upload['file']),
                'file_url' => $upload['url'],
                'expiry_date' => $expiry_date,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return array(
                'success' => true,
                'message' => __('Document uploaded successfully.', 'tekram'),
                'document_id' => $wpdb->insert_id
            );
        }
        
        return array('success' => false, 'message' => __('Failed to save document.', 'tekram'));
    }
    
    /**
     * Get vendor documents
     */
    public static function get_vendor_documents($vendor_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_documents';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE vendor_id = %d ORDER BY uploaded_at DESC",
            $vendor_id
        ));
    }
    
    /**
     * Get document by ID
     */
    public static function get_document($document_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_documents';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $document_id
        ));
    }
    
    /**
     * Approve document
     */
    public static function approve_document($document_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_documents';
        
        return $wpdb->update(
            $table,
            array(
                'status' => 'approved',
                'approved_at' => current_time('mysql')
            ),
            array('id' => $document_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Reject document
     */
    public static function reject_document($document_id, $notes = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_documents';
        
        return $wpdb->update(
            $table,
            array(
                'status' => 'rejected',
                'notes' => $notes
            ),
            array('id' => $document_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Check expiring documents
     */
    public static function check_expiring_documents() {
        global $wpdb;
        $table = $wpdb->prefix . 'lt_documents';
        
        // Get documents expiring in 30 days
        $expiring = $wpdb->get_results(
            "SELECT * FROM $table 
             WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND expiry_date >= CURDATE()
             AND status = 'approved'"
        );
        
        foreach ($expiring as $doc) {
            self::send_expiry_reminder($doc->id);
        }
        
        // Mark expired documents
        $wpdb->query(
            "UPDATE $table SET status = 'expired' 
             WHERE expiry_date < CURDATE() AND status = 'approved'"
        );
    }
    
    /**
     * Send expiry reminder
     */
    private static function send_expiry_reminder($document_id) {
        $doc = self::get_document($document_id);
        $vendor_data = LT_Vendor::get_data($doc->vendor_id);
        
        $days_until_expiry = floor((strtotime($doc->expiry_date) - time()) / (60 * 60 * 24));
        
        $subject = __('Document Expiring Soon', 'tekram');
        $message = sprintf(
            __('Hi %s,

Your %s is expiring soon!

Expiry Date: %s (%d days from now)

Please upload a renewed document to avoid any issues with future bookings.

Best regards,
%s', 'tekram'),
            $vendor_data['first_name'],
            ucfirst(str_replace('_', ' ', $doc->document_type)),
            date('F j, Y', strtotime($doc->expiry_date)),
            $days_until_expiry,
            get_bloginfo('name')
        );
        
        wp_mail($vendor_data['email'], $subject, $message);
    }
    
    /**
     * Get document types
     */
    public static function get_document_types() {
        return array(
            'public_liability_insurance' => __('Public Liability Insurance', 'tekram'),
            'food_safety_certificate' => __('Food Safety Certificate', 'tekram'),
            'business_license' => __('Business License', 'tekram'),
            'product_liability_insurance' => __('Product Liability Insurance', 'tekram'),
            'covid_vaccination' => __('COVID-19 Vaccination Certificate', 'tekram'),
            'other' => __('Other', 'tekram')
        );
    }
    
    /**
     * Check if vendor has valid documents
     */
    public static function has_valid_documents($vendor_id, $required_types = array()) {
        if (empty($required_types)) {
            return true; // No documents required
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'lt_documents';
        
        foreach ($required_types as $type) {
            $valid = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table 
                 WHERE vendor_id = %d 
                 AND document_type = %s 
                 AND status = 'approved'
                 AND (expiry_date IS NULL OR expiry_date >= CURDATE())",
                $vendor_id, $type
            ));
            
            if (!$valid) {
                return false;
            }
        }
        
        return true;
    }
}



