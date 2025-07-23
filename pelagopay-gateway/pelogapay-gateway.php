<?php

/**
 * @wordpress-plugin
 * Plugin Name:         Pelago Payment for WooCommerce
 * Plugin URI:          https://pelago.io
 * Author Name:         Simon
 * Author URI:          https://pelago.io
 * Description:         Pay with pelago's digital QR Code payment method for WooCommerce.
 * Version:             1.0.1
 * License:             1.0.1
 * text-domain:         wc-pelago
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
        echo '<div class="error"><p><strong>' .__('PelagoPay Gateway for WooCommerce requires WooCommerce to be installed.', 'woocommerce'). '</strong></p></div>';
    }

    add_action('admin_notices','woocommerce_not_active_notice');
    return;
}

//Check if WooCommerce is active
if(!is_plugin_active( 'woocommerce/woocommerce.php' ))
{
    //notice when woocommerce is not active
    function woocommerce_not_active_notice(){
        echo '<div class="error"><p><strong>' .__('PelagoPay Gateway for WooCommerce requires WooCommerce to be installed and active.', 'woocommerce'). '</strong></p></div>';
    }

    add_action('admin_notices','woocommerce_not_active_notice');
    return;
}


// Once WooCommerce is loaded, initialize the Pelago plugin.
add_action( 'plugins_loaded', 'pelago_payment_init', 11 );
function pelago_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
        class WC_Pelago_Pay_Gateway extends WC_Payment_Gateway {
            public $instructions,$appKey,$merchantPrikey,$platformPublicKey,$callBackUrl,$merchantId;

            public function __construct() {
                $this->id   = 'pelago_payment';
//                $this->icon = apply_filters( 'woocommerce_pelago_icon',plugins_url('assets/pelago_logo.png', __FILE__ ) );
                $this->has_fields = false;
                $this->method_title = __( 'Pelago Payment', 'pelago-pay-woo');
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
                        'label' => __( 'Enable or Disable Pelago Payment', 'pelago-pay-woo'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __( 'Title', 'pelago-pay-woo'),
                        'type' => 'text',
                        'default' => __( 'Pelago Payment', 'pelago-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Title that customers will see in the checkout page.', 'pelago-pay-woo')
                    ),
                    'description' => array(
                        'title' => __( 'Description', 'pelago-pay-woo'),
                        'type' => 'textarea',
                        'default' => __( "Pay with Pelago's digital QR Code payment method.", 'pelago-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Description customers will see in the checkout page.', 'pelago-pay-woo')
                    ),
                    'instructions' => array(
                        'title' => __( 'Instructions', 'pelago-pay-woo'),
                        'type' => 'textarea',
                        'default' => __( 'Default instructions', 'pelago-pay-woo'),
                        'desc_tip' => true,
                        'description' => __( 'Instructions that will be added to the thank you page and odrer email', 'pelago-pay-woo')
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
                    $api_url = $this->testMode === 'no' ? 'https://pgpay.weroam.xyz':'https://pgpay-stage.weroam.xyz';

                    //Check whether the appKey and appSecret is configured
                    if(empty($this->merchantId)){
                        $msg = "PelagoPay's merchantId is not set!";
                        $order->add_order_note($msg);
                        return ['result'=>'success', 'messages'=>home_notice('The pelago plugin Settings failed(60035)!')];
                    }
                    if(empty($this->appKey)){
                        $msg = "PelagoPay's appKey is not set!";
                        $order->add_order_note($msg);
                        return ['result'=>'success', 'messages'=>home_notice('The pelago plugin Settings failed(60036)!')];
                    }
                    if(empty($this->merchantPrikey)){
                        $msg = "PelagoPay's merchantPrikey is not set!";
                        $order->add_order_note($msg);
                        return ['result'=>'success', 'messages'=>home_notice('The pelago plugin Settings failed(60037)!')];
                    }
                    if(empty($this->platformPublicKey)){
                        $msg = "PelagoPay's platformPublicKey is not set!";
                        $order->add_order_note($msg);
                        return ['result'=>'success', 'messages'=>home_notice('The pelago plugin Settings failed(60038)!')];
                    }

                    $total_amount = $order->get_total();
                    $paymentCurrency = strtolower($order->get_currency());
                    $d = [
                        'do'=>'POST',
                        'url'=>$api_url."/api/currency_to_usd",
                        'data'=>[
                            'order_id'=>$order_id,
                            'currencyCode'=>$paymentCurrency,
                            'total_amount'=>$total_amount,
                        ]
                    ];
                    $res = json_decode(chttp($d),true);

                    if(!isset($res['code']) || $res['code'] !== 1){
                        $order->add_order_note($res['msg']);
                        return ['result'=>'success', 'messages'=>home_notice('Something went wrong, please contact the merchant for handling(60038)!')];
                    }
                    $total_amount = $res['data'];

                    //pre pay
                    $currency_unit = "USDT-ERC20";
                    $nonce = mt_rand(100000,999999);
                    $timestamp = floor(microtime(true) * 1000);
                    $data = [
                        'merchantId' => $this->merchantId,
                        'merchantPrikey' =>$this->merchantPrikey,
                        'appKey' =>$this->appKey,
                        'order_id' => $order_id,
                        'coinId' =>$currency_unit,
                        'amount' => $total_amount,
                        'timestamp' => $timestamp,
                        'nonce' => $nonce,
                        'notifyUrl' => $this->callBackUrl,
                        'redirectUrl' =>$order->get_checkout_order_received_url(),
//                    'remarks' => $order_note,
                    ];


                    $d = [
                        'do'=>'POST',
                        'url'=>$api_url."/woo/api-pre-pay",
                        'data'=>$data
                    ];
                    $res = json_decode(chttp($d),true);
                    if(!isset($res['code']) || $res['code'] !== 1){
                        $order->add_order_note($res['msg']);
                        return ['result'=>'success', 'messages'=>home_notice('Something went wrong, please contact the merchant for handling(60039)!')];
                    }

                    return array(
                        'result'   => 'success',
                        'redirect' => $res['data']['url'],
                        // Redirects to the order confirmation page:
                        // 'redirect' => $this->get_return_url($order)
                    );
                }
                catch (Exception $e) {
                    return ['result'=>'success', 'messages'=>home_notice('Something went wrong, please contact the merchant for handling(60040)!')];
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

                if(empty($data) || !isset($data['signature']) || !isset($data['data']['merchantOrderId']) || !isset($data['data']['coinId']) || !isset($data['data']['amount']) ) {
                    writeLog($this->testMode,"check_ipn_request_is_valid1-----",$data);
                    return false;
                }

                //Verify signature todo 888
//                $sign = md5($this->signKey.$data['merchantOrderId'].$data['coinUnit'].$data['amount'].$data['rate']);
//                if(strtolower($sign) === strtolower($data['sign'])){
//                    writeLog($this->testMode,"check_ipn_request_is_valid3-----",$data);
//                    return true;
//                }
                writeLog($this->testMode,"check_ipn_request_is_valid2-----",$data);
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
                try {
                    writeLog($this->testMode,"successful_callback-----init",[]);
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

                    $Verify = verifySHA256withRSA(arr2SignStr($arrJson['data'] ?? [],''),$arrJson['signature'] ?? '',$this->platformPublicKey);
                    if(!$Verify){
                        exit('failed:signature not match');
                    }

                    $client_order_id = explode('_',$arrOrderData['merchantOrderId'])[0];
                    $order_id = $arrOrderData['orderId'];
                    writeLog($this->testMode,"successful_callback-----$order_id",$res_data);

                    $order = new WC_Order($client_order_id);

                    //If the order has been processing, it is not allowed to modify the order status
                    if($order->get_status() === 'processing' || $order->get_status() === 'completed'){
                        exit('SUCCESS');
                    }

                    // orderStatus = 0 is Pending, 1 is Success, 2 is Timeout, 3 is Cancelled, 4 is Refund
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
                /*$this->check_ipn_request_is_valid()*/
                if (true ) {
                    $this->successful_request();
                } else {
                    wp_die("PelagoPay IPN Request Failure");
//            $this->debug_post_out( 'response result wp_die' ,  'PelagoPay IPN Request Failure');
                }
            }
        }
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_to_woo_pelago_payment_gateway');
function add_to_woo_pelago_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Pelago_Pay_Gateway';
    return $gateways;
}

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_pelago_payment_plugin_links( $links ) {
    $adminurl = 'admin.php?page=wc-settings&tab=checkout&section=pelago_payment';
    $plugin_links = array(
        '<a href="' . admin_url( $adminurl ) . '">' . __( 'Setting', 'woocommerce' ) . '</a>',
    );
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_pelago_payment_plugin_links' );

if (!function_exists('home_notice')){
    function home_notice($msg) :string {
        return  '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
<div class="woocommerce-error">'.$msg.'</div>
</div>';
    }
}

if (!function_exists('chttp')) {
    function chttp($d = [])
    {
        $mrd = ['url' => '', 'do' => '', 'tz' => '', 'data' => '', 'ref' => '', 'llq' => '', 'qt' => '', 'cookie' => '', 'time' => '', 'daili' => [], 'headon' => '', 'code' => '', 'nossl' => '', 'to_utf8' => '', 'gzip' => '', 'port' => ''];
        $d = array_merge($mrd, $d);

        $url = $d['url'];
        if ($url == "") {
            exit("URL不能为空!");
        }
        $header = [];

        if ($d['llq']) {
            $header[] = "User-Agent:" . $d['agent'];
        } else {
            $header[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64)AppleWebKit/537.36 (KHTML, like Gecko)Chrome/63.0.3239.26 Safari/537.36';
        }
        if ($d['ref']) {
            $header[] = "Referer:" . $d['ref'];
        }

        $ch = curl_init($url);
        if ($d['port'] != '') {
            curl_setopt($ch, CURLOPT_PORT, intval($d['port']));
        }
        //cookie 文件/文本
        if ($d['cookie'] != "") {
            if (substr($d['cookie'], -4) == ".txt") {
                //文件不存在则生成
                if (!wjif($d['cookie'])) {
                    wjxie($d['cookie'], '');
                }
                $d['cookie'] = realpath($d['cookie']);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $d['cookie']);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $d['cookie']);
            } else {
                $cookie = 'cookie: ' . $d['cookie'];
                $header[] = $cookie;
            }
        }

        //附加头信息
        if ($d['qt']) {
            foreach ($d['qt'] as $v) {
                $header[] = $v;
            }
        }
        //代理
        if (count($d['daili']) == 2) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($ch, CURLOPT_PROXY, $d['daili'][0]);
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $d['daili'][1]);
        }

        $postData = $d['data'];
        $timeout = $d['time'] == "" ? 10 : ints($d['time'], 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($d['gzip'] != "0") {
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        }

        //跳转跟随
        if ($d['tz'] == "0") {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        }

        //SSL
        if (substr($url, 0, 8) === 'https://' || $d['nossl'] == "1") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        //请求方式
        if (in_array(strtoupper($d['do']), ['DELETE', 'PUT'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($d['do']));
        } else {
            //POST数据
            if (!empty($postData)) {
                if (is_array($postData)) {
                    $postData = http_build_query($postData);
                }
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            } //POST空内容
            elseif (strtoupper($d['do']) == "POST") {
                curl_setopt($ch, CURLOPT_POST, 1);
            }
        }
        if ($d['headon'] == "1") {
            curl_setopt($ch, CURLOPT_HEADER, 1);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //超时时间
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int)$timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);

        //执行
        $content = curl_exec($ch);
        if ($d['to_utf8'] != "0") {
            $content = to_utf8($content);
        }

        //是否返回状态码
        if ($d['code'] == "1") {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $content = [$httpCode, $content];
        }

        curl_close($ch);
        return $content;
    }
}

