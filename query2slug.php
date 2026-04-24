<?php
/**
 * Plugin Name: Query2Slug
 * Plugin URI:  https://query2slug.com
 * Description: Map query string parameters to clean, canonical URL slugs.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      Bufox
 * Author URI:  https://bufox.it
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: query2slug
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'Q2SLUG_VERSION', '1.0.0' );
define( 'Q2SLUG_PLUGIN_FILE', __FILE__ );
define( 'Q2SLUG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once Q2SLUG_PLUGIN_DIR . 'includes/class-q2slug-db.php';
require_once Q2SLUG_PLUGIN_DIR . 'includes/class-q2slug-rewrite.php';
require_once Q2SLUG_PLUGIN_DIR . 'includes/class-q2slug-redirect.php';
require_once Q2SLUG_PLUGIN_DIR . 'includes/class-q2slug-rules-table.php';
require_once Q2SLUG_PLUGIN_DIR . 'includes/class-q2slug-admin.php';

register_activation_hook( __FILE__, array( 'Q2SLUG_DB', 'activate' ) );
register_deactivation_hook( __FILE__, 'q2slug_deactivate' );

add_action( 'plugins_loaded', 'q2slug_init' );

function q2slug_init(): void {
	new Q2SLUG_Rewrite();
	new Q2SLUG_Redirect();

	if ( is_admin() ) {
		new Q2SLUG_Admin();
	}
}

function q2slug_deactivate(): void {
	// Remove our rewrite rule from wp_rewrite before flushing,
	// otherwise flush regenerates it from the still-loaded extra_rules_top.
	global $wp_rewrite;
	$prefix  = Q2SLUG_Rewrite::get_prefix();
	$pattern = '^' . preg_quote( $prefix, '/' ) . '/([^/]+)/?$';
	unset( $wp_rewrite->extra_rules_top[ $pattern ] );

	Q2SLUG_DB::deactivate();
}
