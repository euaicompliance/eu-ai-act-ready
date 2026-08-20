<?php
/**
 * EU AI Act Ready - AI Tools Orchestrator
 *
 * Wires together the registry, detector, cron, AJAX handlers, and frontend notice.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central controller for the AI Tools Registry feature.
 */
class Euaiactready_AI_Tools {

	const OPTION_ENABLED      = 'euaiactready_ai_tools_notice_enabled';
	const OPTION_VISIBILITY   = 'euaiactready_ai_tools_visibility';
	const OPTION_NOTICE_STYLE = 'euaiactready_ai_tools_notice_style';

	/**
	 * @var Euaiactready_AI_Tools_Registry
	 */
	private $registry;

	/**
	 * @var Euaiactready_AI_Tools_Detector
	 */
	private $detector;

	/**
	 * Initialize and register all hooks.
	 */
	public function __construct() {
		$this->registry = new Euaiactready_AI_Tools_Registry();
		$this->detector = new Euaiactready_AI_Tools_Detector( $this->registry );

		$this->euaiactready_init_hooks();
	}

	/**
	 * Return the registry instance (used by admin page).
	 *
	 * @return Euaiactready_AI_Tools_Registry
	 */
	public function get_registry() {
		return $this->registry;
	}

	/**
	 * Return the detector instance (used by admin page).
	 *
	 * @return Euaiactready_AI_Tools_Detector
	 */
	public function get_detector() {
		return $this->detector;
	}

	/**
	 * Register all WordPress hooks.
	 *
	 * @return void
	 */
	private function euaiactready_init_hooks() {
		// Schedule daily registry refresh on init.
		add_action( 'init', array( $this, 'euaiactready_schedule_cron' ) );

		// Register the notice style setting (must be on admin_init so options.php can process it).
		add_action( 'admin_init', array( $this, 'euaiactready_register_settings' ) );

		// Cron callback.
		add_action( Euaiactready_AI_Tools_Registry::CRON_HOOK, array( $this, 'euaiactready_run_cron' ) );

		// AJAX handlers (admin only).
		add_action( 'wp_ajax_euaiactready_refresh_ai_tools_registry', array( $this, 'euaiactready_ajax_refresh_registry' ) );
		add_action( 'wp_ajax_euaiactready_ai_tools_toggle_visibility', array( $this, 'euaiactready_ajax_toggle_visibility' ) );
		add_action( 'wp_ajax_euaiactready_ai_tools_add_manual', array( $this, 'euaiactready_ajax_add_manual' ) );
		add_action( 'wp_ajax_euaiactready_ai_tools_remove_manual', array( $this, 'euaiactready_ajax_remove_manual' ) );

		// Auto-rescan on plugin activation/deactivation (Watchtower).
		add_action( 'activated_plugin', array( $this, 'euaiactready_flag_rescan' ) );
		add_action( 'deactivated_plugin', array( $this, 'euaiactready_flag_rescan' ) );

		// Process any pending rescan flag on admin_init.
		add_action( 'admin_init', array( $this, 'euaiactready_maybe_rescan' ), 20 );

		// Frontend notice.
		add_action( 'wp_footer', array( $this, 'euaiactready_render_frontend_notice' ), 998 );
		add_action( 'wp_enqueue_scripts', array( $this, 'euaiactready_enqueue_frontend_scripts' ) );
	}

	/**
	 * Register the AI Tools notice style setting for the Settings API.
	 *
	 * @return void
	 */
	public function euaiactready_register_settings() {
		register_setting(
			'euaiactready_ai_tools_notice',
			'euaiactready_ai_tools_notice_enabled',
			array( 'sanitize_callback' => 'rest_sanitize_boolean' )
		);
		register_setting(
			'euaiactready_ai_tools_notice',
			'euaiactready_ai_tools_notice_style',
			array( 'sanitize_callback' => 'sanitize_key' )
		);
	}

	/**
	 * Register cron if not already scheduled.
	 *
	 * @return void
	 */
	public function euaiactready_schedule_cron() {
		$this->registry->schedule_refresh();
	}

