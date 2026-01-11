<?php
/**
 * Payment Class
 * Handles all payment operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Payment {
    
    /**
     * Process payment
     */
    public static function process($data) {
        $booking_id = intval($data['booking_id']);
        $payment_method = sanitize_text_field($data['payment_method']);
        $amount = floatval($data['amount']);
        
        // Get booking
        $booking = LT_Database::get_booking($booking_id);
        
        if (!$booking) {
            return array(
                'success' => false,
                'message' => __('Booking not found.', 'tekram')
            );
        }
        
        // Validate amount
        $remaining = $booking->amount - $booking->paid_amount;
        if ($amount > $remaining) {
            return array(
                'success' => false,
                'message' => __('Payment amount exceeds remaining balance.', 'tekram')
            );
        }
        
        $result = array('success' => false);
        
        // Process based on payment method
        switch ($payment_method) {
            case 'stripe':
                $result = self::process_stripe($data, $booking);
                break;
            case 'paypal':
                $result = self::process_paypal($data, $booking);
                break;
            case 'cash':
            case 'check':
            case 'bank_transfer':
                $result = self::process_manual($data, $booking);
                break;
            default:
                $result = array(
                    'success' => false,
                    'message' => __('Invalid payment method.', 'tekram')
                );
        }
        
        return $result;
    }
    
    /**
     * Process Stripe payment
     */
    private static function process_stripe($data, $booking) {
        $stripe_enabled = get_option('lt_enable_stripe');
        
        if (!$stripe_enabled) {
            return array(
                'success' => false,
                'message' => __('Stripe payments are not enabled.', 'tekram')
            );
        }
        
        $stripe_secret = get_option('lt_stripe_secret_key');
        
        if (!$stripe_secret) {
            return array(
                'success' => false,
                'message' => __('Stripe is not configured.', 'tekram')
            );
        }
        
        try {
            // Include Stripe library (you'll need to add this via Composer or manually)
            // require_once LT_PLUGIN_DIR . 'vendor/stripe/stripe-php/init.php';
            
            // \Stripe\Stripe::setApiKey($stripe_secret);
            
            // Create charge
            // $charge = \Stripe\Charge::create([
            //     'amount' => intval($data['amount'] * 100), // Convert to cents
            //     'currency' => strtolower(get_option('lt_currency', 'usd')),
            //     'source' => $data['stripe_token'],
            //     'description' => 'Booking #' . $booking->booking_reference,
            // ]);
            
            // For now, return placeholder
            $transaction_id = 'stripe_' . uniqid();
            
            return self::record_payment($booking->id, array(
                'amount' => floatval($data['amount']),
                'payment_method' => 'stripe',
                'transaction_id' => $transaction_id,
                'status' => 'completed'
            ));
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Process PayPal payment
     */
    private static function process_paypal($data, $booking) {
        $paypal_enabled = get_option('lt_enable_paypal');
        
        if (!$paypal_enabled) {
            return array(
                'success' => false,
                'message' => __('PayPal payments are not enabled.', 'tekram')
            );
        }
        
        // PayPal integration would go here
        // For now, return placeholder
        $transaction_id = 'paypal_' . uniqid();
        
        return self::record_payment($booking->id, array(
            'amount' => floatval($data['amount']),
            'payment_method' => 'paypal',
            'transaction_id' => $transaction_id,
            'status' => 'completed'
        ));
    }
    
    /**
     * Process manual payment
     */
    private static function process_manual($data, $booking) {
        // Manual payments are recorded directly
        return self::record_payment($booking->id, array(
            'amount' => floatval($data['amount']),
            'payment_method' => $data['payment_method'],
            'transaction_id' => sanitize_text_field($data['transaction_reference'] ?? ''),
            'status' => 'completed',
            'notes' => sanitize_textarea_field($data['notes'] ?? '')
        ));
    }
    
    /**
     * Record payment
     */
    public static function record_payment($booking_id, $data) {
        $booking = LT_Database::get_booking($booking_id);
        
        if (!$booking) {
            return array(
                'success' => false,
                'message' => __('Booking not found.', 'tekram')
            );
        }
        
        // Insert payment record
        $payment_data = array(
            'booking_id' => $booking_id,
            'vendor_id' => $booking->vendor_id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'] ?? '',
            'status' => $data['status'],
            'payment_date' => current_time('mysql'),
            'notes' => $data['notes'] ?? ''
        );
        
        $payment_id = LT_Database::insert_payment($payment_data);
        
        if (!$payment_id) {
            return array(
                'success' => false,
                'message' => __('Failed to record payment.', 'tekram')
            );
        }
        
        // Update booking
        $new_paid_amount = $booking->paid_amount + $data['amount'];
        $payment_status = ($new_paid_amount >= $booking->amount) ? 'paid' : 'partial';
        
        LT_Database::update_booking($booking_id, array(
            'paid_amount' => $new_paid_amount,
            'payment_status' => $payment_status
        ));
        
        // If fully paid, update booking status to confirmed
        if ($payment_status === 'paid' && $booking->status === 'pending') {
            LT_Database::update_booking($booking_id, array('status' => 'confirmed'));
        }
        
        // Send notification
        LT_Notifications::send_payment_confirmation($payment_id);
        
        return array(
            'success' => true,
            'message' => __('Payment processed successfully.', 'tekram'),
            'payment_id' => $payment_id
        );
    }
    
    /**
     * Process refund
     */
    public static function process_refund($booking_id) {
        $booking = LT_Database::get_booking($booking_id);
        
        if (!$booking || $booking->paid_amount <= 0) {
            return array(
                'success' => false,
                'message' => __('No payment to refund.', 'tekram')
            );
        }
        
        // Get payments
        $payments = LT_Database::get_booking_payments($booking_id);
        
        // Process refund for each payment
        foreach ($payments as $payment) {
            if ($payment->status === 'completed') {
                // In a real implementation, you would process actual refunds here
                // For now, just record the refund
                self::record_payment($booking_id, array(
                    'amount' => -$payment->amount,
                    'payment_method' => $payment->payment_method . '_refund',
                    'transaction_id' => 'refund_' . $payment->transaction_id,
                    'status' => 'refunded',
                    'notes' => 'Refund for cancelled booking'
                ));
            }
        }
        
        return array('success' => true);
    }
    
    /**
     * Get payment details
     */
    public static function get_details($payment_id) {
        return LT_Database::get_payment($payment_id);
    }
    
    /**
     * Get booking payments
     */
    public static function get_booking_payments($booking_id) {
        return LT_Database::get_booking_payments($booking_id);
    }
}



