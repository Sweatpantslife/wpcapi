<?php
/**
 * Plugin Name: Elementor to Meta CAPI Integration
 * Plugin URI:  https://example.com
 * Description: Connects Elementor Forms to Meta's Conversions API with server-side deduplication and event logging.
 * Version:     1.0.0
 * Author:      SuperGravity
 * Author URI:  https://google.com
 * Text Domain: elementor-meta-capi
 * Domain Path: /languages
 *
 * @package ElementorMetaCAPI
 */

namespace ElementorMetaCAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'EMC_VERSION', '1.0.0' );
define( 'EMC_PLUGIN_FILE', __FILE__ );
define( 'EMC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EMC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Elementor Meta CAPI Class.
 */
final class Plugin {

	/**
	 * Instance of this class.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return Plugin
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
		$this->includes();
		$this->hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once EMC_PLUGIN_DIR . 'includes/class-database.php';
		require_once EMC_PLUGIN_DIR . 'includes/class-settings.php';
		require_once EMC_PLUGIN_DIR . 'includes/class-capi-service.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function hooks() {
		// Elementor initialization hook
		add_action( 'elementor/init', [ $this, 'init_elementor_hooks' ] );

		// Activation hook
		register_activation_hook( EMC_PLUGIN_FILE, [ '\ElementorMetaCAPI\Database', 'create_tables' ] );
		
		// Initialize settings/admin hooks
		\ElementorMetaCAPI\Settings::instance();
	}

	/**
	 * Initialize Elementor hooks once Elementor is loaded.
	 */
	public function init_elementor_hooks() {
		// Proceed only if Elementor Pro Forms module is available.
		if ( class_exists( '\ElementorPro\Modules\Forms\Classes\Action_Base' ) ) {
			require_once EMC_PLUGIN_DIR . 'includes/class-elementor-action.php';
			
			$action = new \ElementorMetaCAPI\Elementor_Action();
			\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $action->get_name(), $action );
		}
	}
}

// Initialize the plugin.
add_action( 'plugins_loaded', function() {
	Plugin::instance();
} );
