<?php

/**
 * @wordpress-plugin
 * Plugin Name:         AirSwift Payment for WooCommerce
 * Plugin URI:          https://airswift.com
 * Author Name:         Simon
 * Author URI:          https://airswift.com
 * Description:         Pay with Airswift's digital QR Code payment method for WooCommerce.
 * Version:             1.0.1
 * License:             1.0.1
 * text-domain:         wc-airswift
 */


//Prevent direct access to files
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

//If the function is_plugin_active does not exist, the plugin.php function library will be imported
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Check if WooCommerce is installed.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
{
    //notice when woocommerce is not installed
    function woocommerce_not_active_notice(){
        echo '<div class="error"><p><strong>' .__('AirSwiftPay Gateway for WooCommerce requires WooCommerce to be installed.', 'woocommerce'). '</strong></p></div>';
    }

    add_action('admin_notices','woocommerce_not_active_notice');
    return;
}

//Check if WooCommerce is active
if(!is_plugin_active( 'woocommerce/woocommerce.php' ))
{
    //notice when woocommerce is not active
    function woocommerce_not_active_notice(){
        echo '<div class="error"><p><strong>' .__('AirSwiftPay Gateway for WooCommerce requires WooCommerce to be installed and active.', 'woocommerce'). '</strong></p></div>';
    }

    add_action('admin_notices','woocommerce_not_active_notice');
    return;
}


// Once WooCommerce is loaded, initialize the AirSwift plugin.
add_action( 'plugins_loaded', 'airswift_payment_init', 11 );

