<?php
namespace ElementorMetaCAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the plugin settings and Logs UI.
 */
class Settings {

	/**
	 * Instance of this class.
	 */
	private static $instance = null;

	/**
	 * Returns the instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'register_menu_pages' ] );
		add_action( 'admin_post_emcapi_retry_log', [ $this, 'handle_retry_log' ] );
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'emcapi_settings_group', 'emcapi_pixel_id', 'sanitize_text_field' );
		register_setting( 'emcapi_settings_group', 'emcapi_access_token', 'sanitize_text_field' );
	}

	/**
	 * Register admin menu and submenu pages.
	 */
	public function register_menu_pages() {
		add_options_page(
			__( 'Elementor Meta CAPI', 'elementor-meta-capi' ),
			__( 'Elementor Meta CAPI', 'elementor-meta-capi' ),
			'manage_options',
			'emcapi_settings',
			[ $this, 'render_settings_page' ]
		);

		add_submenu_page(
			'options-general.php',
			__( 'Meta CAPI Logs', 'elementor-meta-capi' ),
			__( 'Meta CAPI Logs', 'elementor-meta-capi' ),
			'manage_options',
			'emcapi_logs',
			[ $this, 'render_logs_page' ]
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Elementor Meta CAPI Settings', 'elementor-meta-capi' ); ?></h1>
			<p>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=emcapi_logs' ) ); ?>">
					<?php esc_html_e( 'View Logs', 'elementor-meta-capi' ); ?>
				</a>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'emcapi_settings_group' ); ?>
				<?php do_settings_sections( 'emcapi_settings_group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Meta Pixel ID', 'elementor-meta-capi' ); ?></th>
						<td>
							<input type="text" name="emcapi_pixel_id" value="<?php echo esc_attr( get_option( 'emcapi_pixel_id' ) ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'CAPI Access Token', 'elementor-meta-capi' ); ?></th>
						<td>
							<input type="password" name="emcapi_access_token" value="<?php echo esc_attr( get_option( 'emcapi_access_token' ) ); ?>" class="regular-text" />
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the logs page.
	 */
	public function render_logs_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'emcapi_logs';
		$logs = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'emcapi_logs` ORDER BY id DESC LIMIT %d', 50 ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Meta CAPI Logs', 'elementor-meta-capi' ); ?></h1>
			<p><?php esc_html_e( 'Showing the last 50 events.', 'elementor-meta-capi' ); ?></p>
			
			<?php if ( isset( $_GET['retried'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Log retried. Check logs for new status.', 'elementor-meta-capi' ); ?></p></div>
			<?php endif; ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Event Name</th>
						<th>Status</th>
						<th>Created At</th>
						<th>Response</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No logs found.', 'elementor-meta-capi' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log->id ); ?></td>
								<td><?php echo esc_html( $log->event_name ); ?></td>
								<td>
									<?php 
										$color = 'black';
										if ( 'failed' === $log->status ) $color = 'red';
										if ( 'success' === $log->status ) $color = 'green';
									?>
									<span style="color: <?php echo esc_attr( $color ); ?>; font-weight: bold;">
										<?php echo esc_html( strtoupper( $log->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $log->created_at ); ?></td>
								<td>
									<textarea readonly style="width: 100%; height: 50px;"><?php echo esc_textarea( $log->api_response ); ?></textarea>
								</td>
								<td>
									<?php if ( 'failed' === $log->status ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<input type="hidden" name="action" value="emcapi_retry_log">
											<input type="hidden" name="log_id" value="<?php echo esc_attr( $log->id ); ?>">
											<?php wp_nonce_field( 'emcapi_retry_' . $log->id ); ?>
											<button type="submit" class="button"><?php esc_html_e( 'Retry', 'elementor-meta-capi' ); ?></button>
										</form>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Handle manual retry.
	 */
	public function handle_retry_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$log_id = isset( $_POST['log_id'] ) ? intval( $_POST['log_id'] ) : 0;
		if ( ! $log_id ) {
			wp_die( 'Invalid Log ID' );
		}

		check_admin_referer( 'emcapi_retry_' . $log_id );

		require_once EMC_PLUGIN_DIR . 'includes/class-capi-service.php';
		
		$log = Database::get_log( $log_id );
		if ( $log ) {
			$payload = json_decode( $log['payload'], true );
			if ( $payload && is_array( $payload ) ) {
				// Retry sending the payload.
				CAPI_Service::send_payload( $payload, $log_id );
			}
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=emcapi_logs&retried=1' ) );
		exit;
	}
}
