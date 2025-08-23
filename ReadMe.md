# Pelago Payment for WooCommerce User Guide

## Plugin Overview

Pelago Payment for WooCommerce is a digital payment plugin designed specifically for WordPress WooCommerce stores, supporting online payments through Pelago's digital QR code payment method.

## System Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher
- SSL certificate (recommended for production environment)

## Installation Steps

### Method 1: Manual Folder Upload

Upload the entire `pelagopay-gateway` folder to your WordPress website's `/wp-content/plugins/` directory.

### Method 2: Upload ZIP File via WordPress Admin

1. Download the latest `pelagopay-gateway.zip` file from GitHub Release:
   - Visit the plugin's GitHub repository
   - Click the **Releases** tab
   - Select the latest tag version
   - Download the `pelagopay-gateway.zip` file
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins** > **Add New**
4. Click the **Upload Plugin** button
5. Select the downloaded `pelagopay-gateway.zip` file and click **Install Now**
6. After installation completes, click **Activate Plugin**

### Activate Plugin (For Method 1)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins** > **Installed Plugins**
3. Find "Pelago Payment for WooCommerce" plugin
4. Click the **Activate** button

### 3. Verify Dependencies

The plugin will automatically check if WooCommerce is installed and activated. If WooCommerce is not installed or activated, you will see corresponding notification messages.

## Configuration Settings

### 1. Access Payment Settings

1. In WordPress admin, navigate to **WooCommerce** > **Settings**
2. Click the **Payments** tab
3. Find the **Pelago Payment** option

### 2. Basic Settings

#### Enable/Disable
- **Enable Pelago Payment**: Check this option to enable Pelago payment method

#### Display Settings
- **Title**: Payment method name that customers will see on the checkout page
  - Default value: `Pelago Payment`
- **Description**: Payment method description that customers will see on the checkout page
  - Default value: `Pay with Pelago's digital QR Code payment method.`
- **Instructions**: Instructions that will be added to the thank you page and order emails

### 3. API Configuration (Important)

The following configuration items are required parameters for the plugin to work properly. Please contact Pelago to obtain them:

#### merchantId
- **Description**: Your PelagoPay merchant ID
- **How to obtain**: Get from Pelago merchant dashboard

#### appKey  
- **Description**: Your PelagoPay application key
- **How to obtain**: Get from Pelago merchant dashboard

#### merchantPrikey
- **Description**: Merchant private key, used for signature verification
- **Security note**: Please keep it safe and do not disclose to others

#### platformPublicKey
- **Description**: Platform public key, used to verify callback signatures
- **How to obtain**: Get from Pelago's technical documentation

### 4. Test Mode

- **Enable Test Mode**: Check this option to use the test environment
  - Test environment APIs: `https://pgpay-stage.weroam.xyz` and `https://stage-api.pelagotech.com`
  - Production environment APIs: `https://pgpay.weroam.xyz` and `https://api.pelagotech.com`

## Usage Flow

### 1. Customer Payment Flow

1. Customer selects products in your store and adds them to cart
2. On checkout page, select "Pelago Payment" as payment method
3. Click "Place Order" button
4. System will automatically perform currency conversion (convert to USD)
5. Customer is redirected to Pelago payment page
6. Customer completes payment using QR code
7. After payment completion, automatically returns to order confirmation page

### 2. Order Status Management

The plugin will automatically update order status based on Pelago callbacks:

- **Payment Success**: Order status updated to "Processing"
- **Payment Timeout**: Order status updated to "Failed"
- **Payment Cancelled**: Order status updated to "Cancelled"
- **Partial Payment**: Order status updated to "Failed"
- **Overpayment**: Order status updated to "Processing"

## Callback URL Configuration (Optional)

The plugin will automatically generate a callback URL in the format:
```
https://yourdomain.com/?wc-api=wc_pelagopay_gateway
```

Please provide this URL to Pelago technical support team for configuration.
When placing an order, the callback link will be submitted together with the order, and Pelago will call this link after successful payment.

## Currency Support

- The plugin supports multiple currencies and will automatically convert order amounts to USD for payment
- Supported currencies depend on your WooCommerce settings and Pelago's exchange rate service

## Logging

The plugin includes detailed logging functionality:
- Test mode records detailed debugging information
- Production mode records key operations and error information
- Logs help troubleshoot payment issues

## Security Features

- **Signature Verification**: All API requests and callbacks use RSA signature verification
- **Data Encryption**: Sensitive data transmission uses HTTPS encryption
- **Anti-replay Attack**: Uses timestamps and random numbers to prevent replay attacks

## Troubleshooting

### Common Issues

1. **Payment page inaccessible**
   - Check if API configuration is correct
   - Confirm network connection is normal
   - Verify SSL certificate is valid

2. **Callback failure**
   - Check if callback URL is correctly configured
   - Confirm server can receive external requests
   - Verify signature configuration is correct

3. **Currency conversion failure**
   - Check network connection
   - Confirm currency code format is correct
   - Contact technical support to check exchange rate service

### Debugging Steps

1. Enable test mode for debugging
2. Check WordPress error logs
3. Contact Pelago technical support for assistance

## Technical Support

For technical support, please contact:
- **Pelago Website**: https://pelagotech.com
- **Technical Support**: Please contact through official channels

## Version Information

- **Current Version**: 2.0.0
- **Compatibility**: WooCommerce 3.0+
- **Update Date**: Please check plugin files for latest information

## Important Notes

1. Please thoroughly test all functions before using in production environment
2. Regularly backup your website data
3. Keep the plugin and WordPress system updated to the latest versions
4. Properly safeguard API keys and private key information
5. It is recommended to use this plugin in an SSL environment