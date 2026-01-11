<?php
/**
 * Shortcodes Class
 * Handles all shortcodes for frontend display
 */

if (!defined('ABSPATH')) {
    exit;
}

class LT_Shortcodes {
    
    public static function init() {
        add_shortcode('lt_application_form', array(__CLASS__, 'application_form'));
        add_shortcode('lt_booking_form', array(__CLASS__, 'booking_form'));
        add_shortcode('lt_vendor_dashboard', array(__CLASS__, 'vendor_dashboard'));
        add_shortcode('lt_event_list', array(__CLASS__, 'event_list'));
        add_shortcode('lt_sellers', array(__CLASS__, 'sellers_directory'));
    }
    
    /**
     * Vendor Application Form
     * Usage: [lt_application_form]
     */
    public static function application_form($atts) {
        // Check if coming from successful submission
        if (isset($_GET['application']) && $_GET['application'] === 'submitted') {
            ?>
            <div class="lt-application-form-container">
                <div style="text-align:center;padding:60px 20px;">
                    <div style="font-size:80px;margin-bottom:30px;">✅</div>
                    <h2 style="color:#28a745;font-size:32px;margin-bottom:20px;">Application Submitted Successfully!</h2>
                    <p style="font-size:18px;margin-bottom:15px;">Thank you for your application!</p>
                    <p style="font-size:16px;color:#666;margin-bottom:30px;">
                        We have received your vendor application and sent a confirmation email with your Vendor Reference ID.<br>
                        Please check your email (including spam folder) for your reference number.<br><br>
                        We will review your application and contact you shortly.
                    </p>
                    <a href="<?php echo home_url(); ?>" class="lt-button smp-button-primary" style="display:inline-block;padding:15px 30px;background:#28a745;color:white;text-decoration:none;border-radius:4px;font-size:16px;">Return to Home</a>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lt_submit_application'])) {
            // Verify nonce
            if (!isset($_POST['lt_application_nonce']) || !wp_verify_nonce($_POST['lt_application_nonce'], 'lt_application_form')) {
                wp_die('Security check failed. Please go back and try again.');
            }
            
            // Process the application
            $result = LT_Vendor::create_application($_POST);
            
            if (isset($result['success']) && $result['success'] === true) {
                // Redirect to same page with success parameter
                wp_redirect(add_query_arg('application', 'submitted', $_SERVER['REQUEST_URI']));
                exit;
            } else {
                // Show error and die
                wp_die('Error: ' . (isset($result['message']) ? $result['message'] : 'An error occurred. Please try again.'));
            }
        }
        
        // Show the form
        ob_start();
        ?>
        <div class="lt-application-form-container">
            <h2><?php _e('Vendor Application Form', 'tekram'); ?></h2>
            
            <form id="lt-application-form" class="lt-form" method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field('lt_application_form', 'lt_application_nonce'); ?>
                <div class="lt-form-section">
                    <h3><?php _e('Personal Information', 'tekram'); ?></h3>
                    
                    <div class="lt-form-row">
                        <div class="lt-form-field smp-field-half">
                            <label for="first_name"><?php _e('First Name', 'tekram'); ?> <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" required placeholder="Enter your first name">
                        </div>
                        
                        <div class="lt-form-field smp-field-half">
                            <label for="last_name"><?php _e('Last Name', 'tekram'); ?> <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" required placeholder="Enter your last name">
                        </div>
                    </div>
                    
                    <div class="lt-form-row">
                        <div class="lt-form-field smp-field-half">
                            <label for="email"><?php _e('Email Address', 'tekram'); ?> <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required placeholder="your.email@example.com">
                        </div>
                        
                        <div class="lt-form-field smp-field-half">
                            <label for="phone"><?php _e('Phone Number', 'tekram'); ?> <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required placeholder="(123) 456-7890">
                        </div>
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Business Information', 'tekram'); ?></h3>
                    
                    <div class="lt-form-field">
                        <label for="business_name"><?php _e('Business Name', 'tekram'); ?></label>
                        <input type="text" id="business_name" name="business_name" placeholder="Your Business Name">
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="abn"><?php _e('ABN / Business Number', 'tekram'); ?></label>
                        <input type="text" id="abn" name="abn" placeholder="12 345 678 901">
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="products_description"><?php _e('Product/Service Description', 'tekram'); ?> <span class="required">*</span></label>
                        <textarea id="products_description" name="products_description" rows="4" required placeholder="Describe what you sell or the services you provide..."></textarea>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="logo"><?php _e('Business Logo/Photo', 'tekram'); ?></label>
                        <input type="file" id="logo" name="logo" accept="image/*">
                        <p class="lt-field-description"><?php _e('Upload your business logo or a photo of your products (JPG, PNG, max 2MB)', 'tekram'); ?></p>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="website"><?php _e('Website URL', 'tekram'); ?></label>
                        <input type="url" id="website" name="website" placeholder="https://yourwebsite.com">
                    </div>
                    
                    <div class="lt-form-row">
                        <div class="lt-form-field lt-field-half">
                            <label for="facebook"><?php _e('Facebook Page', 'tekram'); ?></label>
                            <input type="url" id="facebook" name="facebook" placeholder="https://facebook.com/yourbusiness">
                        </div>
                        
                        <div class="lt-form-field lt-field-half">
                            <label for="instagram"><?php _e('Instagram Profile', 'tekram'); ?></label>
                            <input type="url" id="instagram" name="instagram" placeholder="https://instagram.com/yourbusiness">
                        </div>
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Address', 'tekram'); ?></h3>
                    
                    <div class="lt-form-field">
                        <label for="address"><?php _e('Street Address', 'tekram'); ?></label>
                        <textarea id="address" name="address" rows="2" placeholder="123 Main Street"></textarea>
                    </div>
                    
                    <div class="lt-form-row">
                        <div class="lt-form-field smp-field-third">
                            <label for="city"><?php _e('City', 'tekram'); ?></label>
                            <input type="text" id="city" name="city" placeholder="City">
                        </div>
                        
                        <div class="lt-form-field smp-field-third">
                            <label for="state"><?php _e('State/Province', 'tekram'); ?></label>
                            <input type="text" id="state" name="state" placeholder="State">
                        </div>
                        
                        <div class="lt-form-field smp-field-third">
                            <label for="postcode"><?php _e('Postcode', 'tekram'); ?></label>
                            <input type="text" id="postcode" name="postcode" placeholder="12345">
                        </div>
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Emergency Contact', 'tekram'); ?></h3>
                    
                    <div class="lt-form-row">
                        <div class="lt-form-field smp-field-half">
                            <label for="emergency_name"><?php _e('Emergency Contact Name', 'tekram'); ?> <span class="required">*</span></label>
                            <input type="text" id="emergency_name" name="emergency_name" required placeholder="Full Name">
                        </div>
                        
                        <div class="lt-form-field smp-field-half">
                            <label for="emergency_phone"><?php _e('Emergency Contact Phone', 'tekram'); ?> <span class="required">*</span></label>
                            <input type="tel" id="emergency_phone" name="emergency_phone" required placeholder="(123) 456-7890">
                        </div>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="emergency_relationship"><?php _e('Relationship', 'tekram'); ?></label>
                        <input type="text" id="emergency_relationship" name="emergency_relationship" placeholder="Spouse, Parent, Sibling, Friend, etc.">
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Product Information', 'tekram'); ?></h3>
                    
                    <div class="lt-form-field">
                        <label><?php _e('Product Categories', 'tekram'); ?> <span class="required">*</span></label>
                        <p class="lt-field-description"><?php _e('Select all that apply', 'tekram'); ?></p>
                        <div class="lt-checkbox-group">
                            <label><input type="checkbox" name="product_categories[]" value="food_beverage"> <?php _e('Food / Beverage', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_categories[]" value="take_home_food"> <?php _e('Take home food', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_categories[]" value="health_wellbeing"> <?php _e('Health & Wellbeing', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_categories[]" value="arts_crafts"> <?php _e('Arts / Crafts', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_categories[]" value="jewellery"> <?php _e('Jewellery', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_categories[]" value="fashion_accessories"> <?php _e('Fashion & Accessories', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_categories[]" value="homewares"> <?php _e('Homewares', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_categories[]" value="promotional_charity"> <?php _e('Promotional or Charity', 'tekram'); ?></label>
                        </div>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="product_type"><?php _e('Type of products', 'tekram'); ?> <span class="required">*</span></label>
                        <p class="lt-field-description"><?php _e('Select all that apply', 'tekram'); ?></p>
                        <div class="lt-checkbox-group">
                            <label><input type="checkbox" name="product_type[]" value="handmade_by_me"> <?php _e('Handmade by me', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_type[]" value="service_by_me"> <?php _e('A service provided by me', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_type[]" value="grown_by_me"> <?php _e('Grown by me', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_type[]" value="designed_made_australia"> <?php _e('Designed by me and made in Australia', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_type[]" value="designed_made_overseas"> <?php _e('Designed by me and made overseas', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_type[]" value="australian_supplier"> <?php _e('Purchased from an Australian supplier', 'tekram'); ?></label><br>
                            <label><input type="checkbox" name="product_type[]" value="imported"> <?php _e('Imported goods', 'tekram'); ?></label>
                        </div>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="products_full_description"><?php _e('Please give a full description of the goods and/or services available at your stall', 'tekram'); ?> <span class="required">*</span></label>
                        <textarea id="products_full_description" name="products_full_description" rows="5" required placeholder="Provide a detailed description of what you will be selling..."></textarea>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="country_of_origin"><?php _e('Please provide the country of origin of your products', 'tekram'); ?> <span class="required">*</span></label>
                        <input type="text" id="country_of_origin" name="country_of_origin" required placeholder="e.g., Australia, China, Mixed">
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="price_range"><?php _e('Price range of products to be sold', 'tekram'); ?> <span class="required">*</span></label>
                        <input type="text" id="price_range" name="price_range" required placeholder="e.g., $5 - $50">
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="sustainability_practices"><?php _e('Sustainability practices', 'tekram'); ?></label>
                        <p class="lt-field-description"><?php _e('This will be displayed on your vendor page', 'tekram'); ?></p>
                        <textarea id="sustainability_practices" name="sustainability_practices" rows="4" placeholder="Describe any sustainability practices, eco-friendly packaging, local sourcing, etc."></textarea>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="accessibility_practices"><?php _e('Accessibility practices', 'tekram'); ?></label>
                        <p class="lt-field-description"><?php _e('This will be displayed on your vendor page', 'tekram'); ?></p>
                        <textarea id="accessibility_practices" name="accessibility_practices" rows="4" placeholder="Describe how you accommodate customers with disabilities, accessible setup, etc."></textarea>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="product_photos"><?php _e('Product Photos', 'tekram'); ?> <span class="required">*</span></label>
                        <input type="file" id="product_photos" name="product_photos[]" accept="image/*" multiple required>
                        <p class="lt-field-description"><?php _e('You can select up to 5 photos at once (Ctrl+Click or Cmd+Click to select multiple files, or drag & drop). JPG, PNG - Max 5MB each', 'tekram'); ?></p>
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Stall Requirements', 'tekram'); ?></h3>
                    
                    <div class="lt-form-field">
                        <label for="stall_type"><?php _e('What will you be using?', 'tekram'); ?> <span class="required">*</span></label>
                        <select id="stall_type" name="stall_type" required>
                            <option value="">Select...</option>
                            <option value="own_gazebo">I have my own gazebo/tent</option>
                            <option value="food_van_truck">Food Van / Truck</option>
                            <option value="need_hire">I need to hire a gazebo</option>
                            <option value="other">Other (please specify in special requirements)</option>
                        </select>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="require_electricity"><?php _e('Do you require electricity?', 'tekram'); ?> <span class="required">*</span></label>
                        <select id="require_electricity" name="require_electricity" required>
                            <option value="">Select...</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="require_water"><?php _e('Do you require water access?', 'tekram'); ?></label>
                        <select id="require_water" name="require_water">
                            <option value="">Select...</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    
                    <div class="lt-form-row">
                        <div class="lt-form-field smp-field-half">
                            <label for="tables_needed"><?php _e('Number of Tables Needed', 'tekram'); ?></label>
                            <input type="number" id="tables_needed" name="tables_needed" min="0" max="10" value="0">
                        </div>
                        
                        <div class="lt-form-field smp-field-half">
                            <label for="chairs_needed"><?php _e('Number of Chairs Needed', 'tekram'); ?></label>
                            <input type="number" id="chairs_needed" name="chairs_needed" min="0" max="10" value="0">
                        </div>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="vehicle_rego"><?php _e('Vehicle Registration', 'tekram'); ?></label>
                        <input type="text" id="vehicle_rego" name="vehicle_rego" placeholder="ABC123">
                        <p class="lt-field-description"><?php _e('For site access on event day', 'tekram'); ?></p>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="special_requirements"><?php _e('Special Requirements / Access Needs', 'tekram'); ?></label>
                        <textarea id="special_requirements" name="special_requirements" rows="3" placeholder="Any special requirements, access needs, or additional information..."></textarea>
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Access to Power', 'tekram'); ?></h3>
                    <p class="lt-field-description"><?php _e('Please provide detailed information about your power requirements', 'tekram'); ?></p>
                    
                    <div class="lt-form-field">
                        <label for="power_type"><?php _e('Type of power required', 'tekram'); ?></label>
                        <select id="power_type" name="power_type">
                            <option value="">Select...</option>
                            <option value="single_phase">Single Phase (240V / 10 Amp)</option>
                            <option value="three_phase">Three Phase (415V)</option>
                            <option value="generator">Own Generator</option>
                            <option value="none">No power required</option>
                        </select>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="power_usage"><?php _e('Estimated power usage (watts/amps)', 'tekram'); ?></label>
                        <input type="text" id="power_usage" name="power_usage" placeholder="e.g., 2000W or 10 Amps">
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="power_equipment"><?php _e('Equipment requiring power', 'tekram'); ?></label>
                        <textarea id="power_equipment" name="power_equipment" rows="3" placeholder="List all equipment that will require power (e.g., coffee machine, fridge, lighting, etc.)"></textarea>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="extension_cord"><?php _e('Do you have your own extension cord and power board?', 'tekram'); ?></label>
                        <select id="extension_cord" name="extension_cord">
                            <option value="">Select...</option>
                            <option value="yes">Yes</option>
                            <option value="no">No - I need to borrow/hire</option>
                        </select>
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Additional Information', 'tekram'); ?></h3>
                    
                    <div class="lt-form-field">
                        <label for="referral_source"><?php _e('How did you hear about this market?', 'tekram'); ?></label>
                        <select id="referral_source" name="referral_source">
                            <option value="">Select...</option>
                            <option value="social_media">Social Media</option>
                            <option value="friend">Friend / Word of Mouth</option>
                            <option value="google">Google Search</option>
                            <option value="market_directory">Market Directory Website</option>
                            <option value="returning">I'm a returning vendor</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="previous_experience"><?php _e('Have you sold at markets before?', 'tekram'); ?></label>
                        <select id="previous_experience" name="previous_experience">
                            <option value="">Select...</option>
                            <option value="yes">Yes</option>
                            <option value="no">No - This will be my first market</option>
                        </select>
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="additional_comments"><?php _e('Additional Comments / Questions', 'tekram'); ?></label>
                        <textarea id="additional_comments" name="additional_comments" rows="4" placeholder="Any additional information you'd like to share..."></textarea>
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Required Documents', 'tekram'); ?></h3>
                    <p class="lt-field-description"><?php _e('Please provide details and upload copies of the following documents', 'tekram'); ?></p>
                    
                    <!-- Public Liability Insurance -->
                    <div class="lt-document-group">
                        <h4><?php _e('Public Liability Insurance', 'tekram'); ?> <span class="required">*</span></h4>
                        <div class="lt-form-row">
                            <div class="lt-form-field smp-field-third">
                                <label for="insurance_policy_number"><?php _e('Policy Number', 'tekram'); ?> <span class="required">*</span></label>
                                <input type="text" id="insurance_policy_number" name="insurance_policy_number" required>
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="insurance_issuer"><?php _e('Issuer', 'tekram'); ?> <span class="required">*</span></label>
                                <input type="text" id="insurance_issuer" name="insurance_issuer" required placeholder="Insurance Company Name">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="insurance_expiry"><?php _e('Expiry Date', 'tekram'); ?> <span class="required">*</span></label>
                                <input type="date" id="insurance_expiry" name="insurance_expiry" required>
                            </div>
                        </div>
                        <div class="lt-form-field">
                            <label for="doc_insurance"><?php _e('Upload Document', 'tekram'); ?> <span class="required">*</span></label>
                            <input type="file" id="doc_insurance" name="doc_insurance" accept=".pdf,.jpg,.jpeg,.png" required>
                            <p class="lt-field-description"><?php _e('PDF or JPG/PNG accepted (Max 5MB)', 'tekram'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Food Licence -->
                    <div class="lt-document-group">
                        <h4><?php _e('Food Licence', 'tekram'); ?> <?php _e('(if selling food)', 'tekram'); ?></h4>
                        <div class="lt-form-row">
                            <div class="lt-form-field smp-field-third">
                                <label for="food_licence_number"><?php _e('Licence Number', 'tekram'); ?></label>
                                <input type="text" id="food_licence_number" name="food_licence_number">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="food_licence_issuer"><?php _e('Issuer', 'tekram'); ?></label>
                                <input type="text" id="food_licence_issuer" name="food_licence_issuer" placeholder="Council/Authority">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="food_licence_expiry"><?php _e('Expiry Date', 'tekram'); ?></label>
                                <input type="date" id="food_licence_expiry" name="food_licence_expiry">
                            </div>
                        </div>
                        <div class="lt-form-field">
                            <label for="doc_food_licence"><?php _e('Upload Document', 'tekram'); ?></label>
                            <input type="file" id="doc_food_licence" name="doc_food_licence" accept=".pdf,.jpg,.jpeg,.png">
                            <p class="lt-field-description"><?php _e('PDF or JPG/PNG accepted (Max 5MB)', 'tekram'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Food Handling Certificate -->
                    <div class="lt-document-group">
                        <h4><?php _e('Food Handling Certificate', 'tekram'); ?> <?php _e('(if selling food)', 'tekram'); ?></h4>
                        <div class="lt-form-row">
                            <div class="lt-form-field smp-field-third">
                                <label for="food_handling_number"><?php _e('Certificate Number', 'tekram'); ?></label>
                                <input type="text" id="food_handling_number" name="food_handling_number">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="food_handling_issuer"><?php _e('Issuer', 'tekram'); ?></label>
                                <input type="text" id="food_handling_issuer" name="food_handling_issuer" placeholder="Training Provider">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="food_handling_expiry"><?php _e('Expiry Date', 'tekram'); ?></label>
                                <input type="date" id="food_handling_expiry" name="food_handling_expiry">
                            </div>
                        </div>
                        <div class="lt-form-field">
                            <label for="doc_food_handling"><?php _e('Upload Document', 'tekram'); ?></label>
                            <input type="file" id="doc_food_handling" name="doc_food_handling" accept=".pdf,.jpg,.jpeg,.png">
                            <p class="lt-field-description"><?php _e('PDF or JPG/PNG accepted (Max 5MB)', 'tekram'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Food Safety Certificate -->
                    <div class="lt-document-group">
                        <h4><?php _e('Food Safety Certificate', 'tekram'); ?> <?php _e('(if selling food)', 'tekram'); ?></h4>
                        <div class="lt-form-row">
                            <div class="lt-form-field smp-field-third">
                                <label for="food_safety_number"><?php _e('Certificate Number', 'tekram'); ?></label>
                                <input type="text" id="food_safety_number" name="food_safety_number">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="food_safety_issuer"><?php _e('Issuer', 'tekram'); ?></label>
                                <input type="text" id="food_safety_issuer" name="food_safety_issuer" placeholder="Training Provider">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="food_safety_expiry"><?php _e('Expiry Date', 'tekram'); ?></label>
                                <input type="date" id="food_safety_expiry" name="food_safety_expiry">
                            </div>
                        </div>
                        <div class="lt-form-field">
                            <label for="doc_food_safety"><?php _e('Upload Document', 'tekram'); ?></label>
                            <input type="file" id="doc_food_safety" name="doc_food_safety" accept=".pdf,.jpg,.jpeg,.png">
                            <p class="lt-field-description"><?php _e('PDF or JPG/PNG accepted (Max 5MB)', 'tekram'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Business License -->
                    <div class="lt-document-group">
                        <h4><?php _e('Business License', 'tekram'); ?> <?php _e('(if applicable)', 'tekram'); ?></h4>
                        <div class="lt-form-row">
                            <div class="lt-form-field smp-field-third">
                                <label for="business_licence_number"><?php _e('Licence Number', 'tekram'); ?></label>
                                <input type="text" id="business_licence_number" name="business_licence_number">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="business_licence_issuer"><?php _e('Issuer', 'tekram'); ?></label>
                                <input type="text" id="business_licence_issuer" name="business_licence_issuer" placeholder="Government Authority">
                            </div>
                            <div class="lt-form-field smp-field-third">
                                <label for="business_licence_expiry"><?php _e('Expiry Date', 'tekram'); ?></label>
                                <input type="date" id="business_licence_expiry" name="business_licence_expiry">
                            </div>
                        </div>
                        <div class="lt-form-field">
                            <label for="doc_business_license"><?php _e('Upload Document', 'tekram'); ?></label>
                            <input type="file" id="doc_business_license" name="doc_business_license" accept=".pdf,.jpg,.jpeg,.png">
                            <p class="lt-field-description"><?php _e('PDF or JPG/PNG accepted (Max 5MB)', 'tekram'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="lt-form-section">
                    <h3><?php _e('Terms & Conditions', 'tekram'); ?></h3>
                    
                    <div class="lt-form-field">
                        <label class="lt-checkbox-label">
                            <input type="checkbox" name="accept_rules" required>
                            <?php _e('I have read and accept the Market Rules & Regulations', 'tekram'); ?> <span class="required">*</span>
                        </label>
                    </div>
                    
                    <div class="lt-form-field">
                        <label class="lt-checkbox-label">
                            <input type="checkbox" name="accept_cancellation" required>
                            <?php _e('I understand and accept the Cancellation Policy', 'tekram'); ?> <span class="required">*</span>
                        </label>
                    </div>
                    
                    <div class="lt-form-field">
                        <label class="lt-checkbox-label">
                            <input type="checkbox" name="marketing_consent">
                            <?php _e('I consent to photos of my stall being used for marketing purposes', 'tekram'); ?>
                        </label>
                    </div>
                    
                    <div class="lt-form-field">
                        <label class="lt-checkbox-label">
                            <input type="checkbox" name="privacy_consent" required>
                            <?php _e('I consent to my information being stored in accordance with the Privacy Policy', 'tekram'); ?> <span class="required">*</span>
                        </label>
                    </div>
                </div>
                
                <div class="lt-form-actions">
                    <button type="submit" name="lt_submit_application" class="lt-button smp-button-primary" id="lt-submit-application">
                        <?php _e('Submit Application', 'tekram'); ?>
                    </button>
                    <p style="background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 4px; margin-top: 15px; color: #856404; font-size: 14px;">
                        <strong>ℹ️ Please note:</strong> Processing your application may take 1-2 minutes. Please be patient and do not refresh the page.
                    </p>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Booking Form
     * Usage: [lt_booking_form]
     */
    public static function booking_form($atts) {
        $events = LT_Event::get_upcoming();
        
        ob_start();
        ?>
        <div class="lt-booking-form-container">
            <h2><?php _e('Make a Booking', 'tekram'); ?></h2>
            
            <p class="lt-info-message">
                <?php _e('Enter your Vendor Reference ID (sent to you after approval) to make a booking.', 'tekram'); ?>
            </p>
            
            <form id="lt-booking-form" class="lt-form" method="post">
                <div class="lt-form-field">
                    <label for="vendor_reference"><?php _e('Vendor Reference ID', 'tekram'); ?> <span class="required">*</span></label>
                    <input type="text" id="vendor_reference" name="vendor_reference" required placeholder="VEN-12345678" pattern="VEN-[0-9]{8}">
                    <p class="description"><?php _e('Format: VEN-12345678 (check your approval email)', 'tekram'); ?></p>
                </div>
                
                <div class="lt-form-field">
                    <label for="email_verify"><?php _e('Email Address', 'tekram'); ?> <span class="required">*</span></label>
                    <input type="email" id="email_verify" name="email_verify" required placeholder="your.email@example.com">
                    <p class="description"><?php _e('Must match the email on your application', 'tekram'); ?></p>
                </div>
                
                <div class="lt-form-field">
                    <label for="event_id"><?php _e('Select Market', 'tekram'); ?> <span class="required">*</span></label>
                    <select id="event_id" name="event_id" required>
                        <option value=""><?php _e('-- Select a Market --', 'tekram'); ?></option>
                        <?php foreach ($events as $event) {
                            $event_data = LT_Event::get_data($event->ID);
                            ?>
                            <option value="<?php echo $event->ID; ?>" 
                                    data-start-date="<?php echo esc_attr($event_data['start_date']); ?>"
                                    data-end-date="<?php echo esc_attr($event_data['end_date']); ?>"
                                    data-fee="<?php echo esc_attr($event_data['site_fee']); ?>">
                                <?php echo esc_html($event->post_title); ?> - <?php echo date('M j, Y', strtotime($event_data['start_date'])); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                
                <div class="lt-form-field">
                    <label for="booking_date"><?php _e('Event Date', 'tekram'); ?> <span class="required">*</span></label>
                    <input type="date" id="booking_date" name="booking_date" required>
                    <p class="description"><?php _e('Select the specific date you want to book', 'tekram'); ?></p>
                </div>
                
                <div class="lt-form-field">
                    <label for="stall_size_booking"><?php _e('Stall Size Required', 'tekram'); ?> <span class="required">*</span></label>
                    <select id="stall_size_booking" name="stall_size_booking" required>
                        <option value=""><?php _e('-- Select Stall Size --', 'tekram'); ?></option>
                        <option value="2x1">2m x 1m</option>
                        <option value="3x3">3m x 3m</option>
                        <option value="6x3">6m x 3m</option>
                        <option value="food_3x3">FOOD 3m x 3m</option>
                        <option value="food_6x3">FOOD 6m x 3m</option>
                    </select>
                    <p class="description"><?php _e('Select the stall size you need for this specific event', 'tekram'); ?></p>
                </div>
                
                <div class="lt-availability-check" style="display:none;">
                    <p class="lt-availability-message"></p>
                </div>
                
                <div class="lt-form-field" id="site-selection" style="display:none;">
                    <label for="site_id"><?php _e('Select Site (Optional)', 'tekram'); ?></label>
                    <select id="site_id" name="site_id">
                        <option value="" data-price=""><?php _e('Any Available Site', 'tekram'); ?></option>
                    </select>
                </div>
                
                <div class="lt-form-section" id="extras-selection" style="display:none;">
                    <h3><?php _e('Available Extras', 'tekram'); ?></h3>
                    <p class="description"><?php _e('Select any additional items you need:', 'tekram'); ?></p>
                    <div id="extras-list"></div>
                </div>
                
                <div class="lt-booking-summary" style="display:none;">
                    <h3><?php _e('Booking Summary', 'tekram'); ?></h3>
                    <div class="lt-summary-content">
                        <p><strong><?php _e('Event:', 'tekram'); ?></strong> <span id="summary-event">-</span></p>
                        <p><strong><?php _e('Date:', 'tekram'); ?></strong> <span id="summary-date">-</span></p>
                        <p><strong><?php _e('Site Fee:', 'tekram'); ?></strong> <span id="summary-fee">-</span></p>
                        <p id="summary-extras-line" style="display:none;"><strong><?php _e('Extras:', 'tekram'); ?></strong> <span id="summary-extras">$0.00</span></p>
                        <p class="lt-summary-total"><strong><?php _e('Total:', 'tekram'); ?></strong> <span id="summary-total">-</span></p>
                    </div>
                </div>
                
                <div class="lt-form-actions">
                    <button type="submit" class="lt-button smp-button-primary" id="lt-submit-booking">
                        <?php _e('Confirm Booking', 'tekram'); ?>
                    </button>
                </div>
                
                <div class="lt-form-message" style="display:none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Vendor Dashboard
     * Usage: [lt_vendor_dashboard]
     */
    public static function vendor_dashboard($atts) {
        // Check if vendor is accessing via session or form submission
        session_start();
        $vendor = null;
        
        // Check session first
        if (isset($_SESSION['lt_vendor_id'])) {
            $vendor = get_post($_SESSION['lt_vendor_id']);
        }
        
        // Check for form submission
        if (isset($_POST['lt_dashboard_access']) && check_admin_referer('lt_dashboard_access_nonce')) {
            $reference = sanitize_text_field($_POST['vendor_reference']);
            $email = sanitize_email($_POST['email']);
            
            $vendor = LT_Vendor::verify_vendor($reference, $email);
            
            if ($vendor) {
                $_SESSION['lt_vendor_id'] = $vendor->ID;
            }
        }
        
        // Check for logout
        if (isset($_GET['lt_logout'])) {
            unset($_SESSION['lt_vendor_id']);
            $vendor = null;
        }
        
        // If no vendor found, show access form
        if (!$vendor || $vendor->post_status !== 'publish') {
            ob_start();
            ?>
            <div class="lt-dashboard-access-form">
                <h2><?php _e('Vendor Dashboard Access', 'tekram'); ?></h2>
                <p><?php _e('Enter your Vendor Reference ID and email to access your dashboard.', 'tekram'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('lt_dashboard_access_nonce'); ?>
                    <input type="hidden" name="lt_dashboard_access" value="1">
                    
                    <div class="lt-form-field">
                        <label for="vendor_reference"><?php _e('Vendor Reference ID', 'tekram'); ?></label>
                        <input type="text" name="vendor_reference" id="vendor_reference" required placeholder="VEN-12345678" pattern="VEN-[0-9]{8}">
                    </div>
                    
                    <div class="lt-form-field">
                        <label for="email"><?php _e('Email Address', 'tekram'); ?></label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    
                    <button type="submit" class="lt-button lt-button-primary"><?php _e('Access Dashboard', 'tekram'); ?></button>
                </form>
                
                <?php if (isset($_POST['lt_dashboard_access']) && !$vendor) { ?>
                    <p class="lt-error-message"><?php _e('Invalid Vendor Reference ID or email. Please check and try again.', 'tekram'); ?></p>
                <?php } ?>
            </div>
            <?php
            return ob_get_clean();
        }
        
        // Get vendor data
        $vendor_data = LT_Vendor::get_data($vendor->ID);
        $bookings = LT_Booking::get_vendor_bookings($vendor->ID);
        
        ob_start();
        ?>
        <div class="lt-dashboard-container">
            <div class="lt-dashboard-header">
                <h2><?php _e('Vendor Dashboard', 'tekram'); ?></h2>
                <a href="?lt_logout=1" class="lt-button lt-button-small"><?php _e('Logout', 'tekram'); ?></a>
            </div>
            
            <div class="lt-dashboard-welcome">
                <p><?php printf(__('Welcome back, %s!', 'tekram'), esc_html($vendor_data['first_name'])); ?></p>
                <p class="lt-vendor-ref"><?php _e('Your Reference ID:', 'tekram'); ?> <strong><?php echo esc_html($vendor_data['vendor_reference']); ?></strong></p>
            </div>
            
            <div class="lt-dashboard-stats">
                <div class="lt-stat-box">
                    <h3><?php echo count($bookings); ?></h3>
                    <p><?php _e('Total Bookings', 'tekram'); ?></p>
                </div>
                
                <div class="lt-stat-box">
                    <?php
                    $upcoming = array_filter($bookings, function($b) {
                        return strtotime($b->booking_date) >= time() && $b->status === 'confirmed';
                    });
                    ?>
                    <h3><?php echo count($upcoming); ?></h3>
                    <p><?php _e('Upcoming Markets', 'tekram'); ?></p>
                </div>
            </div>
            
            <div class="lt-dashboard-bookings">
                <h3><?php _e('My Bookings', 'tekram'); ?></h3>
                
                <?php if ($bookings) { ?>
                    <table class="lt-table">
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
                                    <td><?php echo esc_html($booking->booking_reference); ?></td>
                                    <td><?php echo $event ? esc_html($event->post_title) : '-'; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></td>
                                    <td><?php echo get_option('lt_currency_symbol', '$') . number_format($booking->amount, 2); ?></td>
                                    <td>
                                        <span class="lt-payment-status lt-payment-<?php echo esc_attr($booking->payment_status); ?>">
                                            <?php echo ucfirst($booking->payment_status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="lt-status lt-status-<?php echo esc_attr($booking->status); ?>">
                                            <?php echo ucfirst($booking->status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p><?php _e('No bookings yet.', 'tekram'); ?></p>
                    <a href="#" class="lt-button smp-button-primary"><?php _e('Make Your First Booking', 'tekram'); ?></a>
                <?php } ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Event List
     * Usage: [lt_event_list]
     */
    public static function event_list($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'upcoming_only' => 'yes'
        ), $atts);
        
        if ($atts['upcoming_only'] === 'yes') {
            $events = LT_Event::get_upcoming();
        } else {
            $events = LT_Event::get_all();
        }
        
        if ($atts['limit'] != -1) {
            $events = array_slice($events, 0, intval($atts['limit']));
        }
        
        ob_start();
        ?>
        <div class="lt-event-list">
            <?php if ($events) { ?>
                <div class="lt-events-grid">
                    <?php foreach ($events as $event) {
                        $data = LT_Event::get_data($event->ID);
                        $available_slots = LT_Event::get_available_slots($event->ID, $data['start_date']);
                        ?>
                        <div class="lt-event-card">
                            <h3><?php echo esc_html($event->post_title); ?></h3>
                            <div class="lt-event-details">
                                <p><strong><?php _e('Date:', 'tekram'); ?></strong> <?php echo date('F j, Y', strtotime($data['start_date'])); ?></p>
                                <p><strong><?php _e('Location:', 'tekram'); ?></strong> <?php echo esc_html($data['location']); ?></p>
                                <p><strong><?php _e('Time:', 'tekram'); ?></strong> <?php echo esc_html($data['start_time']) . ' - ' . esc_html($data['end_time']); ?></p>
                                <p><strong><?php _e('Site Fee:', 'tekram'); ?></strong> <?php echo get_option('lt_currency_symbol', '$') . number_format($data['site_fee'], 2); ?></p>
                                <p><strong><?php _e('Available Sites:', 'tekram'); ?></strong> <?php echo $available_slots; ?></p>
                            </div>
                            <div class="lt-event-description">
                                <?php echo wpautop(wp_kses_post($event->post_content)); ?>
                            </div>
                            <?php if (LT_Event::is_booking_open($event->ID)) { ?>
                                <a href="#" class="lt-button smp-button-primary smp-book-now" data-event-id="<?php echo $event->ID; ?>">
                                    <?php _e('Book Now', 'tekram'); ?>
                                </a>
                            <?php } else { ?>
                                <p class="lt-booking-closed"><?php _e('Booking Closed', 'tekram'); ?></p>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p><?php _e('No upcoming events at this time.', 'tekram'); ?></p>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Sellers Directory - Shows all vendors booked for upcoming markets
     * Usage: [lt_sellers] or [lt_sellers event_id="123"]
     */
    public static function sellers_directory($atts) {
        $atts = shortcode_atts(array(
            'event_id' => '',
            'date' => '',
            'show_all' => 'no' // Show all vendors or only upcoming
        ), $atts);
        
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'lt_bookings';
        
        // Build query
        $sql = "SELECT DISTINCT vendor_id, event_id, booking_date 
                FROM $bookings_table 
                WHERE status IN ('confirmed', 'pending') 
                AND payment_status IN ('paid', 'partial')";
        
        // Filter by event
        if (!empty($atts['event_id'])) {
            $sql .= $wpdb->prepare(" AND event_id = %d", intval($atts['event_id']));
        }
        
        // Filter by date
        if (!empty($atts['date'])) {
            $sql .= $wpdb->prepare(" AND booking_date = %s", sanitize_text_field($atts['date']));
        } elseif ($atts['show_all'] !== 'yes') {
            // Only show future bookings
            $sql .= " AND booking_date >= CURDATE()";
        }
        
        $sql .= " ORDER BY booking_date ASC, event_id ASC";
        
        $bookings = $wpdb->get_results($sql);
        
        // Group by event and date
        $grouped = array();
        foreach ($bookings as $booking) {
            $key = $booking->event_id . '_' . $booking->booking_date;
            if (!isset($grouped[$key])) {
                $grouped[$key] = array(
                    'event_id' => $booking->event_id,
                    'date' => $booking->booking_date,
                    'vendors' => array()
                );
            }
            $grouped[$key]['vendors'][] = $booking->vendor_id;
        }
        
        ob_start();
        ?>
        <div class="lt-sellers-directory">
            <h2><?php _e('Our Market Sellers', 'tekram'); ?></h2>
            
            <?php if (empty($grouped)) { ?>
                <p><?php _e('No vendors currently booked. Check back soon!', 'tekram'); ?></p>
            <?php } else { ?>
                
                <?php foreach ($grouped as $group) {
                    $event = get_post($group['event_id']);
                    $event_data = LT_Event::get_data($group['event_id']);
                    ?>
                    
                    <div class="lt-sellers-event-section">
                        <h3 class="lt-event-header">
                            <?php echo esc_html($event->post_title); ?> - 
                            <?php echo date('F j, Y', strtotime($group['date'])); ?>
                        </h3>
                        
                        <div class="lt-sellers-grid">
                            <?php foreach ($group['vendors'] as $vendor_id) {
                                $vendor = get_post($vendor_id);
                                if (!$vendor) continue;
                                
                                $vendor_data = LT_Vendor::get_data($vendor_id);
                                $business_name = $vendor_data['business_name'] ? $vendor_data['business_name'] : $vendor->post_title;
                                $products = $vendor_data['products_description'];
                                $logo = get_the_post_thumbnail_url($vendor_id, 'medium');
                                ?>
                                
                                <div class="lt-seller-card">
                                    <div class="lt-seller-logo">
                                        <?php if ($logo) { ?>
                                            <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($business_name); ?>">
                                        <?php } else { ?>
                                            <div class="lt-seller-placeholder">
                                                <span class="dashicons dashicons-store"></span>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    
                                    <div class="lt-seller-info">
                                        <h4 class="lt-seller-name"><?php echo esc_html($business_name); ?></h4>
                                        
                                        <?php if ($products) { ?>
                                            <p class="lt-seller-description"><?php echo esc_html($products); ?></p>
                                        <?php } ?>
                                        
                                        <?php 
                                        $website = $vendor_data['website'];
                                        $facebook = $vendor_data['facebook'];
                                        $instagram = $vendor_data['instagram'];
                                        
                                        if ($website || $facebook || $instagram) { ?>
                                            <div class="lt-seller-social">
                                                <?php if ($website) { ?>
                                                    <a href="<?php echo esc_url($website); ?>" target="_blank" class="lt-social-link" title="Website">
                                                        <span class="dashicons dashicons-admin-site"></span>
                                                    </a>
                                                <?php } ?>
                                                <?php if ($facebook) { ?>
                                                    <a href="<?php echo esc_url($facebook); ?>" target="_blank" class="lt-social-link" title="Facebook">
                                                        <span class="dashicons dashicons-facebook"></span>
                                                    </a>
                                                <?php } ?>
                                                <?php if ($instagram) { ?>
                                                    <a href="<?php echo esc_url($instagram); ?>" target="_blank" class="lt-social-link" title="Instagram">
                                                        <span class="dashicons dashicons-instagram"></span>
                                                    </a>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                
                            <?php } ?>
                        </div>
                    </div>
                    
                <?php } ?>
                
            <?php } ?>
        </div>
        
        <style>
        .lt-sellers-directory {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .lt-sellers-event-section {
            margin-bottom: 50px;
        }
        
        .lt-event-header {
            padding: 15px 20px;
            background: #f5f5f5;
            border-left: 4px solid #0073aa;
            margin-bottom: 30px;
        }
        
        .lt-sellers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .lt-seller-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            overflow: hidden;
            transition: box-shadow 0.3s;
        }
        
        .lt-seller-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .lt-seller-logo {
            height: 200px;
            overflow: hidden;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lt-seller-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .lt-seller-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f5f5 0%, #e9e9e9 100%);
        }
        
        .lt-seller-placeholder .dashicons {
            font-size: 80px;
            width: 80px;
            height: 80px;
            color: #ccc;
        }
        
        .lt-seller-info {
            padding: 20px;
        }
        
        .lt-seller-name {
            margin: 0 0 10px;
            font-size: 20px;
            color: #333;
        }
        
        .lt-seller-description {
            margin: 10px 0;
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .lt-seller-social {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e5e5;
            display: flex;
            gap: 10px;
        }
        
        .lt-social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #f5f5f5;
            border-radius: 50%;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .lt-social-link:hover {
            background: #0073aa;
            color: #fff;
        }
        
        @media (max-width: 768px) {
            .lt-sellers-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
}



