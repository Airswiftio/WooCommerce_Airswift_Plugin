<?php

/**
 * @wordpress-plugin
 * Plugin Name: Airswift Payment for WooCommerce
 * Plugin URI: https://airswift.com
 * Author Name: Heesoo
 * Author URI: https://airswift.com
 * Description: Adds Airswift's QR code payment method for WooCommerce.
 * Version: 0.1.0
 * License: 0.1.0
 * text-domain: wc-airswift
*/ 

// Check if WooCommerce is installed.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

// Once WooCommerce is loaded, initialize the Airswift plugin.
add_action( 'plugins_loaded', 'airswift_payment_init', 11 );

function airswift_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
        class WC_AirSwift_Pay_Gateway extends WC_Payment_Gateway {

            public function __construct() {
                $this->id   = 'airswift_payment';
                // $this->icon = apply_filters( 'woocommerce_airswift_icon', plugins_url('https://www.mars.cloud/images/logo2.svg', __FILE__ ) );
                $this->has_fields = false;
                $this->method_title = __( 'Airswift Payment', 'airswift-pay-woo');
                $this->method_description = __( 'Airswift QR code digital payment method.', 'airswift-pay-woo');

                $this->title = $this->get_option( 'title' );
                $this->description = $this->get_option( 'description' );
                $this->instructions = $this->get_option( 'instructions', $this->description );

                $this->init_form_fields();
                $this->init_settings();

                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thank_you_' . $this->id, array( $this, 'thank_you_page' ) );
            }

            // Plugin Contents - Can be access through WooCommerce/Settings/Payment
            public function init_form_fields() {
                $this->form_fields = apply_filters( 'woo_airswift_pay_fields', array(
                    'enabled' => array(
                        'title' => __( 'Enable/Disable', 'airswift-pay-woo'),
                        'type' => 'checkbox',
                        'label' => __( 'Enable or Disable Airswift Payment', 'airswift-pay-woo'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __( 'Title', 'airswift-pay-woo'),
                        'type' => 'text',
                        'default' => __( 'Airswift Payment', 'airswift-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Title that customers will see in the checkout page.', 'airswift-pay-woo')
                    ),
                    'description' => array(
                        'title' => __( 'Description', 'airswift-pay-woo'),
                        'type' => 'textarea',
                        'default' => __( "Pay with Airswift's digital QR Code payment method.", 'airswift-pay-woo'),
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
                    // 'enable_for_virtual' => array(
                    //     'title'   => __( 'Accept for virtual orders', 'woocommerce' ),
                    //     'label'   => __( 'Accept COD if the order is virtual', 'woocommerce' ),
                    //     'type'    => 'checkbox',
                    //     'default' => 'yes',
                    // ),
                ));
            }

            public function process_payment( $order_id ) {
                // TODO: Update backend order status.
                // Get order information and update order status.
                $order = wc_get_order($order_id);
                $order->update_status('processing',  __( 'Awaiting Airswift Payment', 'airswift-pay-woo'));

                // TODO: Get valid appKey.
                $appKey = '2f678c1e-72e7-47cf-904e-3be6cbed0fae';
                $nonce = mt_rand(100000,999999);
                $timestamp = floor(microtime(true) * 1000);
                // TODO: Calculate sign with valid appKey.
                // Sign calculation: Md5.md5Digest($appKey+$nonce+$timestamp+$coinUnit+$amount+$clientOrderSn+$basicsType+$tradeType+$appSecret);
                $sign = '0F2590EE3F4435F8723D9E9E047C3CB6';

                // // Add API here:
                $url = `https://order.airswift.io/docking/order/create?appKey=$appKey&sign=$sign&timestamp=$timestamp&nonce=$nonce`;

                $data = array(
                    'clientOrderSn' => $order_id,
                    'tradeType' => 0,
                    'coinUnit' => 'USDT',
                    'basicsType' => 1,
                    'amount' => (float)$order->get_total(),
                    'remarks' => $order->customer_note,
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

                if ($php_result->code === 200) {
                    // Reduce instock product # and emtpy user's cart.
                    $order->reduce_order_stock();
                    WC()->cart->empty_cart();

                    return array(
                        'result'   => 'success',
                        // 'redirect' => $php_result->url
                        // 'redirect' => $this->get_return_url($order)
                        // 'redirect' => 'https://order.airswift.io/order/index.html?orderSn=orderSn:611835414564093952&amount=99.00000000&fee=0E-8'
                    );
                } else {
                    echo $php_result->message;
                }
            }

            public function thank_you_page(){
                if( $this->instructions ){
                    echo wpautop( $this->instructions );
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