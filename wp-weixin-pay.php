<?php
/*
Plugin Name: WP Weixin Pay
Plugin URI: https://github.com/froger-me/wp-weixin-pay
Description: Simple WeChat Pay integration for WordPress
Version: 1.3.11
Author: Alexandre Froger
Author URI: https://froger.me/
Text Domain: wp-weixin-pay
Domain Path: /languages
WC tested up to: 4.0.0
WP Weixin minimum required version: 1.3.10
WP Weixin tested up to: 1.3.11
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'WP_WEIXIN_PAY_PLUGIN_FILE' ) ) {
	define( 'WP_WEIXIN_PAY_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WP_WEIXIN_PAY_PLUGIN_PATH' ) ) {
	define( 'WP_WEIXIN_PAY_PLUGIN_PATH', plugin_dir_path( WP_WEIXIN_PAY_PLUGIN_FILE ) );
}

if ( ! defined( 'WP_WEIXIN_PAY_PLUGIN_URL' ) ) {
	define( 'WP_WEIXIN_PAY_PLUGIN_URL', plugin_dir_url( WP_WEIXIN_PAY_PLUGIN_FILE ) );
}

require_once WP_WEIXIN_PAY_PLUGIN_PATH . 'inc/class-wp-weixin-pay.php';

register_activation_hook( WP_WEIXIN_PAY_PLUGIN_FILE, array( 'WP_Weixin_Pay', 'activate' ) );
register_deactivation_hook( WP_WEIXIN_PAY_PLUGIN_FILE, array( 'WP_Weixin_Pay', 'deactivate' ) );
register_uninstall_hook( WP_WEIXIN_PAY_PLUGIN_FILE, array( 'WP_Weixin_Pay', 'uninstall' ) );

function wp_weixin_pay_extension( $wechat, $wp_weixin_settings, $wp_weixin, $wp_weixin_auth, $wp_weixin_responder, $wp_weixin_menu ) {

	if ( wp_weixin_get_option( 'enabled' ) ) {
		$wp_weixin_pay = new WP_Weixin_Pay( $wechat, $wp_weixin_auth, true );
	}
}

function wp_weixin_pay_run() {
	add_action( 'wp_weixin_extensions', 'wp_weixin_pay_extension', 0, 6 );
}
add_action( 'plugins_loaded', 'wp_weixin_pay_run', 0, 0 );
