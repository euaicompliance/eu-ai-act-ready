<?php
/**
 * EU AI Act Ready - Media transparency detection and labeling utilities.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects and labels AI-generated images, videos, and other media.
 */
class EUAIACTREADY_Media_Transparency {

	/**
	 * AI image generation tools patterns.
	 *
	 * @var array
	 */
	private $ai_tools = array(
		'dall-e',
		'dalle',
		'midjourney',
		'stable diffusion',
		'stablediffusion',
		'imagen',
		'firefly',
		'leonardo',
		'bluewillow',
		'craiyon',
		'nightcafe',
		'artbreeder',
		'deepai',
		'runway',
		'canva ai',
		'photoshop ai',
		'generative fill',
		'ai generated',
		'ai-generated',
		'ai created',
		'artificial intelligence',
		'bing image creator',
		'adobe firefly',
		'jasper art',
		'synthesia',
		'flux',
		'ideogram',
	);

	/**
	 * Suspicious EXIF patterns that may indicate AI generation.
	 *
	 * @var array
	 */
	private $suspicious_exif = array(
		'software' => array( 'Python', 'pytorch', 'tensorflow', 'stable-diffusion', 'automatic1111' ),
		'model'    => array( 'unknown', '', null ),
		'make'     => array( 'unknown', '', null ),
	);

