<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly
}

if ( is_multisite() ) {
	$blog_ids = array_map( function( $site ) {

		return $site->blog_id;
	}, get_sites() );
} else {
	$blog_ids = array( get_current_blog_id() );
}

foreach ( $blog_ids as $blog_id ) {

	if ( is_multisite() ) {
		switch_to_blog( $blog_id );
	}

	$settings = get_option( 'wp_weixin_settings' );

	if ( $settings && isset( $settings['wp_weixin_custom_transfer'] ) ) {
		unset( $settings['wp_weixin_custom_transfer'] );
		update_option( 'wp_weixin_settings', $settings );
	}

	if ( is_multisite() ) {
		restore_current_blog();
	}
}
