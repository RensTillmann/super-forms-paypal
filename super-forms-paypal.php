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

            require __DIR__ . '/../bootstrap.php';
            use PayPal\Api\Amount;
            use PayPal\Api\Details;
            use PayPal\Api\Item;
            use PayPal\Api\ItemList;
            use PayPal\Api\Payer;
            use PayPal\Api\Payment;
            use PayPal\Api\RedirectUrls;
            use PayPal\Api\Transaction;

            $payer = new Payer();
            $payer->setPaymentMethod("paypal");

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

            $details = new Details();
            $details->setShipping(1.2)
                ->setTax(1.3)
                ->setSubtotal(17.50);

            $amount = new Amount();
            $amount->setCurrency("USD")
                ->setTotal(20)
                ->setDetails($details);

            $transaction = new Transaction();
            $transaction->setAmount($amount)
                ->setItemList($itemList)
                ->setDescription("Payment description")
                ->setInvoiceNumber(uniqid());

            $baseUrl = getBaseUrl();
            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl("$baseUrl/ExecutePayment.php?success=true")
                ->setCancelUrl("$baseUrl/ExecutePayment.php?success=false");

            $payment = new Payment();
            $payment->setIntent("sale")
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions(array($transaction));

            $request = clone $payment;

            try {
                $payment->create($apiContext);
            } catch (Exception $ex) {
                ResultPrinter::printError("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $request, $ex);
                exit(1);
            }
            $approvalUrl = $payment->getApprovalLink();
            ResultPrinter::printResult("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $payment);
            return $payment;



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

            // @since 1.2.2 - first reset order entry data
            SUPER_Forms()->session->set( '_super_paypal_entry_data', false );

            if( (isset($settings['paypal_checkout'])) && ($settings['paypal_checkout']=='true') ) {

                // @since 1.2.2 - save the entry data to the order
                SUPER_Forms()->session->set( '_super_paypal_entry_data', $data );

                // No products defined to add to cart!
                if( (!isset($settings['paypal_checkout_products'])) || (empty($settings['paypal_checkout_products'])) ) {
                    $msg = __( 'You haven\'t defined what products should be added to the cart. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form settings and try again', 'super-forms' );
                    SUPER_Common::output_error(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }

                $products = array();
                $paypal_checkout_products = explode( "\n", $settings['paypal_checkout_products'] );  
                $new_paypal_checkout_products = $paypal_checkout_products;
                foreach( $paypal_checkout_products as $k => $v ) {
                    $product =  explode( "|", $v );
                    if( isset( $product[0] ) ) $product_id_tag = trim($product[0], '{}');
                    if( isset( $product[1] ) ) $product_quantity_tag = trim($product[1], '{}');
                    if( isset( $product[2] ) ) $product_variation_id_tag = trim($product[2], '{}');
                    if( isset( $product[3] ) ) $product_price_tag = trim($product[3], '{}');

                    $looped = array();
                    $i=2;
                    while( isset( $data[$product_id_tag . '_' . ($i)]) ) {
                        if(!in_array($i, $looped)){
                            $new_line = '';
                            if( $product[0][0]=='{' ) { $new_line .= '{' . $product_id_tag . '_' . $i . '}'; }else{ $new_line .= $product[0]; }
                            if( $product[1][0]=='{' ) { $new_line .= '|{' . $product_quantity_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[1]; }
                            if( $product[2][0]=='{' ) { $new_line .= '|{' . $product_variation_id_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[2]; }
                            if( $product[3][0]=='{' ) { $new_line .= '|{' . $product_price_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[3]; }
                            $new_paypal_checkout_products[] = $new_line;
                            $looped[$i] = $i;
                            $i++;
                        }else{
                            break;
                        }
                    }

                    $i=2;
                    while( isset( $data[$product_quantity_tag . '_' . ($i)]) ) {
                        if(!in_array($i, $looped)){
                            $new_line = '';
                            if( $product[0][0]=='{' ) { $new_line .= '{' . $product_id_tag . '_' . $i . '}'; }else{ $new_line .= $product[0]; }
                            if( $product[1][0]=='{' ) { $new_line .= '|{' . $product_quantity_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[1]; }
                            if( $product[2][0]=='{' ) { $new_line .= '|{' . $product_variation_id_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[2]; }
                            if( $product[3][0]=='{' ) { $new_line .= '|{' . $product_price_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[3]; }
                            $new_paypal_checkout_products[] = $new_line;
                            $looped[$i] = $i;
                            $i++;
                        }else{
                            break;
                        }
                    }

                    $i=2;
                    while( isset( $data[$product_variation_id_tag . '_' . ($i)]) ) {
                        if(!in_array($i, $looped)){
                            $new_line = '';
                            if( $product[0][0]=='{' ) { $new_line .= '{' . $product_id_tag . '_' . $i . '}'; }else{ $new_line .= $product[0]; }
                            if( $product[1][0]=='{' ) { $new_line .= '|{' . $product_quantity_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[1]; }
                            if( $product[2][0]=='{' ) { $new_line .= '|{' . $product_variation_id_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[2]; }
                            if( $product[3][0]=='{' ) { $new_line .= '|{' . $product_price_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[3]; }
                            $new_paypal_checkout_products[] = $new_line;
                            $looped[$i] = $i;
                            $i++;
                        }else{
                            break;
                        }
                    }

                    $i=2;
                    while( isset( $data[$product_price_tag . '_' . ($i)]) ) {
                        if(!in_array($i, $looped)){
                            $new_line = '';
                            if( $product[0][0]=='{' ) { $new_line .= '{' . $product_id_tag . '_' . $i . '}'; }else{ $new_line .= $product[0]; }
                            if( $product[1][0]=='{' ) { $new_line .= '|{' . $product_quantity_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[1]; }
                            if( $product[2][0]=='{' ) { $new_line .= '|{' . $product_variation_id_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[2]; }
                            if( $product[3][0]=='{' ) { $new_line .= '|{' . $product_price_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $product[3]; }
                            $new_paypal_checkout_products[] = $new_line;
                            $looped[$i] = $i;
                            $i++;
                        }else{
                            break;
                        }
                    }
                }

                foreach( $new_paypal_checkout_products as $k => $v ) {
                    $product =  explode( "|", $v );
                    $product_id = 0;
                    $product_quantity = 0;
                    $product_variation_id = '';
                    $product_price = '';
                    if( isset( $product[0] ) ) $product_id = SUPER_Common::email_tags( $product[0], $data, $settings );
                    if( isset( $product[1] ) ) $product_quantity = SUPER_Common::email_tags( $product[1], $data, $settings );
                    if( isset( $product[2] ) ) $product_variation_id = SUPER_Common::email_tags( $product[2], $data, $settings );
                    if( isset( $product[3] ) ) $product_price = SUPER_Common::email_tags( $product[3], $data, $settings );
                    $product_quantity = absint($product_quantity);
                    if( $product_quantity>0 ) {
                        $products[] = array(
                            'id' => absint($product_id),
                            'quantity' => absint($product_quantity),
                            'variation_id' => absint($product_variation_id),
                            'price' => $product_price,
                        );
                    }
                }

                global $paypal;

                // Empty the cart
                if( (isset($settings['paypal_checkout_empty_cart'])) && ($settings['paypal_checkout_empty_cart']=='true') ) {
                    $paypal->cart->empty_cart();
                }

                // Remove any coupons.
                if( (isset($settings['paypal_checkout_remove_coupons'])) && ($settings['paypal_checkout_remove_coupons']=='true') ) {
                    $paypal->cart->remove_coupons();
                }

                // Add discount
                if( (isset($settings['paypal_checkout_coupon'])) && ($settings['paypal_checkout_coupon']!='') ) {
                    $paypal->cart->add_discount($settings['paypal_checkout_coupon']);
                }

                // Delete any fees
                if( (isset($settings['paypal_checkout_remove_fees'])) && ($settings['paypal_checkout_remove_fees']=='true') ) {
                    $paypal->session->set( 'fees', array() );
                    SUPER_Forms()->session->set( '_super_wc_fee', false );
                }

                // Add fee
                if( (isset($settings['paypal_checkout_fees'])) && ($settings['paypal_checkout_fees']!='') ) {
                    $fees = array();
                    $paypal_checkout_fees = explode( "\n", $settings['paypal_checkout_fees'] );  
                    foreach( $paypal_checkout_fees as $k => $v ) {
                        $fee =  explode( "|", $v );
                        $name = '';
                        $amount = 0;
                        $taxable = false;
                        $tax_class = '';
                        if( isset( $fee[0] ) ) $name = SUPER_Common::email_tags( $fee[0], $data, $settings );
                        if( isset( $fee[1] ) ) $amount = SUPER_Common::email_tags( $fee[1], $data, $settings );
                        if( isset( $fee[2] ) ) $taxable = SUPER_Common::email_tags( $fee[2], $data, $settings );
                        if( isset( $fee[3] ) ) $tax_class = SUPER_Common::email_tags( $fee[3], $data, $settings );
                        if( $amount>0 ) {
                            $fees[] = array(
                                'name' => $name,            // ( string ) required – Unique name for the fee. Multiple fees of the same name cannot be added.
                                'amount' => $amount,        // ( float ) required – Fee amount.
                                'taxable' => $taxable,      // ( bool ) optional – (default: false) Is the fee taxable?
                                'tax_class' => $tax_class,  // ( string ) optional – (default: '') The tax class for the fee if taxable. A blank string is standard tax class.
                            );
                        }
                    }
                    SUPER_Forms()->session->set( '_super_wc_fee', $fees );
                }

                // @since 1.2.2 - Add custom checkout fields
                if( (isset($settings['paypal_checkout_fields'])) && ($settings['paypal_checkout_fields']!='') ) {
                    $fields = array();
                    $paypal_checkout_fields = explode( "\n", $settings['paypal_checkout_fields'] );  
                    foreach( $paypal_checkout_fields as $k => $v ) {
                        $field =  explode( "|", $v );
                        if( !isset( $field[0] ) ) {
                            continue; 
                        }
                        $name = '';
                        $value = '';
                        $label = '';
                        $placeholder = '';
                        $type = 'text';
                        $section = 'billing';
                        $required = 'true';
                        $clear = 'true';
                        $class = 'super-checkout-custom';
                        $label_class = 'super-checkout-custom-label';
                        $options = 'red,Red;blue,Blue;green,Green';
                        if( isset( $field[0] ) ) $name = SUPER_Common::email_tags( $field[0], $data, $settings );
                        if( isset( $field[1] ) ) $value = SUPER_Common::email_tags( $field[1], $data, $settings );
                        if( isset( $field[2] ) ) $label = SUPER_Common::email_tags( $field[2], $data, $settings );
                        if( isset( $field[3] ) ) $placeholder = SUPER_Common::email_tags( $field[3], $data, $settings );
                        if( isset( $field[4] ) ) $type = SUPER_Common::email_tags( $field[4], $data, $settings );
                        if( isset( $field[5] ) ) $section = SUPER_Common::email_tags( $field[5], $data, $settings );
                        if( isset( $field[6] ) ) $required = SUPER_Common::email_tags( $field[6], $data, $settings );
                        if( isset( $field[7] ) ) $clear = SUPER_Common::email_tags( $field[7], $data, $settings );
                        if( isset( $field[8] ) ) $class = SUPER_Common::email_tags( $field[8], $data, $settings );
                        if( isset( $field[9] ) ) $label_class = SUPER_Common::email_tags( $field[9], $data, $settings );
                        if( isset( $field[10] ) ) $options = SUPER_Common::email_tags( $field[10], $data, $settings );


                        // Only add the field if the field name was visible in the form itself
                        if( (isset($settings['paypal_checkout_fields_skip_empty'])) && ($settings['paypal_checkout_fields_skip_empty']=='true') ) {
                            if( !isset($data[$name]) ) {
                                continue;
                            }
                        }

                        $fields[] = array(
                            'name' => $name,
                            'value' => $value,
                            'label' => $label,
                            'placeholder' => $placeholder,
                            'type' => $type,
                            'section' => $section,
                            'required' => $required,
                            'clear' => $clear,
                            'class' => $class,
                            'label_class' => $label_class,
                            'options' => $options
                        );
                    }
                    SUPER_Forms()->session->set( '_super_wc_custom_fields', $fields );
                }


                global $wpdb;

                // Now add the product(s) to the cart
                foreach( $products as $k => $v ) {

                    if( class_exists('WC_Name_Your_Price_Helpers') ) {
                        $posted_nyp_field = 'nyp' . apply_filters( 'nyp_field_prefix', '', $v['id'] );
                        $value = trim( str_replace( '.', get_option( 'paypal_price_decimal_sep' ), $v['price'] ) );
                        $_REQUEST[$posted_nyp_field] = $value;
                    }

                    $new_attributes = array();
                    if( $v['variation_id']!=0 ) {
                        $product = wc_get_product( $v['id'] );
                        if( $product->product_type=='variable' ) {
                            $attributes = $product->get_variation_attributes();
                            foreach( $attributes as $ak => $av ) {
                                $new_attributes[$ak] = get_post_meta( $v['variation_id'], 'attribute_' . $ak, true );
                            }
                        }
                    }
                    $paypal->cart->add_to_cart(
                        $v['id'],               // ( int ) optional – contains the id of the product to add to the cart
                        $v['quantity'],         // ( int ) optional default: 1 – contains the quantity of the item to add
                        $v['variation_id'],     // ( int ) optional –
                        $new_attributes         // ( array ) optional – attribute values
                                                // ( array ) optional – extra cart item data we want to pass into the item
                    );

                }

                // Redirect to cart / checkout page
                if( isset($settings['paypal_redirect']) ) {
                    $paypal->session->set( '_super_form_data', $data ); // @since 1.2.0 - save data to session for billing fields
                    $redirect = null;
                    if( $settings['paypal_redirect']=='checkout' ) {
                        $redirect = $paypal->cart->get_checkout_url();
                    }
                    if( $settings['paypal_redirect']=='cart' ) {
                        $redirect = $paypal->cart->get_cart_url();
                    }
                    if( $redirect!=null ) {
                        SUPER_Common::output_error(
                            $error = false,
                            $msg = '',
                            $redirect = $redirect
                        );
                    }
                }

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