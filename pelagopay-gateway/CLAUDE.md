[Root](../CLAUDE.md) > **pelagopay-gateway**

# Pelago Payment Gateway Module

> AI Context Document - Auto-generated on 2026-01-20T17:58:16+0800

## Module Purpose

This is the core WooCommerce payment gateway implementation for Pelago Crypto Pay. It extends `WC_Payment_Gateway` to integrate cryptocurrency payments into WooCommerce checkout.

## Entry & Initialization

### Bootstrap Sequence

1. `pelogapay-gateway.php` - Main plugin file, WordPress loads this first
2. `function.php` - Utility functions loaded via `require_once`
3. `plugin-function.php` - WordPress integration helpers loaded
4. `init-start.php` - WooCommerce dependency checks, registers `pelago_payment_init` on `plugins_loaded`

### Key Class

```php
class WC_Pelago_Pay_Gateway extends WC_Payment_Gateway {
    // Gateway ID: 'pelago_payment'
    // Payment method title: 'Pelago Crypto Pay'
}
```

## Public Interface

### WordPress Hooks

| Hook Type | Hook Name | Callback | Description |
|-----------|-----------|----------|-------------|
| Filter | `woocommerce_payment_gateways` | `add_to_woo_pelago_payment_gateway` | Registers gateway with WooCommerce |
| Filter | `plugin_action_links_*` | `wc_pelago_payment_plugin_links` | Adds Settings link in plugin list |
| Action | `woocommerce_update_options_payment_gateways_pelago_payment` | `process_admin_options` | Saves admin settings |
| Action | `woocommerce_thank_you_pelago_payment` | `thank_you_page` | Displays thank you message |
| Action | `woocommerce_api_wc_pelagopay_gateway` | `check_ipn_response` | Handles IPN callbacks |

### Admin Settings Fields

| Field ID | Type | Default | Description |
|----------|------|---------|-------------|
| `enabled` | checkbox | no | Enable/disable gateway |
| `title` | text | Pelago Crypto Pay | Checkout display title |
| `description` | textarea | Pay with Pelago... | Checkout description |
| `instructions` | textarea | Default instructions | Thank you page text |
| `merchantId` | text | - | Pelago merchant ID |
| `appKey` | text | - | API application key |
| `merchantPrikey` | text | - | RSA private key |
| `platformPublicKey` | text | - | Pelago public key |
| `testMode` | checkbox | no | Use staging environment |

## Key Dependencies & Configuration

### External APIs

| API | Purpose | Production URL | Staging URL |
|-----|---------|----------------|-------------|
| Currency Conversion | Convert to USD | `pgpay.weroam.xyz/api/currency_to_usd` | `pgpay-stage.weroam.xyz/api/currency_to_usd` |
| Crypto Order | Create payment | `api.pelagotech.com/merchant-api/crypto-order` | `stage-api.pelagotech.com/merchant-api/crypto-order` |
| Logging | Debug logs | `pgpay.weroam.xyz/wlog` | `pgpay-stage.weroam.xyz/wlog` |

### Required PHP Extensions

- `openssl` - RSA signature operations
- `curl` - HTTP client
- `json` - JSON encoding/decoding
- `mbstring` - Character encoding

## Data Model

### Payment Request Structure

```php
$orderData = [
    'merchantId'      => string,  // Pelago merchant ID
    'merchantOrderId' => string,  // WooCommerce order ID + timestamp
    'amount'          => float,   // Amount in USD
    'timestamp'       => int,     // Unix timestamp (milliseconds)
    'nonce'           => int,     // Random 6-digit number
    'notifyUrl'       => string,  // IPN callback URL
    'redirectUrl'     => string,  // Post-payment redirect URL
];
```

### IPN Callback Structure

```php
$callbackData = [
    'data' => [
        'merchantOrderId' => string,  // Original order ID
        'orderId'         => string,  // Pelago order ID
        'amount'          => float,   // Payment amount
        'orderStatus'     => int,     // 0=Pending, 1=Success, 2=Timeout, 3=Cancelled, 4=Failed
        'payStatus'       => int,     // 0=Not Paid, 1=Partial, 2=Full, 3=Overpaid
    ],
    'signature' => string,  // RSA-SHA256 signature
];
```

### Order Status Mapping

| Pelago orderStatus | Pelago payStatus | WooCommerce Status |
|--------------------|------------------|--------------------|
| 1 (Success) | 2 (Full) | processing |
| 1 (Success) | 3 (Overpaid) | processing |
| 1 (Success) | 1 (Partial) | failed |
| 2 (Timeout) | - | failed |
| 3 (Cancelled) | - | cancelled |
| 4 (Failed) | - | (note added) |

## Testing & Quality

### Test Mode

Enable via admin settings to use staging APIs. Test mode also enables detailed logging to remote logging endpoint.

### Logging

All critical operations logged via `writeLog()` function:
- Payment processing steps
- API responses
- IPN callbacks
- Signature verification results

### Manual Testing Checklist

- [ ] Plugin activation with WooCommerce inactive (should show error)
- [ ] Plugin settings save correctly
- [ ] Checkout displays payment option
- [ ] Currency conversion works
- [ ] Payment redirect works
- [ ] IPN callback updates order status
- [ ] Invalid signature rejected
- [ ] Error messages display correctly

## FAQ

### Q: Why is the callback failing?

Check:
1. Callback URL is accessible from external networks
2. `platformPublicKey` is correctly configured (no extra whitespace/newlines)
3. Server firewall allows incoming POST requests

### Q: Currency conversion returns error?

Check:
1. Network connectivity to `pgpay.weroam.xyz`
2. Currency code is valid WooCommerce currency
3. API service is operational

### Q: Signature verification fails?

Check:
1. `merchantPrikey` matches the key registered with Pelago
2. Key format is correct (base64 encoded, no PEM headers)
3. No invisible characters in key fields

## Related Files

| File | Lines | Description |
|------|-------|-------------|
| `pelogapay-gateway.php` | 482 | Main gateway class and plugin entry |
| `init-start.php` | 37 | WooCommerce dependency checks |
| `function.php` | 327 | HTTP client and crypto utilities |
| `plugin-function.php` | 35 | WordPress integration helpers |
| `assets/Pelago_logo_black.png` | - | Gateway icon |
| `assets/images/*.png` | - | Cryptocurrency icons |

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-01-20 | - | Initial AI context document created |
