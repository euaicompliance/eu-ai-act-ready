<?php
/**
 * EU AI Act Ready - Core orchestrator
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 */
class EUAIACTREADY {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var EUAIACTREADY_Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Media bulk scan batch size.
	 *
	 * @var int
	 */
	protected $media_bulk_scan_batch_size;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		$this->plugin_slug = EUAIACTREADY_PLUGIN_SLUG;
		$this->version     = EUAIACTREADY_VERSION;

		$this->euaiactready_load_dependencies();
		$this->euaiactready_define_admin_hooks();
		$this->euaiactready_define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function euaiactready_load_dependencies() {
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-data-store.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-chatbot-transparency.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-content-transparency.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-shortcode.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-loader.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-media-transparency.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-post-meta-box.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/ai-tools/class-euaiactready-ai-tools-registry.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/ai-tools/class-euaiactready-ai-tools-detector.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/ai-tools/class-euaiactready-category-suggester.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-ai-tools.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-readiness.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-ai-literacy.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-assessment.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'includes/ai-tools/class-euaiactready-ai-tools-site-fetch.php';
		require_once EUAIACTREADY_PLUGIN_DIR . 'admin/class-euaiactready-admin.php';

		$this->loader = new EUAIACTREADY_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function euaiactready_define_admin_hooks() {
		$plugin_admin = new EUAIACTREADY_Admin(
			$this->euaiactready_get_plugin_slug(),
			$this->euaiactready_get_version(),
			$this->euaiactready_get_batch_size()
		);

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'euaiactready_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'euaiactready_enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'euaiactready_add_plugin_admin_menu' );
		$this->loader->add_filter( 'plugin_action_links_' . EUAIACTREADY_PLUGIN_BASENAME, $plugin_admin, 'euaiactready_add_action_links' );
		$this->loader->add_action( 'wp_ajax_euaiactready_chunk_scan', $plugin_admin, 'euaiactready_ajax_chunk_scan' );
		$this->loader->add_action( 'wp_ajax_euaiactready_check_bulk_scan_buffer', $plugin_admin, 'euaiactready_ajax_check_bulk_scan_buffer' );
		$this->loader->add_action( 'wp_ajax_euaiactready_flush_bulk_scan_buffer', $plugin_admin, 'euaiactready_ajax_flush_bulk_scan_buffer' );
		$this->loader->add_action( 'wp_ajax_euaiactready_clear_bulk_scan_buffer', $plugin_admin, 'euaiactready_ajax_clear_bulk_scan_buffer' );
		$this->loader->add_action( 'wp_ajax_euaiactready_unmark_content', $plugin_admin, 'euaiactready_ajax_unmark_content' );
		$this->loader->add_action( 'wp_ajax_euaiactready_unmark_image', $plugin_admin, 'euaiactready_ajax_unmark_image' );
		$this->loader->add_action( 'wp_ajax_euaiactready_restore_image', $plugin_admin, 'euaiactready_ajax_restore_image' );
		$this->loader->add_action( 'wp_ajax_euaiactready_mark_image_as_ai', $plugin_admin, 'euaiactready_ajax_mark_image_as_ai' );
		$this->loader->add_action( 'wp_ajax_euaiactready_bulk_action', $plugin_admin, 'euaiactready_ajax_bulk_action' );
		$this->loader->add_action( 'admin_post_euaiactready_print_report', $plugin_admin, 'euaiactready_handle_print_report' );
		$this->loader->add_action( 'wp_dashboard_setup', $plugin_admin, 'euaiactready_register_dashboard_widget' );

		// Initialize post meta box for AI disclosure.
		new EUAIACTREADY_Post_Meta_Box();
		new EUAIACTREADY_Data_Store();
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function euaiactready_define_public_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'euaiactready_enqueue_frontend_assets' ) );
		
		new EUAIACTREADY_Content_Transparency();
		new EUAIACTREADY_Shortcode();

		// Chatbot transparency is frontend-only; skip instantiation entirely when disabled.
		if ( get_option( 'euaiactready_chatbot_transparency', true ) ) {
			new EUAIACTREADY_Chatbot_Transparency();
		}

		// Media transparency is always instantiated because it also powers admin-side features.
		// The euaiactready_media_transparency option controls frontend label display only.
		new EUAIACTREADY_Media_Transparency();

		// AI Tools Registry - always active (handles its own frontend guard).
		global $euaiactready_ai_tools_instance;
		$euaiactready_ai_tools_instance = new Euaiactready_AI_Tools();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function euaiactready_run() {
		$this->loader->run();
	}

	/**
	 * Enqueue frontend styles and scripts.
	 */
	public function euaiactready_enqueue_frontend_assets() {
		wp_enqueue_style(
			'eu-ai-act-ready-frontend',
			EUAIACTREADY_PLUGIN_URL . 'build/assets/eu-ai-act-ready.css',
			array(),
			EUAIACTREADY_VERSION
		);

		wp_enqueue_script(
			'eu-ai-act-ready-frontend',
			EUAIACTREADY_PLUGIN_URL . 'build/assets/eu-ai-act-ready.js',
			array( 'jquery' ),
			EUAIACTREADY_VERSION,
			true
		);
	}

	/**
	 * The slug of the plugin.
	 *
	 * @return string
	 */
	public function euaiactready_get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * The version number of the plugin.
	 *
	 * @return string
	 */
	public function euaiactready_get_version() {
		return $this->version;
	}

	/**
	 * Get the media bulk scan limit.
	 *
	 * @return string
	 */
	public function euaiactready_get_batch_size() {
		return $this->media_bulk_scan_batch_size;
	}

	/**
	 * Get the unified AI icon used throughout the plugin.
	 *
	 * @param int    $size  Icon size in pixels.
	 * @param string $color Icon color (hex or CSS color).
	 * @return string Unescaped SVG markup.
	 */
	public static function euaiactready_get_ai_icon( $size = 16, $color = '#667eea' ) {
		$svg = sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 24 24" fill="%s" stroke="none" class="eu-ai-act-ready-icon"><path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z"/></svg>',
			$size,
			$size,
			esc_attr( $color )
		);

		return $svg;
	}

	/**
	 * Get allowed HTML tags for SVG icons.
	 *
	 * @return array Allowed HTML array for wp_kses().
	 */
	public static function euaiactready_get_svg_allowed_html() {
		return array(
			'svg'  => array(
				'xmlns'   => true,
				'width'   => true,
				'height'  => true,
				'viewbox' => true,
				'fill'    => true,
				'stroke'  => true,
				'class'   => true,
			),
			'path' => array(
				'd' => true,
			),
		);
	}
}
