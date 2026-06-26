<?php
/**
 * EU AI Act Ready - AI Tools Registry
 *
 * Fetches and caches the remote AI tools list from eu-ai-act-ready.com/ai-tools.json.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the remote AI tools registry.
 */
class Euaiactready_AI_Tools_Registry {

	const OPTION_DATA    = 'euaiactready_ai_tools_registry';
	const OPTION_META    = 'euaiactready_ai_tools_registry_meta';
	const CRON_HOOK      = 'euaiactready_refresh_ai_tools_registry';
	const REMOTE_URL     = 'https://eu-ai-act-ready.com/ai-tools.json';
	const CACHE_TTL      = DAY_IN_SECONDS;
	const REGISTRY_TOKEN = 'euaiactready-registry-2026';

	/**
	 * Return the full list of tools from the cached registry.
	 *
	 * @return array
	 */
	public function get_all() {
		$data = get_option( self::OPTION_DATA, array() );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Return registry metadata: total tool count, last fetch timestamp, status.
	 *
	 * @return array{total: int, fetched_at: int|null, status: string}
	 */
	public function get_meta() {
		$meta = get_option( self::OPTION_META, array() );
		return wp_parse_args(
			$meta,
			array(
				'total'      => count( $this->get_all() ),
				'fetched_at' => null,
				'status'     => 'never',
			)
		);
	}

	/**
	 * Fetch the remote registry and cache it. Returns true on success, WP_Error on failure.
	 *
	 * @return true|WP_Error
	 */
	public function fetch_remote() {
		$response = wp_remote_get(
			self::REMOTE_URL,
			array(
				'timeout'    => 15,
				'user-agent' => 'EU-AI-Act-Ready/' . EUAIACTREADY_VERSION . '; ' . get_bloginfo( 'url' ),
				'headers'    => array(
					'X-EU-AI-Act-Ready-Token' => self::REGISTRY_TOKEN,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->euaiactready_store_meta( 'error', null );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			$err = new WP_Error(
				'euaiactready_registry_http_error',
				/* translators: %d is the HTTP response code */
				sprintf( __( 'Registry fetch returned HTTP %d.', 'eu-ai-act-ready' ), $code )
			);
			$this->euaiactready_store_meta( 'error', null );
			return $err;
		}

		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( ! is_array( $json ) ) {
			$err = new WP_Error( 'euaiactready_registry_invalid_json', __( 'Registry response is not valid JSON.', 'eu-ai-act-ready' ) );
			$this->euaiactready_store_meta( 'error', null );
			return $err;
		}

		// Support both flat array and {meta, tools} envelope.
		$tools = isset( $json['tools'] ) ? $json['tools'] : $json;

		if ( ! is_array( $tools ) ) {
			$err = new WP_Error( 'euaiactready_registry_no_tools', __( 'Registry JSON does not contain a tools array.', 'eu-ai-act-ready' ) );
			$this->euaiactready_store_meta( 'error', null );
			return $err;
		}

		// Sanitize each tool entry before storing.
		$clean = array();
		foreach ( $tools as $tool ) {
			if ( ! is_array( $tool ) || empty( $tool['id'] ) ) {
				continue;
			}
			$clean[] = $this->euaiactready_sanitize_tool( $tool );
		}

		update_option( self::OPTION_DATA, $clean, false );
		$this->euaiactready_store_meta( 'ok', time() );

		/**
		 * Fires after the AI tools registry is successfully refreshed.
		 *
		 * @param array $clean Sanitized tools array.
		 */
		do_action( 'euaiactready_registry_refreshed', $clean );

		return true;
	}

	/**
	 * Refresh only if the cache is older than CACHE_TTL.
	 *
	 * @return void
	 */
	public function maybe_refresh() {
		$meta = $this->get_meta();
		if ( null === $meta['fetched_at'] || ( time() - (int) $meta['fetched_at'] ) > self::CACHE_TTL ) {
			$this->fetch_remote();
		}
	}

	/**
	 * Schedule the daily cron event if not already scheduled.
	 *
	 * @return void
	 */
	public function schedule_refresh() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the cron event (called on deactivation).
	 *
	 * @return void
	 */
	public static function unschedule_refresh() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Sanitize a single tool entry from the remote JSON.
	 *
	 * @param array $tool Raw tool data.
	 * @return array Sanitized tool data.
	 */
	private function euaiactready_sanitize_tool( array $tool ) {
		$slugs = array();
		if ( isset( $tool['wp_slugs'] ) && is_array( $tool['wp_slugs'] ) ) {
			foreach ( $tool['wp_slugs'] as $slug ) {
				$slugs[] = sanitize_key( $slug );
			}
		}

		return array(
			'id'                => sanitize_key( $tool['id'] ),
			'name'              => sanitize_text_field( $tool['name'] ?? '' ),
			'category'          => sanitize_key( $tool['category'] ?? 'other' ),
			'description'       => sanitize_text_field( $tool['description'] ?? '' ),
			'eu_ai_act_article' => sanitize_text_field( $tool['eu_ai_act_article'] ?? '' ),
			'risk_level'        => sanitize_key( $tool['risk_level'] ?? 'limited' ),
			'url'               => esc_url_raw( $tool['url'] ?? '' ),
			'wp_slugs'          => $slugs,
		);
	}

	/**
	 * Persist registry metadata.
	 *
	 * @param string   $status     'ok' | 'error' | 'never'.
	 * @param int|null $fetched_at Unix timestamp or null.
	 * @return void
	 */
	private function euaiactready_store_meta( $status, $fetched_at ) {
		update_option(
			self::OPTION_META,
			array(
				'total'      => count( $this->get_all() ),
				'fetched_at' => $fetched_at,
				'status'     => $status,
			),
			false
		);
	}
}