	/**
	 * Cron callback: refresh registry then re-scan.
	 *
	 * @return void
	 */
	public function euaiactready_run_cron() {
		$this->registry->fetch_remote();
		$this->detector->scan();
	}

	/**
	 * AJAX: manually trigger a registry refresh + re-scan.
	 *
	 * @return void
	 */
	public function euaiactready_ajax_refresh_registry() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ) ) );
			return;
		}

		// Rate limit: prevent sync button from being hammered.
		if ( get_transient( 'euaiactready_sync_cooldown' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Please wait a moment before syncing again.', 'eu-ai-act-ready' ) ) );
			return;
		}
		set_transient( 'euaiactready_sync_cooldown', 1, 60 );

		$result = $this->registry->fetch_remote();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			return;
		}

		$detected   = $this->detector->scan();
		$site_fetch = new Euaiactready_AI_Tools_Site_Fetch();
		$site_fetch->fetch();
		$meta = $this->registry->get_meta();

		wp_send_json_success(
			array(
				'total'          => $meta['total'],
				'detected_count' => count( $detected ),
				'fetched_at'     => $meta['fetched_at'],
				'message'        => sprintf(
					/* translators: 1: total tools in registry, 2: detected tool count */
					esc_html__( 'Registry refreshed: %1$d tools, %2$d detected on this site.', 'eu-ai-act-ready' ),
					(int) $meta['total'],
					count( $detected )
				),
			)
		);
	}

	/**
	 * AJAX: toggle a tool's visibility in the frontend notice.
	 *
	 * @return void
	 */
	public function euaiactready_ajax_toggle_visibility() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ) ) );
			return;
		}

		$tool_id = isset( $_POST['tool_id'] ) ? sanitize_key( wp_unslash( $_POST['tool_id'] ) ) : '';
		$visible = isset( $_POST['visible'] ) ? rest_sanitize_boolean( wp_unslash( $_POST['visible'] ) ) : false;

		if ( empty( $tool_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid tool ID.', 'eu-ai-act-ready' ) ) );
			return;
		}

		$visibility             = get_option( self::OPTION_VISIBILITY, array() );
		$visibility[ $tool_id ] = (bool) $visible;
		update_option( self::OPTION_VISIBILITY, $visibility, false );

		wp_send_json_success( array( 'tool_id' => $tool_id, 'visible' => (bool) $visible ) );
	}

	/**
	 * AJAX: add a manual AI tool declaration.
	 *
	 * @return void
	 */
	public function euaiactready_ajax_add_manual() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ) ) );
			return;
		}

		$slug     = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$category = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'other';

		if ( empty( $slug ) || empty( $name ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Slug and name are required.', 'eu-ai-act-ready' ) ) );
			return;
		}

		$this->detector->add_manual_tool( $slug, $name, $category );

		// Re-scan so the new tool appears immediately in detected list.
		$this->detector->scan();

		wp_send_json_success( array( 'message' => esc_html__( 'Tool added and scan updated.', 'eu-ai-act-ready' ) ) );
	}

	/**
	 * AJAX: remove a manual AI tool declaration.
	 *
	 * @return void
	 */
	public function euaiactready_ajax_remove_manual() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ) ) );
			return;
		}

		$tool_id = isset( $_POST['tool_id'] ) ? sanitize_key( wp_unslash( $_POST['tool_id'] ) ) : '';

		if ( empty( $tool_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid tool ID.', 'eu-ai-act-ready' ) ) );
			return;
		}

		$this->detector->remove_manual_tool( $tool_id );
		$this->detector->scan();

		wp_send_json_success( array( 'message' => esc_html__( 'Tool removed.', 'eu-ai-act-ready' ) ) );
	}

	/**
	 * Enqueue frontend notice script and localize with visible tool data.
	 *
	 * @return void
	 */
	public function euaiactready_enqueue_frontend_scripts() {
		if ( ! get_option( self::OPTION_ENABLED, true ) ) {
			return;
		}

		$visible_tools = $this->euaiactready_get_visible_tools();

		if ( empty( $visible_tools ) ) {
			return;
		}

		wp_enqueue_script(
			'euaiactready-ai-tools-notice',
			EUAIACTREADY_PLUGIN_URL . 'build/assets/ai-tools-notice.js',
			array(),
			EUAIACTREADY_VERSION,
			true
		);

		wp_localize_script(
			'euaiactready-ai-tools-notice',
			'euaiactreadyAiToolsConfig',
			array(
				'tools'   => array_values( $visible_tools ),
				'style'   => get_option( self::OPTION_NOTICE_STYLE, 'floating' ),
				'message' => $this->euaiactready_build_notice_message( $visible_tools ),
			)
		);
	}

	/**
	 * Render the AI tools frontend notice via wp_footer.
	 *
	 * @return void
	 */
	public function euaiactready_render_frontend_notice() {
		if ( ! get_option( self::OPTION_ENABLED, true ) ) {
			return;
		}

		$visible_tools = $this->euaiactready_get_visible_tools();

		if ( empty( $visible_tools ) ) {
			return;
		}

		$style = get_option( self::OPTION_NOTICE_STYLE, 'floating' );

		echo '<div id="euaiactready-ai-tools-notice" class="euaiactready-ai-tools-notice ai-notice-' . esc_attr( $style ) . '" style="display:none;" aria-live="polite">';
		echo wp_kses_post( $this->euaiactready_build_notice_message( $visible_tools ) );
		echo '</div>';
	}

	/**
	 * Return the tools that may be disclosed on the front end.
	 *
	 * Applies the same two gates as the notice itself: the AI Systems notice has to
	 * be enabled, and each tool has to be toggled visible. Anything that discloses
	 * tool names to visitors - the notice, the schema.org markup, the RSS
	 * disclosure - should go through here, so one setting governs them all.
	 *
	 * @return array
	 */
	public function euaiactready_get_disclosable_tools() {
		if ( ! get_option( self::OPTION_ENABLED, true ) ) {
			return array();
		}

		return $this->euaiactready_get_visible_tools();
	}

	/**
	 * Return detected tools that are toggled visible.
	 *
	 * @return array
	 */
	private function euaiactready_get_visible_tools() {
		$detected   = $this->detector->get_detected();
		$visibility = get_option( self::OPTION_VISIBILITY, array() );

		return array_filter(
			$detected,
			static function ( $tool ) use ( $visibility ) {
				$id = $tool['id'];
				// Default to hidden until admin explicitly enables it.
				return isset( $visibility[ $id ] ) && true === $visibility[ $id ];
			}
		);
	}

	/**
	 * Build the human-readable notice message for the AI Systems disclosure.
	 *
	 * @param array $visible_tools Visible tool entries.
	 * @return string Escaped HTML.
	 */
	private function euaiactready_build_notice_message( array $visible_tools ) {
		$message = __( 'AI Transparency: This site uses AI-powered tools and services.', 'eu-ai-act-ready' );

		/**
		 * Filter the AI tools frontend notice message.
		 *
		 * @param string $message      Generated notice message.
		 * @param array  $visible_tools Tools included in the message.
		 */
		return wp_kses_post( apply_filters( 'euaiactready_ai_tools_notice_message', $message, $visible_tools ) );
	}

	/**
	 * Set a flag indicating the detected-tools list needs refreshing.
	 * Called on activated_plugin and deactivated_plugin.
	 *
	 * @return void
	 */
	public function euaiactready_flag_rescan() {
		update_option( 'euaiactready_ai_tools_needs_rescan', 1, false );
	}

	/**
	 * Run a rescan if the watchtower flag was set by a recent plugin change.
	 *
	 * @return void
	 */
	public function euaiactready_maybe_rescan() {
		if ( ! get_option( 'euaiactready_ai_tools_needs_rescan' ) ) {
			return;
		}
		delete_option( 'euaiactready_ai_tools_needs_rescan' );
		$this->detector->scan();

		/**
		 * Fired after an automatic rescan triggered by a plugin change.
		 */
		do_action( 'euaiactready_ai_tools_rescanned' );
	}

	/**
	 * Unschedule cron on plugin deactivation (called from activator/deactivator).
	 *
	 * @return void
	 */
	public static function deactivate() {
		Euaiactready_AI_Tools_Registry::unschedule_refresh();
	}
}
