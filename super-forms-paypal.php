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

        public static $currency_codes = array(
            'AUD' => array('symbol'=>'$', 'name'=>'Australian Dollar'),
            'BRL' => array('symbol'=>'R$', 'name'=>'Brazilian Real'),
            'CAD' => array('symbol'=>'$', 'name'=>'Canadian Dollar'),
            'CZK' => array('symbol'=>'&#75;&#269;', 'name'=>'Czech Koruna'),
            'DKK' => array('symbol'=>'&#107;&#114;', 'name'=>'Danish Krone'),
            'EUR' => array('symbol'=>'&#128;', 'name'=>'Euro'),
            'HKD' => array('symbol'=>'&#20803;', 'name'=>'Hong Kong Dollar'),
            'HUF' => array('symbol'=>'&#70;&#116;', 'name'=>'Hungarian Forint', 'decimal'=>true),
            'ILS' => array('symbol'=>'&#8362;', 'name'=>'Israeli New Sheqel'),
            'JPY' => array('symbol'=>'&#165;', 'name'=>'Japanese Yen', 'decimal'=>true),
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
            'TWD' => array('symbol'=>'&#36;', 'name'=>'Taiwan New Dollar', 'decimal'=>true),
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

            add_action( 'parse_request', array( $this, 'paypal_ipn' ) );

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
         * PayPal IPN
         * 
         * @since       1.1.0
        */
        public function paypal_ipn() {

            if( (isset($_REQUEST['page'])) && ($_REQUEST['page']=='super_paypal_api') ) {
                if( isset( $_POST['txn_id'] ) ) {
                    global $wpdb;
                    $req = 'cmd=_notify-validate';
                    foreach( $_POST as $key => $value ) {
                        $value = urlencode( stripslashes( $value ) );
                        $req .= "&$key=$value";
                    }

                    $customs = explode( '|', $_REQUEST['custom'] );
                    $form_id = $customs[1];
                    if (!$form_id) return;

                    $form_data = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . 'arf_paypal_forms WHERE form_id = %d', $form_id));

                    if (count($form_data) == 0)
                        return;

                    $form_data = $form_data[0];
                    $options = maybe_unserialize($form_data->options);

                    $sandbox = ( isset($options['paypal_mode']) and $options['paypal_mode'] == 0 ) ? 'sandbox.' : '';

                    $url = "https://www." . $sandbox . "paypal.com/cgi-bin/webscr/";

                    $request = new WP_Http();
                    $response = $request->post($url, array("sslverify" => false, "ssl" => true, "body" => $req, "timeout" => 20));

                    if (!is_wp_error($response) and $response['body'] == 'VERIFIED') {
                        $txn_id = $_POST['txn_id'];
                        $payment_status = $_POST['payment_status'];
                        $payment_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "arf_paypal_order WHERE txn_id = %s", $txn_id));
                        $payment_results = $payment_results[0];

                                        
                        $item_name = $_POST['item_name'];
                        $txn_id = $_POST['txn_id'];
                        $payment_status = $_POST['payment_status'];
                        $payment_amount = $_POST['mc_gross'];
                        $payment_currency = $_POST['mc_currency'];
                        $receiver_email = $_POST['receiver_email'];
                        $payer_email = $_POST['payer_email'];
                        $quantity = $_POST["quantity"];
                        $user_id = get_current_user_id();
                        $payment_date = $_POST['payment_date'];
                        $payer_name = $_POST['first_name'] . ' ' . $_POST['last_name'];
                        $entry_id = $customs[0];
                        $form_id = $customs[1];
                        $payment_type = $customs[2];

                        $insert_array = array(
                            'item_name' => $item_name,
                            'txn_id' => $txn_id,
                            'payment_status' => $payment_status,
                            'mc_gross' => floatval($payment_amount),
                            'mc_currency' => $payment_currency,
                            'quantity' => $quantity,
                            'payer_email' => $payer_email,
                            'payer_name' => $payer_name,
                            'payment_type' => $payment_type,
                            'user_id' => $user_id,
                            'entry_id' => $entry_id,
                            'form_id' => $form_id,
                            'payment_date' => $payment_date,
                            'created_at' => current_time('mysql'),
                            'is_verified' => 1,
                        );
                        $wpdb->insert($wpdb->prefix . 'arf_paypal_order', $insert_array, array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d'));

                        

                        update_option('IPN_LOG' . $form_id . '_' . time(), maybe_serialize($_POST));

                        
                        do_action('arf_after_paypal_successful_paymnet', $form_id, $entry_id, $txn_id);

                        if (isset($options['notification']) and $options['notification'] and $this->is_arforms_support()) {
                            global $arfsettings;

                            $arf_form_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "arf_forms WHERE id = %d", $form_id));
                            $arf_form_data = $arf_form_data[0];
                            $arf_options = maybe_unserialize($arf_form_data->options);

                            $arfblogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

                            $admin_email = $arf_options['reply_to'];
                            if (!is_email($admin_email))
                                $admin_email = $arfsettings->reply_to;

                            $admin_from_reply = $arf_options['ar_admin_from_email'];
                            if (!is_email($admin_from_reply))
                                $admin_from_reply = $admin_email;

                            $reply_to_name = (isset($arf_options['ar_admin_from_name'])) ? $arf_options['ar_admin_from_name'] : $arfsettings->reply_to_name;

                            
                            $subject = __('Payment received on', 'ARForms-paypal') . ' ' . $arfblogname;
                            $message = $options['email_content'];
                            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

                            if (empty($message))
                                $message = $arf_paypal->defalut_email_content();

                            $item_name = $_POST['item_name'];
                            $txn_id = $_POST['txn_id'];
                            $payment_status = $_POST['payment_status'];
                            $payment_amount = $_POST['mc_gross'];
                            $payment_currency = $_POST['mc_currency'];
                            $payment_date = $_POST['payment_date'];
                            $payer_email = $_POST['payer_email'];
                            $payer_id = $_POST['payer_id'];
                            $payer_fname = $_POST['first_name'];
                            $payer_lname = $_POST['last_name'];

                            $message = str_replace('{paypal_transaction_id}', $txn_id, $message);
                            $message = str_replace('{paypal_amount}', floatval($payment_amount), $message);
                            $message = str_replace('{paypal_currency}', $payment_currency, $message);
                            $message = str_replace('{paypal_payment_date}', $payment_date, $message);
                            $message = str_replace('{paypal_site_name}', $blogname, $message);
                            $message = str_replace('{paypal_payer_email}', $payer_email, $message);
                            $message = str_replace('{paypal_payer_id}', $payer_id, $message);
                            $message = str_replace('{paypal_payer_fname}', $payer_fname, $message);
                            $message = str_replace('{paypal_payer_lname}', $payer_lname, $message);

                            $arnotifymodel->send_notification_email_user($admin_email, $subject, $message, $admin_from_reply, $reply_to_name);
                        }
                    }
                }
            }
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

                if(!isset($settings['paypal_mode'])) $settings['paypal_mode'] = 'sandbox';
                if(!isset($settings['paypal_payment_type'])) $settings['paypal_payment_type'] = 'product';
                if(!isset($settings['paypal_merchant_email'])) $settings['paypal_merchant_email'] = '';
                if(!isset($settings['paypal_cancel_url'])) $settings['paypal_cancel_url'] = get_home_url();               
                if(!isset($settings['paypal_custom_return_url'])) $settings['paypal_custom_return_url'] = '';
                if(!isset($settings['paypal_return_url'])) $settings['paypal_return_url'] = get_home_url();
                if(!isset($settings['paypal_currency_code'])) $settings['paypal_currency_code'] = 'USD';
                if(!isset($settings['paypal_item_amount'])) $settings['paypal_item_amount'] = '5.00';
                if( is_numeric( $settings['paypal_item_amount'] ) ) {
                    $settings['paypal_item_amount'] = number_format((float) $settings['paypal_item_amount'], 2);
                    if( ( isset( self::$currency_codes[$settings['paypal_currency_code']]['decimal'] ) ) && (self::$currency_codes[$settings['paypal_currency_code']]['decimal']==true) ) {
                        $settings['paypal_item_amount'] = (float) $settings['paypal_item_amount'];
                        $settings['paypal_item_amount'] = floor($settings['paypal_item_amount']);
                    }
                }

                $home_url = get_home_url(). "/";
                if( strstr($home_url, '?') ) {
                    $return_url = $home_url . '&page=super_paypal_response&custom=custom-data'; // . absint($atts['entry_id']) . '|' . $form_id . '|' . $payment_type;
                    $notify_url = $home_url . '&page=super_paypal_ipn';
                }else{
                    $return_url = $home_url . '?page=super_paypal_response&custom=custom-data'; // . absint($atts['entry_id']) . '|' . $form_id . '|' . $payment_type;
                    $notify_url = $home_url . '?page=super_paypal_ipn';
                }
                if($settings['paypal_custom_return_url']=='true'){
                    $return_url = $settings['paypal_return_url'];
                }

                /*
                $custom_data
                if( $settings['save_contact_entry']=='yes' ) {
                    $entry_id = $atts['entry_id'];
                }
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
                */

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
                    case 'product':
                        $cmd = '_xclick';
                        break;
                    case 'donation':
                        $cmd = '_donations';
                        break;
                    case 'subscription':
                        $cmd = '_xclick-subscriptions';
                        break;
                    case 'cart':
                        $cmd = '_cart';
                        break;
                }

                $message = '';
                $message .= '<form id="super_paypal_' . $settings['id'] . '" action="https://www.' . ($settings['paypal_mode']=='sandbox' ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr" method="post">';
                $message .= '<input type="hidden" name="cmd" value="' . $cmd . '">';
                $message .= '<input type="hidden" name="charset" value="UTF-8">';
                $message .= '<input type="hidden" name="business" value="' . esc_attr( $settings['paypal_merchant_email'] ) . '">';
                $message .= '<input type="hidden" name="notify_url" value="' . esc_url($notify_url) . '">';
                $message .= '<input type="hidden" name="return" value="' . esc_url($return_url) . '">';
                $message .= '<input type="hidden" name="cancel_return" value="' . esc_url($settings['paypal_cancel_url']) . '">';
                $message .= '<input type="hidden" name="currency_code" value="' . $settings['paypal_currency_code'] . '" />';

                if( $settings['paypal_invoice']!='' ) {
                    $message .= '<input type="hidden" name="invoice" value="' . $settings['paypal_invoice'] . '">';
                }
                if( $settings['paypal_handling']!='' ) {
                    $message .= '<input type="hidden" name="handling" value="' . $settings['paypal_handling'] . '">';
                }
                if( $settings['paypal_tax']!='' ) {
                    $message .= '<input type="hidden" name="tax" value="' . $settings['paypal_tax'] . '">';
                }
                if( $settings['paypal_tax_rate']!='' ) {
                    $message .= '<input type="hidden" name="tax_rate" value="' . $settings['paypal_tax_rate'] . '">';
                }
                if( $settings['paypal_weight_unit']!='' ) {
                    $message .= '<input type="hidden" name="weight_unit" value="' . $settings['paypal_weight_unit'] . '">';
                }

                if( ($cmd=='_xclick') || ($cmd=='_donations') ) {

                    if( $settings['paypal_item_name']!='' ) {
                        $message .= '<input type="hidden" name="item_name" value="' . $settings['paypal_item_name'] . '">';
                    }
                    if( $settings['paypal_item_number']!='' ) {
                        $message .= '<input type="hidden" name="item_number" value="' . $settings['paypal_item_number'] . '">';
                    }
                    if( $settings['paypal_item_quantity']!='' ) {
                        $message .= '<input type="hidden" name="quantity" value="' . $settings['paypal_item_quantity'] . '">';
                    }
                    if( $settings['paypal_item_shipping']!='' ) {
                        $message .= '<input type="hidden" name="shipping" value="' . $settings['paypal_item_shipping'] . '">';
                        //$message .= '<input type="hidden" name="shipping2" value="' . $settings['paypal_item_shipping'] . '">';
                    }
                    if( $settings['paypal_undefined_quantity']!='' ) {
                        $message .= '<input type="hidden" name="undefined_quantity" value="' . $settings['paypal_undefined_quantity'] . '">';
                    }
                    if( $settings['paypal_item_weight']!='' ) {
                        $message .= '<input type="hidden" name="weight" value="' . $settings['paypal_item_weight'] . '">';
                    }

                    if($cmd=='_xclick'){
                        if( $settings['paypal_item_discount_amount']!='' ) {
                            $message .= '<input type="hidden" name="discount_amount" value="' . $settings['paypal_item_discount_amount'] . '">';
                            $message .= '<input type="hidden" name="discount_amount2" value="' . $settings['paypal_item_discount_amount'] . '">';
                        }
                        if( $settings['paypal_item_discount_rate']!='' ) {
                            $message .= '<input type="hidden" name="discount_rate" value="' . $settings['paypal_item_discount_rate'] . '">';
                            $message .= '<input type="hidden" name="discount_rate2" value="' . $settings['paypal_item_discount_rate'] . '">';
                        }
                        if( $settings['paypal_item_discount_num']!='' ) {
                            $message .= '<input type="hidden" name="discount_num" value="' . $settings['paypal_item_discount_num'] . '">';
                        }
                    }

                    $message .= '<input type="hidden" name="amount" value="' . $settings['paypal_item_amount'] . '">';

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

                    // a1 - the price of the subscription e.g: 5.00
                    // p1 - the period of the subscription e.g: 7 (for 7 days if t1 has value of D)
                    // t1 - the time format for the period e.g: D=days, W=weeks, M=months, Y=years

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
                $message .= 'document.getElementById("super_paypal_' . $settings['id'] . '").submit();';
                $message .= '</script>';

                if($settings['form_show_thanks_msg']=='true'){
                    if($settings['form_thanks_title']!=''){
                        $settings['form_thanks_title'] = '<h1>' . $settings['form_thanks_title'] . '</h1>';
                    }
                    $msg = do_shortcode( $settings['form_thanks_title'] . nl2br($settings['form_thanks_description']) );
                }

                SUPER_Common::output_error(
                    $error = false,
                    $msg = $msg.$message,
                    $redirect = false,
                    $fields = array(),
                    $display = true,
                    $loading = true
                );

            }

        }


        /**
         * Hook into settings and add PayPal settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {

            $statuses = SUPER_Settings::get_entry_statuses();
            $new_statuses = array();
            foreach($statuses as $k => $v){
                $new_statuses[$k] = $v['name'];
            }
            $statuses = $new_statuses;
            unset($new_statuses);

            $currencies = array();
            foreach(self::$currency_codes as $k => $v){
                $currencies[$k] = $k . ' - ' . $v['name'] . ' (' . $v['symbol'] . ')';
            }

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
                    'paypal_mode' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_mode', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'sandbox' => __( 'Enable PayPal Sandbox mode (for testing purposes only)', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                    'paypal_merchant_email' => array(
                        'name' => __( 'PayPal merchant email (to receive payments)', 'super-forms' ),
                        'desc' => __( 'Your PayPal ID or an email address associated with your PayPal account. Email addresses must be confirmed.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_merchant_email', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),


                    'paypal_currency_code' => array(
                        'name' => __( 'PayPal currency code', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_currency_code', $settings['settings'], 'USD' ),
                        'type' => 'select',
                        'values' => $currencies,
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                    'paypal_payment_type' => array(
                        'name' => __( 'PayPal payment method', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_payment_type', $settings['settings'], '_xclick' ),
                        'type' => 'select',
                        'values' => array(
                            'product' => __( 'Single product or service checkout', 'super-forms' ),
                            'donation' => __( 'Donation checkout', 'super-forms' ),
                            'subscription' => __( 'Subscription checkout', 'super-forms' ),
                            'cart' => __( 'Cart checkout (for multiple product checkout)', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                    'paypal_item_name' => array(
                        'name' => __( 'Item description (leave blank to let users enter a name)', 'super-forms' ),
                        'desc' => __( 'Description of item. If you omit this variable, buyers enter their own name during checkout.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_name', $settings['settings'], 'Flower (roses)' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_payment_type',
                        'filter_value' => 'product,donation',
                    ),
                    'paypal_item_amount' => array(
                        'name' => __( 'Item price (leave blank to let user enter their own price)', 'super-forms' ),
                        'desc' => __( 'The price or amount of the product, service, or contribution, not including shipping, handling, or tax. If you omit this variable from Buy Now or Donate buttons, buyers enter their own amount at the time of payment.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed (only decimal format is allowed e.g: 16.95)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_amount', $settings['settings'], '5.00' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_payment_type',
                        'filter_value' => 'product,donation',
                    ),
                    'paypal_item_quantity' => array(
                        'name' => __( 'Quantity (Number of items)', 'super-forms' ),
                        'desc' => __( 'Note: The value for quantity must be a positive integer. Null, zero, or negative numbers are not allowed.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_quantity', $settings['settings'], '1' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_payment_type',
                        'filter_value' => 'product,donation',
                    ),

                    // Custom return URL
                    'paypal_custom_return_url' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_custom_return_url', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Enable custom return URL', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                    'paypal_return_url' => array(
                        'name' => __( 'PayPal return URL (when user successfully returns from paypal)', 'super-forms' ),
                        'desc' => __( 'The URL to which PayPal posts information about the payment, in the form of Instant Payment Notification messages.', 'super-forms' ),
                        'label' => __( 'User will be redirected to this URL after making a payment', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_return_url', $settings['settings'], get_home_url() . '/my-custom-thank-you-page' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_custom_return_url',
                        'filter_value' => 'true',
                    ),
                    // Cancel URL when order was canceled by the user
                    'paypal_cancel_url' => array(
                        'name' => __( 'PayPal cancel URL (when payment is canceled by user)', 'super-forms' ),
                        'label' => __( 'User that cancels payment will be redirected to this URL', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_cancel_url', $settings['settings'], get_home_url() . '/my-custom-canceled-page' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),


                    // Advanced PayPal Settings
                    'paypal_advanced_settings' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_advanced_settings', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Show Advanced PayPal Settings', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),
                    'paypal_item_discount_amount' => array(
                        'name' => __( 'Discount amount (leave blank for no discount)', 'super-forms' ),
                        'desc' => __( 'Discount amount associated with an item, which must be less than the selling price of the item.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_discount_amount', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_item_discount_rate' => array(
                        'name' => __( 'Discount rate (leave blank for no discount)', 'super-forms' ),
                        'desc' => __( 'Discount rate, as a percentage, associated with an item. Set to a value less than 100', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_discount_rate', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_item_discount_num' => array(
                        'name' => __( 'Discount number', 'super-forms' ),
                        'desc' => __( 'Number of additional quantities of the item to which the discount applies.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_discount_num', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),

                    

                    'paypal_item_shipping' => array(
                        'name' => __( 'Shipping cost', 'super-forms' ),
                        'desc' => __( 'The cost of shipping this item.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_shipping', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_handling' => array(
                        'name' => __( 'Handling charges', 'super-forms' ),
                        'desc' => __( 'This variable is not quantity-specific. The same handling cost applies, regardless of the number of items on the order.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_handling', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_tax' => array(
                        'name' => __( 'Tax', 'super-forms' ),
                        'desc' => __( 'Set this variable to a flat tax amount to apply to the payment regardless of the buyer\'s location. This value overrides any tax settings set in your account profile.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_tax', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_tax_rate' => array(
                        'name' => __( 'Tax rate', 'super-forms' ),
                        'desc' => __( 'Set this variable to a percentage that applies to the amount multiplied by the quantity selected during checkout. This value overrides any tax settings set in your account profile', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_tax_rate', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_item_number' => array(
                        'name' => __( 'Item number (to track product or service)', 'super-forms' ),
                        'desc' => __( 'Pass-through variable for you to track product or service purchased or the contribution made. The value you specify is passed back to you upon payment completion.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_number', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_undefined_quantity' => array(
                        'default' => SUPER_Settings::get_value( 0, 'paypal_undefined_quantity', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Allow buyers to specify the quantity', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_item_weight' => array(
                        'name' => __( 'Weight of item', 'super-forms' ),
                        'desc' => __( 'If profile-based shipping rates are configured with a basis of weight, the sum of weight values is used to calculate the shipping charges for the payment. A valid value is a decimal number with two significant digits to the right of the decimal point.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_item_weight', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_weight_unit' => array(
                        'name' => __( 'Select weight unit', 'super-forms' ),
                        'desc' => __( 'The unit of measure if weight is specified.', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_weight_unit', $settings['settings'], 'lbs' ),
                        'type' => 'select',
                        'values'=> array(
                            'lbs' => 'lbs (default)',
                            'kgs' => 'kgs',
                        ),
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),
                    'paypal_invoice' => array(
                        'name' => __( 'Invoice number', 'super-forms' ),
                        'desc' => __( 'Use to identify your invoice number for this purchase.', 'super-forms' ),
                        'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_invoice', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),

                    'paypal_completed_entry_status' => array(
                        'name'=>__( 'Entry status after payment completed', 'super-forms' ),
                        'label' => sprintf( __( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . admin_url() . 'admin.php?page=super_settings#backend">', '</a>'),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_completed_entry_status', $settings['settings'], 'completed' ),
                        'type'=>'select',
                        'values'=> $statuses,
                        'filter' => true,
                        'parent' => 'paypal_checkout',
                        'filter_value' => 'true',
                    ),

                    // Notify URL (for paypal IPN)
                    'paypal_notify_url' => array(
                        'name' => __( 'PayPal notify URL (only for developers!)', 'super-forms' ),
                        'label' => __( 'Used for IPN (Instant payment notifications) when payment is confirmed by paypal', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'paypal_notify_url', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'paypal_advanced_settings',
                        'filter_value' => 'true',
                    ),

                    /*
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
                    /*
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
                    */


                )
            );

            if ( class_exists( 'SUPER_Frontend_Posting' ) ) {
                $array['paypal_checkout']['fields']['paypal_completed_post_status'] = array(
                    'name' => __( 'Post status after payment complete', 'super-forms' ),
                    'desc' => __( 'Only used for Front-end posting (publish, future, draft, pending, private, trash, auto-draft)?', 'super-forms' ),
                    'default' => SUPER_Settings::get_value( 0, 'paypal_completed_post_status', $settings['settings'], 'publish' ),
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
                $array['paypal_checkout']['fields']['paypal_completed_signup_status'] = array(
                    'name' => __( 'Registered user login status after payment complete', 'super-forms' ),
                    'desc' => __( 'Only used for Register & Login add-on (active, pending, blocked)?', 'super-forms' ),
                    'default' => SUPER_Settings::get_value( 0, 'paypal_completed_signup_status', $settings['settings'], 'active' ),
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