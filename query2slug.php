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

define( 'Q2S_VERSION', '1.0.0' );
define( 'Q2S_PLUGIN_FILE', __FILE__ );
define( 'Q2S_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once Q2S_PLUGIN_DIR . 'includes/class-q2s-db.php';
require_once Q2S_PLUGIN_DIR . 'includes/class-q2s-rewrite.php';
require_once Q2S_PLUGIN_DIR . 'includes/class-q2s-redirect.php';
require_once Q2S_PLUGIN_DIR . 'includes/class-q2s-rules-table.php';
require_once Q2S_PLUGIN_DIR . 'includes/class-q2s-admin.php';

register_activation_hook( __FILE__, array( 'Q2S_DB', 'activate' ) );
register_deactivation_hook( __FILE__, 'q2s_deactivate' );

add_action( 'plugins_loaded', 'q2s_init' );

function q2s_init(): void {
	new Q2S_Rewrite();
	new Q2S_Redirect();

	if ( is_admin() ) {
		new Q2S_Admin();
	}
}

function q2s_deactivate(): void {
	// Remove our rewrite rule from wp_rewrite before flushing,
	// otherwise flush regenerates it from the still-loaded extra_rules_top.
	global $wp_rewrite;
	$prefix  = Q2S_Rewrite::get_prefix();
	$pattern = '^' . preg_quote( $prefix, '/' ) . '/([^/]+)/?$';
	unset( $wp_rewrite->extra_rules_top[ $pattern ] );

	Q2S_DB::deactivate();
}
