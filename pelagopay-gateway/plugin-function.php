<?php

if (!function_exists('add_to_woo_pelago_payment_gateway')) {
    function add_to_woo_pelago_payment_gateway( $gateways ) {
        $gateways[] = 'WC_Pelago_Pay_Gateway';
        return $gateways;
    }
}

if (!function_exists('wc_pelago_payment_plugin_links')) {
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
}

if (!function_exists('home_notice')){
    function home_notice($msg) :string {
        return  '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
<div class="woocommerce-error">'.$msg.'</div>
</div>';
    }
}

