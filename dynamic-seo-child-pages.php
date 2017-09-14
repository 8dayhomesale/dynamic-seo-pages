<?php

/**
 * Plugin Name: Dynamic SEO Child Pages
 * Description: Generate a ton of SEO content instantly with dynamic child pages.
 * Version:     1.0
 * Author:      8 Day Home Sale
 * Author URI:  https://8dayhomesale.com
 * License:     GPLv2 or later
 * Text Domain: dynamic-seo-child-pages
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DSCP_VERSION', '1.0' );

require_once( dirname( __FILE__ ) . '/inc/class-dscp-post-type.php' );

function dscp_setup_yoast_sitemap( $providers ) {
	require_once dirname( __FILE__ ) . '/inc/class-dscp-sitemap.php';
	$providers[] = new DSCP_Sitemap_Provider();

	return $providers;
}

add_filter( 'wpseo_sitemaps_providers', 'dscp_setup_yoast_sitemap' );

function dscp_exclude_post_type_sitemap( $value, $post_type ) {
	if ( 'dscp_page' === $post_type ) {
		return true;
	}

	return $value;
}
add_filter( 'wpseo_sitemap_exclude_post_type', 'dscp_exclude_post_type_sitemap', 10, 2 );