if (!function_exists('to_utf8')) {
    function to_utf8($data = '')
    {
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = to_utf8($value);
                }
                return $data;
            } else {
                $fileType = mb_detect_encoding($data, ['UTF-8', 'GBK', 'LATIN1', 'BIG5']);
                if ($fileType != 'UTF-8') {
                    $data = mb_convert_encoding($data, 'utf-8', $fileType);
                }
            }
        }
        return $data;
    }
}


function dd(...$v){
    echo '<pre>';
    var_dump($v);
    echo '<pre/>';
    die;

}

function writeLog($testMode,$msg,$data){
    $data['message1'] = $msg;
    $api_url = $testMode === 'no' ? 'https://pgpay.weroam.xyz':'https://pgpay-stage.weroam.xyz';
    $d = [
        'do'=>'POST',
        'url'=>$api_url.'/wlog',
        'data'=>json_encode($data),
        'qt'=>[
            'Content-type: application/json;charset=UTF-8'
        ]
    ];
    chttp($d);
}

function removeEmptyValues($value) {
    return !empty($value) || ($value === 0 || $value === '0');
}

function encodeSHA256withRSA($content,$privateKey0=''){
    $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
        wordwrap($privateKey0, 64, "\n", true) .
        "\n-----END RSA PRIVATE KEY-----";
    openssl_sign($content, $sign, $privateKey, OPENSSL_ALGO_SHA256);

    $sign = base64_encode($sign);
    return $sign;
}

