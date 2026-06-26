<?php
/**
 * EU AI Act Ready - Readiness Score Calculator.
 *
 * Calculates a 0-100 compliance readiness score based on which Article 50
 * transparency obligations the site owner has addressed.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculates and stores the plugin readiness score.
 */
class Euaiactready_Readiness {

	const OPTION_SCORE = 'euaiactready_readiness_score';
	const OPTION_ITEMS = 'euaiactready_readiness_items';

	/**
	 * Weight per factor kept for backward compatibility.
	 * Score is now normalized: round( met_count / total_factors * 100 ).
	 */
	const FACTOR_WEIGHT = 20;

	/**
	 * Calculate and persist the readiness score, then return it.
	 *
	 * @return int Score 0-100.
	 */
	public function calculate() {
		$factors = $this->euaiactready_evaluate_factors();
		$met     = array();
		$unmet   = array();

		foreach ( $factors as $factor ) {
			if ( $factor['met'] ) {
				$met[] = $factor;
			} else {
				$unmet[] = $factor;
			}
		}

		$total_factors = count( $factors );
		$score         = $total_factors > 0 ? (int) round( count( $met ) / $total_factors * 100 ) : 0;

		update_option( self::OPTION_SCORE, $score, false );
		update_option(
			self::OPTION_ITEMS,
			array(
				'met'   => $met,
				'unmet' => $unmet,
			),
			false
		);

		return $score;
	}

	/**
	 * Return the cached readiness score, or calculate it if not yet stored.
	 *
	 * @return int Score 0-100.
	 */
	public function get_score() {
		$cached = get_option( self::OPTION_SCORE );
		if ( false === $cached ) {
			return $this->calculate();
		}
		return (int) $cached;
	}

	/**
	 * Return cached met/unmet factor lists. Calculates if not stored.
	 *
	 * @return array{met: array, unmet: array}
	 */
	public function get_items() {
		$cached = get_option( self::OPTION_ITEMS );
		if ( ! is_array( $cached ) ) {
			$this->calculate();
			$cached = get_option( self::OPTION_ITEMS, array( 'met' => array(), 'unmet' => array() ) );
		}
		return $cached;
	}

	/**
	 * Convert a score to a traffic-light status.
	 *
	 * @param int $score Score 0-100.
	 * @return string 'green', 'amber', or 'red'.
	 */
	public static function get_traffic_light( $score ) {
		if ( $score >= 80 ) {
			return 'green';
		}
		if ( $score >= 50 ) {
			return 'amber';
		}
		return 'red';
	}

	/**
	 * Return the label for a traffic-light status.
	 *
	 * @param string $status 'green', 'amber', or 'red'.
	 * @return string Translated label.
	 */
	public static function get_traffic_light_label( $status ) {
		$labels = array(
			'green' => __( 'Good', 'eu-ai-act-ready' ),
			'amber' => __( 'Needs attention', 'eu-ai-act-ready' ),
			'red'   => __( 'Action required', 'eu-ai-act-ready' ),
		);
		return $labels[ $status ] ?? $labels['red'];
	}

	/**
	 * Evaluate each of the five readiness factors.
	 *
	 * @return array Each element: { label, met, help_url, setting_label }.
	 */
	private function euaiactready_evaluate_factors() {
		$settings = get_option( 'euaiactready_settings', array() );

		return array(
			$this->euaiactready_factor_ai_tools(),
			$this->euaiactready_factor_content_transparency( $settings ),
			$this->euaiactready_factor_media_transparency( $settings ),
			$this->euaiactready_factor_chatbot_transparency(),
			$this->euaiactready_factor_registry_working(),
			$this->euaiactready_factor_article4_obligations(),
		);
	}

	/**
	 * Factor 1: AI Systems notice enabled with at least one disclosed tool.
	 *
	 * @return array
	 */
	private function euaiactready_factor_ai_tools() {
		$enabled     = (bool) get_option( Euaiactready_AI_Tools::OPTION_ENABLED, true );
		$visibility  = get_option( Euaiactready_AI_Tools::OPTION_VISIBILITY, array() );
		$has_visible = ! empty( array_filter( $visibility ) );
		$met         = $enabled && $has_visible;

		if ( ! $enabled && $has_visible ) {
			$label = __( 'You have AI systems registered but the disclosure notice is turned off - your visitors cannot see them', 'eu-ai-act-ready' );
		} elseif ( ! $enabled ) {
			$label = __( 'AI Systems disclosure notice is disabled - enable it and mark at least one tool to show to visitors', 'eu-ai-act-ready' );
		} elseif ( ! $has_visible ) {
			$label = __( 'AI Systems notice is enabled but no tools are set to show to visitors', 'eu-ai-act-ready' );
		} else {
			$label = __( 'AI Systems disclosure notice is enabled and at least one tool is disclosed to visitors', 'eu-ai-act-ready' );
		}

		return array(
			'id'    => 'ai_tools_notice',
			'label' => $label,
			'met'   => $met,
			'link'  => admin_url( 'admin.php?page=eu-ai-act-ready-ai-systems' ),
			'cta'   => __( 'Go to AI Systems', 'eu-ai-act-ready' ),
		);
	}

