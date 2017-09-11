<?php

/**
 * Plugin Name: Dynamic SEO Pages
 * Description: Create dynamic content for SEO purposes.
 * Version:     1.0
 * Author:      8 Day Home Sale
 * Author URI:  https://8dayhomesale.com
 * License:     GPLv2 or later
 * Text Domain: dynamic-seo-pages
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once( dirname( __FILE__ ) . '/inc/class-dsp-post-type.php' );