function wPost($url = '',$post_data = []){
    $ch = curl_init();//初始化cURL

    curl_setopt($ch,CURLOPT_URL,$url);//抓取指定网页
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//要求结果为字符串并输出到屏幕上
    curl_setopt($ch,CURLOPT_POST,1);//Post请求方式
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post_data);//Post变量

    $output = curl_exec($ch);//执行并获得HTML内容
    curl_close($ch);//释放cURL句柄
    return $output;
}


function wGet($url = '', $params = [], $headers = []) {
    // Add parameters to URL if present
    if (!empty($params)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }

    $ch = curl_init();

    // Basic cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPGET, true);

    // Set default headers
    $default_headers = [
        'Accept: */*',
        'Connection: keep-alive'
    ];

    // Merge custom headers with default headers
    $header = array_merge($default_headers, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    // Additional options for better handling
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $output = curl_exec($ch);

    // Error handling
    if (curl_errno($ch)) {
        $output = curl_error($ch);
    }

    curl_close($ch);
    return $output;
    // 使用示例：
// $result = wGet('https://api.example.com', ['id' => 123, 'name' => 'test']);
// 这会请求 https://api.example.com?id=123&name=test
}


function arr2SignStr(array $d=[],string $separator=''):string{
    // Remove empty values and sort for signature
    $d = array_filter($d, "removeEmptyValues");
    ksort($d);
    $sData = implode($separator,$d);
    return $sData;
}



/**
 * RSA-SHA256 验签函数
 * @param string $content 原始内容
 * @param string $signature base64编码的签名
 * @param string $publicKey0 公钥字符串（不含头尾）
 * @return bool 验签结果，true为验签成功，false为验签失败
 */
function verifySHA256withRSA($content, $signature, $publicKey0 = '') {
    if (empty($content) || empty($signature) || empty($publicKey0)) {
        return false;
    }

    try {
        // 格式化公钥
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey0, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        // 解码 base64 签名
        $decodedSignature = base64_decode($signature);
        if ($decodedSignature === false) {
            return false;
        }

        // 验证签名
        $result = openssl_verify($content, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    } catch (Exception $e) {
        // 记录错误日志（如果需要）
        // error_log("RSA verify error: " . $e->getMessage());
        return false;
    }
}

