<?php
/**
 * Frontend Class for WP Debug Assistant.
 *
 * Handles all frontend functionalities, primarily rendering a debug bar
 * for authorized users to display essential debugging information.
 *
 * @package WPDA
 * @subpackage Frontend
 * @since 1.0.0
 */

namespace WPDA\Frontend;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPDA_Frontend class.
 *
 * @since 1.0.0
 */
class WPDA_Frontend {

	/**
	 * The plugin's admin menu slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $admin_menu_slug = 'wpda-debug-assistant';

	/**
	 * Initialize the class and register hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_debug_bar' ) );
	}

	/**
	 * Enqueue frontend-specific assets (CSS).
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue if the user can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpda-frontend-style',
			WPDA_PLUGIN_URL . 'assets/css/wpda-frontend.css',
			array(),
			WPDA_VERSION,
			'all'
		);
	}

	/**
	 * Renders the debug bar on the frontend.
	 *
	 * This bar is only visible to users with 'manage_options' capability.
	 *
	 * @since 1.0.0
	 */
	public function render_debug_bar() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get memory usage.
		$memory_usage = function_exists( 'memory_get_peak_usage' ) ? size_format( memory_get_peak_usage( true ) ) : esc_html__( 'N/A', 'wp-debug-assistant' );

		// Get page load time.
		// timer_stop() is a WordPress function that returns the page load time.
		// The first argument (0) means to return the value, not echo it.
		// The second argument (3) means to format to 3 decimal places.
		$page_load_time = timer_stop( 0, 3 );

		// Get number of database queries.
		global $wpdb;
		$num_queries = isset( $wpdb->queries ) ? count( $wpdb->queries ) : get_num_queries();

		?>
		<div id="wpda-debug-bar">
			<div class="wpda-debug-bar-content">
				<span class="wpda-item">
					<strong><?php esc_html_e( 'WPDA', 'wp-debug-assistant' ); ?>:</strong>
				</span>
				<span class="wpda-item">
					<?php esc_html_e( 'Load Time:', 'wp-debug-assistant' ); ?> <strong><?php echo esc_html( $page_load_time ); ?>s</strong>
				</span>
				<span class="wpda-item">
					<?php esc_html_e( 'Memory:', 'wp-debug-assistant' ); ?> <strong><?php echo esc_html( $memory_usage ); ?></strong>
				</span>
				<span class="wpda-item">
					<?php esc_html_e( 'Queries:', 'wp-debug-assistant' ); ?> <strong><?php echo esc_html( $num_queries ); ?></strong>
				</span>
				<span class="wpda-item wpda-admin-link">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->admin_menu_slug ) ); ?>" target="_blank">
						<?php esc_html_e( 'Admin Debug', 'wp-debug-assistant' ); ?>
					</a>
				</span>
			</div>
		</div>
		<?php
	}
}
