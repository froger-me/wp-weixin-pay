# WP Weixin Pay - Simple WeChat Pay integration for WordPress

* [General Description](#user-content-general-description)
	* [Requirements](#user-content-requirements)
	* [Important Notes](#user-content-important-notes)
	* [Overview](#user-content-overview)
* [Settings](#user-content-settings)
* [Hooks - actions & filters](#user-content-hooks---actions--filters)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)
* [Templates](#user-content-templates)

## General Description

WP Weixin Pay is a companion plugin for WP Weixin that adds a "Transfer" screen in for the WeChat browser.
It emulates the native WeChat screen available to transfer money between two WeChat users, effectively allowing an Official Account (Service account) to receive custom tranfers - all without requiring any e-commerce extension!

### Requirements

* A [China Mainland WeChat Official Account](https://mp.weixin.qq.com) - **Service account**.
* A [China Mainland WeChat Pay account](https://pay.weixin.qq.com).
* **[WP Weixin](https://wordpress.org/plugins/wp-weixin)** installed, activated, enabled and properly configured.

### Important Notes

Does NOT support [cross-border payments](https://pay.weixin.qq.com/wechatpay_guide/intro_settle.shtml).

### Overview

This plugin adds the following major features to WP Weixin:

* **WeChat Pay - Custom money transfers:** to allow WeChat users to transfer money in a simialr way they transfer money to other users. Transfers can be made with an arbitrary, pre-filled & editable amount, or pre-filled & fixed amount.
* **WP Weixin QR code generator:** to create codes to receive money transfers with custom amount.

Compatible with [WooCommerce](https://wordpress.org/plugins/woocommerce/), [WooCommerce Multilingual](https://wordpress.org/plugins/woocommerce-multilingual/), [WPML](http://wpml.org/), [Ultimate Member](https://wordpress.org/plugins/ultimate-member/), [WordPress Multisite](https://codex.wordpress.org/Create_A_Network), and [many caching plugins](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-object-cache-considerations).

## Settings

The settings below are added to WP Weixin when the plugin is active.

### WP Weixin Settings

The settings below are only available if WP Weixin Pay is installed and activated (this behavior may be altered using the [wp_weixin_show_settings_section](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-wp_weixin_show_settings_section) filter).

Name                                | Type      | Description                                                                     
----------------------------------- |:---------:|---------------------------------------------------------------------------------
Custom amount transfer 			    | checkbox  | Allow users to do custom amount transfers and admins to create payment QR Codes.

Additionally, **required settings** are located on the WP Weixin settings page, in the WeChat Pay Settings section.
See also the [WeChat Pay Settings](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-wechat-pay-settings) of the WP Weixin plugin documentation.

## Hooks - actions & filters

WP Weixin Pay gives developers the possibilty to customise its behavior with a series of custom actions and filters. 

### Actions

Actions index:
* [wp_weixin_pay_payment_result_[hook]](#user-content-wp_weixin_pay_payment_result_hook)
* [wp_weixin_pay_refund_failed](#user-content-wp_weixin_pay_refund_failed)
___

#### wp_weixin_pay_payment_result_[hook]

```php
do_action( 'wp_weixin_pay_payment_result_[hook]', string $transaction_id );
```

**Description**  
Fired after receiving a payment notification from WeChat and if [wp_weixin_pay_redirect_on_[hook]](#user-content-wp_weixin_pay_redirect_on_hook) returns `true`.  
[hook] may be one of the following:
* `success`
* `cancel`
* `failed`
* `timeout`

**Parameters**  
$transaction_id
> (string) The WeChat transaction ID.
___

#### wp_weixin_pay_refund_failed

```php
do_action( 'wp_weixin_pay_refund_failed', array $data );
```

**Description**  
Fired after an automatic refund attempt failed.  

**Parameters**  
$data
> (array) The data used to handle the original transaction.  

___

### Filters

Filters index:
* [wp_weixin_pay_amount](#user-content-wp_weixin_pay_amount)
* [wp_weixin_pay_return_url](#user-content-wp_weixin_pay_return_url)
* [wp_weixin_pay_redirect_on_[hook]](#user-content-wp_weixin_pay_redirect_on_hook)
___

#### wp_weixin_pay_amount

```php
apply_filters( 'wp_weixin_pay_amount', array $amount_info );
```

**Description**  
Filter the amount information given to the custom transfer endpoint.  

**Parameters**  
$amount_info
> (array) The amount information. Default Value types and keys: (float) `amount`, (bool) `fixed`, (string) `note`, (string) `nonce_str`.
___

#### wp_weixin_pay_return_url

```php
apply_filters( 'wp_weixin_pay_return_url', string $return_url );
```

**Description**  
Filter the base return URL the user will be redirected to after the transaction has been handled.
Has an effect only if the screen is not set to close after the transaction (default behavior - see [wp_weixin_pay_redirect_on_[hook]](#user-content-wp_weixin_pay_redirect_on_hook)).  
The parameters `result` and `transaction_id` will be added to the URL depending on the outcome of the transaction: the filtered base return URL must not already have these parameters.

**Parameters**  
$return_url
> (string) The URL the user will be redirected to after the transaction has been handled. Must not contain `result` or `transaction_id` parameters - default `home_url( 'wp-weixin-pay/transfer/' )` (main WP Weixin Pay screen).
___

#### wp_weixin_pay_redirect_on_[hook]

```php
apply_filters( 'wp_weixin_pay_redirect_on_[hook]', bool $do_redirect );
```

**Description**  
Filter wether to redirect the user after the transaction has been handled and the various alert windows have been dismissed.  
[hook] may be one of the following:
* `success` - if no redirection, the screen is closed
* `cancel`  - if no redirection, the user may try to pay again
* `failed` - if no redirection, the screen is closed
* `timeout` - if no redirection, the screen is closed

**Parameters**  
$do_redirect
> (bool) Wether to redirect the user after the transaction has been handled and the various alert windows have been dismissed - default `false`.
___

## Templates

The following template files are selected using the `locate_template()` and included with `load_template()` functions provided by WordPress. This means they can be overloaded in the active WordPress theme. Developers may place their custom template files in the following directories under the theme's folder (in order of selection priority):

* `plugins/wp-weixin/wp-weixin-pay/`
* `wp-weixin/wp-weixin-pay/`
* `plugins/wp-weixin-pay/`
* `wp-weixin-pay/`
* `wp-weixin/`
* at the root of the theme's folder

The available paths of the templates may be customised with the [wp_weixin_locate_template_paths](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-wp_weixin_locate_template_paths) filter.  
The style applied to all the templates below is enqueued as `'wp-weixin-pay-style'`, and the JavaScript affecting the templates is enqueud as `'wp-weixin-pay-script'`.  

Templates index:
* [wp-weixin-pay](#user-content-wp-weixin-pay)
___

#### wp-weixin-pay

```
wp-weixin-pay.php
```  

**Description**  
The template of the custom WeChat transfer endpoint page. By default, it emulates the style of personal transfer screen on WeChat.

**Variables**  
$oa_logo_url
> (string) The URL of the logo of the Official Account to display, as set in WP Weixin settings.

$oa_name
> (string) The name of the Official Account to display, as set in WP Weixin settings.

$amount_info
> (array) Information about the amount to be paid, as set in the "Generate QR codes" tab of WP Weixin. Default Value types and keys: (float) `amount`, (bool) `fixed`, (string) `note`, (string) `nonce_str`.
