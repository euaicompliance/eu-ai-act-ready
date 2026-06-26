<?php
/**
 * EU AI Act Ready - Shortcode support for inline AI disclosure notices.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the [eu_ai_disclosure] shortcode.
 *
 * Usage examples:
 *   [eu_ai_disclosure]
 *   [eu_ai_disclosure type="image"]
 *   [eu_ai_disclosure type="chatbot"]
 *   [eu_ai_disclosure style="inline"]
 *   [eu_ai_disclosure style="badge" message="This article was written with AI assistance."]
 *   [eu_ai_disclosure type="chatbot" style="banner" message="Our support chat is AI-powered."]
 *
 * Attribute behaviour:
 *   - All attributes are optional and fall back to saved Settings values.
 *   - type    : 'content' (default), 'image', or 'chatbot'. Determines which saved message / style
 *               option to fall back to when the corresponding attribute is omitted.
 *   - style   : Any valid notice style. content/image types accept: banner, inline, badge, modal.
 *               chatbot type additionally accepts: tooltip (rendered as banner when used here).
 *               Omit to use the saved style for the chosen type.
 *   - message : Custom notice text for this instance only. Omit to use the saved message for the
 *               chosen type, or the per-disclosure-level default when no saved message exists.
 *
 * The shortcode always renders regardless of whether the global transparency toggle is on or off,
 * because placing the shortcode is an explicit editorial decision.
 */
class EUAIACTREADY_Shortcode {

	/**
	 * Render-only content transparency instance (no hooks registered).
	 *
	 * @var EUAIACTREADY_Content_Transparency
	 */
	private $renderer;

	/**
	 * Register the shortcode.
	 */
	public function __construct() {
		$this->renderer = new EUAIACTREADY_Content_Transparency( false );
		add_shortcode( 'eu_ai_disclosure', array( $this, 'euaiactready_render' ) );
	}

	/**
	 * Render the [eu_ai_disclosure] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function euaiactready_render( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'    => 'content',
				'style'   => '',
				'message' => '',
			),
			$atts,
			'eu_ai_disclosure'
		);

		$type    = sanitize_key( $atts['type'] );
		$style   = sanitize_key( $atts['style'] );
		$message = sanitize_text_field( $atts['message'] );

		$message = $this->euaiactready_resolve_message( $type, $message );
		$style   = $this->euaiactready_resolve_style( $type, $style );

		return $this->renderer->euaiactready_generate_notice_html( $style, $message );
	}

	/**
	 * Resolve the notice message: attribute --> saved option --> hardcoded default.
	 *
	 * @param string $type    Shortcode type attribute value.
	 * @param string $message Sanitized message attribute (may be empty).
	 * @return string Resolved message.
	 */
	private function euaiactready_resolve_message( $type, $message ) {
		if ( ! empty( $message ) ) {
			return $message;
		}

		if ( 'chatbot' === $type ) {
			$saved = sanitize_text_field( get_option( 'euaiactready_chatbot_notice_message', '' ) );
			return ! empty( $saved ) ? $saved : __( 'This chat uses AI assistance.', 'eu-ai-act-ready' );
		}

		// Both 'content' and 'image' fall back to the general notice message option.
		$saved = sanitize_text_field( get_option( 'euaiactready_notice_message', '' ) );
		if ( ! empty( $saved ) ) {
			return $saved;
		}

		if ( 'image' === $type ) {
			return __( 'This image was generated using AI.', 'eu-ai-act-ready' );
		}

		return __( 'This content includes AI-generated text.', 'eu-ai-act-ready' );
	}

	/**
	 * Resolve the notice style: attribute --> saved option for the matched type --> plugin default.
	 *
	 * @param string $type  Shortcode type attribute value.
	 * @param string $style Sanitized style attribute (may be empty).
	 * @return string Resolved style.
	 */
	private function euaiactready_resolve_style( $type, $style ) {
		if ( ! empty( $style ) ) {
			return $style;
		}

		if ( 'chatbot' === $type ) {
			return sanitize_key( get_option( 'euaiactready_chatbot_notice_style', EUAIACTREADY_DEFAULT_CHATBOT_NOTICE_STYLE ) );
		}

		return sanitize_key( get_option( 'euaiactready_notice_style', EUAIACTREADY_DEFAULT_NOTICE_STYLE ) );
	}
}