	/**
	 * Initialize media transparency.
	 */
	public function __construct() {
		$this->euaiactready_init_hooks();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function euaiactready_init_hooks() {
		// Add image labels in content.
		add_filter( 'the_content', array( $this, 'euaiactready_add_image_labels' ), 999 );

		// Add media library column.
		add_filter( 'manage_media_columns', array( $this, 'euaiactready_add_media_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'euaiactready_display_media_column' ), 10, 2 );

		// Add meta box to attachment edit screen.
		add_action( 'add_meta_boxes_attachment', array( $this, 'euaiactready_add_ai_meta_box' ) );
		add_action( 'save_post_attachment', array( $this, 'euaiactready_save_ai_meta' ) );
		add_action( 'edit_attachment', array( $this, 'euaiactready_save_ai_meta' ) );

		// AJAX handler for attachment updates.
		add_action( 'wp_ajax_save-attachment-compat', array( $this, 'euaiactready_ajax_save_ai_meta' ), 0 );
		add_filter( 'attachment_fields_to_edit', array( $this, 'euaiactready_add_ai_attachment_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'euaiactready_save_ai_attachment_field' ), 10, 2 );

		// Enqueue scripts for media modal.
		add_action( 'wp_enqueue_media', array( $this, 'euaiactready_enqueue_media_modal_assets' ) );

		// AJAX handler for re-checking AI detection.
		add_action( 'wp_ajax_euaiactready_recheck_detection', array( $this, 'euaiactready_ajax_recheck_detection' ) );

		// Auto-detect AI on image upload.
		add_action( 'add_attachment', array( $this, 'euaiactready_auto_detect_on_upload' ) );

		// Add filter to media library.
		add_action( 'restrict_manage_posts', array( $this, 'euaiactready_add_media_filter' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'euaiactready_filter_media_query' ) );
	}

	/**
	 * Enqueue admin styles and scripts for media modal.
	 */
	public function euaiactready_enqueue_media_modal_assets() {
		// Enqueue admin styles.
		wp_enqueue_style(
			'euaiactready-admin',
			EUAIACTREADY_PLUGIN_URL . 'build/admin/admin.css',
			array(),
			EUAIACTREADY_VERSION,
			'all'
		);

		// Enqueue the media recheck script.
		wp_enqueue_script(
			'euaiactready-media-recheck',
			EUAIACTREADY_PLUGIN_URL . 'build/admin/media-recheck.js',
			array( 'jquery' ),
			EUAIACTREADY_VERSION,
			true
		);

		// Localize script with configuration.
		$success_icon     = '<span class="dashicons dashicons-yes-alt dashicons-success"></span>';
		$error_icon       = '<span class="dashicons dashicons-warning dashicons-error"></span>';
		$loading_icon     = '<span class="dashicons dashicons-update dashicons-spin"></span>';
		$button_idle_html = $loading_icon . ' ' . __( 'Re-check AI Detection', 'eu-ai-act-ready' );
		$button_loading   = $loading_icon . ' ' . __( 'Checking...', 'eu-ai-act-ready' );
		$default_error    = __( 'Detection failed. Please try again.', 'eu-ai-act-ready' );

		wp_localize_script(
			'euaiactready-media-recheck',
			'euaiactreadyRecheck',
			array(
				'successIcon'  => $success_icon,
				'errorIcon'    => $error_icon,
				'idleHtml'     => $button_idle_html,
				'loadingHtml'  => $button_loading,
				'defaultError' => $default_error,
			)
		);
	}

	/**
	 * Get AI detection info (always runs detection, ignores flag).
	 *
	 * Used for admin display purposes.
	 *
	 * @param int    $attachment_id   Attachment ID.
	 * @param bool   $force_detection Force fresh detection even if stored data exists.
	 * @param string $trigger         Scan trigger label for logging.
	 * @return array|false Detection result or false.
	 */
	public function euaiactready_get_ai_detection_info( $attachment_id, $force_detection = false, $trigger = 'auto' ) {
		$attachment_id = absint( $attachment_id );

		if ( ! $attachment_id ) {
			return false;
		}

		// If forcing detection, skip cache and run fresh.
		if ( $force_detection ) {
			return $this->euaiactready_run_detection( $attachment_id, $trigger );
		}

		// Check if there's stored detection info.
		$detection_source = get_post_meta( $attachment_id, '_euaiactready_ai_detection_source', true );

		if ( ! empty( $detection_source ) ) {
			$detection_info = json_decode( $detection_source, true );
			return array(
				'is_ai'      => true,
				'confidence' => isset( $detection_info['confidence'] ) ? $detection_info['confidence'] : 0,
				'method'     => 'auto',
				'indicators' => isset( $detection_info['indicators'] ) ? $detection_info['indicators'] : array(),
				'source'     => isset( $detection_info['source'] ) ? $detection_info['source'] : '',
			);
		}

		// No stored info, run fresh detection.
		return $this->euaiactready_run_detection( $attachment_id, $trigger );
	}

	/**
	 * Check if media is AI-generated (respects user flag for frontend).
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array|false Detection result or false.
	 */
	public function euaiactready_is_ai_media( $attachment_id ) {
		$attachment_id = absint( $attachment_id );

		if ( ! $attachment_id ) {
			return false;
		}

		// Check manual flag first.
		$manual_flag      = get_post_meta( $attachment_id, '_euaiactready_ai_generated', true );
		$marked_method    = get_post_meta( $attachment_id, '_euaiactready_ai_marked_method', true );
		$detection_source = get_post_meta( $attachment_id, '_euaiactready_ai_detection_source', true );

		// If flagged as '1', return the appropriate result.
		if ( '1' === $manual_flag ) {
			if ( 'manual' === $marked_method && empty( $detection_source ) ) {
				// Truly manual - no auto-detection.
				return array(
					'is_ai'      => true,
					'confidence' => 1.0,
					'method'     => 'manual',
					'indicators' => array( 'manual' ),
					'source'     => __( 'Manually marked as AI-generated', 'eu-ai-act-ready' ),
				);
			} elseif ( 'auto' === $marked_method && empty( $detection_source ) ) {
				// Auto-marked without stored detection data - don't re-scan on frontend.
				return array(
					'is_ai'      => true,
					'confidence' => 1.0,
					'method'     => 'auto',
					'indicators' => array( 'auto' ),
					'source'     => __( 'Auto-detected', 'eu-ai-act-ready' ),
				);
			} elseif ( ! empty( $detection_source ) ) {
				// Auto-detected - use stored detection info.
				$detection_info = json_decode( $detection_source, true );
				return array(
					'is_ai'      => true,
					'confidence' => isset( $detection_info['confidence'] ) ? $detection_info['confidence'] : 0,
					'method'     => 'auto',
					'indicators' => isset( $detection_info['indicators'] ) ? $detection_info['indicators'] : array(),
					'source'     => isset( $detection_info['source'] ) ? $detection_info['source'] : '',
				);
			}
		}

		// If flagged as '0', don't show (user explicitly rejected).
		if ( '0' === $manual_flag ) {
			return false;
		}

		// Not marked yet - only run detection in admin contexts.
		if ( ! is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		return $this->euaiactready_run_detection( $attachment_id, 'auto' );
	}

	/**
	 * Update attachment meta based on detection results.
	 *
	 * Centralized method to avoid duplication.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $detection     Detection result.
	 * @param float $threshold     Confidence threshold.
	 * @return bool Whether the attachment was marked as AI.
	 */
	private function euaiactready_update_ai_meta_from_detection( $attachment_id, $detection, $threshold ) {
		$attachment_id = absint( $attachment_id );

		if ( ! $attachment_id ) {
			return false;
		}

		if ( ! $detection || ! isset( $detection['confidence'] ) ) {
			return false;
		}

		$confidence = $detection['confidence'];

		if ( $confidence >= $threshold ) {
			// Auto-mark as AI if above threshold.
			update_post_meta( $attachment_id, '_euaiactready_ai_generated', '1' );
			update_post_meta( $attachment_id, '_euaiactready_ai_marked_method', 'auto' );

			// Store detection details.
			if ( isset( $detection['indicators'] ) ) {
				$detection_info = array(
					'confidence' => $confidence,
					'indicators' => $detection['indicators'],
					'source'     => isset( $detection['source'] ) ? $detection['source'] : __( 'AI Detection', 'eu-ai-act-ready' ),
				);
				update_post_meta( $attachment_id, '_euaiactready_ai_detection_source', wp_json_encode( $detection_info ) );
			}

			return true;
		} else {
			// Below threshold - store detection info but mark as '0'.
			update_post_meta( $attachment_id, '_euaiactready_ai_generated', '0' );
			update_post_meta( $attachment_id, '_euaiactready_ai_marked_method', 'none' );

			// Store detection details even if below threshold (for reference).
			$detection_info = array(
				'confidence' => $confidence,
				'indicators' => isset( $detection['indicators'] ) ? $detection['indicators'] : array(),
				'source'     => isset( $detection['source'] ) ? $detection['source'] : __( 'No AI detected', 'eu-ai-act-ready' ),
			);
			update_post_meta( $attachment_id, '_euaiactready_ai_detection_source', wp_json_encode( $detection_info ) );

			return false;
		}
	}

	/**
	 * Get human-readable label for indicator code.
	 *
	 * @param string $indicator_code Backend indicator code.
	 * @return string User-friendly label.
	 */
	private function euaiactready_get_indicator_label( $indicator_code ) {
		$labels = array(
			// Filename indicators.
			'filename'                    => __( 'AI tool in filename', 'eu-ai-act-ready' ),
			'hash_filename'               => __( 'Hash-like filename', 'eu-ai-act-ready' ),
			'uuid_filename'               => __( 'UUID filename', 'eu-ai-act-ready' ),
			'ai_keyword_filename'         => __( 'AI keyword in filename', 'eu-ai-act-ready' ),
			'generic_filename'            => __( 'Generic filename', 'eu-ai-act-ready' ),
			'numeric_filename'            => __( 'Numeric filename pattern', 'eu-ai-act-ready' ),

			// Metadata indicators.
			'title'                       => __( 'AI tool in title', 'eu-ai-act-ready' ),
			'alt_text'                    => __( 'AI tool in alt text', 'eu-ai-act-ready' ),
			'caption'                     => __( 'AI tool in caption', 'eu-ai-act-ready' ),
			'description'                 => __( 'AI tool in description', 'eu-ai-act-ready' ),

			// EXIF indicators.
			'exif_credit'                 => __( 'AI tool in EXIF credit', 'eu-ai-act-ready' ),
			'exif_software'               => __( 'Suspicious EXIF software', 'eu-ai-act-ready' ),
			'exif_ai_software'            => __( 'AI software in EXIF', 'eu-ai-act-ready' ),
			'exif_copyright'              => __( 'AI tool in copyright', 'eu-ai-act-ready' ),
			'exif_artist'                 => __( 'AI tool in artist field', 'eu-ai-act-ready' ),
			'exif_author'                 => __( 'AI tool in author field', 'eu-ai-act-ready' ),
			'exif_comment'                => __( 'AI tool in comment', 'eu-ai-act-ready' ),
			'exif_keywords'               => __( 'AI tool in keywords', 'eu-ai-act-ready' ),
			'no_exif_data'                => __( 'No EXIF data', 'eu-ai-act-ready' ),
			'missing_camera_info'         => __( 'Missing camera info', 'eu-ai-act-ready' ),
			'no_photo_exif'               => __( 'Missing photo EXIF', 'eu-ai-act-ready' ),

			// Raw EXIF indicators.
			'raw_exif_Software'           => __( 'AI in software field', 'eu-ai-act-ready' ),
			'raw_exif_ProcessingSoftware' => __( 'AI in processing software', 'eu-ai-act-ready' ),
			'raw_exif_Creator'            => __( 'AI in creator field', 'eu-ai-act-ready' ),
			'raw_exif_Artist'             => __( 'AI in artist field', 'eu-ai-act-ready' ),
			'raw_exif_Copyright'          => __( 'AI in copyright field', 'eu-ai-act-ready' ),
			'raw_exif_ImageDescription'   => __( 'AI in image description', 'eu-ai-act-ready' ),
			'raw_exif_UserComment'        => __( 'AI in user comment', 'eu-ai-act-ready' ),
			'raw_exif_Make'               => __( 'AI in make field', 'eu-ai-act-ready' ),
			'raw_exif_Model'              => __( 'AI in model field', 'eu-ai-act-ready' ),

			// Provenance indicators.
			'c2pa_ai_claim'               => __( 'C2PA AI verification', 'eu-ai-act-ready' ),
			'iptc_ai_source'              => __( 'IPTC AI source tag', 'eu-ai-act-ready' ),
			'c2pa_sidecar_ai'             => __( 'C2PA sidecar AI claim', 'eu-ai-act-ready' ),

			// Image characteristics.
			'common_ai_dimension'         => __( 'Common AI dimensions', 'eu-ai-act-ready' ),
			'perfect_square_ai'           => __( 'Perfect square (AI size)', 'eu-ai-act-ready' ),
			'perfect_square'              => __( 'Perfect square image', 'eu-ai-act-ready' ),
			'common_ai_aspect'            => __( 'Common AI aspect ratio', 'eu-ai-act-ready' ),
			'unusual_compression'         => __( 'Unusual compression', 'eu-ai-act-ready' ),

			// Manual.
			'manual'                      => __( 'Manually marked', 'eu-ai-act-ready' ),
		);

		return isset( $labels[ $indicator_code ] ) ? $labels[ $indicator_code ] : ucwords( str_replace( '_', ' ', $indicator_code ) );
	}

	/**
	 * Get status information for UI display.
	 *
	 * Returns color, icon, and text based on confidence.
	 *
	 * @param float $confidence Confidence value (0-1).
	 * @param float $threshold  Threshold value (0-1).
	 * @return array Status info with 'color', 'icon', 'text' keys.
	 */
	private function euaiactready_get_status_info( $confidence, $threshold ) {
		if ( $confidence >= $threshold ) {
			return array(
				'color' => '#10a37f',
				'icon'  => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="ai-media-icon"><circle cx="8" cy="8" r="7.5" fill="#10a37f"/><path d="M5 8L7 10L11 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
				'text'  => __( 'AI Detected', 'eu-ai-act-ready' ),
				'class' => 'high',
			);
		} elseif ( $confidence > 0 ) {
			return array(
				'color' => '#d97706',
				'icon'  => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="ai-media-icon"><path d="M8 1.5L1.5 13.5C1.22 14.04 1.61 14.5 2.15 14.5H13.85C14.39 14.5 14.78 14.04 14.5 13.5L8 1.5Z" fill="#d97706"/><line x1="8" y1="6" x2="8" y2="9.5" stroke="white" stroke-width="1.8" stroke-linecap="round"/><circle cx="8" cy="11.5" r="0.8" fill="white"/></svg>',
				'text'  => __( 'Possibly AI (Below Threshold)', 'eu-ai-act-ready' ),
				'class' => 'medium',
			);
		} else {
			return array(
				'color' => '#6b7280',
				'icon'  => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="ai-media-icon"><circle cx="8" cy="8" r="7.5" fill="#6b7280"/><path d="M5.5 5.5L10.5 10.5M10.5 5.5L5.5 10.5" stroke="white" stroke-width="1.8" stroke-linecap="round"/></svg>',
				'text'  => __( 'No AI Detected', 'eu-ai-act-ready' ),
				'class' => 'low',
			);
		}
	}

	/**
	 * Generate "not eligible for AI detection" message HTML.
	 *
	 * Used for non-image attachments.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $context       'metabox' or 'popup' - determines styling.
	 * @return string HTML output.
	 */
	private function euaiactready_render_not_eligible_message( $attachment_id, $context = 'metabox' ) {
		$mime_type = get_post_mime_type( $attachment_id );

		$info_icon    = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="ai-media-icon"><circle cx="8" cy="8" r="7.5" fill="#9ca3af"/><line x1="8" y1="7" x2="8" y2="11.5" stroke="white" stroke-width="2" stroke-linecap="round"/><circle cx="8" cy="4.5" r="1" fill="white"/></svg>';
		$notice_title = esc_html__( 'Not Eligible for AI Detection', 'eu-ai-act-ready' );
		$info_message = esc_html__( 'AI detection only works with image files (JPEG, PNG, GIF, WebP, etc.).', 'eu-ai-act-ready' );

		$html  = '<div class="ai-media-not-eligible">';
		$html .= '<div class="ai-media-not-eligible-content">';
		$html .= '<strong>' . $info_icon . $notice_title . '</strong>';

		if ( 'metabox' === $context ) {
			$html .= '<br><br>';
			$html .= '<span>' . $info_message . '</span><br>';
			$html .= '<span>' . esc_html__( 'This file type:', 'eu-ai-act-ready' ) . ' <code>' . esc_html( $mime_type ) . '</code></span>';
		} else {
			$html .= '<br>';
			$html .= '<span class="small">' . $info_message . '</span><br>';
			$html .= '<span class="small">' . esc_html__( 'This file type:', 'eu-ai-act-ready' ) . ' <code>' . esc_html( $mime_type ) . '</code></span>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Handle marking logic - determines whether to mark as manual or keep as auto.
	 *
	 * Centralized to avoid duplication across save methods.
	 *
	 * @param int    $post_id  Attachment ID.
	 * @param string $ai_value '1' or other value from form.
	 * @param string $context  Context for logging ('Save', 'AJAX', 'Attachment field').
	 */
	private function euaiactready_handle_ai_marking( $post_id, $ai_value, $context = 'Save' ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		$ai_value = sanitize_text_field( $ai_value );
		unset( $context ); // Context retained for possible future logging.

		if ( '1' === $ai_value ) {
			update_post_meta( $post_id, '_euaiactready_ai_generated', '1' );

			// Check if it was previously auto-marked.
			$marked_method = get_post_meta( $post_id, '_euaiactready_ai_marked_method', true );

			// If it was previously auto-detected, leave the method untouched; otherwise mark as manual.
			if ( 'auto' !== $marked_method ) {
				// User manually marking (either new or below threshold case).
				update_post_meta( $post_id, '_euaiactready_ai_marked_method', 'manual' );
			}
		} else {
			// User unchecked - set to '0' (won't show in frontend).
			update_post_meta( $post_id, '_euaiactready_ai_generated', '0' );
			update_post_meta( $post_id, '_euaiactready_ai_marked_method', 'none' );
		}
	}

	/**
	 * Generate re-check button with JavaScript.
	 *
	 * Used in both metabox and popup contexts.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $context       'metabox' or 'popup' - determines selectors.
	 * @param bool   $no_scan_done  Whether no scan has been performed yet.
	 * @return string HTML with button and JavaScript.
	 */
	private function euaiactready_render_recheck_button( $attachment_id, $context = 'metabox', $no_scan_done = false ) {
		$attachment_id = absint( $attachment_id );
		$is_popup      = ( 'popup' === $context );

		$button_class = $is_popup ? 'ai-popup-recheck-button' : 'ai_recheck_button';

		// Enqueue the media recheck script.
		wp_enqueue_script(
			'euaiactready-media-recheck',
			EUAIACTREADY_PLUGIN_URL . 'build/admin/media-recheck.js',
			array( 'jquery' ),
			EUAIACTREADY_VERSION,
			true
		);

		// Prepare icons and messages for localization.
		$success_icon     = '<span class="dashicons dashicons-yes-alt dashicons-success"></span>';
		$error_icon       = '<span class="dashicons dashicons-warning dashicons-error"></span>';
		$loading_icon     = '<span class="dashicons dashicons-update dashicons-spin"></span>';
		$button_text      = $no_scan_done ? __( 'Check AI Detection', 'eu-ai-act-ready' ) : __( 'Re-check AI Detection', 'eu-ai-act-ready' );
		$button_idle_html = $loading_icon . ' ' . $button_text;
		$button_loading   = $loading_icon . ' ' . __( 'Checking...', 'eu-ai-act-ready' );
		$default_error    = __( 'Detection failed. Please try again.', 'eu-ai-act-ready' );

		// Localize script with configuration.
		wp_localize_script(
			'euaiactready-media-recheck',
			'euaiactreadyRecheck',
			array(
				'successIcon'  => $success_icon,
				'errorIcon'    => $error_icon,
				'idleHtml'     => $button_idle_html,
				'loadingHtml'  => $button_loading,
				'defaultError' => $default_error,
			)
		);

		$nonce = wp_create_nonce( 'euaiactready_recheck_' . $attachment_id );

		$button_id_attr    = $is_popup ? ' id="ai-popup-recheck-btn-' . $attachment_id . '"' : '';
		$message_id        = $is_popup ? 'ai-popup-recheck-message-' . $attachment_id : 'euaiactready-recheck-message-' . $attachment_id;
		$button_classes    = array( 'button', 'button-secondary', $button_class, 'ai-recheck-btn' );
		$button_data_attrs = ' data-attachment-id="' . esc_attr( $attachment_id ) . '" data-nonce="' . esc_attr( $nonce ) . '" data-message-selector="#' . esc_attr( $message_id ) . '"';

		$html  = '<button type="button"' . $button_id_attr . ' class="' . esc_attr( implode( ' ', $button_classes ) ) . '"' . $button_data_attrs . '>';
		$html .= $button_idle_html;
		$html .= '</button>';
		$html .= '<div id="' . esc_attr( $message_id ) . '" class="euaiactready-recheck-message"></div>';

		return $html;
	}

	/**
	 * Run AI detection on attachment (core detection logic).
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $trigger       Scan trigger identifier (e.g., 'auto', 'manual', 'bulk_scan').
	 * @return array|false          Detection result or false.
	 */
	private function euaiactready_run_detection( $attachment_id, $trigger = 'auto' ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment ) {
			return false;
		}

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return false;
		}

		$confidence       = 0;
		$detected_methods = array();
		$ai_tool          = '';

		$filename = basename( $attachment->guid );
		foreach ( $this->ai_tools as $tool ) {
			if ( false !== stripos( $filename, $tool ) ) {
				$confidence        += 0.4;
				$detected_methods[] = 'filename';
				$ai_tool            = $tool;
				break;
			}
		}

		$title = $attachment->post_title;
		foreach ( $this->ai_tools as $tool ) {
			if ( false !== stripos( $title, $tool ) ) {
				$confidence        += 0.3;
				$detected_methods[] = 'title';
				if ( empty( $ai_tool ) ) {
					$ai_tool = $tool;
				}
				break;
			}
		}

		$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		foreach ( $this->ai_tools as $tool ) {
			if ( false !== stripos( $alt_text, $tool ) ) {
				$confidence        += 0.3;
				$detected_methods[] = 'alt_text';
				if ( empty( $ai_tool ) ) {
					$ai_tool = $tool;
				}
				break;
			}
		}

		$caption = $attachment->post_excerpt;
		foreach ( $this->ai_tools as $tool ) {
			if ( false !== stripos( $caption, $tool ) ) {
				$confidence        += 0.3;
				$detected_methods[] = 'caption';
				if ( empty( $ai_tool ) ) {
					$ai_tool = $tool;
				}
				break;
			}
		}

		$description = $attachment->post_content;
		foreach ( $this->ai_tools as $tool ) {
			if ( false !== stripos( $description, $tool ) ) {
				$confidence        += 0.2;
				$detected_methods[] = 'description';
				if ( empty( $ai_tool ) ) {
					$ai_tool = $tool;
				}
				break;
			}
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( isset( $metadata['image_meta']['credit'] ) ) {
			foreach ( $this->ai_tools as $tool ) {
				if ( false !== stripos( $metadata['image_meta']['credit'], $tool ) ) {
					$confidence        += 0.5;
					$detected_methods[] = 'exif_credit';
					if ( empty( $ai_tool ) ) {
						$ai_tool = $tool;
					}
					break;
				}
			}
		}

		$exif = isset( $metadata['image_meta'] ) ? $metadata['image_meta'] : array();

		$has_any_exif = false;
		if ( ! empty( $exif ) ) {
			foreach ( $exif as $value ) {
				if ( ! empty( $value ) ) {
					$has_any_exif = true;
					break;
				}
			}
		}

		if ( $has_any_exif ) {
			if ( ! empty( $exif['software'] ) ) {
				foreach ( $this->suspicious_exif['software'] as $pattern ) {
					if ( false !== stripos( $exif['software'], $pattern ) ) {
						$confidence        += 0.4;
						$detected_methods[] = 'exif_software';
						if ( empty( $ai_tool ) ) {
							$ai_tool = 'ai_software';
						}
						break;
					}
				}

				foreach ( $this->ai_tools as $tool ) {
					if ( false !== stripos( $exif['software'], $tool ) ) {
						$confidence        += 0.4;
						$detected_methods[] = 'exif_ai_software';
						if ( empty( $ai_tool ) ) {
							$ai_tool = $tool;
						}
						break;
					}
				}
			}

			$exif_fields_to_check = array( 'credit', 'copyright', 'artist', 'author', 'comment', 'keywords' );
			foreach ( $exif_fields_to_check as $field ) {
				if ( ! empty( $exif[ $field ] ) ) {
					$field_value = is_array( $exif[ $field ] ) ? implode( ' ', $exif[ $field ] ) : (string) $exif[ $field ];
					foreach ( $this->ai_tools as $tool ) {
						if ( false !== stripos( $field_value, $tool ) ) {
							$confidence        += 0.35;
							$detected_methods[] = "exif_{$field}";
							if ( empty( $ai_tool ) ) {
								$ai_tool = $tool;
							}
							break 2;
						}
					}
				}
			}

			$has_make  = ! empty( $exif['camera'] ) || ! empty( $exif['make'] );
			$has_model = ! empty( $exif['camera'] ) || ! empty( $exif['model'] );

			if ( ! $has_make && ! $has_model ) {
				$confidence        += 0.15;
				$detected_methods[] = 'missing_camera_info';
			}

			$has_aperture = ! empty( $exif['aperture'] );
			$has_shutter  = ! empty( $exif['shutter_speed'] );
			$has_iso      = ! empty( $exif['iso'] );
			$has_focal    = ! empty( $exif['focal_length'] );

			$photo_exif_count = ( $has_aperture ? 1 : 0 ) + ( $has_shutter ? 1 : 0 ) + ( $has_iso ? 1 : 0 ) + ( $has_focal ? 1 : 0 );

			if ( 0 === $photo_exif_count && ! $has_make && ! $has_model ) {
				$confidence        += 0.15;
				$detected_methods[] = 'no_photo_exif';
			}
		} else {
			$confidence        += 0.3;
			$detected_methods[] = 'no_exif_data';
			$detected_methods[] = 'no_photo_exif';
			$detected_methods[] = 'missing_camera_info';
		}

		$file_path = get_attached_file( $attachment_id );
		if ( $file_path && file_exists( $file_path ) && function_exists( 'exif_read_data' ) ) {
			$raw_exif = @exif_read_data( $file_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Suppress EXIF warnings for legacy image metadata.
			if ( $raw_exif ) {
				$exif_fields_to_scan = array( 'Software', 'ProcessingSoftware', 'Creator', 'Artist', 'Copyright', 'ImageDescription', 'UserComment', 'Make', 'Model' );

				foreach ( $exif_fields_to_scan as $field ) {
					if ( isset( $raw_exif[ $field ] ) && is_string( $raw_exif[ $field ] ) ) {
						$value = $raw_exif[ $field ];

						foreach ( $this->ai_tools as $tool ) {
							if ( false !== stripos( $value, $tool ) ) {
								$confidence        += 0.35;
								$detected_methods[] = "raw_exif_{$field}";
								if ( empty( $ai_tool ) ) {
									$ai_tool = $tool;
								}
								break 2;
							}
						}
					}
				}

				if ( isset( $raw_exif['XMP'] ) ) {
					$xmp_data = $raw_exif['XMP'];

					if ( false !== stripos( $xmp_data, 'c2pa' ) || false !== stripos( $xmp_data, 'contentauthenticity' ) || false !== stripos( $xmp_data, 'DigitalSourceType' ) ) {
						if ( false !== stripos( $xmp_data, 'trainedAlgorithmicMedia' ) || false !== stripos( $xmp_data, 'compositeWithTrainedAlgorithmicMedia' ) || false !== stripos( $xmp_data, 'algorithmicMedia' ) ) {
							$confidence        += 0.9;
							$detected_methods[] = 'c2pa_ai_claim';
							$ai_tool            = 'c2pa_verified_ai';
						}
					}
				}

				if ( isset( $raw_exif['DigitalSourceType'] ) ) {
					$source_type = $raw_exif['DigitalSourceType'];

					if ( false !== stripos( $source_type, 'trainedAlgorithmicMedia' ) || false !== stripos( $source_type, 'algorithmicMedia' ) ) {
						$confidence        += 0.9;
						$detected_methods[] = 'iptc_ai_source';
						$ai_tool            = 'iptc_verified_ai';
					}
				}
			}
		}

		if ( $file_path && file_exists( $file_path ) ) {
			$c2pa_sidecar = $file_path . '.c2pa';

			if ( file_exists( $c2pa_sidecar ) ) {
				$c2pa_content = @file_get_contents( $c2pa_sidecar ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local sidecar parsing needs native file access.
				if ( $c2pa_content ) {
					if ( false !== stripos( $c2pa_content, 'trainedAlgorithmicMedia' ) || false !== stripos( $c2pa_content, 'ai-generated' ) || false !== stripos( $c2pa_content, 'algorithmicMedia' ) ) {
						$confidence        += 0.9;
						$detected_methods[] = 'c2pa_sidecar_ai';
						$ai_tool            = 'c2pa_sidecar_verified_ai';
					}
				}
			}
		}

		if ( $file_path && file_exists( $file_path ) ) {
			$image_size = getimagesize( $file_path );
			if ( $image_size ) {
				$width  = (int) $image_size[0];
				$height = (int) $image_size[1];

				$common_ai_sizes = array( 512, 640, 768, 1024, 1152, 1280, 1536, 2048, 2560 );

				if ( in_array( $width, $common_ai_sizes, true ) || in_array( $height, $common_ai_sizes, true ) ) {
					$confidence        += 0.15;
					$detected_methods[] = 'common_ai_dimension';
				}

				if ( $width === $height ) {
					if ( in_array( $width, $common_ai_sizes, true ) ) {
						$confidence        += 0.25;
						$detected_methods[] = 'perfect_square_ai';
					} else {
						$confidence        += 0.15;
						$detected_methods[] = 'perfect_square';
					}
				}

				$aspect_ratio = $width / $height;
				if ( abs( $aspect_ratio - 1 ) < 0.01 || abs( $aspect_ratio - 1.5 ) < 0.1 || abs( $aspect_ratio - 1.777 ) < 0.1 || abs( $aspect_ratio - 0.667 ) < 0.1 || abs( $aspect_ratio - 0.563 ) < 0.1 ) {
					$confidence        += 0.05;
					$detected_methods[] = 'common_ai_aspect';
				}
			}

			$file_size = filesize( $file_path );
			if ( $image_size && $file_size ) {
				$pixels          = (int) $image_size[0] * (int) $image_size[1];
				$bytes_per_pixel = $pixels > 0 ? $file_size / $pixels : 0;

				if ( $bytes_per_pixel < 0.3 || $bytes_per_pixel > 4 ) {
					$confidence        += 0.05;
					$detected_methods[] = 'unusual_compression';
				}
			}
		}

		$basename = basename( $filename, '.' . pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( preg_match( '/^[a-f0-9]{32,}$/i', $basename ) ) {
			$confidence        += 0.2;
			$detected_methods[] = 'hash_filename';
		}

		if ( preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $basename ) ) {
			$confidence        += 0.25;
			$detected_methods[] = 'uuid_filename';
		}

		if ( preg_match( '/(generated|output|result|render|creation|artwork|ai[-_]?art|synthetic)/i', $basename ) ) {
			$confidence        += 0.25;
			$detected_methods[] = 'ai_keyword_filename';
		}

		if ( preg_match( '/^(image|picture|photo)[-_]?\d+$/i', $basename ) ) {
			$confidence        += 0.15;
			$detected_methods[] = 'generic_filename';
		}

		if ( preg_match( '/^\d+[-_]\d+$/', $basename ) ) {
			$confidence        += 0.15;
			$detected_methods[] = 'numeric_filename';
		}

		$confidence = min( $confidence, 1 );

		$threshold             = get_option( 'euaiactready_media_confidence_threshold', EUAIACTREADY_DEFAULT_MEDIA_CONFIDENCE_THRESHOLD );
		$default_ai_source     = __( 'AI Detection', 'eu-ai-act-ready' );
		$default_non_ai_source = __( 'No AI detected', 'eu-ai-act-ready' );

		$special_sources = array(
			'ai_software'              => __( 'AI Software', 'eu-ai-act-ready' ),
			'c2pa_verified_ai'         => __( 'C2PA Verified AI', 'eu-ai-act-ready' ),
			'iptc_verified_ai'         => __( 'IPTC Verified AI', 'eu-ai-act-ready' ),
			'c2pa_sidecar_verified_ai' => __( 'C2PA Sidecar Verified AI', 'eu-ai-act-ready' ),
		);

		$ai_source_label = '';
		if ( $ai_tool ) {
			if ( isset( $special_sources[ $ai_tool ] ) ) {
				$ai_source_label = $special_sources[ $ai_tool ];
			} else {
				$ai_source_label = ucwords( str_replace( '-', ' ', $ai_tool ) );
			}
		}

		$detection_result = array(
			'is_ai'         => $confidence >= $threshold,
			'confidence'    => $confidence,
			'indicators'    => $detected_methods,
			'source'        => $ai_source_label ? $ai_source_label : ( $confidence >= $threshold ? $default_ai_source : $default_non_ai_source ),
			'attachment_id' => $attachment_id,
		);

		if ( $confidence >= $threshold ) {
			update_post_meta( $attachment_id, '_euaiactready_ai_generated', '1' );
			update_post_meta( $attachment_id, '_euaiactready_ai_marked_method', 'auto' );

			$detection_info = array(
				'confidence' => $confidence,
				'indicators' => $detected_methods,
				'source'     => $ai_source_label ? $ai_source_label : $default_ai_source,
			);
			update_post_meta( $attachment_id, '_euaiactready_ai_detection_source', wp_json_encode( $detection_info ) );
		} else {
			update_post_meta( $attachment_id, '_euaiactready_ai_generated', '0' );
			update_post_meta( $attachment_id, '_euaiactready_ai_marked_method', 'none' );

			$detection_info = array(
				'confidence' => $confidence,
				'indicators' => $detected_methods,
				'source'     => $ai_source_label ? $ai_source_label : $default_non_ai_source,
			);
			update_post_meta( $attachment_id, '_euaiactready_ai_detection_source', wp_json_encode( $detection_info ) );
		}

		EUAIACTREADY_Data_Store::log_media_scan( $attachment_id, $detection_result, $trigger );

		return $detection_result;
	}

	/**
	 * Add image labels to content for AI-generated images.
	 *
	 * Hooks into 'the_content' filter to wrap AI-generated images with labels.
	 *
	 * @param string $content Post content.
	 * @return string Modified content with AI image labels.
	 */
	public function euaiactready_add_image_labels( $content ) {
		if ( ! is_singular() ) {
			return $content;
		}

		if ( ! get_option( 'euaiactready_media_transparency', true ) ) {
			return $content;
		}

		$label_style = get_option( 'euaiactready_media_label_style', 'caption' );

		preg_match_all( '/<img[^>]+>/i', $content, $matches );

		if ( empty( $matches[0] ) ) {
			return $content;
		}

		foreach ( $matches[0] as $img_tag ) {
			$attachment_id = $this->euaiactready_extract_attachment_id( $img_tag );

			if ( ! $attachment_id ) {
				continue;
			}

			$attachment_id = absint( $attachment_id );
			$manual_flag   = get_post_meta( $attachment_id, '_euaiactready_ai_generated', true );

			if ( '1' === $manual_flag ) {
				$detection = $this->euaiactready_is_ai_media( $attachment_id );
				// Note: generate_image_label() escapes all output using esc_html() and esc_attr().
				// $content is already sanitized by WordPress on save via wp_kses_post().
				$label_html = $this->euaiactready_generate_image_label( $detection, $label_style );

				if ( $label_html ) {
					$allowed_html = array(
						'div'  => array(
							'class' => true,
							'title' => true,
						),
						'span' => array(
							'class' => true,
							'title' => true,
						),
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

					$wrapped = '<div class="ai-media-container">' . $img_tag . wp_kses( $label_html, $allowed_html ) . '</div>';
					$content = str_replace( $img_tag, $wrapped, $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Extract attachment ID from image tag.
	 *
	 * @param string $img_tag Image HTML tag.
	 * @return int|false Attachment ID or false.
	 */
	private function euaiactready_extract_attachment_id( $img_tag ) {
		if ( preg_match( '/wp-image-(\d+)/i', $img_tag, $class_id ) ) {
			return absint( $class_id[1] );
		}

		if ( preg_match( '/data-id["\'](\d+)["\']/i', $img_tag, $data_id ) ) {
			return absint( $data_id[1] );
		}

		if ( preg_match( '/src["\'](.*?)["\']/i', $img_tag, $src ) ) {
			$attachment_id = attachment_url_to_postid( $src[1] );
			if ( $attachment_id ) {
				return absint( $attachment_id );
			}
		}

		return false;
	}

	/**
	 * Generate image label HTML.
	 *
	 * @param array  $detection Detection data.
	 * @param string $style Label style.
	 * @return string Label HTML.
	 */
	private function euaiactready_generate_image_label( $detection, $style ) {
		if ( empty( $detection ) ) {
			return '';
		}

		$label_text = __( 'AI-Generated Image', 'eu-ai-act-ready' );
		$tooltip    = $label_text;

		if ( ! empty( $detection['source'] ) ) {
			$source_value = wp_strip_all_tags( (string) $detection['source'] );
			$tooltip      = sprintf(
				/* translators: %s: Source of the AI detection. */
				__( 'AI-Generated Image (%s)', 'eu-ai-act-ready' ),
				$source_value
			);
		}

		$label_text_escaped = esc_html( $label_text );
		$tooltip_escaped    = esc_attr( $tooltip );
		$icon_html          = wp_kses(
			EUAIACTREADY::euaiactready_get_ai_icon( 18, 'currentColor' ),
			EUAIACTREADY::euaiactready_get_svg_allowed_html()
		);

		switch ( $style ) {
			case 'badge':
				return '<div class="ai-media-badge" title="' . $tooltip_escaped . '"><span class="ai-icon">' . $icon_html . '</span><span class="ai-text">' . $label_text_escaped . '</span></div>';

			case 'caption':
				return '<div class="ai-media-caption"><span class="ai-icon">' . $icon_html . '</span><span class="ai-text">' . $label_text_escaped . '</span></div>';

			case 'overlay':
				return '<div class="ai-media-overlay"><span class="ai-overlay-badge" title="' . $tooltip_escaped . '"><span class="ai-icon">' . $icon_html . '</span>' . $label_text_escaped . '</span></div>';

			case 'border':
				return '<div class="ai-media-border-label" title="' . $tooltip_escaped . '"><span class="ai-icon">' . $icon_html . '</span>' . $label_text_escaped . '</div>';

			default:
				return '<div class="ai-media-caption"><span class="ai-icon">' . $icon_html . '</span><span class="ai-text">' . $label_text_escaped . '</span></div>';
		}
	}



	/**
	 * Add AI column to media library.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function euaiactready_add_media_column( $columns ) {
		$icon_html               = wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 14, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() );
		$columns['ai_generated'] = $icon_html . ' ' . esc_html__( 'AI', 'eu-ai-act-ready' );
		return $columns;
	}

	/**
	 * Display AI column content.
	 *
	 * @param string $column_name Column name.
	 * @param int    $attachment_id Attachment ID.
	 */
	public function euaiactready_display_media_column( $column_name, $attachment_id ) {
		if ( 'ai_generated' !== $column_name ) {
			return;
		}

		$manual_flag = get_post_meta( $attachment_id, '_euaiactready_ai_generated', true );

		if ( '1' === $manual_flag ) {
			echo '<span class="ai-media-column-badge confirmed">&#10003; ' . esc_html__( 'AI', 'eu-ai-act-ready' ) . '</span>';
			return;
		}

		$detection = $this->euaiactready_is_ai_media( $attachment_id );

		if ( $detection && isset( $detection['confidence'] ) ) {
			$confidence        = absint( round( $detection['confidence'] * 100 ) );
			$threshold         = (float) get_option( 'euaiactready_media_confidence_threshold', EUAIACTREADY_DEFAULT_MEDIA_CONFIDENCE_THRESHOLD );
			$threshold_percent = (int) round( $threshold * 100 );

			if ( $confidence >= $threshold_percent ) {
				echo '<span class="ai-media-column-badge detected">? ' . esc_html( $confidence ) . '%</span>';
			} elseif ( $confidence > 0 ) {
				echo '<span class="ai-media-column-badge low">' . esc_html( $confidence ) . '%</span>';
			} else {
				echo '<span class="ai-media-column-badge none">&mdash;</span>';
			}
		} else {
			echo '<span class="ai-media-column-badge none">&mdash;</span>';
		}
	}

	/**
	 * Add meta box to attachment edit screen.
	 *
	 * @param WP_Post $post Attachment post object.
	 */
	public function euaiactready_add_ai_meta_box( $post ) {
		unset( $post );

		add_meta_box(
			'euaiactready_media',
			__( 'AI Generation Info', 'eu-ai-act-ready' ),
			array( $this, 'euaiactready_render_ai_meta_box' ),
			'attachment',
			'side',
			'high'
		);
	}

	/**
	 * Render AI meta box content.
	 *
	 * @param WP_Post $post Attachment post object.
	 */
	public function euaiactready_render_ai_meta_box( $post ) {
		$attachment_id = absint( $post->ID );

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			echo wp_kses_post( $this->euaiactready_render_not_eligible_message( $attachment_id, 'metabox' ) );
			return;
		}

		$manual_flag      = get_post_meta( $attachment_id, '_euaiactready_ai_generated', true );
		$marked_method    = get_post_meta( $attachment_id, '_euaiactready_ai_marked_method', true );
		$detection_source = get_post_meta( $attachment_id, '_euaiactready_ai_detection_source', true );
		$no_scan_done     = empty( $detection_source );

		// Only get detection info if data exists (don't auto-scan on page load).
		$detection  = ! $no_scan_done ? $this->euaiactready_get_ai_detection_info( $attachment_id ) : false;
		$threshold  = (float) get_option( 'euaiactready_media_confidence_threshold', EUAIACTREADY_DEFAULT_MEDIA_CONFIDENCE_THRESHOLD );
		$confidence = ( $detection && isset( $detection['confidence'] ) ) ? (float) $detection['confidence'] : 0;

		$confidence_percent = absint( round( $confidence * 100 ) );
		$threshold_percent  = absint( round( $threshold * 100 ) );

		$should_be_checked = ( '1' === $manual_flag );

		wp_nonce_field( 'euaiactready_media_meta', 'euaiactready_media_nonce' );

		$status       = $this->euaiactready_get_status_info( $confidence, $threshold );
		$status_icon  = isset( $status['icon'] ) ? wp_kses_post( $status['icon'] ) : '';
		$status_text  = isset( $status['text'] ) ? esc_html( $status['text'] ) : '';
		$status_class = isset( $status['class'] ) ? sanitize_html_class( $status['class'] ) : 'low';
		$bar_class    = sanitize_html_class( $confidence >= $threshold ? 'high' : ( $confidence > 0 ? 'medium' : 'low' ) );

		echo '<div class="ai-media-detection-container">';

		echo '<label class="ai-media-detection-label">';
			echo '<input type="checkbox" id="_euaiactready_ai_generated_checkbox" name="_euaiactready_ai_generated" value="1" class="ai-compliance-checkbox" ' . checked( $should_be_checked, true, false ) . ' /> ';
			echo '<input type="hidden" id="_euaiactready_ai_generated_hidden" name="attachments[' . esc_attr( $attachment_id ) . '][_euaiactready_ai_generated]" value="' . esc_attr( $should_be_checked ? '1' : '0' ) . '" />';
			echo '<strong>' . esc_html__( 'Mark as AI-Generated', 'eu-ai-act-ready' ) . '</strong>';
		echo '</label>';
		echo '<p class="ai-media-detection-note">' . esc_html__( 'Check this box to manually mark this image as AI-generated. This will display transparency labels on the frontend.', 'eu-ai-act-ready' ) . '</p>';

		if ( $no_scan_done ) {
			echo '<div class="ai-media-status-box status-info">';
			echo '<div class="ai-media-status-header">';
			echo '<strong>';
			echo ' ' . esc_html__( 'No Scan Performed Yet', 'eu-ai-act-ready' );
			echo '</strong>';
			echo '</div>';
			echo '<div class="ai-media-detection-details">';
			echo '<p><em>' . esc_html__( 'This image has not been scanned for AI indicators yet. Click the "Re-check AI Detection" button below to scan this image.', 'eu-ai-act-ready' ) . '</em></p>';
			echo '</div>';
			echo '</div>';
		} else {
			echo '<div class="ai-media-status-box status-' . esc_attr( $status_class ) . '">';
			echo '<div class="ai-media-status-header">';
			if ( $status_icon ) {
				printf(
					'<strong>%1$s %2$s</strong>',
					wp_kses_post( $status_icon ),
					esc_html( $status_text )
				);
			} else {
				printf( '<strong>%s</strong>', esc_html( $status_text ) );
			}
			echo '<span class="ai-media-confidence-badge ' . esc_attr( $status_class ) . '">' . esc_html( $confidence_percent ) . '%</span>';
			echo '</div>';

			echo '<div class="ai-media-confidence-bar-container">';
			echo '<div class="ai-media-confidence-bar ' . esc_attr( $bar_class ) . '" style="width: ' . esc_attr( $confidence_percent ) . '%;"></div>';
			echo '</div>';

			echo '<div class="ai-media-detection-details">';
			printf(
				'<strong>%s</strong> %s%% | <strong>%s</strong> %s%%<br>',
				esc_html__( 'Confidence:', 'eu-ai-act-ready' ),
				esc_html( $confidence_percent ),
				esc_html__( 'Threshold:', 'eu-ai-act-ready' ),
				esc_html( $threshold_percent )
			);

			if ( $detection && ! empty( $detection['is_ai'] ) ) {
				if ( ! empty( $detection['source'] ) ) {
					echo '<strong>' . esc_html__( 'Source:', 'eu-ai-act-ready' ) . '</strong> ' . esc_html( $detection['source'] ) . '<br>';
				}

				if ( '1' === $manual_flag && ! empty( $marked_method ) ) {
					if ( 'manual' === $marked_method ) {
						echo '<strong>' . esc_html__( 'Marking:', 'eu-ai-act-ready' ) . '</strong> ' . esc_html__( 'Manually marked', 'eu-ai-act-ready' ) . '<br>';
					} elseif ( 'auto' === $marked_method ) {
						echo '<strong>' . esc_html__( 'Marking:', 'eu-ai-act-ready' ) . '</strong> ' . esc_html__( 'Auto-detected', 'eu-ai-act-ready' ) . '<br>';
					}
				}

				if ( ! empty( $detection['indicators'] ) && is_array( $detection['indicators'] ) ) {
					echo '<strong>' . esc_html__( 'Indicators found:', 'eu-ai-act-ready' ) . '</strong><br>';
					foreach ( $detection['indicators'] as $indicator ) {
						echo '&#8226; ' . esc_html( $this->euaiactready_get_indicator_label( $indicator ) ) . '<br>';
					}
				}
			} elseif ( $confidence > 0 && $confidence < $threshold ) {
				echo '<strong>' . esc_html__( 'Note:', 'eu-ai-act-ready' ) . '</strong> ' . esc_html__( 'Some AI indicators found, but confidence is below threshold.', 'eu-ai-act-ready' ) . '<br>';
				echo '<em>' . esc_html__( 'Consider manually marking if you know this is AI-generated.', 'eu-ai-act-ready' ) . '</em>';
			} else {
				echo '<em>' . esc_html__( 'No AI indicators detected in filename, metadata, or image properties.', 'eu-ai-act-ready' ) . '</em>';
			}

			echo '</div>';
			echo '</div>';
		}

		echo wp_kses( $this->euaiactready_render_recheck_button( $attachment_id, 'metabox', $no_scan_done ), $this->euaiactready_allowed_html() );
	}

	/**
	 * Save AI meta data.
	 *
	 * @param int $post_id Attachment ID.
	 */
	public function euaiactready_save_ai_meta( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'attachment' !== get_post_type( $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST['euaiactready_media_nonce'] )
				? sanitize_text_field( wp_unslash( $_POST['euaiactready_media_nonce'] ) )
				: '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'euaiactready_media_meta' ) ) {
			return;
		}

		$ai_value = null;

		if ( isset( $_POST['attachments'][ $post_id ]['_euaiactready_ai_generated'] ) ) {
			$ai_value = sanitize_text_field( wp_unslash( $_POST['attachments'][ $post_id ]['_euaiactready_ai_generated'] ) );
		} elseif ( isset( $_POST['_euaiactready_ai_generated'] ) ) {
			$ai_value = sanitize_text_field( wp_unslash( $_POST['_euaiactready_ai_generated'] ) );
		}

		if ( null !== $ai_value ) {
			$this->euaiactready_handle_ai_marking( $post_id, $ai_value, 'Save' );
		}
	}

	/**
	 * AJAX handler for saving AI meta.
	 */
	public function euaiactready_ajax_save_ai_meta() {
		if ( ! isset( $_POST['id'] ) ) {
			return;
		}

		if ( ! isset( $_POST['euaiactready_media_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['euaiactready_media_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'euaiactready_media_meta' ) ) {
			return;
		}

		$post_id = absint( wp_unslash( $_POST['id'] ) );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$ai_value = null;

		if ( isset( $_POST['attachments'][ $post_id ]['_euaiactready_ai_generated'] ) ) {
			$ai_value = sanitize_text_field( wp_unslash( $_POST['attachments'][ $post_id ]['_euaiactready_ai_generated'] ) );
		} elseif ( isset( $_POST['_euaiactready_ai_generated'] ) ) {
			$ai_value = sanitize_text_field( wp_unslash( $_POST['_euaiactready_ai_generated'] ) );
		}

		if ( null !== $ai_value ) {
			$this->euaiactready_handle_ai_marking( $post_id, $ai_value, 'AJAX' );
		}
	}

	/**
	 * AJAX handler for re-checking AI detection.
	 */
	public function euaiactready_ajax_recheck_detection() {
		if ( ! isset( $_POST['nonce'], $_POST['attachment_id'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid request.', 'eu-ai-act-ready' ) ) );
		}

		$attachment_id = absint( wp_unslash( $_POST['attachment_id'] ) );
		$nonce         = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		if ( ! $attachment_id || ! wp_verify_nonce( $nonce, 'euaiactready_recheck_' . $attachment_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'eu-ai-act-ready' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to edit this attachment.', 'eu-ai-act-ready' ) ) );
		}

		$detection = $this->euaiactready_get_ai_detection_info( $attachment_id, true, 'manual_recheck' );

		if ( ! $detection ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Could not detect AI information for this attachment.', 'eu-ai-act-ready' ) ) );
		}

		$threshold          = (float) get_option( 'euaiactready_media_confidence_threshold', EUAIACTREADY_DEFAULT_MEDIA_CONFIDENCE_THRESHOLD );
		$confidence         = isset( $detection['confidence'] ) ? (float) $detection['confidence'] : 0;
		$confidence_percent = absint( round( $confidence * 100 ) );

		$is_ai = $this->euaiactready_update_ai_meta_from_detection( $attachment_id, $detection, $threshold );

		if ( ! $is_ai ) {
			$current_flag  = get_post_meta( $attachment_id, '_euaiactready_ai_generated', true );
			$marked_method = get_post_meta( $attachment_id, '_euaiactready_ai_marked_method', true );

			if ( '1' !== $current_flag || 'manual' !== $marked_method ) {
				update_post_meta( $attachment_id, '_euaiactready_ai_generated', '0' );
			}
		}

		$message = $is_ai
			? sprintf(
				/* translators: %s: Confidence percentage. */
				esc_html__( 'Detection complete! AI detected with %s%% confidence.', 'eu-ai-act-ready' ),
				esc_html( $confidence_percent )
			)
			: sprintf(
				/* translators: %s: Confidence percentage. */
				esc_html__( 'Detection complete! Confidence is %s%%, below threshold.', 'eu-ai-act-ready' ),
				esc_html( $confidence_percent )
			);

		wp_send_json_success(
			array(
				'message'    => $message,
				'confidence' => $confidence_percent,
				'is_ai'      => $is_ai,
			)
		);
	}

	/**
	 * Auto-detect AI on image upload.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function euaiactready_auto_detect_on_upload( $attachment_id ) {
		$attachment_id = absint( $attachment_id );

		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		$detection = $this->euaiactready_get_ai_detection_info( $attachment_id, true, 'upload' );

		if ( ! $detection ) {
			return;
		}

		$threshold = (float) get_option( 'euaiactready_media_confidence_threshold', EUAIACTREADY_DEFAULT_MEDIA_CONFIDENCE_THRESHOLD );

		$this->euaiactready_update_ai_meta_from_detection( $attachment_id, $detection, $threshold );
	}

	/**
	 * Add AI checkbox field to attachment popup.
	 *
	 * @param array   $fields Attachment fields.
	 * @param WP_Post $post Attachment post object.
	 * @return array Modified fields.
	 */
	public function euaiactready_add_ai_attachment_field( $fields, $post ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'attachment' === $screen->id ) {
			return $fields;
		}

		$attachment_id = absint( $post->ID );

		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			$fields['euaiactready_ai_generated'] = array(
				'label' => __( 'AI Generated', 'eu-ai-act-ready' ),
				'input' => 'html',
				'html'  => $this->euaiactready_render_not_eligible_message( $attachment_id, 'popup' ),
				'helps' => '',
			);

			return $fields;
		}

		$value         = get_post_meta( $attachment_id, '_euaiactready_ai_generated', true );
		$marked_method = get_post_meta( $attachment_id, '_euaiactready_ai_marked_method', true );
		$is_checked    = ( '1' === $value );

		$detection_source = get_post_meta( $attachment_id, '_euaiactready_ai_detection_source', true );
		$no_scan_done     = empty( $detection_source );
		$confidence       = 0;
		$methods          = array();
		$source_text      = __( 'No AI detected', 'eu-ai-act-ready' );

		if ( ! $no_scan_done ) {
			$detection_info = json_decode( $detection_source, true );
			if ( $detection_info && isset( $detection_info['confidence'] ) ) {
				$confidence  = (float) $detection_info['confidence'];
				$methods     = isset( $detection_info['indicators'] ) && is_array( $detection_info['indicators'] ) ? $detection_info['indicators'] : array();
				$source_text = isset( $detection_info['source'] ) ? wp_strip_all_tags( $detection_info['source'] ) : __( 'AI Detection', 'eu-ai-act-ready' );
			}
		}

		$threshold          = (float) get_option( 'euaiactready_media_confidence_threshold', EUAIACTREADY_DEFAULT_MEDIA_CONFIDENCE_THRESHOLD );
		$confidence_percent = absint( round( $confidence * 100 ) );
		$threshold_percent  = absint( round( $threshold * 100 ) );

		$status       = $this->euaiactready_get_status_info( $confidence, $threshold );
		$status_icon  = isset( $status['icon'] ) ? wp_kses_post( $status['icon'] ) : '';
		$status_text  = isset( $status['text'] ) ? esc_html( $status['text'] ) : '';
		$status_class = isset( $status['class'] ) ? sanitize_html_class( $status['class'] ) : 'low';
		$bar_class    = sanitize_html_class( $confidence >= $threshold ? 'high' : ( $confidence > 0 ? 'medium' : 'low' ) );

		ob_start();
		?>
		<div class="ai-media-detection-container">
			<label class="ai-media-detection-label-popup">
				<input type="checkbox" name="attachments[<?php echo esc_attr( $attachment_id ); ?>][_euaiactready_ai_generated]" id="attachments-<?php echo esc_attr( $attachment_id ); ?>-_euaiactready_ai_generated" value="1" <?php checked( $is_checked ); ?> />
				<strong><?php esc_html_e( 'Mark as AI-Generated', 'eu-ai-act-ready' ); ?></strong>
			</label>
			<p class="ai-media-detection-note">
				<?php esc_html_e( 'Check this box to manually mark this image as AI-generated. This will display transparency labels on the frontend.', 'eu-ai-act-ready' ); ?>
			</p>

			<?php if ( $no_scan_done ) : ?>
				<div class="ai-media-status-box status-info">
					<div class="ai-media-status-header">
						<strong>
							<?php esc_html_e( 'No Scan Performed Yet', 'eu-ai-act-ready' ); ?>
						</strong>
					</div>
					<div class="ai-media-detection-details">
						<p><em><?php esc_html_e( 'This image has not been scanned for AI indicators yet. Click the "Re-check AI Detection" button below to scan this image.', 'eu-ai-act-ready' ); ?></em></p>
					</div>
				</div>
			<?php else : ?>
				<div class="ai-media-status-box status-<?php echo esc_attr( $status_class ); ?>">
					<div class="ai-media-status-header">
						<strong>
							<?php if ( $status_icon ) : ?>
								<?php echo wp_kses_post( $status_icon ); ?>
							<?php endif; ?>
							<?php echo esc_html( $status_text ); ?>
						</strong>
						<span class="ai-media-confidence-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $confidence_percent ); ?>%</span>
					</div>

					<div class="ai-media-confidence-bar-container">
						<div class="ai-media-confidence-bar <?php echo esc_attr( $bar_class ); ?>" style="width: <?php echo esc_attr( $confidence_percent ); ?>%;"></div>
					</div>

					<div class="ai-media-detection-details">
						<strong><?php esc_html_e( 'Confidence:', 'eu-ai-act-ready' ); ?></strong> <?php echo esc_html( $confidence_percent ); ?>% |
						<strong><?php esc_html_e( 'Threshold:', 'eu-ai-act-ready' ); ?></strong> <?php echo esc_html( $threshold_percent ); ?>%<br>

						<?php if ( $confidence > 0 ) : ?>
							<strong><?php esc_html_e( 'Source:', 'eu-ai-act-ready' ); ?></strong> <?php echo esc_html( $source_text ); ?><br>
						<?php endif; ?>

						<?php if ( $is_checked && ! empty( $marked_method ) ) : ?>
							<?php if ( 'manual' === $marked_method ) : ?>
								<strong><?php esc_html_e( 'Marking:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Manually marked', 'eu-ai-act-ready' ); ?><br>
							<?php elseif ( 'auto' === $marked_method ) : ?>
								<strong><?php esc_html_e( 'Marking:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Auto-detected', 'eu-ai-act-ready' ); ?><br>
							<?php endif; ?>
						<?php endif; ?>

						<?php if ( ! empty( $methods ) ) : ?>
							<strong><?php esc_html_e( 'Indicators found:', 'eu-ai-act-ready' ); ?></strong><br>
							<?php foreach ( $methods as $indicator ) : ?>
								&#8226; <?php echo esc_html( $this->euaiactready_get_indicator_label( $indicator ) ); ?><br>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php echo wp_kses( $this->euaiactready_render_recheck_button( $attachment_id, 'popup' ), $this->euaiactready_allowed_html() ); ?>
		</div>
		<?php
		$html = ob_get_clean();

		$fields['euaiactready_ai_generated'] = array(
			'label' => __( 'AI Generated', 'eu-ai-act-ready' ),
			'input' => 'html',
			'html'  => $html,
			'helps' => '',
		);

		return $fields;
	}

	/**
	 * Save AI field from attachment fields.
	 *
	 * @param array $post Post data.
	 * @param array $attachment Attachment fields.
	 * @return array Modified post data.
	 */
	public function euaiactready_save_ai_attachment_field( $post, $attachment ) {
		$post_id = isset( $post['ID'] ) ? absint( $post['ID'] ) : 0;

		if ( ! $post_id ) {
			return $post;
		}

		if ( isset( $attachment['_euaiactready_ai_generated'] ) ) {
			$value = sanitize_text_field( wp_unslash( $attachment['_euaiactready_ai_generated'] ) );
		} else {
			$value = '0';
		}

		$this->euaiactready_handle_ai_marking( $post_id, $value, 'Attachment field' );

		return $post;
	}

	/**
	 * Add filter dropdown to media library.
	 */
	public function euaiactready_add_media_filter() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'upload' === $screen->id ) {
			$nonce_field = wp_nonce_field( 'euaiactready_media_filter', 'euaiactready_media_filter_nonce', true, false );
			echo wp_kses_post( $nonce_field );

			$current = isset( $_GET['ai_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['ai_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Filter select is read-only.

			echo '<select name="ai_filter">';
			echo '<option value="">' . esc_html__( 'All Media', 'eu-ai-act-ready' ) . '</option>';
			echo '<option value="ai_only" ' . selected( $current, 'ai_only', false ) . '>' . esc_html__( 'AI-Generated Only', 'eu-ai-act-ready' ) . '</option>';
			echo '<option value="no_ai" ' . selected( $current, 'no_ai', false ) . '>' . esc_html__( 'No AI', 'eu-ai-act-ready' ) . '</option>';
			echo '</select>';
		}
	}

	/**
	 * Filter media query by AI status.
	 *
	 * @param array $query Query args.
	 * @return array Modified query args.
	 */
	public function euaiactready_filter_media_query( $query ) {
		if ( ! isset( $_REQUEST['euaiactready_media_filter_nonce'] ) ) {
			return $query;
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['euaiactready_media_filter_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'euaiactready_media_filter' ) ) {
			return $query;
		}

		if ( isset( $_REQUEST['ai_filter'] ) && '' !== $_REQUEST['ai_filter'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Nonce verified above.
			$filter = sanitize_text_field( wp_unslash( $_REQUEST['ai_filter'] ) );

			if ( 'ai_only' === $filter ) {
				$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional filter on attachment meta.
					array(
						'key'     => '_euaiactready_ai_generated',
						'value'   => '1',
						'compare' => '=',
					),
				);
			}
		}

		return $query;
	}

	/**
	 * Get allowed HTML for AI label rendering.
	 *
	 * @return array Allowed HTML tags and attributes.
	 */
	public function euaiactready_allowed_html() {
		return array(
			'button' => array(
				'type'     => true,
				'class'    => true,
				'id'       => true,
				'name'     => true,
				'value'    => true,
				'data-*'   => true,
				'aria-*'   => true,
				'disabled' => true,
				'onclick'  => true,
			),
			'span'   => array(
				'class'  => true,
				'id'     => true,
				'data-*' => true,
				'aria-*' => true,
			),
			'div'    => array(
				'class'  => true,
				'id'     => true,
				'data-*' => true,
				'aria-*' => true,
			),
		);
	}
}
