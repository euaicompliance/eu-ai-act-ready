<?php
/**
 * EU AI Act Ready - AI Tools Site Fetch
 *
 * Fetches the site homepage and latest post URL via wp_remote_get,
 * then scans the HTML for known AI tool JavaScript signatures.
 * This catches tools that are embedded via <script> tag and are NOT
 * registered as WordPress plugins (e.g. Intercom, Drift, Chatbase).
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects AI tools embedded via JavaScript by scanning live page HTML.
 */
class Euaiactready_AI_Tools_Site_Fetch {

	const OPTION_KEY = 'euaiactready_ai_tools_site_fetch';
	const TTL        = 43200; // 12 hours.

	/**
	 * Known JS signature patterns for AI tools not typically installed as WP plugins.
	 * Each entry: id, name, category, signatures (strings that must appear in the HTML).
	 */
	private static $signatures = array(
		array(
			'id'         => 'intercom',
			'name'       => 'Intercom',
			'category'   => 'chatbot',
			'article'    => 'Article 50(1)',
			'signatures' => array( 'app.intercom.io', 'widget.intercom.io', 'intercom.com/widget' ),
		),
		array(
			'id'         => 'drift',
			'name'       => 'Drift',
			'category'   => 'chatbot',
			'article'    => 'Article 50(1)',
			'signatures' => array( 'js.driftt.com', 'drift.com/api/widget', 'drift-snippet' ),
		),
		array(
			'id'         => 'hubspot-chat',
			'name'       => 'HubSpot Chat',
			'category'   => 'chatbot',
			'article'    => 'Article 50(1)',
			'signatures' => array( 'js.hs-scripts.com', 'js.hubspot.com', 'hscollectedforms' ),
		),
		array(
			'id'         => 'freshchat',
			'name'       => 'Freshchat',
			'category'   => 'chatbot',
			'article'    => 'Article 50(1)',
			'signatures' => array( 'wchat.freshchat.com', 'freshchat.com/js/widget.js' ),
		),
		array(
			'id'         => 'crisp-chat',
			'name'       => 'Crisp',
			'category'   => 'chatbot',
			'article'    => 'Article 50(1)',
			'signatures' => array( 'client.crisp.chat', 'CRISP_WEBSITE_ID' ),
		),
		array(
			'id'         => 'chatbase',
			'name'       => 'Chatbase',
			'category'   => 'chatbot',
			'article'    => 'Article 50(1)',
			'signatures' => array( 'chatbase.co/chatbot-iframe', 'cdn.chatbase.co' ),
		),
		array(
			'id'         => 'openai-api',
			'name'       => 'OpenAI Integration',
			'category'   => 'content',
			'article'    => 'Article 50(4)',
			'signatures' => array( 'api.openai.com' ),
		),
		array(
			'id'         => 'anthropic-api',
			'name'       => 'Anthropic / Claude Integration',
			'category'   => 'content',
			'article'    => 'Article 50(4)',
			'signatures' => array( 'api.anthropic.com' ),
		),
		array(
			'id'         => 'google-ai',
			'name'       => 'Google AI (Gemini)',
			'category'   => 'content',
			'article'    => 'Article 50(4)',
			'signatures' => array( 'generativelanguage.googleapis.com', 'ai.google.dev' ),
		),
		array(
			'id'         => 'eleven-labs',
			'name'       => 'ElevenLabs',
			'category'   => 'voice',
			'article'    => 'Article 50(3)',
			'signatures' => array( 'api.elevenlabs.io', 'elevenlabs.io/convai-widget' ),
		),
		array(
			'id'         => 'heygen',
			'name'       => 'HeyGen',
			'category'   => 'media',
			'article'    => 'Article 50(2)',
			'signatures' => array( 'app.heygen.com', 'heygen.com/embed' ),
		),
		array(
			'id'         => 'perplexity',
			'name'       => 'Perplexity AI',
			'category'   => 'search',
			'article'    => 'Article 50(4)',
			'signatures' => array( 'perplexity.ai/api', 'labs.perplexity.ai' ),
		),
	);

	/**
	 * Run a fresh fetch, persist the result, and return detected tools.
	 *
	 * @return array Detected tool entries (same shape as registry tools).
	 */
	public function fetch() {
		$urls     = $this->euaiactready_get_urls_to_scan();
		$detected = array();

		foreach ( $urls as $url ) {
			$html = $this->euaiactready_fetch_url( $url );
			if ( false === $html ) {
				continue;
			}
			$found = $this->euaiactready_parse_html( $html );
			foreach ( $found as $tool_id => $tool ) {
				$detected[ $tool_id ] = $tool;
			}
		}

		$result = array(
			'detected'   => array_values( $detected ),
			'fetched_at' => time(),
		);

		update_option( self::OPTION_KEY, $result, false );

		return $result['detected'];
	}

	/**
	 * Return cached results if still valid (within TTL), otherwise null.
	 *
	 * @return array|null Cached detected tool array or null if stale/missing.
	 */
	public function get_cached() {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( empty( $stored['fetched_at'] ) || empty( $stored['detected'] ) ) {
			return null;
		}

		if ( ( time() - (int) $stored['fetched_at'] ) > self::TTL ) {
			return null;
		}

		return $stored['detected'];
	}

	/**
	 * Return detected tools from cache, running a fresh fetch only if stale.
	 *
	 * @return array
	 */
	public function get_detected() {
		$cached = $this->get_cached();
		if ( null !== $cached ) {
			return $cached;
		}
		return $this->fetch();
	}

	/**
	 * Return the URLs to scan - homepage and, if available, the most recent post URL.
	 *
	 * @return array
	 */
	private function euaiactready_get_urls_to_scan() {
		$urls = array( home_url( '/' ) );

		$latest = get_posts( array(
			'numberposts' => 1,
			'post_status' => 'publish',
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		if ( ! empty( $latest ) ) {
			$urls[] = get_permalink( $latest[0] );
		}

		return array_filter( $urls );
	}

	/**
	 * Fetch a URL and return the response body, or false on failure.
	 *
	 * @param string $url URL to fetch.
	 * @return string|false
	 */
	private function euaiactready_fetch_url( $url ) {
		$response = wp_remote_get(
			esc_url_raw( $url ),
			array(
				'timeout'    => 8,
				'user-agent' => 'EU-AI-Act-Ready-Scanner/' . EUAIACTREADY_VERSION,
				'sslverify'  => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Scan an HTML string for known AI tool JS signatures.
	 *
	 * @param string $html Raw HTML.
	 * @return array Tool entries keyed by tool ID.
	 */
	private function euaiactready_parse_html( $html ) {
		$detected = array();

		foreach ( self::$signatures as $tool ) {
			foreach ( $tool['signatures'] as $sig ) {
				if ( false !== strpos( $html, $sig ) ) {
					$detected[ $tool['id'] ] = array(
						'id'       => $tool['id'],
						'name'     => $tool['name'],
						'category' => $tool['category'],
						'article'  => $tool['article'],
						'source'   => 'script',
					);
					break;
				}
			}
		}

		return $detected;
	}
}
