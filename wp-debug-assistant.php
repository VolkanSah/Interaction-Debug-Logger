<?php
/**
 * Plugin Name: WP Debug Assistant (before Enhanced Interaction & Debug Logger Pro)
 * Plugin URI:  https://github.com/VolkanSah/Debug-Logger-Pro
 * Description: A self-contained tool to provide essential debugging information within the WordPress admin area.
 * Version:     2.0.0
 * Author:      Volkan Sah
 * Author URI:  https://github.com/VolkanSah
 * License:     ESOL
 * License URI: https://github.com/ESOL-License
 * Text Domain: wp-debug-assistant
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 *
 * @since 1.0.0
 */
if ( ! defined( 'WPDA_VERSION' ) ) {
	define( 'WPDA_VERSION', '1.0.0' );
}
if ( ! defined( 'WPDA_PLUGIN_DIR' ) ) {
	define( 'WPDA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPDA_PLUGIN_URL' ) ) {
	define( 'WPDA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WPDA_BASENAME' ) ) {
	define( 'WPDA_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 *
 * This function is called when the plugin is activated.
 * For WP Debug Assistant, there are no specific database tables or options
 * to create, as it's a display-only tool.
 *
 * @since 1.0.0
 */
function activate_wp_debug_assistant() {
	// No specific activation tasks required for this plugin.
}

/**
 * The code that runs during plugin deactivation.
 *
 * This function is called when the plugin is deactivated.
 * For WP Debug Assistant, there are no specific database tables or options
 * to clean up.
 *
 * @since 1.0.0
 */
function deactivate_wp_debug_assistant() {
	// No specific deactivation tasks required for this plugin.
}

register_activation_hook( __FILE__, 'activate_wp_debug_assistant' );
register_deactivation_hook( __FILE__, 'deactivate_wp_debug_assistant' );

/**
 * Begins execution of the plugin.
 *
 * Loads and initializes the appropriate classes based on whether the request
 * is for the admin area or the frontend.
 *
 * @since 1.0.0
 */
function run_wp_debug_assistant() {
	if ( is_admin() ) {
		// Load the core admin class.
		require_once WPDA_PLUGIN_DIR . 'admin/class-wpda-admin.php';

		// Instantiate the admin class and initialize its hooks.
		$plugin_admin = new WPDA\Admin\WPDA_Admin();
		$plugin_admin->init();
	} elseif ( ! is_admin() && current_user_can( 'manage_options' ) ) {
		// Load the core frontend class for authorized users.
		require_once WPDA_PLUGIN_DIR . 'frontend/class-wpda-frontend.php';

		// Instantiate the frontend class and initialize its hooks.
		$plugin_frontend = new WPDA\Frontend\WPDA_Frontend();
		$plugin_frontend->init();
	}
}
add_action( 'plugins_loaded', 'run_wp_debug_assistant' );
