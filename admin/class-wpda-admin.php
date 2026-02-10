<?php
/**
 * Admin Class for WP Debug Assistant.
 *
 * Handles all administrative functionalities: registering the admin menu,
 * enqueueing assets, and rendering the debugging information page content,
 * including tab logic and data retrieval for each feature.
 *
 * @package WPDA
 * @subpackage Admin
 * @since 2.0.0
 */

namespace WPDA\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPDA_Admin class.
 *
 * @since 1.0.0
 */
class WPDA_Admin {

	/**
	 * The plugin's menu slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $menu_slug = 'wpda-debug-assistant';

	/**
	 * Initialize the class and register hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add the top-level admin menu item.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			esc_html__( 'WP Debug Assistant', 'wp-debug-assistant' ),
			esc_html__( 'Debug Assistant', 'wp-debug-assistant' ),
			'manage_options', // Capability required.
			$this->menu_slug,
			array( $this, 'render_admin_page' ),
			'dashicons-admin-tools', // Icon.
			80 // Position.
		);
	}

	/**
	 * Enqueue admin-specific assets (CSS).
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_' . $this->menu_slug !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpda-admin-style',
			WPDA_PLUGIN_URL . 'assets/css/wpda-admin.css',
			array(),
			WPDA_VERSION,
			'all'
		);
	}

	/**
	 * Render the main debugging information page.
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-debug-assistant' ) );
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'system-overview';

		$tabs = array(
			'system-overview'        => esc_html__( 'System Overview', 'wp-debug-assistant' ),
			'request-data'           => esc_html__( 'Request Data', 'wp-debug-assistant' ),
			'database-queries'       => esc_html__( 'Database Queries', 'wp-debug-assistant' ),
			'error-log'              => esc_html__( 'Error Log', 'wp-debug-assistant' ),
			'debug-constants-status' => esc_html__( 'Debug Constants', 'wp-debug-assistant' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Debug Assistant', 'wp-debug-assistant' ); ?></h1>

			<h2 class="nav-tab-wrapper wpda-nav-tab-wrapper">
				<?php
				foreach ( $tabs as $tab_slug => $tab_name ) {
					$active_class = ( $current_tab === $tab_slug ) ? ' nav-tab-active' : '';
					echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . $this->menu_slug . '&tab=' . $tab_slug ) ) . '" class="nav-tab' . esc_attr( $active_class ) . '">' . esc_html( $tab_name ) . '</a>';
				}
				?>
			</h2>

			<div class="wpda-tab-content">
				<?php
				switch ( $current_tab ) {
					case 'system-overview':
						$this->render_system_overview_tab();
						break;
					case 'request-data':
						$this->render_request_data_tab();
						break;
					case 'database-queries':
						$this->render_database_queries_tab();
						break;
					case 'error-log':
						$this->render_error_log_viewer_tab();
						break;
					case 'debug-constants-status':
						$this->render_debug_constants_status_tab();
						break;
					default:
						$this->render_system_overview_tab(); // Default to system overview.
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the content for the 'System Overview' tab.
	 *
	 * @since 1.0.0
	 */
	private function render_system_overview_tab() {
		?>
		<div class="card">
			<h2><?php esc_html_e( 'System Overview', 'wp-debug-assistant' ); ?></h2>
			<table class="form-table wpda-info-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'WordPress Version', 'wp-debug-assistant' ); ?></th>
						<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'PHP Version', 'wp-debug-assistant' ); ?></th>
						<td><?php echo esc_html( phpversion() ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Server Software', 'wp-debug-assistant' ); ?></th>
						<td><?php echo esc_html( getenv( 'SERVER_SOFTWARE' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Memory Limit', 'wp-debug-assistant' ); ?></th>
						<td><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></td>
					</tr>
					<?php
					$theme = wp_get_theme();
					if ( $theme->exists() ) :
						?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Active Theme', 'wp-debug-assistant' ); ?></th>
							<td><?php echo esc_html( $theme->get( 'Name' ) . ' (' . $theme->get( 'Version' ) . ')' ); ?></td>
						</tr>
						<?php
					endif;
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Active Plugins', 'wp-debug-assistant' ); ?></th>
						<td>
							<?php
							$active_plugins = get_option( 'active_plugins' );
							if ( ! empty( $active_plugins ) ) {
								echo '<ul class="wpda-list">';
								foreach ( $active_plugins as $plugin ) {
									// Ensure the plugin file exists before trying to get its data.
									if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
										$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
										echo '<li>' . esc_html( $plugin_data['Name'] . ' (' . $plugin_data['Version'] . ')' ) . '</li>';
									} else {
										echo '<li>' . esc_html( $plugin . ' (File not found)' ) . '</li>';
									}
								}
								echo '</ul>';
							} else {
								esc_html_e( 'No active plugins.', 'wp-debug-assistant' );
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Renders the content for the 'Request Data' tab.
	 *
	 * @since 1.0.0
	 */
	private function render_request_data_tab() {
		?>
		<div class="card">
			<h2><?php esc_html_e( 'Request Data', 'wp-debug-assistant' ); ?></h2>

			<h3><?php esc_html_e( '$_GET', 'wp-debug-assistant' ); ?></h3>
			<?php $this->display_superglobal_array( $_GET ); ?>

			<h3><?php esc_html_e( '$_POST', 'wp-debug-assistant' ); ?></h3>
			<?php $this->display_superglobal_array( $_POST ); ?>

			<h3><?php esc_html_e( '$_SERVER', 'wp-debug-assistant' ); ?></h3>
			<?php $this->display_superglobal_array( $_SERVER ); ?>

			<h3><?php esc_html_e( '$_COOKIE', 'wp-debug-assistant' ); ?></h3>
			<?php $this->display_superglobal_array( $_COOKIE ); ?>
		</div>
		<?php
	}

	/**
	 * Helper function to display superglobal arrays.
	 *
	 * @since 1.0.0
	 * @param array $data The superglobal array to display.
	 */
	private function display_superglobal_array( $data ) {
		if ( empty( $data ) ) {
			echo '<p>' . esc_html__( 'No data available.', 'wp-debug-assistant' ) . '</p>';
			return;
		}
		echo '<table class="form-table wpda-info-table">';
		echo '<thead><tr><th>' . esc_html__( 'Key', 'wp-debug-assistant' ) . '</th><th>' . esc_html__( 'Value', 'wp-debug-assistant' ) . '</th></tr></thead>';
		echo '<tbody>';
		foreach ( $data as $key => $value ) {
			echo '<tr>';
			echo '<th>' . esc_html( $key ) . '</th>';
			echo '<td>';
			if ( is_array( $value ) || is_object( $value ) ) {
				echo '<pre class="wpda-pre-output">' . esc_html( print_r( $value, true ) ) . '</pre>';
			} else {
				echo esc_html( $value );
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}


	/**
	 * Renders the content for the 'Database Queries' tab.
	 *
	 * @since 1.0.0
	 */
	private function render_database_queries_tab() {
		global $wpdb;
		?>
		<div class="card">
			<h2><?php esc_html_e( 'Database Queries', 'wp-debug-assistant' ); ?></h2>
			<?php
			if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES === true ) {
				if ( ! empty( $wpdb->queries ) ) {
					echo '<table class="form-table wpda-info-table wpda-queries-table">';
					echo '<thead><tr><th>' . esc_html__( 'Query', 'wp-debug-assistant' ) . '</th><th>' . esc_html__( 'Time (s)', 'wp-debug-assistant' ) . '</th></tr></thead>';
					echo '<tbody>';
					foreach ( $wpdb->queries as $query_item ) {
						// $query_item is an array: [query, time, callstack].
						echo '<tr>';
						echo '<td><pre class="wpda-pre-output">' . esc_html( $query_item[0] ) . '</pre></td>';
						echo '<td>' . esc_html( number_format( (float) $query_item[1], 4 ) ) . '</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
				} else {
					echo '<p>' . esc_html__( 'No database queries recorded for this page load.', 'wp-debug-assistant' ) . '</p>';
				}
			} else {
				echo '<p>';
				echo esc_html__( 'To view database queries, you need to enable SAVEQUERIES in your wp-config.php file.', 'wp-debug-assistant' );
				echo '<br>';
				echo '<code>define( \'SAVEQUERIES\', true );</code>';
				echo '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Renders the content for the 'Error Log Viewer' tab.
	 *
	 * @since 1.0.0
	 */
	private function render_error_log_viewer_tab() {
		?>
		<div class="card">
			<h2><?php esc_html_e( 'Error Log Viewer', 'wp-debug-assistant' ); ?></h2>
			<?php
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG === true ) {
				$debug_log_path = WP_CONTENT_DIR . '/debug.log';

				if ( file_exists( $debug_log_path ) && is_readable( $debug_log_path ) ) {
					$log_content = file_get_contents( $debug_log_path );
					if ( false !== $log_content ) {
						echo '<pre class="wpda-pre-output wpda-log-viewer">' . esc_html( $log_content ) . '</pre>';
					} else {
						echo '<p>' . esc_html__( 'Could not read the debug log file.', 'wp-debug-assistant' ) . '</p>';
					}
				} else {
					echo '<p>' . esc_html__( 'The debug log file (wp-content/debug.log) does not exist or is not readable.', 'wp-debug-assistant' ) . '</p>';
				}
			} else {
				echo '<p>';
				echo esc_html__( 'To view the error log, you need to enable WP_DEBUG_LOG in your wp-config.php file.', 'wp-debug-assistant' );
				echo '<br>';
				echo '<code>define( \'WP_DEBUG_LOG\', true );</code>';
				echo '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Renders the content for the 'Debug Constants Status' tab.
	 *
	 * @since 1.0.0
	 */
	private function render_debug_constants_status_tab() {
		?>
		<div class="card">
			<h2><?php esc_html_e( 'Debug Constants Status', 'wp-debug-assistant' ); ?></h2>
			<table class="form-table wpda-info-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'WP_DEBUG', 'wp-debug-assistant' ); ?></th>
						<td><?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? esc_html__( 'Enabled', 'wp-debug-assistant' ) : esc_html__( 'Disabled', 'wp-debug-assistant' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'WP_DEBUG_LOG', 'wp-debug-assistant' ); ?></th>
						<td><?php echo defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? esc_html__( 'Enabled', 'wp-debug-assistant' ) : esc_html__( 'Disabled', 'wp-debug-assistant' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'WP_DEBUG_DISPLAY', 'wp-debug-assistant' ); ?></th>
						<td><?php echo defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? esc_html__( 'Enabled', 'wp-debug-assistant' ) : esc_html__( 'Disabled', 'wp-debug-assistant' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'SCRIPT_DEBUG', 'wp-debug-assistant' ); ?></th>
						<td><?php echo defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? esc_html__( 'Enabled', 'wp-debug-assistant' ) : esc_html__( 'Disabled', 'wp-debug-assistant' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'SAVEQUERIES', 'wp-debug-assistant' ); ?></th>
						<td><?php echo defined( 'SAVEQUERIES' ) && SAVEQUERIES ? esc_html__( 'Enabled', 'wp-debug-assistant' ) : esc_html__( 'Disabled', 'wp-debug-assistant' ); ?></td>
					</tr>
				</tbody>
			</table>
			<p class="description">
				<?php esc_html_e( 'These constants are typically defined in your wp-config.php file.', 'wp-debug-assistant' ); ?>
			</p>
		</div>
		<?php
	}
}
