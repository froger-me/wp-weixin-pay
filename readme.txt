=== WP Weixin Pay ===
Contributors: frogerme
Tags: wechat, wechatpay, money transfer, payments, wechat payments, weixin, 微信, 微信支付
Requires at least: 4.9.5
Tested up to: 5.3.2
Stable tag: trunk
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Simple WeChat Pay integration for WordPress.

== Description ==

WP Weixin Pay is a companion plugin for WP Weixin that adds a "Transfer" screen in for the WeChat browser.
It emulates the native WeChat screen available to transfer money between two WeChat users, effectively allowing an Official Account (Service account) to receive custom tranfers - all without requiring any e-commerce extension!

### Requirements

* A [China Mainland WeChat Official Account](https://mp.weixin.qq.com) - **Service account**.
* A [China Mainland WeChat Pay account](https://pay.weixin.qq.com).
* **[WP Weixin](https://wordpress.org/plugins/wp-weixin)** installed, activated, enabled and properly configured.

### Important Notes

Make sure to read the "TROUBLESHOOT, FEATURE REQUESTS AND 3RD PARTY INTEGRATION" section below and [the full documentation](https://github.com/froger-me/wp-weixin-pay/blob/master/README.md) before contacting the author.

### Overview

This plugin adds the following major features to WP Weixin:

* **WeChat Pay - Custom money transfers:** to allow WeChat users to transfer money in a simialr way they transfer money to other users. Transfers can be made with an arbitrary, pre-filled & editable amount, or pre-filled & fixed amount.
* **WP Weixin QR code generator:** to create codes to receive money transfers with custom amount.

Compatible with [WooCommerce](https://wordpress.org/plugins/woocommerce/), [WooCommerce Multilingual](https://wordpress.org/plugins/woocommerce-multilingual/), [WPML](http://wpml.org/), [Ultimate Member](https://wordpress.org/plugins/ultimate-member/), [WordPress Multisite](https://codex.wordpress.org/Create_A_Network), and [many caching plugins](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-object-cache-considerations).

### Troubleshoot, feature requests and 3rd party integration

Unlike most WeChat integration plugins, WP Weixin Pay is provided for free.  

WP Weixin Pay is regularly updated, and bug reports are welcome, preferably on [Github](https://github.com/froger-me/wp-weixin-pay/issues). Each bug report will be addressed in a timely manner, but issues reported on WordPress may take significantly longer to receive a response.  

WP Weixin Pay has been tested with the latest version of WordPress - in case of issue, please ensure you are able to reproduce it with a default installation of WordPress and any of the aforementioned supported plugins if used before reporting a bug.  

Feature requests (such as "it would be nice to have XYZ") or 3rd party integration requests (such as "it is not working with XYZ plugin" or "it is not working with my theme") for WP Weixin and all its companion plugins will be considered only after receiving a red envelope (红包) of a minimum RMB 500 on WeChat (guarantee of best effort, no guarantee of result). 

To add the author on WeChat, click [here](https://froger.me/wp-content/uploads/2018/04/wechat-qr.png), scan the WeChat QR code, and add "WP Weixin" as a comment in your contact request.  

== Upgrade Notice ==

* Make sure to deactivate all the WP Weixin companion plugins before updating.
* Make sure to update WP Weixin to its latest version before updating.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/wp-weixin-pay` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Edit plugin settings

== Screenshots ==
 
1. Custom money transfer screen in the WeChat browser after scanning a previouly generated QR code.
2. The WP Weixin QR code generator with the Payment QR code generator.
3. The WeChat Pay settings added to the WP Weixin settings page.

== Changelog ==

= 1.3.10 =
* WC tested up to: 3.9.2
* WP Weixin tested up to: 1.3.10

= 1.3.9 =
* WC tested up to: 3.9.1
* WP Weixin tested up to: 1.3.9

= 1.3.8 =
* WP Weixin tested up to: 1.3.8

= 1.3.7 =
* WP Weixin tested up to: 1.3.7

= 1.3.6 =
* WC tested up to: 3.9.0

= 1.3.5 =
* Support for latest WordPress version
* Require WP Weixin v1.3.5
* Bump version to match WP Weixin

= 1.3.1 =
* Migrated WP Weixin Pay settings from WP Weixin core

= 1.3 =
* Major overall code refactor
* Multisite support
* Use new [WP Weixin](https://wordpress.org/plugins/wp-weixin) functions instead of using the classes directly
* Handle notifications coming from WeChat API properly when [Woo WeChatPay](https://wordpress.org/plugins/woo-wechatpay) is active
* Ensure compatibility with other plugins using WeChat Pay API
* Add 5 filters
* Add `wp_weixin_pay_refund_failed` action
* Better handling of different outcomes after payment (`success`, `failed`, `cancel`, `timeout`) - customizable via newly added filters and hooks
* Attempt to refund payment automatically in case of failure
* Skip version 1.2 - sync version with WP Weixin plugin
* Improve QR code generation interface
* Update documentation
* Update translation

Special thanks:

* Thanks @alexlii for extensive testing, translation, suggestions and donation!
* Thanks @lssdo for translation
* Thanks @kzgzs for improvement suggestions

= 1.1.1 =
* Better error log

= 1.1 =
* Public plugin on WordPress repository

= 1.0.4 =
* Adjust rewrite rules registration

= 1.0.3 =
* Rearrange hooks firing sequence
* Add icons and banners
* Add readme.txt

= 1.0.2 =
* Coding standards

= 1.0.1 =
* Add WP Package Updater

= 1.0 =
* First version