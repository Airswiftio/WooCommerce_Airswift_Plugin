<?php

//If the function is_plugin_active does not exist, the plugin.php function library will be imported
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Check if WooCommerce is installed.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
{
    //notice when woocommerce is not installed
    if (!function_exists('woocommerce_not_installed_notice')) {
        function woocommerce_not_installed_notice(){
            echo '<div class="error"><p><strong>' .__('PelagoPay Gateway for WooCommerce requires WooCommerce to be installed.', 'woocommerce'). '</strong></p></div>';
        }
    }

    add_action('admin_notices','woocommerce_not_installed_notice');
    return;
}

//Check if WooCommerce is active
if(!is_plugin_active( 'woocommerce/woocommerce.php' ))
{
    //notice when woocommerce is not active
    if (!function_exists('woocommerce_not_active_notice')) {
        function woocommerce_not_active_notice(){
            echo '<div class="error"><p><strong>' .__('PelagoPay Gateway for WooCommerce requires WooCommerce to be installed and active.', 'woocommerce'). '</strong></p></div>';
        }
    }

    add_action('admin_notices','woocommerce_not_active_notice');
    return;
}

// Once WooCommerce is loaded, initialize the Pelago plugin.
add_action( 'plugins_loaded', 'pelago_payment_init', 11 ); 