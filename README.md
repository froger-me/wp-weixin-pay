# WP Weixin Pay - WordPress Simple WeChat Pay integration

* [General description](#user-content-general-description)
	* [Requirements](#user-content-requirements)
	* [Overview](#user-content-overview)
	* [Screenshots](#user-content-screenshots)
* [Settings](#user-content-settings)
* [Hooks - actions & filters](#user-content-hooks---actions--filters)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)
* [Templates](#user-content-templates)

## General Description

**WP Weixin Pay is an extension for WP Weixin.** It adds a "Transfer" screen for users to send money to a WeChat Pay Account.

### Requirements

This plugin requires **WP Weixin** installed, activated, enabled and properly configured.

### Overview

This plugin adds the following major features to WP Weixin:

* **WP Weixin QR code generator:** to create codes to receive money transfers with custom amount.
* **WeChat Pay - Custom WeChat transfers:** to allow WeChat users to transfer money. Transfers can be made with an arbitrary, pre-filled & editable or pre-filled & fixed amount.

### Screenshots

<img src="https://ps.w.org/woo-wechatpay/assets/screenshot-1.png" alt="Payment screen" width="25%"> <img src="https://ps.w.org/woo-wechatpay/assets/screenshot-2.png" alt="Payment QR code" width="72%"> <img src="https://ps.w.org/woo-wechatpay/assets/screenshot-3.png" alt="Payment settings" width="100%">

## Settings

All the settings can be accessed on the WP Weixin settings page, in the WeChat Pay Settings section.  
See also the [WeChat Pay Settings](https://github.com/froger-me/wp-weixin#wechat-pay-settings) of WP Weixin plugin documentation.

## Hooks - actions & filters

WP Weixin Pay gives developers the possibilty to customise its behavior with a series of custom actions and filters. 

### Actions

```php
do_action( 'wp_weixin_pay_payment_result_[hook]', string $transaction_id );
```

**Description**  
Fired after receiving a payment notification from WeChat. [hook] may be one of the following:
* success
* cancel
* failed
* timeout

**Parameters**  
$transaction_id
> (string) The WeChat transaction ID.
___

### Filters

```php
apply_filters( 'wp_weixin_pay_amount', array $amount_info );
```

**Description**  
Filter the amount information given to the custom transfer endpoint.  

**Parameters**  
$amount_info
> (array) The amount information - default empty

**Hooked**
WP_Weixin_Pay::get_amount_info()
___

## Templates

The following plugin files are included using `locate_template()` function of WordPress. This means they can be overloaded in the active WordPress theme if a file with the same name exists at the root of the theme.
___

```
wp-weixin-pay.php
```  

**Description**  
The template of the custom WeChat transfer endpoint page. By default, it emulates the style of personal transfer screen on WeChat.

**Variables**  
No variable is provided to this template by default: it uses the `wp_weixin_pay_amount` filter to get the amount information and `WP_Weixin_Settings::get_option()` to get the Official Account information. 

**Associated styles**  
`wp-weixin/css/main.css`  

**Associated scripts**  
`wp-weixin/js/main.js`  
