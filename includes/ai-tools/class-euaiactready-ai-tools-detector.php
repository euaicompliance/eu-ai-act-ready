<?php
/**
 * EU AI Act Ready - AI Tools Detector
 *
 * Compares the registry against installed WordPress plugins to find AI tools.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects installed AI plugins by matching registry slugs against active_plugins.
 */
class Euaiactready_AI_Tools_Detector {

	const OPTION_DETECTED    = 'euaiactready_ai_tools_detected';
	const OPTION_DETECTED_AT = 'euaiactready_ai_tools_detected_at';

	/**
	 * @var Euaiactready_AI_Tools_Registry
	 */
	private $registry;

	/**
	 * @param Euaiactready_AI_Tools_Registry $registry
	 */
	public function __construct( Euaiactready_AI_Tools_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Run detection and persist results.
	 *
	 * @return array Detected tool entries.
	 */
	public function scan() {
		$active_slugs = $this->euaiactready_get_active_plugin_slugs();
		$tools        = $this->registry->get_all();
		$detected     = array();

		foreach ( $tools as $tool ) {
			if ( empty( $tool['wp_slugs'] ) || ! is_array( $tool['wp_slugs'] ) ) {
				continue;
			}
			foreach ( $tool['wp_slugs'] as $slug ) {
				if ( in_array( $slug, $active_slugs, true ) ) {
					$detected[ $tool['id'] ] = $tool;
					break;
				}
			}
		}

		// Merge in any manual declarations that are not already detected.
		$manual = $this->get_manual_tools();
		foreach ( $manual as $manual_tool ) {
			if ( ! isset( $detected[ $manual_tool['id'] ] ) ) {
				$detected[ $manual_tool['id'] ] = $manual_tool;
			}
		}

		update_option( self::OPTION_DETECTED, array_values( $detected ), false );
		update_option( self::OPTION_DETECTED_AT, time(), false );

		/**
		 * Fires after detection scan completes.
		 *
		 * @param array $detected Detected tools array.
		 */
		do_action( 'euaiactready_ai_tools_detected', $detected );

		return array_values( $detected );
	}

	/**
	 * Return the last cached detection result.
	 *
	 * @return array
	 */
	public function get_detected() {
		$detected = get_option( self::OPTION_DETECTED, array() );
		return is_array( $detected ) ? $detected : array();
	}

	/**
	 * Return the timestamp of the last scan, or null.
	 *
	 * @return int|null
	 */
	public function get_detected_at() {
		$ts = get_option( self::OPTION_DETECTED_AT, null );
		return $ts ? (int) $ts : null;
	}

	/**
	 * Return active plugins that are NOT matched by any registry entry.
	 *
	 * @return array Array of [ 'slug' => string, 'name' => string ] maps.
	 */
	public function get_unknown_plugins() {
		$all_plugins    = get_plugins();
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$registry_slugs = $this->euaiactready_get_all_registry_slugs();
		$manual_slugs   = array_column( $this->get_manual_tools(), 'slug' );
		$excluded       = array_unique( array_merge( $registry_slugs, $manual_slugs ) );

		$unknown = array();
		foreach ( $active_plugins as $plugin_file ) {
			$slug = $this->euaiactready_slug_from_file( $plugin_file );

			// Skip our own plugin.
			if ( 'eu-ai-act-ready' === $slug ) {
				continue;
			}

			if ( in_array( $slug, $excluded, true ) ) {
				continue;
			}

			$info      = $all_plugins[ $plugin_file ] ?? array();
			$unknown[] = array(
				'slug' => $slug,
				'file' => $plugin_file,
				'name' => $info['Name'] ?? $slug,
			);
		}

		return $unknown;
	}

	/**
	 * Return manually declared AI tools stored in options.
	 *
	 * @return array
	 */
	public function get_manual_tools() {
		$manual = get_option( 'euaiactready_ai_tools_manual', array() );
		return is_array( $manual ) ? $manual : array();
	}

	/**
	 * Save a manual tool declaration.
	 *
	 * @param string $slug     Plugin folder slug.
	 * @param string $name     Display name.
	 * @param string $category Tool category.
	 * @return void
	 */
	public function add_manual_tool( $slug, $name, $category = 'other' ) {
		$manual = $this->get_manual_tools();
		$id     = 'manual_' . sanitize_key( $slug );

		// Avoid duplicates.
		foreach ( $manual as $existing ) {
			if ( $existing['id'] === $id ) {
				return;
			}
		}

		$manual[] = array(
			'id'                => $id,
			'name'              => sanitize_text_field( $name ),
			'category'          => sanitize_key( $category ),
			'description'       => '',
			'eu_ai_act_article' => 'Article 50(2)',
			'risk_level'        => 'limited',
			'url'               => '',
			'wp_slugs'          => array( sanitize_key( $slug ) ),
			'slug'              => sanitize_key( $slug ),
			'is_manual'         => true,
		);

		update_option( 'euaiactready_ai_tools_manual', $manual, false );
	}

	/**
	 * Remove a manual tool declaration by its id.
	 *
	 * @param string $id Tool id (e.g. 'manual_some-plugin').
	 * @return void
	 */
	public function remove_manual_tool( $id ) {
		$manual  = $this->get_manual_tools();
		$updated = array_filter(
			$manual,
			static function ( $t ) use ( $id ) {
				return $t['id'] !== $id;
			}
		);
		update_option( 'euaiactready_ai_tools_manual', array_values( $updated ), false );
	}

	/**
	 * Score installed plugins against AI keywords and return those likely to be AI tools.
	 *
	 * Plugins already in the registry or declared manually are excluded.
	 * Only active plugins are evaluated.
	 *
	 * @return array Each element: [ 'slug', 'file', 'name', 'score' ]
	 */
	public function get_possibly_ai_plugins() {
		$all_plugins    = get_plugins();
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$registry_slugs = $this->euaiactready_get_all_registry_slugs();
		$manual_slugs   = array_column( $this->get_manual_tools(), 'slug' );
		$excluded       = array_unique( array_merge( $registry_slugs, $manual_slugs ) );

		// High-value AI keywords (weight 2 each).
		$high = array(
			'artificial intelligence', 'machine learning', 'neural network', 'deep learning',
			'large language model', 'llm', 'gpt', 'chatgpt', 'openai', 'anthropic', 'gemini',
			'claude ai', 'copilot', 'generative ai', 'ai-generated', 'ai generated',
			'stable diffusion', 'midjourney', 'dall-e', 'dalle', 'text-to-image',
			'voice synthesis', 'speech synthesis', 'ai writing', 'ai writer', 'ai assistant',
		);

		// Medium keywords (weight 1 each).
		$medium = array(
			' ai ', ' ai-', '-ai ', 'chatbot', 'natural language', 'text generation',
			'image generation', 'ai content', 'ai image', 'ai tool', 'ai detection',
			'smart suggest', 'auto-tag', 'autotag', 'auto tag', 'ai caption',
		);

		$possibly_ai = array();

		foreach ( $active_plugins as $plugin_file ) {
			$slug = $this->euaiactready_slug_from_file( $plugin_file );

			if ( 'eu-ai-act-ready' === $slug ) {
				continue;
			}
			if ( in_array( $slug, $excluded, true ) ) {
				continue;
			}

			$info        = $all_plugins[ $plugin_file ] ?? array();
			$name        = $info['Name'] ?? $slug;
			$description = $info['Description'] ?? '';
			$haystack    = strtolower( $name . ' ' . $description );
			$score       = 0;

			foreach ( $high as $keyword ) {
				if ( false !== strpos( $haystack, $keyword ) ) {
					$score += 2;
				}
			}
			foreach ( $medium as $keyword ) {
				if ( false !== strpos( $haystack, $keyword ) ) {
					++$score;
				}
			}

			if ( $score >= 1 ) {
				$possibly_ai[] = array(
					'slug'  => $slug,
					'file'  => $plugin_file,
					'name'  => $name,
					'score' => $score,
				);
			}
		}

		// Sort highest score first.
		usort( $possibly_ai, static function ( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		return $possibly_ai;
	}

	/**
	 * Build an array of folder slugs from all active plugin files.
	 *
	 * @return string[]
	 */
	private function euaiactready_get_active_plugin_slugs() {
		$active = (array) get_option( 'active_plugins', array() );
		return array_map( array( $this, 'euaiactready_slug_from_file' ), $active );
	}

	/**
	 * Extract the folder slug from a plugin file path like "tidio-live-chat/tidio-live-chat.php".
	 *
	 * @param string $plugin_file Plugin file path.
	 * @return string
	 */
	private function euaiactready_slug_from_file( $plugin_file ) {
		$parts = explode( '/', $plugin_file );
		return count( $parts ) > 1 ? $parts[0] : str_replace( '.php', '', $parts[0] );
	}

	/**
	 * Collect all wp_slugs from the full registry for unknown-plugin filtering.
	 *
	 * @return string[]
	 */
	private function euaiactready_get_all_registry_slugs() {
		$slugs = array();
		foreach ( $this->registry->get_all() as $tool ) {
			if ( ! empty( $tool['wp_slugs'] ) && is_array( $tool['wp_slugs'] ) ) {
				foreach ( $tool['wp_slugs'] as $slug ) {
					$slugs[] = $slug;
				}
			}
		}
		return $slugs;
	}
}
