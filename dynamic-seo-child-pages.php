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
