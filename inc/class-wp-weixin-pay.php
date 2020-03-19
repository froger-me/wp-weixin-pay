<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Weixin_Pay {

	protected $wechat;
	protected $wp_weixin_auth;
	protected $amount;
	protected $fixed;
	protected $note;
	protected $openid;
	protected $target_url;
	protected $target_blog_id;
	protected $pay_notify_result;
	protected $is_pay_handler = false;

	protected static $core_min_version;
	protected static $core_max_version;

	public function __construct( $wechat, $wp_weixin_auth, $init_hooks = false ) {
		$this->wechat         = $wechat;
		$this->wp_weixin_auth = $wp_weixin_auth;
		$use_ecommerce        = wp_weixin_get_option( 'ecommerce' );
		$custom_transfer      = wp_weixin_get_option( 'custom_transfer' );

		if ( $init_hooks ) {

			if ( $custom_transfer && $use_ecommerce ) {
				// Manage WeChat authentication
				add_action( 'init', array( $this, 'manage_basic_auth' ), PHP_INT_MIN + 10, 0 );
				// Parse the pay endpoint request
				add_action( 'parse_request', array( $this, 'parse_request' ), 0, 0 );
				// Add payment ajax callback
				add_action( 'wp_ajax_wp_weixin_pay', array( $this, 'pay' ), 10, 0 );
				add_action( 'wp_ajax_nopriv_wp_weixin_pay', array( $this, 'pay' ), 10, 0 );
				// Add payment check init callback
				add_action( 'wp_ajax_wp_weixin_pay_init_check', array( $this, 'init_payment_check' ), 10, 0 );
				add_action( 'wp_ajax_nopriv_wp_weixin_pay_init_check', array( $this, 'init_payment_check' ), 10, 0 );
				// Add check payment ajax callback
				add_action( 'wp_ajax_wp_weixin_pay_check', array( $this, 'check_payment' ), 10, 0 );
				add_action( 'wp_ajax_nopriv_wp_weixin_pay_check', array( $this, 'check_payment' ), 10, 0 );
				// Handle the transaction if the notification was captured by another endpoint
				add_action( 'wp_weixin_handle_payment_notification', array( $this, 'handle_notify' ), 10, 0 );
				// Add payment QR code generator in admin
				add_action( 'wp_weixin_before_qr_settings_inner', array( $this, 'pay_qr_generator' ), 10, 0 );
				// Handle automatic refunds in case of payment error
				add_action( 'wp_weixin_handle_auto_refund', array( $this, 'handle_auto_refund' ), 10, 2 );
				// Add admin scripts
				add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_scripts' ), 99, 1 );
				// Add QR code generation ajax callback
				add_action( 'wp_ajax_wp_weixin_pay_get_settings_qr', array( $this, 'get_qr_hash' ), 10, 0 );

				// Add the pay query vars
				add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0, 1 );
			} else {
				// Parse the disabled pay endpoints request
				add_action( 'parse_request', array( $this, 'parse_request_disabled' ), 0, 0 );
			}

			// Add translation
			add_action( 'init', array( $this, 'load_textdomain' ), 0, 0 );
			// Add core version checks
			add_action( 'init', array( $this, 'check_wp_weixin_version' ), 0, 0 );
			// Add the pay endpoints
			add_action( 'wp_weixin_endpoints', array( $this, 'add_endpoints' ), 0, 0 );
			// Clean pay wait flags
			add_action( 'wp_weixin_pay_wait_clean', array( $this, 'pay_wait_clean' ), 10, 0 );

			// Alter the WP Weixin settings
			add_filter( 'wp_weixin_settings', array( $this, 'wp_weixin_settings' ), 10, 1 );
			// Add custom transfer setting
			add_filter( 'wp_weixin_settings_fields', array( $this, 'settings_fields' ), 10, 1 );
			// Show settings section
			add_filter( 'wp_weixin_show_settings_section', array( $this, 'show_section' ), 10, 3 );
			// Add WeChat JSAPI urls
			add_filter( 'wp_weixin_jsapi_urls', array( $this, 'wechat_jsapi_urls' ), 10, 1 );
			// Add payment notification endpoint help
			add_filter( 'wp_weixin_pay_callback_endpoint', array( $this, 'pay_notification_endpoint' ), PHP_INT_MAX - 10, 1 );
			// Detemine where WeChat authentication is needed
			add_filter( 'wp_weixin_auth_needed', array( $this, 'page_needs_wechat_auth' ), PHP_INT_MIN + 10, 1 );
		}
	}

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public static function activate() {
		set_transient( 'wp_weixin_pay_flush', 1, 60 );
		wp_cache_flush();
		wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'wp_weixin_pay_wait_clean' );

		if ( ! get_option( 'wp_weixin_pay_plugin_version' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$plugin_data = get_plugin_data( WP_WEIXIN_PAY_PLUGIN_FILE );
			$version     = $plugin_data['Version'];

			update_option( 'wp_weixin_pay_plugin_version', $version );
		}
	}

	public static function deactivate() {
		flush_rewrite_rules();
		wp_clear_scheduled_hook( 'wp_weixin_pay_wait_clean' );
	}

	public static function uninstall() {
		include_once WP_WEIXIN_PAY_PLUGIN_PATH . 'uninstall.php';
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wp-weixin-pay', false, 'wp-weixin-pay/languages' );
	}

	public function check_wp_weixin_version() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$core_plugin_data       = get_plugin_data( WP_WEIXIN_PLUGIN_FILE );
		$plugin_data            = get_plugin_data( WP_WEIXIN_PAY_PLUGIN_FILE );
		$core_version           = $core_plugin_data['Version'];
		$current_version        = $plugin_data['Version'];
		self::$core_min_version = defined('WP_WEIXIN_PLUGIN_FILE') ? $plugin_data[ WP_Weixin::VERSION_REQUIRED_HEADER ] : 0;
		self::$core_max_version = defined('WP_WEIXIN_PLUGIN_FILE') ? $plugin_data[ WP_Weixin::VERSION_TESTED_HEADER ] : 0;

		if (
			! version_compare( $current_version, self::$core_min_version, '>=' ) ||
			! version_compare( $current_version, self::$core_max_version, '<=' )
		) {
			add_action( 'admin_notices', array( 'WP_Weixin_Pay', 'core_version_notice' ) );
			deactivate_plugins( WP_WEIXIN_PAY_PLUGIN_FILE );
		}
	}

	public static function core_version_notice() {
		$class   = 'notice notice-error is-dismissible';
		$message = 	sprintf(
			__(
				// translators: WP Weixin requirements - %1$s is the minimum version, %2$s is the maximum
				'WP Weixin Pay has been disabled: it requires WP Weixin with version between %1$s and %2$s to be activated.',
				'wp-weixin-pay'
			),
			self::$core_min_version,
			self::$core_max_version
		);

		echo sprintf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); // WPCS: XSS ok
	}

	public function pay_wait_clean() {
		global $wpdb;

		$timestamp = current_time( 'timestamp' ) - 3600;
		$sql       = "DELETE FROM $wpdb->options WHERE option_name LIKE 'wp_weixin_pay_wait_%' AND CONVERT( SUBSTR( option_name, 21 ), UNSIGNED INTEGER ) < %d";

		$wpdb->query( $wpdb->prepare( $sql, $timestamp ) ); //@codingStandardsIgnoreLine
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'amount';
		$vars[] = 'fixed';
		$vars[] = 'result';
		$vars[] = 'query';
		$vars[] = 'transaction_id';
		$vars[] = 'note';
		$vars[] = 'pay-openid';

		return $vars;
	}

	public function add_endpoints() {
		add_rewrite_rule(
			'^wp-weixin-pay/transfer/\?amount=([a-z0-9\.,]*)&note=([a-zA-Z0-9]*)(&(.*))?$',
			'index.php?__wp_weixin_api=1&amount=$matches[1]&action=transfer&note=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin-pay/transfer/\?amount=([a-z0-9\.,]*)&note=([a-zA-Z0-9]*)&fixed=1(&(.*))?$',
			'index.php?__wp_weixin_api=1&amount=$matches[1]&fixed=1&action=transfer&note=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin-pay/transfer/\?note=([a-zA-Z0-9]*)(&(.*))?$',
			'index.php?__wp_weixin_api=1&amount=&action=transfer&note=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^wp-weixin-pay/transfer/\?amount=([a-z0-9\.,]*)(&(.*))?$',
			'index.php?__wp_weixin_api=1&amount=$matches[1]&action=transfer',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin-pay/transfer/\?amount=([a-z0-9\.,]*)&fixed=1(&(.*))?$',
			'index.php?__wp_weixin_api=1&amount=$matches[1]&fixed=1&action=transfer',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin-pay/transfer([^\/]*)(\/)?(\?(.*))?$',
			'index.php?__wp_weixin_api=1&amount=&action=transfer',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin-pay/transfer/\?result=([a-z]*)$',
			'index.php?__wp_weixin_api=1&result=$matches[1]&action=pay-result',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin-pay/transfer/\?result=([a-z]*)&transaction_id=(T[0-9]*)$',
			'index.php?__wp_weixin_api=1&result=$matches[1]&transaction_id=$matches[2]&action=pay-result',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin-pay/notify(\/)?(\?(.*))?$',
			'index.php?__wp_weixin_api=1&action=pay-notify',
			'top'
		);
		add_rewrite_rule(
			'^wp-weixin-pay/basic-auth/([^\/]*)(\/)?(\?(.*))?$',
			'index.php?__wp_weixin_api=1&action=basic-auth-redirect-callback&hash=$matches[1]',
			'top'
		);

		if ( is_multisite() ) {
			add_rewrite_rule(
				'^wp-weixin-pay/(ms-crossdomain|ms-set-target)/hash/([^\/\?\#]*)(\/)?(\?(.*))?$',
				'index.php?__wp_weixin_api=1&action=$matches[1]-wp-weixin-pay&hash=$matches[2]',
				'top'
			);
		}

		if ( get_transient( 'wp_weixin_pay_flush' ) ) {
			delete_transient( 'wp_weixin_pay_flush' );
			flush_rewrite_rules();
		}
	}

	public function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wp_weixin_api'] ) ) {
			$action = $wp->query_vars['action'];

			if ( 'ms-set-target-wp-weixin-pay' === $action && WP_Weixin::is_wechat_mobile() ) {
				$hash          = $wp->query_vars['hash'];
				$payload       = WP_Weixin_Settings::decode_url( $hash );
				$payload_parts = explode( '|', $payload );

				$this->target_url     = reset( $payload_parts );
				$this->target_blog_id = absint( end( $payload_parts ) );

				return;
			}

			if ( 'ms-crossdomain-wp-weixin-pay' === $action && WP_Weixin::is_wechat_mobile() ) {
				$hash         = $wp->query_vars['hash'];
				$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

				if ( ! $auth_blog_id ) {
					$callback = home_url( 'wp-weixin-pay/ms-set-target/hash/' . $hash );
				} else {
					$callback = get_home_url( $auth_blog_id, 'wp-weixin-pay/ms-set-target/hash/' . $hash );
				}

				$scope = 'snsapi_base';
				$state = wp_create_nonce( 'wp_weixin_pay_auth_state' );
				$url   = $this->wechat->getOAuthRedirect( $callback, $state, $scope );

				header( 'Location: ' . $url );

				exit();
			}

			if ( 'basic-auth-redirect-callback' === $action && WP_Weixin::is_wechat_mobile() ) {
				$hash = $wp->query_vars['hash'];
				$url  = WP_Weixin_Settings::decode_url( $hash );

				$this->openid = get_query_var( 'pay-openid', false );

				$this->basic_oauth();

				header( 'Location: ' . $url );

				exit();
			}

			if ( 'transfer' === $action && WP_Weixin::is_wechat_mobile() ) {
				$this->prepare_transfer();
			}

			if ( 'pay-result' === $action && WP_Weixin::is_wechat_mobile() ) {
				$this->handle_result();
			}

			if ( 'pay-notify' === $action ) {
				$this->is_pay_handler = true;

				do_action( 'wp_weixin_handle_payment_notification' );
			}

			$this->wp_weixin_auth->prevent_force_follow = true;
		}
	}

	public function parse_request_disabled() {
		global $wp;

		if ( isset( $wp->query_vars['__wp_weixin_api'] ) ) {
			global $wp_query;

			$action = $wp->query_vars['action'];

			if ( 'transfer' === $action || 'pay-notify' === $action || 'pay-result' === $action ) {
				$wp_query->set_404();
				status_header( 404 );
				get_template_part( 404 );

				exit();
			}
		}
	}

	public function pay_template() {
		set_query_var( 'amount_info', apply_filters( 'wp_weixin_pay_amount', $this->get_amount_info() ) );
		set_query_var( 'oa_name', wp_weixin_get_option( 'name' ) );
		set_query_var( 'oa_logo_url', filter_var( wp_weixin_get_option( 'logo_url' ), FILTER_VALIDATE_URL ) );

		wp_weixin_wpml_switch_lang( false );
		WP_Weixin::locate_template( 'wp-weixin-pay.php', true, true, 'wp-weixin-pay' );

		exit();
	}

	public function pay() {
		wp_weixin_ajax_safe();

		$amount    = filter_input( INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$notes     = filter_input( INPUT_POST, 'notes', FILTER_SANITIZE_STRING );
		$nonce_str = filter_input( INPUT_POST, 'nonceStr', FILTER_SANITIZE_STRING );

		try {
			$parameters = $this->get_js_api_params( $amount, $notes, $nonce_str );
		} catch ( WechatException $e ) {
			$error = new WP_Error( 'WP_Weixin_Pay::pay', $e->getMessage() );

			wp_send_json_error( $error );
		}

		if ( isset( $parameters['error'] ) ) {
			$error = new WP_Error( __METHOD__, $parameters['message'] );

			wp_send_json_error( $error );
		} else {
			wp_send_json_success( $parameters );
		}
	}

	public function check_payment() {
		$transaction_id = filter_input( INPUT_POST, 'transactionId', FILTER_SANITIZE_STRING );

		if ( $transaction_id && is_numeric( str_replace( 'WPWP', '', $transaction_id ) ) ) {
			$data = array(
				'confirmed' => empty( get_option( 'wp_weixin_pay_wait_' . $transaction_id ) ),
			);

			wp_send_json_success( $data );
		} else {
			$error = new WP_Error( __METHOD__, 'Invalid transaction_id' );

			wp_send_json_error( $error );
		}
	}

	public function init_payment_check() {
		$transaction_id = filter_input( INPUT_POST, 'transactionId', FILTER_SANITIZE_STRING );
		$nonce_str      = filter_input( INPUT_POST, 'nonceStr', FILTER_SANITIZE_STRING );

		if (
			$transaction_id &&
			is_numeric( str_replace( 'WPWP', '', $transaction_id ) ) &&
			$nonce_str
		) {
			update_option( 'wp_weixin_pay_wait_' . $transaction_id, $nonce_str );

			wp_send_json_success();
		} elseif ( ! $nonce_str ) {
			$error = new WP_Error( __METHOD__, 'Invalid nonce_str' );

			wp_send_json_error( $error );
		} else {
			$error = new WP_Error( __METHOD__, 'Invalid transaction_id' );

			wp_send_json_error( $error );
		}
	}

	public function pay_qr_generator() {
		$custom_transfer = wp_weixin_get_option( 'custom_transfer' );

		include WP_WEIXIN_PAY_PLUGIN_PATH . 'inc/templates/admin/settings-generate-pay-qr.php';
	}

	public function handle_notify( $use_endpoint = false ) {
		$pay_wait = false;
		$blog_id  = '';
		$result   = $this->wechat->getNotify();
		$error    = $this->wechat->getError();
		$result   = is_array( $result ) ? $result : array( $result );
		$success  = false;

		if ( apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) ) ) {
			WP_Weixin::log( $result );
		}

		if ( $error ) {
			$error = isset( $error['code'] ) ? $error['code'] . ': ' . $error['message'] : $error['message'];
		} else {

			if (
				isset( $result['out_trade_no'] ) &&
				is_numeric( str_replace( 'WPWP', '', $result['out_trade_no'] ) )
			) {
				$pay_wait = get_option( 'wp_weixin_pay_wait_' . $result['out_trade_no'], false );
			}

			if ( $pay_wait ) {
				$success = true;

				if (
					isset( $result['transaction_id'] ) &&
					$this->wechat->getOrderInfo( $result['transaction_id'], true ) &&
					isset( $result['nonce_str'] ) &&
					$result['nonce_str'] === $pay_wait
				) {
					delete_option( 'wp_weixin_pay_wait_' . $result['out_trade_no'] );
				} else {
					$error = __( 'Invalid WeChat Response', 'wp-weixin-pay' );
				}

				if ( ! empty( $error ) ) {
					WP_Weixin::log( $result, 'API request data' );
					WP_Weixin::log( $error, 'Error' );
					WP_Weixin::log( '"' . $result['nonce_str'] . '" !== "' . $pay_wait . '"' );
				}
			}
		}

		$this->pay_notify_result = array(
			'success'            => $success,
			'data'               => $result,
			'refund'             => $error,
			'notify_error'       => ( $pay_wait ) ? false : $error,
			'blog_id'            => get_current_blog_id(),
			'pay_handler'        => $this->is_pay_handler,
			'wp_weixin_pay_wait' => 'wp_weixin_pay_wait_' . $result['out_trade_no'],
		);

		add_filter( 'wp_weixin_pay_notify_results', array( $this, 'add_pay_notify_result' ), 10, 1 );
	}

	public function add_pay_notify_result( $results ) {

		if ( ! empty( $this->pay_notify_result ) ) {
			$results[] = $this->pay_notify_result;
		}

		return $results;
	}

	public function handle_auto_refund( $refund_result, $notification_data ) {

		if ( $refund_result && isset( $notification_data['wp_weixin_pay_wait'] ) ) {
			delete_option( 'wp_weixin_pay_wait_' . $notification_data['data']['out_trade_no'] );
		} elseif ( isset( $notification_data['wp_weixin_pay_wait'] ) ) {
			delete_option( 'wp_weixin_pay_wait_' . $notification_data['data']['out_trade_no'] );
			do_action( 'wp_weixin_pay_refund_failed', $notification_data );
		}
	}

	public function wp_weixin_settings( $settings ) {

		if ( isset( $settings['custom_transfer'] ) ) {
			$settings['custom_transfer'] = (bool) $settings['custom_transfer'];
		}

		return $settings;
	}

	public function settings_fields( $fields ) {
		$extra_fields = array(
			array(
				'id'    => 'custom_transfer',
				'label' => __( 'Custom amount transfer', 'wp-weixin-pay' ),
				'type'  => 'checkbox',
				'class' => '',
				'help'  => __( 'Allow users to transfer custom amounts and admins to create payment QR codes.', 'wp-weixin-pay' ),
			),
		);

		array_splice( $fields['ecommerce'], 2, 0, $extra_fields );

		return $fields;
	}

	public function show_section( $include_section, $section_name, $section ) {

		if ( 'ecommerce' === $section_name ) {
			$include_section = true;
		}

		return $include_section;
	}

	public function wechat_jsapi_urls( $jsapi_urls ) {
		$current_blog_id = get_current_blog_id();
		$pay_blog_id     = apply_filters( 'wp_weixin_ms_pay_blog_id', $current_blog_id );

		if ( is_multisite() && $pay_blog_id !== $current_blog_id ) {
			switch_to_blog( $pay_blog_id );
		}

		global $sitepress;

		if ( wp_weixin_get_option( 'custom_transfer' ) ) {
			$jsapi_urls[] = strtok( home_url( 'wp-weixin-pay/transfer/' ), '?' );

			if (
				$sitepress &&
				( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $sitepress->get_setting( 'language_negotiation_type' ) )
			) {
				$languages        = apply_filters( 'wpml_active_languages', null, '' );
				$default_language = apply_filters( 'wpml_default_language', null );

				foreach ( $languages as $code => $language ) {

					if ( $default_language !== $code ) {
						$jsapi_urls[] = strtok( home_url( $code . '/wp-weixin-pay/transfer/' ), '?' );
					}
				}
			}
		}

		if ( is_multisite() && $pay_blog_id !== $current_blog_id ) {
			restore_current_blog();
		}

		return $jsapi_urls;
	}

	public function pay_notification_endpoint( $endpoint ) {

		return 'wp-weixin-pay/notify/';
	}

	public function manage_basic_auth() {
		$protocol    = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://';
		$current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$path        = wp_parse_url( $current_url, PHP_URL_PATH );
		$needs_auth  = strpos( $path, '/wp-weixin-pay/' );

		if ( $needs_auth ) {
			add_action( 'wp', array( $this, 'basic_oauth' ), 0, 0 );
		}
	}

	public function page_needs_wechat_auth( $needs_auth ) {
		$protocol    = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://';
		$current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$path        = wp_parse_url( $current_url, PHP_URL_PATH );
		$needs_auth  = $needs_auth && strpos( $path, 'wp-weixin-pay/' ) === false;

		return $needs_auth;
	}

	public function basic_oauth() {
		$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
		$openid       = filter_input( INPUT_COOKIE, 'wx_openId-' . $auth_blog_id, FILTER_SANITIZE_STRING );

		$blog_id     = get_current_blog_id();
		$pay_blog_id = apply_filters( 'wp_weixin_ms_pay_blog_id', $blog_id );

		if ( absint( $auth_blog_id ) !== $blog_id && absint( $pay_blog_id ) !== $blog_id ) {
			global $wp;

			$destination = get_home_url( $pay_blog_id, $wp->request );

			wp_redirect( $destination );

			exit();
		}

		if ( ! $openid ) {
			$oauth_access_token_info = $this->wechat->getOauthAccessToken();

			if ( $this->openid ) {

				if ( $this->openid !== $oauth_access_token_info['openid'] ) {

					wp_die( __METHOD__ . ': Unauthorized access' );
				}

				$this->set_openid_cookie( $auth_blog_id, $this->openid, $oauth_access_token_info['expires_in'] );
			} elseif ( false !== $oauth_access_token_info ) {
				$state = filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING );

				if ( wp_verify_nonce( $state, 'wp_weixin_pay_auth_state' ) ) {
					$this->openid = $oauth_access_token_info['openid'];
					$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );

					$this->set_openid_cookie( $auth_blog_id, $this->openid, $oauth_access_token_info['expires_in'] );

					if ( $this->target_blog_id && $auth_blog_id !== $this->target_blog_id ) {
						$this->target_url = add_query_arg(
							'pay-openid',
							rawurlencode( $this->openid ),
							$this->target_url
						);

						$this->set_openid_cookie(
							$this->target_blog_id,
							$this->openid,
							$oauth_access_token_info['expires_in']
						);
					}
				} else {
					$title    = '<h2>' . __( 'System error.', 'wp-weixin' ) . '</h2>';
					$message  = '<p>' . __( 'Invalid CSRF token. Please refresh the page. ', 'wp-weixin' );
					$message .= __( 'If the problem persists, please contact an administrator.', 'wp-weixin' ) . '</p>';

					wp_die( $title . $message ); // WPCS: XSS ok
				}
			} else {
				$this->pre_basic_oauth();
			}
		} else {
			$this->openid = $openid;
		}

		if ( $this->target_blog_id ) {
			wp_redirect( $this->target_url );

			exit();
		}
	}

	public function add_admin_scripts( $hook ) {

		if ( 'toplevel_page_wp-weixin' === $hook ) {
			$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
			$js_ext  = ( $debug ) ? '.js' : '.min.js';
			$css_ext = ( $debug ) ? '.css' : '.min.css';
			$version = filemtime( WP_WEIXIN_PAY_PLUGIN_PATH . 'js/admin/main' . $js_ext );

			$parameters = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'debug'    => $debug,
			);

			wp_enqueue_script(
				'wp-weixin-pay-settings-script',
				WP_WEIXIN_PAY_PLUGIN_URL . 'js/admin/main' . $js_ext,
				array( 'jquery' ),
				$version,
				true
			);
			wp_localize_script( 'wp-weixin-pay-settings-script', 'WpWeixinPay', $parameters );
		}
	}

	public function get_qr_hash() {
		$amount           = filter_input( INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		$fixed            = filter_input( INPUT_POST, 'fixed', FILTER_VALIDATE_BOOLEAN );
		$product_name     = filter_input( INPUT_POST, 'productName', FILTER_SANITIZE_STRING );
		$url              = filter_input( INPUT_POST, 'url', FILTER_VALIDATE_URL );
		$base_payment_url = home_url( 'wp-weixin-pay/transfer/' );
		$hash             = false;

		if ( ! $amount && $fixed ) {
			$fixed = false;
		} elseif ( $amount ) {

			if ( $amount ) {
				$base_payment_url = add_query_arg( 'amount', $amount, $base_payment_url );
			}

			if ( $product_name ) {
				$base_payment_url = add_query_arg( 'note', $product_name, $base_payment_url );
			}

			if ( $fixed ) {
				$base_payment_url = add_query_arg( 'fixed', '1', $base_payment_url );
			}

			$nonce = wp_create_nonce( 'wp_weixin_qr_code' );
			$hash  = WP_Weixin_Settings::encode_url( $base_payment_url . '|' . $nonce ); // @codingStandardsIgnoreLine
		} elseif ( $url ) {

			if ( $product_name ) {
				$url = add_query_arg( 'note', $product_name, $base_payment_url );
			}

			$nonce = wp_create_nonce( 'wp_weixin_qr_code' );
			$hash  = WP_Weixin_Settings::encode_url( $url . '|' . $nonce );
		}

		if ( $hash ) {
			wp_send_json_success( $hash );
		} else {
			$error = new WP_Error( __METHOD__, __( 'Invalid parameters', 'wp-weixin' ) );

			wp_send_json_error( $error );
		}

		wp_die();
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function prepare_transfer() {
		global $wp;

		$amount = $wp->query_vars['amount'];

		if ( is_numeric( $amount ) ) {
			$amount = floatval( number_format( $amount, 2, '.', '' ) );
		} else {
			$amount = '';
		}

		remove_all_actions( 'wp_footer' );
		remove_all_actions( 'shutdown' );

		add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
		add_action( 'shutdown', 'wp_ob_end_flush_all', 1 );

		WP_Weixin::$scripts[] = 'wp-weixin-pay-script';
		WP_Weixin::$scripts[] = 'wechat-api-script';
		WP_Weixin::$styles[]  = 'wp-weixin-pay-style';

		add_action( 'wp_print_scripts', array( 'WP_Weixin', 'remove_all_scripts' ), 100 );
		add_action( 'wp_print_styles', array( 'WP_Weixin', 'remove_all_styles' ), 100 );

		$this->amount = $amount;
		$this->fixed  = ( isset( $wp->query_vars['fixed'] ) );
		$this->note   = isset( $wp->query_vars['note'] ) ? $wp->query_vars['note'] : '';

		$debug   = apply_filters( 'wp_weixin_debug', (bool) ( constant( 'WP_DEBUG' ) ) );
		$js_ext  = ( $debug ) ? '.js' : '.min.js';
		$css_ext = ( $debug ) ? '.css' : '.min.css';
		$params  = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'weixin'   => wp_weixin_get_signed_package(),
			'debug'    => $debug,
		);
		$ver_js  = filemtime( WP_WEIXIN_PAY_PLUGIN_PATH . 'js/main' . $js_ext );
		$ver_css = filemtime( WP_WEIXIN_PAY_PLUGIN_PATH . 'css/main' . $css_ext );

		wp_enqueue_script( 'wechat-api-script', '//res.wx.qq.com/open/js/jweixin-1.0.0.js', false, false );
		wp_enqueue_script(
			'wp-weixin-pay-script',
			WP_WEIXIN_PAY_PLUGIN_URL . 'js/main' . $js_ext,
			array( 'jquery' ),
			$ver_js
		);
		wp_localize_script( 'wp-weixin-pay-script', 'WpWeixinPay', $params );

		wp_enqueue_style(
			'wp-weixin-pay-style',
			WP_WEIXIN_PAY_PLUGIN_URL . 'css/main' . $css_ext,
			array(),
			$ver_css
		);
		add_filter( 'template_redirect', array( $this, 'pay_template' ), 0, 1 );
	}

	protected function handle_result() {
		global $wp;

		$transaction_id = $wp->query_vars['transaction_id'];
		$result         = $wp->query_vars['result'];
		$valid_results  = array(
			'success',
			'cancel',
			'failed',
			'timeout',
		);

		if ( $transaction_id ) {
			delete_option( 'wp_weixin_pay_wait_' . $transaction_id );
		}

		if ( in_array( $result, $valid_results, true ) ) {
			do_action( 'wp_weixin_pay_payment_result_' . $result, $transaction_id );
		}
	}

	protected function get_js_api_params( $amount, $notes, $nonce_str ) {
		$parameters = array();
		$openid     = $this->get_current_user_openid();
		$time       = current_time( 'timestamp' );
		$total_fee  = $amount;
		$date       = new DateTime( '@' . $time );
		$body       = get_bloginfo( 'name' ) . ' - ' . __( 'Custom Payment', 'wp-weixin-pay' );
		$details    = array(
			'cost_price'   => $total_fee,
			'receipt_id'   => 'wx' . $time,
			'goods_detail' => array(
				array(
					'goods_id'   => 'wpweixinpay_' . $time,
					'goods_name' => __( 'Custom Payment', 'wp-weixin-pay' ),
					'quantity'   => 1,
					'price'      => $total_fee,
				),
			),
		);

		$date->setTimezone( new DateTimeZone( 'Asia/Shanghai' ) );

		$unified_order_id = 'WPWP' . $time;
		$notify_url       = home_url( apply_filters( 'wp_weixin_pay_callback_url', 'wp-weixin-pay/notify/' ) );
		$start_time       = $date->format( 'YmdHis' );
		$expired_time     = date( 'YmdHis', strtotime( '+2 hours', strtotime( $start_time ) ) );
		$extend           = array(
			'time_start'  => $start_time,
			'time_expire' => $expired_time,
			'nonce_str'   => $nonce_str,
			'product_id'  => 'T' . $time,
			'detail'      => $this->wechat->json_encode( $details ),
		);

		$unified_order_result = $this->wechat->unifiedOrder(
			$openid,
			$body,
			$unified_order_id,
			$total_fee,
			$notify_url,
			$extend
		);

		if ( ! $this->wechat->getError() ) {
			$parameters = json_decode( $unified_order_result['payment_params'], true );
			$is_error   = ! array_key_exists( 'appId', $parameters );
			$is_error   = $is_error || ! array_key_exists( 'prepay_id', $unified_order_result );
			$is_error   = $is_error || '' === $unified_order_result['prepay_id'];

			if ( $is_error ) {
				throw new WechatPayException( 'Invalid parameters' );
			}

			$parameters['transactionId'] = $unified_order_id;
			$parameters['result']        = 'success';
			$parameters['type']          = 'wechatPayMobile';
			$valid_results               = array(
				'success',
				'cancel',
				'failed',
				'timeout',
			);

			foreach ( $valid_results as $result_type ) {
				$url_key     = ucfirst( $result_type ) . 'PayUrl';
				$do_redirect = apply_filters( 'wp_weixin_pay_redirect_on_' . $result_type, false );
				$return_url  = apply_filters(
					'wp_weixin_pay_return_url',
					home_url( 'wp-weixin-pay/transfer/' )
				);

				if ( $do_redirect ) {
					$parameters[ $url_key ] = add_query_arg( 'result', $result_type, $return_url );
					$parameters[ $url_key ] = add_query_arg( 'transaction_id', $unified_order_id, $return_url );
				} else {
					$parameters[ $url_key ] = false;
				}
			}
		} else {
			$parameters['error']   = true;
			$wechat_error          = $this->wechat->getError();
			$parameters['message'] = $wechat_error['message'];

			WP_Weixin::log( $wechat_error );
		}

		return $parameters;
	}

	protected function get_current_user_openid() {
		$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
		$openid       = filter_input( INPUT_COOKIE, 'wx_openId-' . $auth_blog_id, FILTER_SANITIZE_STRING );

		if ( ! $openid ) {
			$openid = $this->openid;
		}

		if ( empty( $openid ) ) {
			$message = __METHOD__ . ': ' . __( 'User is authenticated but openid cannot be found', 'wp-weixin-pay' );

			throw new WechatException( $message );
		}

		return $openid;
	}

	protected function set_openid_cookie( $auth_blog_id, $openid, $expiry ) {
		setcookie(
			'wx_openId-' . $auth_blog_id,
			$openid,
			current_time( 'timestamp' ) + (int) $expiry,
			'/',
			COOKIE_DOMAIN
		);
	}

	protected function pre_basic_oauth() {
		$scope = 'snsapi_base';
		$state = wp_create_nonce( 'wp_weixin_pay_auth_state' );

		if ( is_multisite() ) {
			$auth_blog_id = apply_filters( 'wp_weixin_ms_auth_blog_id', 1 );
			$query_string = ( ! empty( $_SERVER['QUERY_STRING'] ) ) ? '?' . $_SERVER['QUERY_STRING'] : '';
			$pay_blog_id  = apply_filters( 'wp_weixin_ms_pay_blog_id', get_current_blog_id() );
			$destination  = get_home_url( $pay_blog_id, 'wp-weixin-pay/transfer/' ) . $query_string;
			$destination  = WP_Weixin_Settings::encode_url( $destination . '|' . $pay_blog_id );

			if ( ! $auth_blog_id ) {
				$url = network_home_url( 'wp-weixin-pay/ms-crossdomain/hash/' . $destination );
			} else {
				$url = get_home_url( $auth_blog_id, 'wp-weixin-pay/ms-crossdomain/hash/' . $destination );
			}
		} else {
			$protocol    = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://';
			$destination = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$destination = WP_Weixin_Settings::encode_url( $destination );
			$callback    = home_url( 'wp-weixin-pay/basic-auth/' . $destination );
			$url         = $this->wechat->getOAuthRedirect( $callback, $state, $scope );
		}

		header( 'Location: ' . $url );

		exit();
	}

	protected function get_amount_info() {
		$info              = array();
		$info['amount']    = $this->amount;
		$info['fixed']     = $this->fixed;
		$info['note']      = $this->note;
		$info['nonce_str'] = Wechat_SDK::getNonceStr();

		return $info;
	}

}
