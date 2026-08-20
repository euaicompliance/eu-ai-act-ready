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
	 *
	 * @param bool $register_hooks Pass false to create a render-only instance (used by the shortcode).
	 */
	public function __construct( $register_hooks = true ) {
		if ( $register_hooks ) {
			$this->euaiactready_init_hooks();
		}
	}

	/**
	 * Register WordPress hooks.
	 */
	private function euaiactready_init_hooks() {
		add_filter( 'the_content', array( $this, 'euaiactready_add_content_notice' ), 999 );
		add_filter( 'the_title', array( $this, 'euaiactready_append_ai_badge_to_title' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'euaiactready_show_admin_notices' ) );
		add_action( 'wp_head', array( $this, 'euaiactready_add_schema_org_markup' ) );

		if ( get_option( 'euaiactready_rss_disclosure_enabled', 0 ) ) {
			add_filter( 'the_content_feed', array( $this, 'euaiactready_add_rss_disclosure' ) );
			add_filter( 'the_excerpt_rss', array( $this, 'euaiactready_add_rss_disclosure' ) );
			add_action( 'rss2_item', array( $this, 'euaiactready_rss_add_dc_description' ) );
		}

		if ( get_option( 'euaiactready_rss_title_prefix', 0 ) ) {
			add_filter( 'the_title_rss', array( $this, 'euaiactready_prefix_rss_title' ) );
		}
	}

	/**
	 * Add transparency notice to single content items (posts, pages, CPTs).
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function euaiactready_add_content_notice( $content ) {
		if ( ! get_option( 'euaiactready_transparency_enabled', true ) ) {
			return $content;
		}

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

		$position = $this->euaiactready_get_notice_position( $disclosure, $post );

		$leading  = '';
		$trailing = '';

		if ( 'after' !== $position ) {
			$leading = $this->euaiactready_render_placed_notice( $notice_style, $custom_message, $disclosure, $post, 'before' );
		}

		if ( 'before' !== $position ) {
			$trailing = $this->euaiactready_render_placed_notice( $notice_style, $custom_message, $disclosure, $post, 'after' );
		}

		// Notice markup is already properly escaped.
		return $leading . $content . $trailing;
	}

	/**
	 * Render a single notice for one placement around the content.
	 *
	 * Called twice when the position is 'both'. The modal style keeps its dialog
	 * markup single-output internally, so only the trigger is repeated.
	 *
	 * @param string  $notice_style   Resolved notice style.
	 * @param string  $custom_message Custom message from settings (may be empty).
	 * @param string  $disclosure     Disclosure level.
	 * @param WP_Post $post           Post the notice belongs to.
	 * @param string  $placement      Either 'before' or 'after'.
	 * @return string Notice markup (already escaped), or an empty string.
	 */
	private function euaiactready_render_placed_notice( $notice_style, $custom_message, $disclosure, $post, $placement ) {
		$notice_html = $this->euaiactready_generate_notice_html( $notice_style, $custom_message, $disclosure );

		/**
		 * Filter the transparency notice markup before it is placed around the content.
		 *
		 * Allows themes to replace the notice markup entirely, for example to match a
		 * theme's own design system, without having to override the plugin stylesheet.
		 * Return an empty string to suppress the notice for this placement.
		 *
		 * @param string  $notice_html  Notice markup (already escaped).
		 * @param string  $notice_style Resolved notice style.
		 * @param string  $disclosure   Disclosure level: 'assisted', 'generated', or 'generated_reviewed'.
		 * @param WP_Post $post         Post the notice belongs to.
		 * @param string  $placement    Placement being rendered: 'before' or 'after'.
		 */
		return (string) apply_filters( 'euaiactready_notice_html', $notice_html, $notice_style, $disclosure, $post, $placement );
	}

	/**
	 * Resolve where the transparency notice is placed relative to the content.
	 *
	 * @param string  $disclosure Disclosure level.
	 * @param WP_Post $post       Post the notice belongs to.
	 * @return string One of 'before', 'after', or 'both'.
	 */
	private function euaiactready_get_notice_position( $disclosure, $post ) {
		$position = sanitize_key( get_option( 'euaiactready_notice_position', EUAIACTREADY_DEFAULT_NOTICE_POSITION ) );

		/**
		 * Filter the transparency notice position.
		 *
		 * @param string  $position   One of 'before', 'after', or 'both'.
		 * @param string  $disclosure Disclosure level: 'assisted', 'generated', or 'generated_reviewed'.
		 * @param WP_Post $post       Post the notice belongs to.
		 */
		$position = apply_filters( 'euaiactready_notice_position', $position, $disclosure, $post );

		if ( ! in_array( $position, self::euaiactready_get_notice_positions_keys(), true ) ) {
			$position = EUAIACTREADY_DEFAULT_NOTICE_POSITION;
		}

		return $position;
	}

	/**
	 * Available notice positions with their translated labels.
	 *
	 * @return array<string,string> Position key => label.
	 */
	public static function euaiactready_get_notice_positions() {
		return array(
			'before' => __( 'Above the content', 'eu-ai-act-ready' ),
			'after'  => __( 'Below the content', 'eu-ai-act-ready' ),
			'both'   => __( 'Above and below the content', 'eu-ai-act-ready' ),
		);
	}

	/**
	 * Valid notice position keys.
	 *
	 * @return string[]
	 */
	public static function euaiactready_get_notice_positions_keys() {
		return array_keys( self::euaiactready_get_notice_positions() );
	}

	/**
	 * Appends the EU AI Act Ready badge to post titles in loops (archives, feeds, searches).
	 *
	 * @param string $title Post title.
	 * @param int    $id    Post ID.
	 * @return string Modified title.
	 */
	public function euaiactready_append_ai_badge_to_title( $title, $id = null ) {
		if ( ! get_option( 'euaiactready_transparency_enabled', true ) ) {
			return $title;
		}

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
	 * Return the default disclosure message for a given disclosure level.
	 *
	 * Used by the badge tooltip, RSS disclosure, and shortcode fallback.
	 *
	 * @param string $disclosure Disclosure level.
	 * @return string Translated message text.
	 */
	public function euaiactready_get_disclosure_badge_title( $disclosure ) {
		$titles = array(
			'assisted'           => __( 'This content was created with AI assistance.', 'eu-ai-act-ready' ),
			'generated'          => __( 'This content includes AI-generated text.', 'eu-ai-act-ready' ),
			'generated_reviewed' => __( 'This content was AI-generated and reviewed by a human editor.', 'eu-ai-act-ready' ),
		);
		return isset( $titles[ $disclosure ] ) ? $titles[ $disclosure ] : $titles['generated'];
	}

	/**
	 * Append a plain-text AI disclosure to RSS feed content/excerpts for AI-marked posts.
	 * Includes detected AI tool names when available.
	 *
	 * Fires on 'the_content_feed' and 'the_excerpt_rss'.
	 *
	 * @param string $content Feed content or excerpt.
	 * @return string Modified content.
	 */
	public function euaiactready_add_rss_disclosure( $content ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		$raw        = get_post_meta( $post_id, '_euaiactready_ai_content', true );
		$disclosure = EUAIACTREADY_Post_Meta_Box::euaiactready_normalize_disclosure_value( $raw );

		if ( 'none' === $disclosure ) {
			return $content;
		}

		$custom_message = sanitize_text_field( get_option( 'euaiactready_notice_message', '' ) );
		$message        = ! empty( $custom_message ) ? $custom_message : $this->euaiactready_get_disclosure_badge_title( $disclosure );

		$tool_names = $this->euaiactready_get_detected_tool_names();
		if ( ! empty( $tool_names ) ) {
			$message .= ' ' . sprintf(
				/* translators: %s: comma-separated list of AI tool names. */
				__( 'AI tools in use on this site: %s.', 'eu-ai-act-ready' ),
				implode( ', ', $tool_names )
			);
		}

		return $content . "\n\n" . sprintf(
			/* translators: %s: AI disclosure message. */
			__( '- AI Disclosure: %s', 'eu-ai-act-ready' ),
			$message
		);
	}

	/**
	 * Output a <dc:description> element inside each RSS 2.0 <item> for AI-marked posts.
	 *
	 * WordPress already declares xmlns:dc in its RSS feed, so no namespace registration needed.
	 * Fires on 'rss2_item'.
	 */
	public function euaiactready_rss_add_dc_description() {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$raw        = get_post_meta( $post_id, '_euaiactready_ai_content', true );
		$disclosure = EUAIACTREADY_Post_Meta_Box::euaiactready_normalize_disclosure_value( $raw );

		if ( 'none' === $disclosure ) {
			return;
		}

		$message    = $this->euaiactready_get_disclosure_badge_title( $disclosure );
		$tool_names = $this->euaiactready_get_detected_tool_names();
		if ( ! empty( $tool_names ) ) {
			$message .= ' ' . sprintf(
				/* translators: %s: comma-separated list of AI tool names. */
				__( 'AI tools in use on this site: %s.', 'eu-ai-act-ready' ),
				implode( ', ', $tool_names )
			);
		}

		echo "\t\t<dc:description>" . esc_xml( $message ) . "</dc:description>\n";
	}

	/**
	 * Output schema.org JSON-LD structured data on single AI-marked posts.
	 *
	 * Fires on 'wp_head'.
	 */
	public function euaiactready_add_schema_org_markup() {
		if ( ! get_option( 'euaiactready_transparency_enabled', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$raw        = get_post_meta( $post_id, '_euaiactready_ai_content', true );
		$disclosure = EUAIACTREADY_Post_Meta_Box::euaiactready_normalize_disclosure_value( $raw );

		if ( 'none' === $disclosure ) {
			return;
		}

		$keywords = array( 'AI-generated content', 'EU AI Act Article 50' );

		$disclosure_keywords = array(
			'assisted'           => 'AI-assisted content',
			'generated'          => 'AI-generated content',
			'generated_reviewed' => 'AI-generated human-reviewed content',
		);
		if ( isset( $disclosure_keywords[ $disclosure ] ) ) {
			$keywords[] = $disclosure_keywords[ $disclosure ];
		}

		$tool_names = $this->euaiactready_get_detected_tool_names();
		foreach ( $tool_names as $name ) {
			$keywords[] = $name;
		}

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Article',
			'name'        => get_the_title( $post_id ),
			'url'         => get_permalink( $post_id ),
			'description' => $this->euaiactready_get_disclosure_badge_title( $disclosure ),
			'keywords'    => implode( ', ', $keywords ),
		);

		if ( ! empty( $tool_names ) ) {
			$schema['creator'] = array_map(
				static function ( $tool_name ) {
					return array(
						'@type' => 'SoftwareApplication',
						'name'  => $tool_name,
					);
				},
				$tool_names
			);
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		if ( false === $json ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $json is produced by wp_json_encode(), which is safe for script content.
		echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	/**
	 * Return names of all currently detected AI tools.
	 *
	 * @return string[]
	 */
	private function euaiactready_get_detected_tool_names() {
		global $euaiactready_ai_tools_instance;
		if ( ! ( $euaiactready_ai_tools_instance instanceof Euaiactready_AI_Tools ) ) {
			return array();
		}

		$detected = $euaiactready_ai_tools_instance->euaiactready_get_disclosable_tools();
		$names    = array();
		foreach ( $detected as $tool ) {
			if ( ! empty( $tool['name'] ) ) {
				$names[] = $tool['name'];
			}
		}
		return $names;
	}

	/**
	 * Prefix the RSS post title with "[AI Content]" for AI-marked posts.
	 *
	 * Fires on 'the_title_rss'.
	 *
	 * @param string $title Post title (already escaped for RSS by WordPress).
	 * @return string Modified title.
	 */
	public function euaiactready_prefix_rss_title( $title ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $title;
		}

		$raw        = get_post_meta( $post_id, '_euaiactready_ai_content', true );
		$disclosure = EUAIACTREADY_Post_Meta_Box::euaiactready_normalize_disclosure_value( $raw );

		if ( 'none' !== $disclosure ) {
			$title = '[' . __( 'AI Content', 'eu-ai-act-ready' ) . '] ' . $title;
		}

		return $title;
	}

	/**
	 * Generate notice HTML based on style.
	 *
	 * @param string $style           Notice style: 'banner', 'inline', 'badge', or 'modal'.
	 * @param string $custom_message  Custom message (overrides per-type defaults when non-empty).
	 * @param string $disclosure_type Disclosure level: 'assisted', 'generated', or 'generated_reviewed'.
	 * @return string Notice HTML.
	 */
	public function euaiactready_generate_notice_html( $style, $custom_message = '', $disclosure_type = 'generated' ) {
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
			'<div class="eu-ai-act-ready-notice ai-notice-banner" role="note" aria-label="%1$s">
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
