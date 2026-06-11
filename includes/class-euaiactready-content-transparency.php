<?php
/**
 * EU AI Act Ready - Adds front-end transparency notices for AI-marked content.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates transparency badges, notices, and assets.
 */
class EUAIACTREADY_Content_Transparency {

	/**
	 * Initialize transparency notices.
	 */
	public function __construct() {
		$this->euaiactready_init_hooks();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function euaiactready_init_hooks() {
		add_filter( 'the_content', array( $this, 'euaiactready_add_content_notice' ), 999 );
		add_filter( 'the_title', array( $this, 'euaiactready_append_ai_badge_to_title' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'euaiactready_show_admin_notices' ) );
	}

	/**
	 * Add transparency notice to single content items (posts, pages, CPTs).
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function euaiactready_add_content_notice( $content ) {
		// Only show on single content items (is_singular covers all enabled post types).
		if ( ! is_singular() ) {
			return $content;
		}

		global $post;
		if ( ! $post ) {
			return $content;
		}

		$ai_content = get_post_meta( $post->ID, '_euaiactready_ai_content', true );
		$disclosure = EUAIACTREADY_Post_Meta_Box::euaiactready_normalize_disclosure_value( $ai_content );

		if ( 'none' === $disclosure ) {
			return $content;
		}

		$notice_style   = get_option( 'euaiactready_notice_style', EUAIACTREADY_DEFAULT_NOTICE_STYLE );
		$custom_message = sanitize_text_field( get_option( 'euaiactready_notice_message', '' ) );

		$notice_html = $this->euaiactready_generate_notice_html( $notice_style, $custom_message, $disclosure );

		// Return notice HTML (already properly escaped) prepended to content.
		return $notice_html . $content;
	}

	/**
	 * Appends the EU AI Act Ready badge to post titles in loops (archives, feeds, searches).
	 *
	 * @param string $title Post title.
	 * @param int    $id    Post ID.
	 * @return string Modified title.
	 */
	public function euaiactready_append_ai_badge_to_title( $title, $id = null ) {
		// Only show in loops (archives, search, etc.).
		if ( is_admin() || ! in_the_loop() || is_singular() ) {
			return $title;
		}

		// Check if show in excerpts/archive is enabled.
		$show_in_excerpts = get_option( 'euaiactready_show_in_excerpts', true );
		if ( ! $show_in_excerpts ) {
			return $title;
		}

		if ( ! $id ) {
			$id = get_the_ID();
		}

		$ai_content = get_post_meta( $id, '_euaiactready_ai_content', true );
		$disclosure = EUAIACTREADY_Post_Meta_Box::euaiactready_normalize_disclosure_value( $ai_content );

		if ( 'none' !== $disclosure ) {
			$icon  = wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 14, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() );
			$badge = sprintf(
				' <span class="eu-ai-act-ready-badge eu-ai-act-ready-badge-title" title="%1$s">%2$s %3$s</span>',
				esc_attr( $this->euaiactready_get_disclosure_badge_title( $disclosure ) ),
				$icon,
				esc_html__( 'AI', 'eu-ai-act-ready' )
			);
			return $title . $badge;
		}

		return $title;
	}

	/**
	 * Return the badge tooltip title for each disclosure level.
	 *
	 * @param string $disclosure Disclosure level.
	 * @return string Translated tooltip text.
	 */
	private function euaiactready_get_disclosure_badge_title( $disclosure ) {
		$titles = array(
			'assisted'           => __( 'This content was created with AI assistance.', 'eu-ai-act-ready' ),
			'generated'          => __( 'This content includes AI-generated text.', 'eu-ai-act-ready' ),
			'generated_reviewed' => __( 'This content was AI-generated and reviewed by a human editor.', 'eu-ai-act-ready' ),
		);
		return isset( $titles[ $disclosure ] ) ? $titles[ $disclosure ] : $titles['generated'];
	}

	/**
	 * Generate notice HTML based on style.
	 *
	 * @param string $style           Notice style.
	 * @param string $custom_message  Custom message (overrides per-type defaults when non-empty).
	 * @param string $disclosure_type Disclosure level: 'assisted', 'generated', or 'generated_reviewed'.
	 * @return string Notice HTML.
	 */
	private function euaiactready_generate_notice_html( $style, $custom_message = '', $disclosure_type = 'generated' ) {
		$default_messages = array(
			'assisted'           => __( 'This content was created with AI assistance.', 'eu-ai-act-ready' ),
			'generated'          => __( 'This content includes AI-generated text.', 'eu-ai-act-ready' ),
			'generated_reviewed' => __( 'This content was AI-generated and reviewed by a human editor.', 'eu-ai-act-ready' ),
		);

		$default_message = isset( $default_messages[ $disclosure_type ] )
			? $default_messages[ $disclosure_type ]
			: $default_messages['generated'];

		$message = ! empty( $custom_message ) ? $custom_message : $default_message;

		switch ( $style ) {
			case 'banner':
				return $this->euaiactready_get_banner_html( $message );
			case 'inline':
				return $this->euaiactready_get_inline_html( $message );
			case 'badge':
				return $this->euaiactready_get_badge_html( $message );
			case 'modal':
				return $this->euaiactready_get_modal_trigger_html( $message );
			default:
				return $this->euaiactready_get_banner_html( $message );
		}
	}

