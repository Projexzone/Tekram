<?php
/**
 * Notifications Class
 * Handles all notification operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Notifications {
    
    /**
     * Send booking confirmation
     */
    public static function send_booking_confirmation($booking_id) {
        $details = LT_Booking::get_details($booking_id);
        
        if (!$details) {
            return false;
        }
        
        $booking = $details['booking'];
        $event = $details['event'];
        $vendor = $details['vendor'];
        
        $to = $vendor['email'];
        $subject = __('Booking Confirmation', 'tekram');
        
        $message = self::get_template('booking-confirmation', array(
            'first_name' => $vendor['first_name'],
            'booking_reference' => $booking->booking_reference,
            'event_name' => $event['title'],
            'event_date' => date('F j, Y', strtotime($booking->booking_date)),
            'site_name' => $details['site_name'],
            'amount' => self::format_currency($booking->amount),
            'payment_status' => $booking->payment_status,
        ));
        
        return self::send_email($to, $subject, $message);
    }
    
    /**
     * Send booking status update
     */
    public static function send_booking_status_update($booking_id, $status) {
        $details = LT_Booking::get_details($booking_id);
        
        if (!$details) {
            return false;
        }
        
        $booking = $details['booking'];
        $vendor = $details['vendor'];
        
        $to = $vendor['email'];
        $subject = __('Booking Status Update', 'tekram');
        
        $message = self::get_template('booking-status-update', array(
            'first_name' => $vendor['first_name'],
            'booking_reference' => $booking->booking_reference,
            'status' => ucfirst($status),
        ));
        
        return self::send_email($to, $subject, $message);
    }
    
    /**
     * Send booking cancellation
     */
    public static function send_booking_cancellation($booking_id) {
        $details = LT_Booking::get_details($booking_id);
        
        if (!$details) {
            return false;
        }
        
        $booking = $details['booking'];
        $vendor = $details['vendor'];
        
        $to = $vendor['email'];
        $subject = __('Booking Cancelled', 'tekram');
        
        $message = self::get_template('booking-cancellation', array(
            'first_name' => $vendor['first_name'],
            'booking_reference' => $booking->booking_reference,
        ));
        
        return self::send_email($to, $subject, $message);
    }
    
    /**
     * Send payment confirmation
     */
    public static function send_payment_confirmation($payment_id) {
        $payment = LT_Payment::get_details($payment_id);
        
        if (!$payment) {
            return false;
        }
        
        $details = LT_Booking::get_details($payment->booking_id);
        $booking = $details['booking'];
        $vendor = $details['vendor'];
        
        $to = $vendor['email'];
        $subject = __('Payment Confirmation', 'tekram');
        
        $message = self::get_template('payment-confirmation', array(
            'first_name' => $vendor['first_name'],
            'amount' => self::format_currency($payment->amount),
            'booking_reference' => $booking->booking_reference,
            'payment_method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
            'transaction_id' => $payment->transaction_id,
            'payment_date' => date('F j, Y', strtotime($payment->payment_date)),
        ));
        
        return self::send_email($to, $subject, $message);
    }
    
    /**
     * Send reminder
     */
    public static function send_reminder($booking_id, $type = 'upcoming_event') {
        $details = LT_Booking::get_details($booking_id);
        
        if (!$details) {
            return false;
        }
        
        $booking = $details['booking'];
        $event = $details['event'];
        $vendor = $details['vendor'];
        
        $to = $vendor['email'];
        $subject = __('Event Reminder', 'tekram');
        
        $message = self::get_template('event-reminder', array(
            'first_name' => $vendor['first_name'],
            'event_name' => $event['title'],
            'event_date' => date('F j, Y', strtotime($booking->booking_date)),
            'event_time' => $event['start_time'],
            'location' => $event['location'],
        ));
        
        return self::send_email($to, $subject, $message);
    }
    
    /**
     * Get email template
     */
    private static function get_template($template_name, $vars = array()) {
        $template_file = LT_PLUGIN_DIR . 'templates/emails/' . $template_name . '.php';
        
        if (file_exists($template_file)) {
            ob_start();
            extract($vars);
            include $template_file;
            return ob_get_clean();
        }
        
        // Fallback to default template
        return self::get_default_template($template_name, $vars);
    }
    
    /**
     * Get default email template
     */
    private static function get_default_template($template_name, $vars) {
        $site_name = get_bloginfo('name');
        
        $message = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6;">';
        $message .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;">';
        $message .= '<div style="background: white; padding: 30px; border-radius: 5px;">';
        
        switch ($template_name) {
            case 'booking-confirmation':
                $message .= '<h2>Booking Confirmation</h2>';
                $message .= '<p>Hi ' . esc_html($vars['first_name']) . ',</p>';
                $message .= '<p>Thank you for your booking! Here are your booking details:</p>';
                $message .= '<ul>';
                $message .= '<li><strong>Booking Reference:</strong> ' . esc_html($vars['booking_reference']) . '</li>';
                $message .= '<li><strong>Event:</strong> ' . esc_html($vars['event_name']) . '</li>';
                $message .= '<li><strong>Date:</strong> ' . esc_html($vars['event_date']) . '</li>';
                if ($vars['site_name']) {
                    $message .= '<li><strong>Site:</strong> ' . esc_html($vars['site_name']) . '</li>';
                }
                $message .= '<li><strong>Amount:</strong> ' . esc_html($vars['amount']) . '</li>';
                $message .= '<li><strong>Payment Status:</strong> ' . esc_html(ucfirst($vars['payment_status'])) . '</li>';
                $message .= '</ul>';
                break;
                
            case 'payment-confirmation':
                $message .= '<h2>Payment Confirmation</h2>';
                $message .= '<p>Hi ' . esc_html($vars['first_name']) . ',</p>';
                $message .= '<p>We have received your payment. Thank you!</p>';
                $message .= '<ul>';
                $message .= '<li><strong>Amount:</strong> ' . esc_html($vars['amount']) . '</li>';
                $message .= '<li><strong>Booking Reference:</strong> ' . esc_html($vars['booking_reference']) . '</li>';
                $message .= '<li><strong>Payment Method:</strong> ' . esc_html($vars['payment_method']) . '</li>';
                $message .= '<li><strong>Transaction ID:</strong> ' . esc_html($vars['transaction_id']) . '</li>';
                $message .= '<li><strong>Date:</strong> ' . esc_html($vars['payment_date']) . '</li>';
                $message .= '</ul>';
                break;
        }
        
        $message .= '<p>If you have any questions, please contact us.</p>';
        $message .= '<p>Best regards,<br>' . esc_html($site_name) . '</p>';
        $message .= '</div>';
        $message .= '</div>';
        $message .= '</body></html>';
        
        return $message;
    }
    
    /**
     * Send email
     */
    private static function send_email($to, $subject, $message) {
        $from_name = get_option('lt_email_from_name', get_bloginfo('name'));
        $from_email = get_option('lt_email_from_address', get_option('admin_email'));
        
        // Sanitize and validate from email
        $from_email = sanitize_email($from_email);
        if (!is_email($from_email)) {
            $from_email = get_option('admin_email');
        }
        
        // Build comprehensive headers to prevent spam
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'X-Mailer: WordPress/' . get_bloginfo('version')
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Format currency
     */
    private static function format_currency($amount) {
        $symbol = get_option('lt_currency_symbol', '$');
        return $symbol . number_format($amount, 2);
    }
}
