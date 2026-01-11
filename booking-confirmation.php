<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Pending Payment</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background-color: #0073aa; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px;">Booking Confirmation Pending Payment</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; font-size: 16px; color: #333333;">Hi <?php echo esc_html($first_name); ?>,</p>
                            
                            <p style="margin: 0 0 20px; font-size: 16px; color: #333333;">Thank you for your booking! Here are your booking request details:</p>
                            
                            <table width="100%" cellpadding="10" cellspacing="0" style="margin: 20px 0; border: 1px solid #e5e5e5; border-radius: 4px;">
                                <tr>
                                    <td style="border-bottom: 1px solid #e5e5e5; background-color: #f9f9f9;"><strong>Booking Reference:</strong></td>
                                    <td style="border-bottom: 1px solid #e5e5e5;"><?php echo esc_html($booking_reference); ?></td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #e5e5e5; background-color: #f9f9f9;"><strong>Event:</strong></td>
                                    <td style="border-bottom: 1px solid #e5e5e5;"><?php echo esc_html($event_name); ?></td>
                                </tr>
                                <tr>
                                    <td style="border-bottom: 1px solid #e5e5e5; background-color: #f9f9f9;"><strong>Date:</strong></td>
                                    <td style="border-bottom: 1px solid #e5e5e5;"><?php echo esc_html($event_date); ?></td>
                                </tr>
                                <?php if (!empty($site_name)) { ?>
                                <tr>
                                    <td style="border-bottom: 1px solid #e5e5e5; background-color: #f9f9f9;"><strong>Site:</strong></td>
                                    <td style="border-bottom: 1px solid #e5e5e5;"><?php echo esc_html($site_name); ?></td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <td style="border-bottom: 1px solid #e5e5e5; background-color: #f9f9f9;"><strong>Amount:</strong></td>
                                    <td style="border-bottom: 1px solid #e5e5e5;"><?php echo esc_html($amount); ?></td>
                                </tr>
                                <tr>
                                    <td style="background-color: #f9f9f9;"><strong>Payment Status:</strong></td>
                                    <td><?php echo ucfirst(esc_html($payment_status)); ?></td>
                                </tr>
                            </table>
                            
                            <?php if ($payment_status !== 'paid') { ?>
                            <div style="margin: 30px 0; padding: 15px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
                                <p style="margin: 0; color: #856404;"><strong>Payment Required:</strong> Please complete your payment here to confirm your booking.</p>
                            </div>
                            <?php } ?>
                            
                            <p style="margin: 20px 0 0; font-size: 16px; color: #333333;">If you have any questions, please don't hesitate to contact us.</p>
                            
                            <p style="margin: 20px 0 0; font-size: 16px; color: #333333;">
                                Best regards,<br>
                                <strong><?php echo esc_html(get_bloginfo('name')); ?></strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; text-align: center; background-color: #f9f9f9; border-radius: 0 0 8px 8px; color: #666666; font-size: 14px;">
                            <p style="margin: 0;">© <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