	/**
	 * Banner style notice.
	 *
	 * @param string $message Notice message.
	 * @return string Banner HTML.
	 */
	private function euaiactready_get_banner_html( $message ) {
		return sprintf(
			'<div class="eu-ai-act-ready-notice ai-notice-banner" role="alert" aria-label="%1$s">
                <div class="ai-notice-icon">%2$s</div>
                <div class="ai-notice-content">
                    <strong>%3$s</strong> %4$s
                </div>
                <button class="ai-notice-close" aria-label="%5$s">&times;</button>
            </div>',
			esc_attr__( 'AI Content Notice', 'eu-ai-act-ready' ),
			wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 24, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ),
			esc_html__( 'AI Disclosure:', 'eu-ai-act-ready' ),
			esc_html( $message ),
			esc_attr__( 'Close notice', 'eu-ai-act-ready' )
		);
	}

	/**
	 * Inline style notice.
	 *
	 * @param string $message Notice message.
	 * @return string Inline notice HTML.
	 */
	private function euaiactready_get_inline_html( $message ) {
		return sprintf(
			'<p class="eu-ai-act-ready-notice ai-notice-inline">
                <span class="ai-icon">%1$s</span>
				<strong>%2$s</strong> <em>%3$s</em>
            </p>',
			wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 16, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ),
			esc_html__( 'AI Disclosure:', 'eu-ai-act-ready' ),
			esc_html( $message )
		);
	}

	/**
	 * Badge style notice.
	 *
	 * @param string $message Notice message.
	 * @return string Badge notice HTML.
	 */
	private function euaiactready_get_badge_html( $message ) {
		return sprintf(
			'<div class="eu-ai-act-ready-badge-wrapper">
                <span class="eu-ai-act-ready-badge" title="%1$s">
                    %2$s %3$s
                </span>
            </div>',
			esc_attr( $message ),
			wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 14, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ),
			esc_html__( 'AI Disclosure', 'eu-ai-act-ready' )
		);
	}

	/**
	 * Modal trigger.
	 *
	 * @param string $message Notice message.
	 * @return string Modal trigger HTML.
	 */
	private function euaiactready_get_modal_trigger_html( $message ) {
		static $modal_added = false;

		$html = '<button type="button" class="eu-ai-act-ready-modal-trigger" data-message="' . esc_attr( $message ) . '">
            ' . wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 16, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ) . ' ' . esc_html__( 'AI Disclosure', 'eu-ai-act-ready' ) . '
        </button>';

		// Add modal HTML only once.
		if ( ! $modal_added ) {
			$html       .= $this->euaiactready_get_modal_html();
			$modal_added = true;
		}

		return $html;
	}

	/**
	 * Modal HTML structure.
	 */
	private function euaiactready_get_modal_html() {
		return '
        <div id="eu-ai-act-ready-modal" class="eu-ai-act-ready-modal" style="display:none;">
            <div class="ai-modal-content">
                <span class="ai-modal-close">&times;</span>
                <h3>' . wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 20, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ) . ' ' . esc_html__( 'AI Disclosure', 'eu-ai-act-ready' ) . '</h3>
                <div class="ai-modal-body">
                    <p id="ai-modal-message"></p>
                </div>
            </div>
        </div>';
	}

	/**
	 * Show admin notices about AI usage.
	 */
	public function euaiactready_show_admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && 'post' === $screen->base ) {
			global $post;
			if ( $post ) {
				$ai_content = get_post_meta( $post->ID, '_euaiactready_ai_content', true );
				$disclosure = EUAIACTREADY_Post_Meta_Box::euaiactready_normalize_disclosure_value( $ai_content );

				if ( 'none' !== $disclosure ) {
					$levels = EUAIACTREADY_Post_Meta_Box::euaiactready_get_disclosure_levels();
					$label  = isset( $levels[ $disclosure ] ) ? $levels[ $disclosure ] : $disclosure;

					printf(
						'<div class="notice notice-info"><p><strong>%1$s %2$s:</strong> %3$s %4$s</p></div>',
						wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 16, '#0073aa' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ),
						esc_html__( 'AI Content Marked', 'eu-ai-act-ready' ),
						esc_html( $label ),
						esc_html__( '- a transparency notice will be displayed to visitors.', 'eu-ai-act-ready' )
					);
				}
			}
		}
	}
}
