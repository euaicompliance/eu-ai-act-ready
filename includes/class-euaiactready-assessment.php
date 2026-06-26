<?php
/**
 * EU AI Act Ready - Compliance Self-Assessment
 *
 * Stores and evaluates answers to a 6-question self-assessment covering
 * Article 50 and Article 4 obligations under the EU AI Act.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the compliance self-assessment questionnaire.
 */
class Euaiactready_Assessment {

	const OPTION_KEY = 'euaiactready_assessment';

	/**
	 * Return the ordered question definitions.
	 *
	 * Each question has: id, text, hint, yes_articles (articles triggered by Yes),
	 * action (recommended next step when Yes), settings_link (admin URL to act).
	 *
	 * @return array
	 */
	public static function get_questions() {
		return array(
			array(
				'id'           => 'chatbot',
				'text'         => __( 'Does your site use AI chatbots or virtual assistants that interact with visitors?', 'eu-ai-act-ready' ),
				'hint'         => __( 'Examples: Tidio, Intercom, Drift, Tawk.to, custom chat widgets', 'eu-ai-act-ready' ),
				'yes_articles' => array( 'Article 50(1)' ),
				'action'       => __( 'Enable Chatbot Transparency and configure your chatbot platform in Settings.', 'eu-ai-act-ready' ),
				'settings_link' => admin_url( 'admin.php?page=eu-ai-act-ready-settings&tab=chatbot' ),
			),
			array(
				'id'           => 'ai_text',
				'text'         => __( 'Does your site publish content generated or substantially assisted by AI tools?', 'eu-ai-act-ready' ),
				'hint'         => __( 'Examples: blog posts written with ChatGPT, product descriptions from Jasper, AI-assisted copy', 'eu-ai-act-ready' ),
				'yes_articles' => array( 'Article 50(4)' ),
				'action'       => __( 'Enable Content Transparency and mark AI-generated posts with the disclosure meta box.', 'eu-ai-act-ready' ),
				'settings_link' => admin_url( 'admin.php?page=eu-ai-act-ready-settings&tab=transparency' ),
			),
			array(
				'id'           => 'ai_images',
				'text'         => __( 'Does your site use AI-generated images, illustrations, or media?', 'eu-ai-act-ready' ),
				'hint'         => __( 'Examples: images from DALL-E, Midjourney, Stable Diffusion, Adobe Firefly', 'eu-ai-act-ready' ),
				'yes_articles' => array( 'Article 50(4)' ),
				'action'       => __( 'Enable Media Transparency and scan or manually mark AI-generated images.', 'eu-ai-act-ready' ),
				'settings_link' => admin_url( 'admin.php?page=eu-ai-act-ready-settings&tab=media' ),
			),
			array(
				'id'           => 'personalisation',
				'text'         => __( 'Does your site use AI for personalisation, recommendations, or audience targeting?', 'eu-ai-act-ready' ),
				'hint'         => __( 'Examples: product recommendations, content personalisation, behavioural targeting', 'eu-ai-act-ready' ),
				'yes_articles' => array( 'Article 50(1)', 'Article 50(4)' ),
				'action'       => __( 'Declare personalisation AI tools in the AI Systems registry and enable the disclosure notice.', 'eu-ai-act-ready' ),
				'settings_link' => admin_url( 'admin.php?page=eu-ai-act-ready-ai-systems' ),
			),
			array(
				'id'           => 'translation',
				'text'         => __( 'Does your site use AI translation tools to serve content in multiple languages?', 'eu-ai-act-ready' ),
				'hint'         => __( 'Examples: DeepL, Google Translate, Weglot AI, WPML AI', 'eu-ai-act-ready' ),
				'yes_articles' => array( 'Article 50(4)' ),
				'action'       => __( 'Declare translation AI tools in the AI Systems registry and consider adding a notice.', 'eu-ai-act-ready' ),
				'settings_link' => admin_url( 'admin.php?page=eu-ai-act-ready-ai-systems' ),
			),
			array(
				'id'           => 'synthetic_media',
				'text'         => __( 'Does your site generate or host synthetic audio, video, or deepfake-style content?', 'eu-ai-act-ready' ),
				'hint'         => __( 'Examples: AI voiceovers, AI-generated video, cloned voices, digital avatars', 'eu-ai-act-ready' ),
				'yes_articles' => array( 'Article 50(2)', 'Article 50(3)' ),
				'action'       => __( 'Label synthetic audio/video content clearly. Add an explicit disclosure to affected pages.', 'eu-ai-act-ready' ),
				'settings_link' => admin_url( 'admin.php?page=eu-ai-act-ready-content' ),
			),
		);
	}

	/**
	 * Return the stored answers, or an empty array if none saved.
	 *
	 * @return array Associative: question_id => 'yes'|'no'|'sometimes'
	 */
	public static function get_answers() {
		$answers = get_option( self::OPTION_KEY, array() );
		return is_array( $answers ) ? $answers : array();
	}

	/**
	 * Persist answers from a submission.
	 *
	 * @param array $raw_answers Unsanitised POST values keyed by question ID.
	 * @return void
	 */
	public static function save( array $raw_answers ) {
		$allowed   = array( 'yes', 'no', 'sometimes' );
		$sanitised = array();

		foreach ( self::get_questions() as $q ) {
			$val = isset( $raw_answers[ $q['id'] ] ) ? sanitize_key( $raw_answers[ $q['id'] ] ) : '';
			$sanitised[ $q['id'] ] = in_array( $val, $allowed, true ) ? $val : '';
		}

		update_option( self::OPTION_KEY, $sanitised, false );

		/**
		 * Fired after the compliance assessment is saved.
		 *
		 * @param array $sanitised Sanitised answers.
		 */
		do_action( 'euaiactready_assessment_saved', $sanitised );
	}

	/**
	 * Return true if all questions have been answered (any non-empty value).
	 *
	 * @return bool
	 */
	public static function is_complete() {
		$answers   = self::get_answers();
		$questions = self::get_questions();

		if ( count( $answers ) < count( $questions ) ) {
			return false;
		}

		foreach ( $questions as $q ) {
			if ( empty( $answers[ $q['id'] ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Return questions where the stored answer triggers an Article obligation (Yes or Sometimes).
	 *
	 * @return array Subset of question definitions.
	 */
	public static function get_applicable_questions() {
		$answers    = self::get_answers();
		$applicable = array();

		foreach ( self::get_questions() as $q ) {
			$answer = $answers[ $q['id'] ] ?? '';
			if ( in_array( $answer, array( 'yes', 'sometimes' ), true ) ) {
				$applicable[] = $q;
			}
		}

		return $applicable;
	}
}