function airswift_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
        class WC_AirSwift_Pay_Gateway extends WC_Payment_Gateway {
            public $instructions,$appKey,$appSecret,$signKey,$callBackUrl;

            public function __construct() {
                $this->id   = 'airswift_payment';
                // $this->icon = apply_filters( 'woocommerce_airswift_icon', plugins_url('https://www.mars.cloud/images/logo2.svg', __FILE__ ) );
                $this->has_fields = false;
                $this->method_title = __( 'AirSwift Payment', 'airswift-pay-woo');
                $this->method_description = __( 'AirSwift QR code digital payment method.', 'airswift-pay-woo');

                $this->title = $this->get_option( 'title' );
                $this->description = $this->get_option( 'description' );
                $this->instructions = $this->get_option( 'instructions', $this->description );
                $this->appKey = $this->get_option('appKey','');
                $this->appSecret = $this->get_option('appSecret','');
                $this->signKey = $this->get_option('signKey','');
                $this->callBackUrl = add_query_arg('wc-api', 'wc_airswiftpay_gateway', home_url('/'));

                $this->init_form_fields();
                $this->init_settings();

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thank_you_' . $this->id, array( $this, 'thank_you_page' ) );
                add_action('woocommerce_api_wc_airswiftpay_gateway', array($this, 'check_ipn_response'));

            }

            // Plugin Contents - Can be access through WooCommerce/Settings/Payment
            public function init_form_fields() {
                $this->form_fields = apply_filters( 'woo_airswift_pay_fields', array(
                    'enabled' => array(
                        'title' => __( 'Enable/Disable', 'airswift-pay-woo'),
                        'type' => 'checkbox',
                        'label' => __( 'Enable or Disable AirSwift Payment', 'airswift-pay-woo'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __( 'Title', 'airswift-pay-woo'),
                        'type' => 'text',
                        'default' => __( 'AirSwift Payment', 'airswift-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Title that customers will see in the checkout page.', 'airswift-pay-woo')
                    ),
                    'description' => array(
                        'title' => __( 'Description', 'airswift-pay-woo'),
                        'type' => 'textarea',
                        'default' => __( "Pay with AirSwift's digital QR Code payment method.", 'airswift-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Description customers will see in the checkout page.', 'airswift-pay-woo')
                    ),
                    'instructions' => array(
                        'title' => __( 'Instructions', 'airswift-pay-woo'),
                        'type' => 'textarea',
                        'default' => __( 'Default instructions', 'airswift-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Instructions that will be added to the thank you page and odrer email', 'airswift-pay-woo')
                    ),
                    'appKey' => array(
                        'title' => __('appKey', 'airswift-pay-woo'),
                        'type' => 'text',
                        'description' => __('Please enter your AirSwiftPay appKey.', 'airswift-pay-woo'),
                        'default' => '',
                    ),
                    'appSecret' => array(
                        'title' => __('appSecret', 'airswift-pay-woo'),
                        'type' => 'text',
                        'description' => __('Please enter your AirSwiftPay appSecret.', 'airswift-pay-woo'),
                        'default' => '',
                    ),
                    'signKey' => array(
                        'title' => __('signKey', 'airswift-pay-woo'),
                        'type' => 'text',
                        'description' => __('Please enter your AirSwiftPay signKey.', 'airswift-pay-woo'),
                        'default' => '',
                    ),

                ));
            }

            /**
             * Check if this gateway is enabled and available in the user's country
             *
             * @access public
             * @return bool
             */
            public function is_valid_for_use():bool
            {
                //if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_boundarypay_supported_currencies', array( 'AUD', 'CAD', 'USD', 'EUR', 'JPY', 'GBP', 'CZK', 'BTC', 'LTC' ) ) ) ) return false;
                // ^- instead of trying to maintain this list just let it always work
                return true;
            }

            /**
             * Displaying admin options
             * - That will output your settings in the correct format.
             * @since 1.0.0
             */
            public function admin_options()
            {
                ?>
                <h3><?php _e('AirSwiftPay', 'woocommerce'); ?></h3>
                <p><?php _e('Completes checkout via AirSwiftPay', 'woocommerce'); ?></p>
                <p><?php _e("callBackUrl:  $this->callBackUrl", 'woocommerce'); ?></p>

                <?php if ($this->is_valid_for_use()) : ?>

                <table class="form-table">
                    <?php
                    $this->generate_settings_html();
                    ?>
                </table>
            <?php else : ?>
                <div class="inline error">
                    <p><strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: <?php _e('AirSwiftPay does not support your store currency.', 'woocommerce'); ?></p>
                </div>
            <?php endif;

            }


            /**
             * Check IPN request validity
             *
             * @return bool
             */
            public function check_ipn_request_is_valid():bool
            {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                if(empty($data) || !isset($data['sign']) || !isset($data['clientOrderSn']) || !isset($data['coinUnit']) || !isset($data['amount']) || !isset($data['rate']) ) {
                    return false;
                }

                //Verify signature
                $sign = md5($this->signKey.$data['clientOrderSn'].$data['coinUnit'].$data['amount'].$data['rate']);
                if(strtolower($sign) === strtolower($data['sign'])){
                    return true;
                }
                return false;
            }


            /**
             * Successful Payment!
             *
             * @access public
             * @return void
             */
            public function successful_request()
            {
                global $woocommerce;

                $rjson = file_get_contents('php://input');
                $rdata = json_decode($rjson, true);

                $valid_order_id = $rdata["clientOrderSn"];
                $order = new WC_Order($valid_order_id);
                if ($rdata["status"] == 1) {
                    $order->update_status('completed', 'Order has been paid.');
                    exit('SUCCESS');
                }
                else if ($rdata["status"] == 2) {
                    $order->update_status('failed', 'Order is failed.');
                    exit('SUCCESS');
                }
                else if ($rdata["status"] == 3) {
                    $order->update_status('cancelled', 'Order is cancelled.');
                    exit('SUCCESS');
                }

                $order->add_order_note('AirSwiftPay Payment Status: ' . $rdata["status"]);
                exit('failed');
            }

            public function thank_you_page(){
                if( $this->instructions ){
                    echo wpautop( $this->instructions );
                }
            }

            public function process_payment( $order_id ):array {
                $order = wc_get_order($order_id);

                //At present, the AirSwift payment gateway only supports the conversion of USD to cryptocurrencies, so it is necessary to determine
                $paymentCurrency = strtolower($order->get_currency());
                if($paymentCurrency !== 'usd'){
                    $msg = "AirSwift Payment gateway only supports USD!";
                    $order->add_order_note($msg);
                    return ['result'=>'success', 'messages'=>home_notice($msg)];
                }

                //Check whether the appKey and appSecret is configured
                if(empty($this->appKey)){
                    $msg = "AirSwiftPay's appKey is not set!";
                    $order->add_order_note($msg);
                    return ['result'=>'success', 'messages'=>home_notice('Something went wrong, please contact the merchant for handling!')];
                }
                if(empty($this->appSecret)){
                    $msg = "AirSwiftPay's appSecret is not set!";
                    $order->add_order_note($msg);
                    return ['result'=>'success', 'messages'=>home_notice('Something went wrong, please contact the merchant for handling!')];
                }

                //Create payment
                $appKey = $this->appKey;
                $tradeType = 0;
                $basicsType = 1;
                $currency_unit = "USDT";
                $nonce = mt_rand(100000,999999);
//                $customer_id = $order->customer_id;
//                $order_note = $order->customer_note;
                $timestamp = floor(microtime(true) * 1000);
                $total_amount = $order->get_total();//todo 正式
//                $total_amount = 0.01;
                $appSecret = $this->appSecret;
                $clientOrderSn = $order_id;
                $hash_value = md5($appKey.$nonce.$timestamp.$currency_unit.$total_amount.$order_id.$basicsType.$tradeType.$appSecret);
                $url = "https://order.airswift.io/docking/order/create?appKey=$appKey&sign=$hash_value&timestamp=$timestamp&nonce=$nonce";
                $data = array(
                    'clientOrderSn' => $clientOrderSn,
                    'tradeType' => $tradeType,
                    'coinUnit' =>$currency_unit,
                    'basicsType' => $basicsType,
                    'amount' => $total_amount,
//                    'remarks' => $order_note,
                );
                $options = array(
                    'http' => array(
                        'header'  => "Content-type: application/json;charset=UTF-8",
                        'method'  => 'POST',
                        'content' => json_encode($data),
                    )
                );

                $context  = stream_context_create($options);
                $result = file_get_contents($url, false, $context);
                $php_result = json_decode($result);
                if ($php_result->code !== 200) {
                    $msg = "AirSwiftPay's createPayment failed!(".$php_result->message.")";
                    $order->add_order_note($msg);
                    return ['result'=>'success', 'messages'=>home_notice('Something went wrong, please contact the merchant!')];
                } else {
                    $order->update_status('processing',  __( 'Awaiting AirSwift Payment', 'airswift-pay-woo'));
//                    $order->reduce_order_stock();
//                    WC()->cart->empty_cart();
                    return array(
                        'result'   => 'success',
                        'redirect' => $php_result->data,
                        // Redirects to the order confirmation page:
                        // 'redirect' => $this->get_return_url($order)
                    );
                }
            }

            /**
             * Check for IPN Response
             *
             * @access public
             * @return void
             */
            public function check_ipn_response()
            {
                $url = "http://bp.rome9.com/api-xielog";
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                $options = array(
                    'http' => array(
                        'header'  => "Content-type: application/json;charset=UTF-8",
                        'method'  => 'POST',
                        'content' => json_encode($data),
                    )
                );
                $result = file_get_contents($url, false, stream_context_create($options));
                @ob_clean();
                if ($this->check_ipn_request_is_valid()) {
                    $this->successful_request();
                } else {
                    wp_die("AirSwiftPay IPN Request Failure");
//            $this->debug_post_out( 'response result wp_die' ,  'AirSwiftPay IPN Request Failure');
                }
            }
        }
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_to_woo_airswift_payment_gateway');
function add_to_woo_airswift_payment_gateway( $gateways ) {
    $gateways[] = 'WC_AirSwift_Pay_Gateway';
    return $gateways;
}

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_airswift_payment_plugin_links( $links ) {
    $adminurl = 'admin.php?page=wc-settings&tab=checkout&section=airswift_payment';
    $plugin_links = array(
        '<a href="' . admin_url( $adminurl ) . '">' . __( 'Setting', 'woocommerce' ) . '</a>',
    );
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_airswift_payment_plugin_links' );

if (!function_exists('home_notice')){
    function home_notice($msg) :string {
        return  '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
<div class="woocommerce-error">'.$msg.'</div>
</div>';
    }
}