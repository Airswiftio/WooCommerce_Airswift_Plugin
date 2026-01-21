<?php

/**
 * @wordpress-plugin
 * Plugin Name:         Pelago Crypto Pay for WooCommerce
 * Plugin URI:          https://pelagotech.com
 * Author Name:         Simon
 * Author URI:          https://pelagotech.com
 * Description:         Pay with Pelago - Secure crypto payments made simple for WooCommerce.
 * Version:             2.0.0
 * License:             2.0.0
 * text-domain:         wc-pelago
 */


//Prevent direct access to files
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include function library
require_once(plugin_dir_path(__FILE__) . 'function.php');

// Include plugin function library
require_once(plugin_dir_path(__FILE__) . 'plugin-function.php');

// Include init-start library
require_once(plugin_dir_path(__FILE__) . 'init-start.php');



function pelago_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
        class WC_Pelago_Pay_Gateway extends WC_Payment_Gateway {
            public $instructions,$appKey,$merchantPrikey,$platformPublicKey,$callBackUrl,$merchantId;

            public function __construct() {
                $this->id   = 'pelago_payment';
//                $this->icon = apply_filters( 'woocommerce_pelago_icon',plugins_url('assets/pelago_logo.png', __FILE__ ) );
                $this->has_fields = false;
                $this->method_title = __( 'Pelago Crypto Pay', 'pelago-pay-woo');
                $this->method_description = __( 'Pelago QR code digital payment method.', 'pelago-pay-woo');

                $this->title = $this->get_option( 'title' );
                $this->description = $this->get_option( 'description' );
                $this->instructions = $this->get_option( 'instructions', $this->description );
                $this->merchantId = $this->get_option('merchantId','');
                $this->appKey = $this->get_option('appKey','');
                $this->merchantPrikey = $this->get_option('merchantPrikey','');
                $this->platformPublicKey = $this->get_option('platformPublicKey','');

                $this->callBackUrl = add_query_arg('wc-api', 'wc_pelagopay_gateway', home_url('/'));
                $this->testMode = $this->get_option('testMode','');

                $this->init_form_fields();
                $this->init_settings();

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thank_you_' . $this->id, array( $this, 'thank_you_page' ) );
                add_action('woocommerce_api_wc_pelagopay_gateway', array($this, 'check_ipn_response'));

            }

            /**
             * Get gateway icon.
             * @return string
             */
            public function get_icon():string {
                $icon = apply_filters( 'woocommerce_pelago_icon','<img width="100" src="' . plugins_url('assets/Pelago_logo_black.png', __FILE__ ) . '" alt="' . esc_attr__( 'pelago_logo', 'pelago' ) . '" />' ,$this->id);
                return $icon;
            }

            // Plugin Contents - Can be access through WooCommerce/Settings/Payment
            public function init_form_fields() {
                $this->form_fields = apply_filters( 'woo_pelago_pay_fields', array(
                    'enabled' => array(
                        'title' => __( 'Enable/Disable', 'pelago-pay-woo'),
                        'type' => 'checkbox',
                        'label' => __( 'Enable or Disable Pelago Crypto Pay', 'pelago-pay-woo'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __( 'Title', 'pelago-pay-woo'),
                        'type' => 'text',
                        'default' => __( 'Pelago Crypto Pay', 'pelago-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Title that customers will see in the checkout page.', 'pelago-pay-woo')
                    ),
                    'description' => array(
                        'title' => __( 'Description', 'pelago-pay-woo'),
                        'type' => 'textarea',
                        'default' => __( "Pay with Pelago - Secure crypto payments made simple.", 'pelago-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Description customers will see in the checkout page.', 'pelago-pay-woo')
                    ),
                    'instructions' => array(
                        'title' => __( 'Instructions', 'pelago-pay-woo'),
                        'type' => 'textarea',
                        'default' => __( 'Default instructions', 'pelago-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Instructions that will be added to the thank you page and order email', 'pelago-pay-woo')
                    ),
                    'merchantId' => array(
                        'title' => __('merchantId', 'pelago-pay-woo'),
                        'type' => 'text',
                        'description' => __('Please enter your PelagoPay merchantId.', 'pelago-pay-woo'),
                        'default' => '',
                    ),
                    'appKey' => array(
                        'title' => __('appKey', 'pelago-pay-woo'),
                        'type' => 'text',
                        'description' => __('Please enter your PelagoPay appKey.', 'pelago-pay-woo'),
                        'default' => '',
                    ),
                    'merchantPrikey' => array(
                        'title' => __('merchantPrikey', 'pelago-pay-woo'),
                        'type' => 'text',
                        'description' => __('Please enter your Merchant Private key.', 'pelago-pay-woo'),
                        'default' => '',
                    ),
                    'platformPublicKey' => array(
                        'title' => __('platformPublicKey', 'pelago-pay-woo'),
                        'type' => 'text',
                        'description' => __('Please enter your Platform PublicKey key.', 'pelago-pay-woo'),
                        'default' => '',
                    ),
                    'testMode' => array(
                        'title' => __( 'Enable/Disable', 'pelago-pay-woo'),
                        'type' => 'checkbox',
                        'label' => __( 'Enable or Disable testMode', 'pelago-pay-woo'),
                        'default' => 'no'
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
                <h3><?php _e('PelagoPay', 'woocommerce'); ?></h3>
                <p><?php _e('Completes checkout via PelagoPay', 'woocommerce'); ?></p>
                <p><?php _e("callBackUrl:  $this->callBackUrl", 'woocommerce'); ?></p>

                <?php if ($this->is_valid_for_use()) : ?>

                <table class="form-table">
                    <?php
                    $this->generate_settings_html();
                    ?>
                </table>
            <?php else : ?>
                <div class="inline error">
                    <p><strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: <?php _e('PelagoPay does not support your store currency.', 'woocommerce'); ?></p>
                </div>
            <?php endif;

            }


            public function process_payment( $order_id ):array {
                try {
                    $order = wc_get_order($order_id);
                    if (!$order) {
                        writeLog($this->testMode, "Invalid order ID: $order_id", []);
                        wc_add_notice('Order does not exist, please try again!', 'error');
                        return ['result' => 'failure'];
                    }
                    
                    $api_url = $this->testMode === 'no' ? 'https://pgpay.weroam.xyz':'https://pgpay-stage.weroam.xyz';
                    $pelago_api_url = $this->testMode === 'no' ? 'https://api.pelagotech.com':'https://stage-api.pelagotech.com';

                    // Check whether the required configuration is set
                    $required_configs = [
                        'merchantId' => 'merchantId is not set',
                        'appKey' => 'appKey is not set', 
                        'merchantPrikey' => 'merchantPrikey is not set',
                        'platformPublicKey' => 'platformPublicKey is not set'
                    ];
                    
                    foreach ($required_configs as $config => $error_msg) {
                        if (empty($this->$config)) {
                            $order->add_order_note("PelagoPay configuration error: $error_msg");
                            writeLog($this->testMode, "Configuration missing: $config", []);
                            wc_add_notice('Payment configuration error, please contact merchant!', 'error');
                            return ['result' => 'failure'];
                        }
                    }

                    $total_amount = $order->get_total();
                    $paymentCurrency = strtolower($order->get_currency());
                    
                    // Currency conversion request
                    $currency_data = [
                        'order_id'=>$order_id,
                        'currencyCode'=>$paymentCurrency,
                        'total_amount'=>$total_amount,
                    ];
                    
                    $d = [
                        'do'=>'POST',
                        'url'=>$api_url."/api/currency_to_usd",
                        'data'=>$currency_data
                    ];
                    
                    $res = json_decode(chttp($d), true);
                    if (!$res || !isset($res['code']) || $res['code'] !== 1) {
                        $error_msg = isset($res['msg']) ? $res['msg'] : 'Currency conversion failed';
                        $order->add_order_note("Currency conversion failed: $error_msg");
                        writeLog($this->testMode, "Currency conversion failed", $res);
                        wc_add_notice('Currency conversion failed, please try again later!', 'error');
                        return ['result' => 'failure'];
                    }
                    
                    $total_amount = $res['data'];

                    // Create payment order using v2.0 API
                    $da0  = [
                        'merchantId' => $this->merchantId,
                        'merchantOrderId' => $order_id.'_'.time(),
                        'orderAmount' => $total_amount,
                        'orderCurrency' => 'USD',
                        'notifyUrl' => $this->callBackUrl,
                        // 'redirectUrl' => $order->get_checkout_order_received_url(),
                    ];

                    // Remove empty values and sort for signature
                    $sData = arr2SignStr($da0);
                    $sign = encodeSHA256withRSA($sData,$this->merchantPrikey);

                    $url = $pelago_api_url."/openapi/v2.0/order/create";
                    $post_data = [
                        'data' => $da0,
                        'signature' => $sign
                    ];

                    // Updated header according to new API documentation
                    $headers = [
                        "Content-Type: application/json",
                        "X-App-Key: {$this->appKey}",
                    ];

                    $php_result = json_decode(wPost($url,json_encode($post_data),$headers),true);
                    if ($php_result['code'] !== 0) {
                        $order->add_order_note($php_result['msg']);
                        writeLog($this->testMode, $php_result['msg'], $php_result);
                        wc_add_notice($php_result['msg'], 'error');
                        return ['result' => 'failure'];
                    }

                    // Validate returned payment URL
                    if (!isset($php_result['data']['cashierUrl']) || empty($php_result['data']['cashierUrl'])) {
                        $order->add_order_note("Payment URL retrieval failed");
                        writeLog($this->testMode, "Payment URL missing", $php_result);
                        wc_add_notice('Payment link retrieval failed, please try again!', 'error');
                        return ['result' => 'failure'];
                    }
                    return array(
                        'result'   => 'success',
                        'redirect' => $php_result['data']['cashierUrl'],
                    );
                }
                catch (Exception $e) {
                    $error_msg = "Payment processing exception: " . $e->getMessage();
                    writeLog($this->testMode, $error_msg, [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    if (isset($order)) {
                        $order->add_order_note($error_msg);
                    }
                    
                    return ['result'=>'failure', 'messages'=>home_notice('Payment processing exception occurred, please contact merchant!')];
                }
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

                // Verify necessary fields
                if(empty($data) || !isset($data['signature']) || !isset($data['data']['merchantOrderId'])  || !isset($data['data']['amount']) ) {
                    writeLog($this->testMode,"IPN validation failed: missing required fields",$data);
                    return false;
                }

                // Verify signature
                try {
                    $signatureData = $data['data'] ?? [];
                    $signature = $data['signature'] ?? '';
                    
                    // Use platform public key to verify signature
                    $isValid = verifySHA256withRSA(arr2SignStr($signatureData,''), $signature, str_replace('\n','',$this->platformPublicKey));
                    
                    if (!$isValid) {
                        writeLog($this->testMode,"IPN validation failed: invalid signature", [
                            'signature' => $signature,
                            'data' => $signatureData
                        ]);
                        return false;
                    }
                    
                    writeLog($this->testMode,"IPN validation successful",$data);
                    return true;
                    
                } catch (Exception $e) {
                    writeLog($this->testMode,"IPN validation error: " . $e->getMessage(), $data);
                    return false;
                }
            }


            /**
             * Successful Payment!
             *
             * @access public
             * @return void
             */
            public function successful_request()
            {
                try {
                    $rjson = file_get_contents('php://input');
                    $res_data = json_decode($rjson, true);
                    if(!(isset($res_data) && !empty($res_data))){
                        exit('null');
                    }
                    if(!$res_data['data']){
                        exit('failed:no data');
                    }
                    $arrOrderData = $res_data['data'];
                    if(!$arrOrderData['merchantOrderId']){
                        exit('failed:no merchantOrderId');
                    }
                    if(!$res_data['signature']){
                        exit('failed:no signature');
                    }

                    // Verify signature
                    $Verify = verifySHA256withRSA(arr2SignStr($res_data['data'] ?? [],''),$res_data['signature'] ?? '',str_replace('\n','',$this->platformPublicKey));
                    if(!$Verify){
                        writeLog($this->testMode,"Signature verification failed",$res_data);
                        exit('failed:signature not match');
                    }

                    $client_order_id = explode('_',$arrOrderData['merchantOrderId'])[0];
                    $order_id = $arrOrderData['orderId'];
                    writeLog($this->testMode,"successful_callback-----$order_id",$res_data);

                    $order = new WC_Order($client_order_id);

                    // If the order has been processing, it is not allowed to modify the order status
                    if($order->get_status() === 'processing' || $order->get_status() === 'completed'){
                        exit('SUCCESS');
                    }

                    // orderStatus: 0=Pending, 1=Success, 2=Timeout, 3=Cancelled, 4=Failed, 10=Exchange Pending, 11=Exchange Success, 12=Exchange Failed
                    if ($arrOrderData["orderStatus"] != 1){
                        if ($arrOrderData["orderStatus"] == 2) {
                            $order->update_status('failed', 'Order is timeout.');
                            exit('SUCCESS');
                        }
                        else if ($arrOrderData["orderStatus"] == 3) {
                            $order->update_status('cancelled', 'Order is cancelled.');
                            exit('SUCCESS');
                        }
                        else if ($arrOrderData["orderStatus"] == 4) {
                            $order->add_order_note('Order is refund.');
                            exit('SUCCESS');
                        }
                        else{
//                    $order->add_order_note('PelagoPay PaymentGateWay Error:PelagoPay Payment Status: ' . $arrOrderData["status"]);
                            $order->add_order_note('PelagoPay PaymentGateWay Error : please contact the provider !');
                            exit('failed:PelagoPay PaymentGateWay Error');
                        }
                    }

                    // payStatus = 0 is Not Paid, 1 is Partially Paid, 2 is Fully Paid, 3 is Over Paid
                    // Query the order details. When the payStatus is 1 or 4, the order is marked as paid (completed)
                    if($arrOrderData['payStatus'] == 2){
                        $order->update_status('processing', 'Order has been paid.');
                        exit('SUCCESS');
                    }
                    elseif($arrOrderData['payStatus'] == 3){
                        $order->update_status('processing', 'Order has been paid(over pay).');
                        exit('SUCCESS');
                    }
                    elseif($arrOrderData['payStatus'] == 1){
                        $order->update_status('failed', 'Order is failed(not enough payment).');
                        exit('SUCCESS');
                    }
                    elseif($arrOrderData['payStatus'] == 0){
                        $order->add_order_note('Not Paid !');
                        exit('SUCCESS');
                    }
                    else{
                        $order->update_status('pending', "Order has been paid(waiting).");
                        exit('SUCCESS');
                    }

                }
                catch (Exception $e) {
                    $order->add_order_note('Something went wrong, please contact the merchant for handling(60041)!');
                    exit("failed:{$e->getMessage()}");
                }
            }

            public function thank_you_page(){
                if( $this->instructions ){
                    echo wpautop( $this->instructions );
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
                @ob_clean();
                writeLog($this->testMode,"successful_callback-----init",[]);

                // Use real IPN verification instead of forcing through
                if ($this->check_ipn_request_is_valid()) {
                    $this->successful_request();
                } else {
                    writeLog($this->testMode, "IPN validation failed", $_POST);
                    wp_die("PelagoPay IPN Request Failure - Invalid signature or missing data");
                }
            }
        }
    }
}


// Register the payment gateway with WooCommerce
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_pelago_payment_gateway'); 

// Add plugin settings link (needs to stay in main file for correct __FILE__ reference)
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_pelago_payment_plugin_links' );