	/**
	 * Factor 2: Content transparency enabled with at least one marked post.
	 *
	 * @param array $settings Plugin settings array.
	 * @return array
	 */
	private function euaiactready_factor_content_transparency( $settings ) {
		$enabled     = (bool) get_option( 'euaiactready_transparency_enabled', true );
		$marked_post = false;

		if ( $enabled ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => '_euaiactready_ai_content',
							'value'   => array( '1', 'assisted', 'generated', 'generated_reviewed' ),
							'compare' => 'IN',
						),
					),
				)
			);
			$marked_post = $query->found_posts > 0;
		}

		return array(
			'id'    => 'content_transparency',
			'label' => __( 'Content transparency is enabled and at least one post is marked as AI-generated', 'eu-ai-act-ready' ),
			'met'   => $enabled && $marked_post,
			'link'  => admin_url( 'admin.php?page=eu-ai-act-ready-settings' ),
			'cta'   => __( 'Go to Settings', 'eu-ai-act-ready' ),
		);
	}

	/**
	 * Factor 3: Media (image) transparency is enabled.
	 *
	 * @param array $settings Plugin settings array.
	 * @return array
	 */
	private function euaiactready_factor_media_transparency( $settings ) {
		$enabled = get_option( 'euaiactready_media_transparency', true );

		return array(
			'id'    => 'media_transparency',
			'label' => __( 'Media transparency is enabled (AI-generated images are flagged)', 'eu-ai-act-ready' ),
			'met'   => (bool) $enabled,
			'link'  => admin_url( 'admin.php?page=eu-ai-act-ready-settings' ),
			'cta'   => __( 'Go to Settings', 'eu-ai-act-ready' ),
		);
	}

	/**
	 * Factor 4: Chatbot transparency is enabled with a platform configured.
	 *
	 * @return array
	 */
	private function euaiactready_factor_chatbot_transparency() {
		$enabled  = get_option( 'euaiactready_chatbot_transparency', true );
		$platform = get_option( 'euaiactready_chatbot_platform', '' );
		$met      = $enabled && ! empty( $platform );

		return array(
			'id'    => 'chatbot_transparency',
			'label' => __( 'Chatbot transparency is enabled and a chatbot platform is configured', 'eu-ai-act-ready' ),
			'met'   => $met,
			'link'  => admin_url( 'admin.php?page=eu-ai-act-ready-settings#chatbot-transparency' ),
			'cta'   => __( 'Go to Settings', 'eu-ai-act-ready' ),
		);
	}

	/**
	 * Factor 5: At least one AI tool has been detected or declared on this site.
	 *
	 * @return array
	 */
	private function euaiactready_factor_registry_working() {
		$detected = get_option( Euaiactready_AI_Tools_Detector::OPTION_DETECTED, array() );
		$met      = ! empty( $detected );

		return array(
			'id'    => 'registry_working',
			'label' => __( 'At least one AI tool has been detected or declared on this site', 'eu-ai-act-ready' ),
			'met'   => $met,
			'link'  => admin_url( 'admin.php?page=eu-ai-act-ready-ai-systems' ),
			'cta'   => __( 'Go to AI Systems', 'eu-ai-act-ready' ),
		);
	}

	/**
	 * Factor 6: Article 4 obligations addressed (assessment complete + literacy complete).
	 *
	 * @return array
	 */
	private function euaiactready_factor_article4_obligations() {
		$assessment_done = Euaiactready_Assessment::is_complete();
		$literacy_done   = Euaiactready_AI_Literacy::is_complete();
		$met             = $assessment_done && $literacy_done;

		if ( ! $assessment_done && ! $literacy_done ) {
			$label = __( 'Article 4 obligations not yet started - complete the assessment and AI literacy checklist', 'eu-ai-act-ready' );
		} elseif ( ! $assessment_done ) {
			$label = __( 'Compliance assessment not yet completed - complete all questions in the Assessment wizard', 'eu-ai-act-ready' );
		} elseif ( ! $literacy_done ) {
			$label = __( 'AI literacy checklist not yet complete - review the Article 4 obligations checklist', 'eu-ai-act-ready' );
		} else {
			$label = __( 'Article 4 obligations addressed - assessment complete and AI literacy checklist done', 'eu-ai-act-ready' );
		}

		$link = ! $assessment_done
			? admin_url( 'admin.php?page=eu-ai-act-ready-assessment' )
			: admin_url( 'admin.php?page=eu-ai-act-ready-literacy' );

		return array(
			'id'    => 'article4_obligations',
			'label' => $label,
			'met'   => $met,
			'link'  => $link,
			'cta'   => ! $assessment_done ? __( 'Go to Assessment', 'eu-ai-act-ready' ) : __( 'Go to AI Literacy', 'eu-ai-act-ready' ),
		);
	}
}
