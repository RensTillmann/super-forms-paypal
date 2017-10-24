<?php
/**
 * Super Forms - PayPal Checkout
 *
 * @package   Super Forms - PayPal Checkout
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - PayPal Checkout
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Checkout with PayPal after form submission. Charge users for registering or posting content.
 * Version:     1.0.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_PayPal')) :


    /**
     * Main SUPER_PayPal Class
     *
     * @class SUPER_PayPal
     */
    final class SUPER_PayPal {
    
        
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $version = '1.0.0';


        /**
         * @var string
         *
         *  @since      1.1.0
        */
        public $add_on_slug = 'paypal_checkout';
        public $add_on_name = 'PayPal Checkout';

        public $currency_codes = array(
            'AUD' => array('symbol'=>'$', 'name'=>'Australian Dollar'),
            'BRL' => array('symbol'=>'R$', 'name'=>'Brazilian Real'),
            'CAD' => array('symbol'=>'$', 'name'=>'Canadian Dollar'),
            'CZK' => array('symbol'=>'&#75;&#269;', 'name'=>'Czech Koruna'),
            'DKK' => array('symbol'=>'&#107;&#114;', 'name'=>'Danish Krone'),
            'EUR' => array('symbol'=>'&#128;', 'name'=>'Euro'),
            'HKD' => array('symbol'=>'&#20803;', 'name'=>'Hong Kong Dollar'),
            'HUF' => array('symbol'=>'&#70;&#116;', 'name'=>'Hungarian Forint', 'decimal'=>false),
            'ILS' => array('symbol'=>'&#8362;', 'name'=>'Israeli New Sheqel'),
            'JPY' => array('symbol'=>'&#165;', 'name'=>'Japanese Yen', 'decimal'=>false),
            'MYR' => array('symbol'=>'&#82;&#77;', 'name'=>'Malaysian Ringgit'),
            'MXN' => array('symbol'=>'&#36;', 'name'=>'Mexican Peso'),
            'NOK' => array('symbol'=>'&#107;&#114;', 'name'=>'Norwegian Krone'),
            'NZD' => array('symbol'=>'&#36;', 'name'=>'New Zealand Dollar'),
            'PHP' => array('symbol'=>'&#80;&#104;&#11;', 'name'=>'Philippine Peso'),
            'PLN' => array('symbol'=>'&#122;&#322;', 'name'=>'Polish Zloty'),
            'GBP' => array('symbol'=>'&#163;', 'name'=>'Pound Sterling'),
            'RUB' => array('symbol'=>'&#1088;&#1091;', 'name'=>'Russian Ruble'),
            'SGD' => array('symbol'=>'&#36;', 'name'=>'Singapore Dollar'),
            'SEK' => array('symbol'=>'&#107;&#114;', 'name'=>'Swedish Krona'),
            'CHF' => array('symbol'=>'&#67;&#72;&#70;', 'name'=>'Swiss Franc'),
            'TWD' => array('symbol'=>'&#36;', 'name'=>'Taiwan New Dollar', 'decimal'=>false),
            'THB' => array('symbol'=>'&#3647;', 'name'=>'Thai Baht'),
            'USD' => array('symbol'=>'$', 'name'=>'U.S. Dollar'),
        );

        /**
         * @var SUPER_PayPal The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_PayPal Instance
         *
         * Ensures only one instance of SUPER_PayPal is loaded or can be loaded.
         *
         * @static
         * @see SUPER_PayPal()
         * @return SUPER_PayPal - Main instance
         *
         *  @since      1.0.0
        */
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }


        /**
         * SUPER_PayPal Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){

            $settings = array();
            if(!isset($settings['paypal_mode'])) $settings['paypal_mode'] = 'sandbox';
            if(!isset($settings['paypal_payment_type'])) $settings['paypal_payment_type'] = 'product_service';
            if(!isset($settings['paypal_merchant_email'])) $settings['paypal_merchant_email'] = 'payments@feeling4design.nl';
            if(!isset($settings['paypal_cancel_url'])) $settings['paypal_cancel_url'] = get_home_url();
            if(!isset($settings['paypal_currency_code'])) $settings['paypal_currency_code'] = 'USD';
            if(!isset($settings['paypal_items'])) $settings['paypal_items'] = '';

            $entry_id = 1;
            $form_id = 2;
            $payment_type = 3;
            $home_url = get_home_url(). "/";
            if( strstr($home_url, '?') ) {
                $return_url = $home_url . '&page=super_paypal_response&custom=' . $entry_id . '|' . $form_id . '|' . $payment_type;
                $notify_url = $home_url . '&page=super_paypal_ipn';
            }else{
                $return_url = $home_url . '?page=super_paypal_response&custom=' . $entry_id . '|' . $form_id . '|' . $payment_type;
                $notify_url = $home_url . '?page=super_paypal_ipn';
            }


            // _xclick                  - The button that the person clicked was a Buy Now button.
            // _cart                    - For shopping cart purchases. The following variables specify the kind of shopping cart button that the person clicked:
            //                              - add — Add to Cart buttons for the PayPal Shopping Cart
            //                              - display — View Cart buttons for the PayPal Shopping Cart
            //                              - upload — The Cart Upload command for third-party carts
            // _xclick-subscriptions    - The button that the person clicked was a Subscribe button.
            // _xclick-auto-billing     - The button that the person clicked was an Automatic Billing button.
            // _xclick-payment-plan     - The button that the person clicked was an Installment Plan button.
            // _donations               - The button that the person clicked was a Donate button.
            // _s-xclick                - The button that the person clicked was protected from tampering by using encryption, or the button was saved in the merchant's PayPal account. PayPal determines which kind of button was clicked by decoding the encrypted code or by looking up the saved button in the merchant's account.


            $cmd = '_xclick';
            switch ($settings['paypal_payment_type']) {
                case 'product_service':
                    $cmd = '_xclick';
                    break;
                case 'donation':
                    $cmd = '_donations';
                    break;
                case 'subscription':
                    $cmd = '_xclick-subscriptions';
                    break;
            }
            $cmd = '_cart';

            $message = '';
            $message .= '<form id="super_paypal_form" action="https://www.' . ($settings['paypal_mode']=='sandbox' ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr" method="post">';
            $message .= '<input type="hidden" name="cmd" value="' . $cmd . '">';
            $message .= '<input type="hidden" name="charset" value="UTF-8">';
            $message .= '<input type="hidden" name="business" value="' . esc_attr( $settings['paypal_merchant_email'] ) . '">';
            $message .= '<input type="hidden" name="notify_url" value="' . esc_url($notify_url) . '">';
            $message .= '<input type="hidden" name="return" value="' . esc_url($return_url) . '">';
            $message .= '<input type="hidden" name="cancel_return" value="' . esc_url($settings['paypal_cancel_url']) . '">';
            $message .= '<input type="hidden" name="currency_code" value="' . $settings['paypal_currency_code'] . '" />';

            if( $cmd=='_xclick' ) {
                $message .= '<input type="hidden" name="item_name" value="Some peanuts">';
                $message .= '<input type="hidden" name="amount" value="10">';
            }

            if( $cmd=='_cart' ) {
                $message .= '<input type="hidden" name="upload" value="1">';

                $message .= '<input type="hidden" name="item_name_1" value="beach ball">';
                $message .= '<input type="hidden" name="amount_1" value="15">';
                $message .= '<input type="hidden" name="quantity_1" value="2">';

                $message .= '<input type="hidden" name="item_name_2" value="towel">';
                $message .= '<input type="hidden" name="amount_2" value="25">';
                $message .= '<input type="hidden" name="quantity_2" value="3">';
            }

            if( $cmd=='_xclick-subscriptions' ) {
                $message .= '<input type="hidden" name="item_name" value="Alice\'s Weekly Digest">';
                $message .= '<input type="hidden" name="item_number" value="DIG Weekly">';

                // Set the terms of the 1st trial period.
                // An initial trial period that is free and lasts for seven days.
                $message .= '<input type="hidden" name="a1" value="0">';
                $message .= '<input type="hidden" name="p1" value="7">';
                $message .= '<input type="hidden" name="t1" value="D">';

                // Set the terms of the 2nd trial period.
                // A second trial period that costs $5.00 USD and lasts for an additional three weeks.
                $message .= '<input type="hidden" name="a2" value="5.00">';
                $message .= '<input type="hidden" name="p2" value="3">';
                $message .= '<input type="hidden" name="t2" value="W">';

                // Set the terms of the regular subscription.
                // The regular subscription begins four weeks after the subscriber signs up.
                $message .= '<input type="hidden" name="a3" value="49.99">';
                $message .= '<input type="hidden" name="p3" value="1">';
                $message .= '<input type="hidden" name="t3" value="Y">';

                // Set recurring payments until canceled.
                $message .= '<input type="hidden" name="src" value="1">';
            }

            //foreach($settings['paypal_items'] as $k => $v){
            //    $message .= '<input type="hidden" name="item_name" value="' . $item_name . '">';
            //}
            //$message .= '<input type="hidden" name="custom" value="' . $entry_id . '|' . $form_id . '|' . $payment_type . '">';
            //$message .= '<input type="hidden" name="cbt" value="' . $continue_text . '">';
            //$message .= '<input type="hidden" name="rm" value="2">';

            //if( $settings['paypal_payment_type']=='subscription' ) {
            //    $message .= '<input type="hidden" name="a3" value="' . $amount . '">';
            //    $message .= '<input type="hidden" name="p3" value="' . $recurring_days . '">';
            //    $message .= '<input type="hidden" name="t3" value="' . $recurring_type . '">';
            //    $message .= '<input type="hidden" name="sra" value="' . $recurring_retry . '">';
            //    $message .= '<input type="hidden" name="src" value="1">';
            //    if( $recurring_time > 1 ) {
            //        $message .= '<input type="hidden" name="srt" value="' . $recurring_time . '">';
            //    }
            //    if( $trial_period_val=='1' ) {
            //        $message .= '<input type="hidden" name="a1" value="' . $trial_amount_val . '">';
            //        $message .= '<input type="hidden" name="p1" value="' . $trial_days . '">';
            //        $message .= '<input type="hidden" name="t1" value="' . $trial_recurring_type . '">';
            //    }
            //}else{
            //    $message .= '<input type="hidden" name="amount" value="' . $amount . '">';
            //}
            //if( (isset($options['shipping_info'])) && ($options['shipping_info']==1) ) {
            //    $message .= '<input type="hidden" name="first_name" value="' . $paypal_values['first_name'] . '" />';
            //    $message .= '<input type="hidden" name="last_name" value="' . $paypal_values['last_name'] . '" />';
            //    $message .= '<input type="hidden" name="email" value="' . $paypal_values['email'] . '" />';
            //    $message .= '<input type="hidden" name="address1" value="' . $paypal_values['address1'] . '" />';
            //    $message .= '<input type="hidden" name="address2" value="' . $paypal_values['address2'] . '" />';
            //    $message .= '<input type="hidden" name="city" value="' . $paypal_values['city'] . '" />';
            //    $message .= '<input type="hidden" name="state" value="' . $paypal_values['state'] . '" />';
            //    $message .= '<input type="hidden" name="zip" value="' . $paypal_values['zip'] . '" />';
            //    $message .= '<input type="hidden" name="country" value="' . $paypal_values['country'] . '" />';
            //}
            $message .= '<input type="submit" value="Pay with PayPal!" style="display:none;">';
            $message .= '</form>';
            $message .= '<script data-cfasync="false" type="text/javascript" language="javascript">';
            $message .= 'document.getElementById("super_paypal_form").submit();';
            $message .= '</script>';
            echo $message;

            $this->init_hooks();
            do_action('super_paypal_loaded');
        }

        
        /**
         * Define constant if not already set
         *
         * @param  string $name
         * @param  string|bool $value
         *
         *  @since      1.0.0
        */
        private function define($name, $value){
            if(!defined($name)){
                define($name, $value);
            }
        }

        
        /**
         * What type of request is this?
         *
         * string $type ajax, frontend or admin
         * @return bool
         *
         *  @since      1.0.0
        */
        private function is_request($type){
            switch ($type){
                case 'admin' :
                    return is_admin();
                case 'ajax' :
                    return defined( 'DOING_AJAX' );
                case 'cron' :
                    return defined( 'DOING_CRON' );
                case 'frontend' :
                    return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
            }
        }

        
        /**
         * Hook into actions and filters
         *
         *  @since      1.0.0
        */
        private function init_hooks() {

            // @since 1.1.0
            register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
            // Filters since 1.1.0
            add_filter( 'super_after_activation_message_filter', array( $this, 'activation_message' ), 10, 2 );

            // Filters since 1.0.0
            add_filter( 'super_after_contact_entry_data_filter', array( $this, 'add_entry_order_link' ), 10, 2 );

            // Actions since 1.0.0
            add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_wc_order_post_session_data' ) );
            add_action( 'super_after_wp_insert_user_action', array( $this, 'save_wc_order_signup_session_data' ) );
            add_action( 'paypal_checkout_update_order_meta', array( $this, 'update_order_meta' ), 10, 1 );

            if ( $this->is_request( 'frontend' ) ) {

            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );

                // Filters since 1.1.0
                add_filter( 'super_settings_end_filter', array( $this, 'activation' ), 100, 2 );
                
                // Actions since 1.1.0
                add_action( 'init', array( $this, 'update_plugin' ) );

                // Actions since 1.3.0
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );

            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Actions since 1.0.0
                add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ) );

            }
            
        }


        /**
         * Display activation message for automatic updates
         *
         *  @since      1.3.0
        */
        public function display_activation_msg() {
            $sac = get_option( 'sac_' . $this->add_on_slug, 0 );
            if( $sac!=1 ) {
                echo '<div class="notice notice-error">'; // notice-success
                    echo '<p>';
                    echo sprintf( __( '%sPlease note:%s You are missing out on important updates for %s! Please %sactivate your copy%s to receive automatic updates.', 'super_forms' ), '<strong>', '</strong>', 'Super Forms - ' . $this->add_on_name, '<a href="' . admin_url() . 'admin.php?page=super_settings#activate">', '</a>' );
                    echo '</p>';
                echo '</div>';
            }
        }


        /**
         * Automatically update plugin from the repository
         *
         *  @since      1.1.0
        */
        function update_plugin() {
            if( defined('SUPER_PLUGIN_DIR') ) {
                $sac = get_option( 'sac_' . $this->add_on_slug, 0 );
                if( $sac==1 ) {
                    require_once ( SUPER_PLUGIN_DIR . '/includes/admin/update-super-forms.php' );
                    $plugin_remote_path = 'http://f4d.nl/super-forms/';
                    $plugin_slug = plugin_basename( __FILE__ );
                    new SUPER_WP_AutoUpdate( $this->version, $plugin_remote_path, $plugin_slug, '', '', $this->add_on_slug );
                }
            }
        }


        /**
         * Add the activation under the "Activate" TAB
         * 
         * @since       1.1.0
        */
        public function activation($array, $data) {
            if (method_exists('SUPER_Forms','add_on_activation')) {
                return SUPER_Forms::add_on_activation($array, $this->add_on_slug, $this->add_on_name);
            }else{
                return $array;
            }
        }


        /**  
         *  Deactivate
         *
         *  Upon plugin deactivation delete activation
         *
         *  @since      1.1.0
         */
        public static function deactivate(){
            if (method_exists('SUPER_Forms','add_on_deactivate')) {
                SUPER_Forms::add_on_deactivate(SUPER_PayPal()->add_on_slug);
            }
        }


        /**
         * Check license and show activation message
         * 
         * @since       1.1.0
        */
        public function activation_message( $activation_msg, $data ) {
            if (method_exists('SUPER_Forms','add_on_activation_message')) {
                $form_id = absint($data['id']);
                $settings = $data['settings'];
                if( (isset($settings['paypal_checkout'])) && ($settings['paypal_checkout']=='true') ) {
                    return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
                }
            }
            return $activation_msg;
        }


        /**
         * Add the WC Order link to the entry info/data page
         * 
         * @since       1.0.0
        */
        public static function add_entry_order_link( $result, $data ) {
            $order_id = get_post_meta( $data['entry_id'], '_super_contact_entry_wc_order_id', true );
            if ( ! empty( $order_id ) ) {
                $order_id = absint($order_id);
                if( $order_id!=0 ) {
                    $result .= '<tr><th align="right">' . __( 'PayPal Order', 'super-forms' ) . ':</th><td><span class="super-contact-entry-data-value">';
                    $result .= '<a href="' . get_admin_url() . 'post.php?post=' . $order_id . '&action=edit">' . get_the_title( $order_id ) . '</a>';
                    $result .= '</span></td></tr>';
                }
            }
            return $result;
        }


        /**
         * If Front-end posting add-on is activated and being used retrieve the inserted Post ID and save it to the PayPal Order
         *
         *  @since      1.0.0
        */
        function save_wc_order_post_session_data( $data ) {
            global $paypal;

            // Check if Front-end Posting add-on is activated
            if ( class_exists( 'SUPER_Frontend_Posting' ) ) {
                $post_id = absint($data['post_id']);
                $settings = $data['atts']['settings'];
                if( (isset($settings['frontend_posting_action']) ) && ($settings['frontend_posting_action']=='create_post') ) {
                    $paypal->session->set( '_super_wc_post', array( 'post_id'=>$post_id, 'status'=>$settings['paypal_post_status'] ) );
                }else{
                    $paypal->session->set( '_super_wc_post', array() );
                }
            }else{
                $paypal->session->set( '_super_wc_post', array() );
            }

        }


        /**
         * If Register & Login add-on is activated and being used retrieve the created User ID and save it to the PayPal Order
         *
         *  @since      1.0.0
        */
        function save_wc_order_signup_session_data( $data ) {
            global $paypal;

            // Check if Register & Login add-on is activated
            if ( class_exists( 'SUPER_Register_Login' ) ) {
                $user_id = absint($data['user_id']);
                $settings = $data['atts']['settings'];
                if( (isset($settings['register_login_action']) ) && ($settings['register_login_action']=='register') ) {
                    $paypal->session->set( '_super_wc_signup', array( 'user_id'=>$user_id, 'status'=>$settings['paypal_signup_status'] ) );
                }else{
                    $paypal->session->set( '_super_wc_signup', array() );
                }
            }else{
                $paypal->session->set( '_super_wc_signup', array() );
            }

        }


        /**
         * Set the post ID and status to the order post_meta so we can update it after payment completed
         * 
         * @since       1.0.0
        */
        public static function update_order_meta( $order_id ) {

            // @since 1.2.2 - save the custom fields to the order, so we can retrieve it in back-end for later use
            $custom_fields = SUPER_Forms()->session->get( '_super_wc_custom_fields' );
            update_post_meta( $order_id, '_super_wc_custom_fields', $custom_fields );
            foreach( $custom_fields as $k => $v ) {
                if ( !empty($_POST[$v['name']]) ) {
                    update_post_meta( $order_id, $v['name'], sanitize_text_field( $_POST[$v['name']] ) );
                }
            }
            
            // @since 1.2.2 - save entry data to the order
            $data = SUPER_Forms()->session->get( '_super_paypal_entry_data' );
            update_post_meta( $order_id, '_super_paypal_entry_data', $data );

            global $paypal;
            $_super_wc_post = $paypal->session->get( '_super_wc_post', array() );
            update_post_meta( $order_id, '_super_wc_post', $_super_wc_post );

            $_super_wc_signup = $paypal->session->get( '_super_wc_signup', array() );
            update_post_meta( $order_id, '_super_wc_signup', $_super_wc_signup );

            $_super_entry_id = $paypal->session->get( '_super_entry_id', array() );
            update_post_meta( $_super_entry_id['entry_id'], '_super_contact_entry_wc_order_id', $order_id );

        }


        /**
         * Hook into before sending email and check if we need to create or update a post or taxonomy
         *
         *  @since      1.0.0
        */
        public static function before_email_success_msg( $atts ) {

            $settings = $atts['settings'];
            if( isset( $atts['data'] ) ) {
                $data = $atts['data'];
            }else{
                if( $settings['save_contact_entry']=='yes' ) {
                    $data = get_post_meta( $atts['entry_id'], '_super_contact_entry_data', true );
                }else{
                    $data = $atts['post']['data'];
                }
            }

            if( (isset($settings['paypal_checkout'])) && ($settings['paypal_checkout']=='true') ) {

                /*
                // Create new payer and method
                $payer = new Payer();
                $payer->setPaymentMethod("paypal");

                // Set redirect urls
                $redirectUrls = new RedirectUrls();
                $redirectUrls->setReturnUrl('http://localhost:3000/process.php')
                  ->setCancelUrl('http://localhost:3000/cancel.php');

                /*
                $baseUrl = getBaseUrl();
                $redirectUrls = new RedirectUrls();
                $redirectUrls->setReturnUrl("$baseUrl/ExecutePayment.php?success=true")
                    ->setCancelUrl("$baseUrl/ExecutePayment.php?success=false");
                */

                /*
                $item1 = new Item();
                $item1->setName('Ground Coffee 40 oz')
                    ->setCurrency('USD')
                    ->setQuantity(1)
                    ->setSku("123123") // Similar to `item_number` in Classic API
                    ->setPrice(7.5);

                $item2 = new Item();
                $item2->setName('Granola bars')
                    ->setCurrency('USD')
                    ->setQuantity(5)
                    ->setSku("321321") // Similar to `item_number` in Classic API
                    ->setPrice(2);

                $itemList = new ItemList();
                $itemList->setItems(array($item1, $item2));


                // Set payment amount
                $amount = new Amount();
                $amount->setCurrency("USD")
                  ->setTotal(10);

                /*
                $details = new Details();
                $details->setShipping(1.2)
                    ->setTax(1.3)
                    ->setSubtotal(17.50);
                
                $amount = new Amount();
                $amount->setCurrency("USD")
                    ->setTotal(20)
                    ->setDetails($details);
                */

                /*
                // Set transaction object
                $transaction = new Transaction();
                $transaction->setAmount($amount)
                  ->setDescription("Payment description");

                /*
                $transaction = new Transaction();
                $transaction->setAmount($amount)
                    ->setItemList($itemList)
                    ->setDescription("Payment description")
                    ->setInvoiceNumber(uniqid());
                */

                /*
                // Create the full payment object
                $payment = new Payment();
                $payment->setIntent('sale')
                  ->setPayer($payer)
                  ->setRedirectUrls($redirectUrls)
                  ->setTransactions(array($transaction));

                // Create payment with valid API context
                try {
                    $payment->create($apiContext);

                    // Get PayPal redirect URL and redirect user
                    $approvalUrl = $payment->getApprovalLink();

                    // REDIRECT USER TO $approvalUrl
                } catch (PayPal\Exception\PayPalConnectionException $ex) {
                    echo $ex->getCode();
                    echo $ex->getData();
                    die($ex);
                } catch (Exception $ex) {
                    die($ex);
                }


                // Complete the payment
                // After the customer confirms the payment information, he or she is redirected to the URL that
                // was specified in the payment information object, set in the first step. In the query string will
                // also be two parameters, PayerID and paymentId. These parameters are confirmation objects
                // used to complete the payment.
                /*
                use PayPal\Api\Amount;
                use PayPal\Api\Details;
                use PayPal\Api\ExecutePayment;
                use PayPal\Api\Payment;
                use PayPal\Api\PaymentExecution;
                use PayPal\Api\Transaction;

                // Get payment object by passing paymentId
                $paymentId = $_GET['paymentId'];
                $payment = Payment::get($paymentId, $apiContext);
                $payerId = $_GET['PayerID'];

                // Execute payment with payer id
                $execution = new PaymentExecution();
                $execution->setPayerId($payerId);

                try {
                // Execute payment
                $result = $payment->execute($execution, $apiContext);
                    var_dump($result);
                } catch (PayPal\Exception\PayPalConnectionException $ex) {
                    echo $ex->getCode();
                    echo $ex->getData();
                    die($ex);
                } catch (Exception $ex) {
                    die($ex);
                }
                */
            }

        }


        /**
         * Hook into settings and add PayPal settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
            $array['paypal_checkout'] = array(        
                'hidden' => 'settings',
                'name' => __( 'PayPal Checkout', 'super-forms' ),
                'label' => __( 'PayPal Checkout', 'super-forms' ),
                'fields' => array(
                    'paypal_checkout' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Enable PayPal Checkout', 'super-forms' ),
                        ),
                    ),               
                    'paypal_checkout_empty_cart' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout_empty_cart', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Empty cart before adding products', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                    'paypal_checkout_remove_coupons' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout_remove_coupons', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Remove/clear coupons before redirecting to cart', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ), 
                    'paypal_checkout_remove_fees' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout_remove_fees', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Remove/clear fees before redirecting to cart', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                    'paypal_checkout_products' => array(
                        'name' => __( 'Enter the product(s) ID that needs to be added to the cart', 'super-forms' ) . '<br /><i>' . __( 'If field is inside dynamic column, system will automatically add all the products. Put each product ID with it\'s quantity on a new line separated by pipes "|".<br /><strong>Example with tags:</strong> {id}|{quantity}<br /><strong>Example without tags:</strong> 82921|3<br /><strong>Example with variations:</strong> {id}|{quantity}|{variation_id}<br /><strong>Example with dynamic pricing:</strong> {id}|{quantity}|none|{price}<br /><strong>Allowed values:</strong> integer|integer|integer|float<br />(dynamic pricing requires <a target="_blank" href="https://paypal.com/products/name-your-price/">PayPal Name Your Price add-on</a>).', 'super-forms' ) . '</i>',
                        'desc' => __( 'Put each on a new line, {tags} can be used to retrieve data', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout_products', $settings['settings'], "{id}|{quantity}|none|{price}" ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
               
                    'paypal_checkout_coupon' => array(
                        'name' => __( 'Apply the following coupon code (leave blank for none):', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout_coupon', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                    'paypal_checkout_fees' => array(
                        'name' => __( 'Add checkout fee(s)', 'super-forms' ) . '<br /><i>' . __( 'Put each fee on a new line with values seperated by pipes "|".<br /><strong>Example with tags:</strong> {fee_name}|{amount}|{taxable}|{tax_class}<br /><strong>Example without tags:</strong> Administration fee|5|fales|\'\'<br /><strong>Allowed values:</strong> string|float|bool|string', 'super-forms' ) . '</i>',
                        'desc' => __( 'Leave blank for no fees', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout_fees', $settings['settings'], "{fee_name}|{amount}|{taxable}|{tax_class}" ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),

                    // @since 1.2.2 - add custom checkout fields to checkout page
                    'paypal_checkout_fields' => array(
                        'name' => __( 'Add custom checkout field(s)', 'super-forms' ) . '<br /><i>' . __( 'Put each field on a new line with field options seperated by pipes "|".', 'super-forms' ) . '</i><br />',
                        'label' => 'Example:<br />billing_custom|{billing_custom}|Billing custom|This is a custom field|text|billing|true|true|super-billing-custom|super-billing-custom-label|red,Red;blue,Blue;green,Green<br /><strong>Available field options:</strong><br /><strong>name</strong> - the field name<br /><strong>value</strong> - the field value ({tags} can be used here)<br /><strong>label</strong> – label for the input field<br /><strong>placeholder</strong> – placeholder for the input<br /><strong>type</strong> – type of field (text, textarea, password, select)<br /><strong>section</strong> - billing, shipping, account, order<br /><strong>required</strong> – true or false, whether or not the field is require<br /><strong>clear</strong> – true or false, applies a clear fix to the field/label<br /><strong>class</strong> – class for the input<br /><strong>label_class</strong> – class for the label element<br /><strong>options</strong> – for select boxes, array of options (key => value pairs)',
                        'desc' => __( 'Leave blank for no custom fields', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout_fields', $settings['settings'], "" ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                        /*
                        name            value              label          placeholder         type section  req. clear.       class              label class           options for select boxes
                        billing_custom|{billing_custom}|Billing custom|This is a custom field|text|billing||true|true|super-billing-custom|super-billing-custom-label|red,Red;blue,Blue;green,Green
                        <strong>name</strong> - the field name<br />
                        <strong>value</strong> - the field value ({tags} can be used here)<br />
                        <strong>label</strong> – label for the input field<br />
                        <strong>placeholder</strong> – placeholder for the input<br />
                        <strong>type</strong> – type of field (text, textarea, password, select)<br />
                        <strong>section</strong> - billing, shipping, account, order<br />
                        <strong>required</strong> – true or false, whether or not the field is require<br />
                        <strong>clear</strong> – true or false, applies a clear fix to the field/label<br />
                        <strong>class</strong> – class for the input<br />
                        <strong>label_class</strong> – class for the label element<br />
                        <strong>options</strong> – for select boxes, array of options (key => value pairs)
                        */
                    ),
                    'paypal_checkout_fields_skip_empty' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_checkout_fields_skip_empty', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Only add custom field if field exists in form and not conditionally hidden', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),

                    'paypal_redirect' => array(
                        'name' => __( 'Redirect to Checkout page or Shopping Cart?', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_redirect', $settings['settings'], 'checkout' ),
                        'type' => 'select',
                        'values' => array(
                            'checkout' => __( 'Checkout page (default)', 'super-forms' ),
                            'cart' => __( 'Shopping Cart', 'super-forms' ),
                            'none' => __( 'None (use the form redirect)', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                )
            );

            if ( class_exists( 'SUPER_Frontend_Posting' ) ) {
                $array['paypal_checkout']['fields']['paypal_post_status'] = array(
                    'name' => __( 'Post status after payment complete', 'super-forms' ),
                    'desc' => __( 'Only used for Front-end posting (publish, future, draft, pending, private, trash, auto-draft)?', 'super-forms' ),
                    'default' => SUPER_Settings::get_value( 0, 'paypal_post_status', $settings['settings'], 'publish' ),
                    'type' => 'select',
                    'values' => array(
                        'publish' => __( 'Publish (default)', 'super-forms' ),
                        'future' => __( 'Future', 'super-forms' ),
                        'draft' => __( 'Draft', 'super-forms' ),
                        'pending' => __( 'Pending', 'super-forms' ),
                        'private' => __( 'Private', 'super-forms' ),
                        'trash' => __( 'Trash', 'super-forms' ),
                        'auto-draft' => __( 'Auto-Draft', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'paypal_checkout',
                    'filter_value' => 'true',
                );
            }

            if ( class_exists( 'SUPER_Register_Login' ) ) {
                $array['paypal_checkout']['fields']['paypal_signup_status'] = array(
                    'name' => __( 'Registered user login status after payment complete', 'super-forms' ),
                    'desc' => __( 'Only used for Register & Login add-on (active, pending, blocked)?', 'super-forms' ),
                    'default' => SUPER_Settings::get_value( 0, 'paypal_signup_status', $settings['settings'], 'active' ),
                    'type' => 'select',
                    'values' => array(
                        'active' => __( 'Active (default)', 'super-forms' ),
                        'pending' => __( 'Pending', 'super-forms' ),
                        'blocked' => __( 'Blocked', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'paypal_checkout',
                    'filter_value' => 'true',
                );
            }

            return $array;
        }
    }
        
endif;


/**
 * Returns the main instance of SUPER_PayPal to prevent the need to use globals.
 *
 * @return SUPER_PayPal
 */
function SUPER_PayPal() {
    return SUPER_PayPal::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_PayPal'] = SUPER_PayPal();