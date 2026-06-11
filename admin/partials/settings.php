<?php
/**
 * EU AI Act Ready - Settings Page
 *
 * @package EUAIACTREADY
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get active tab from URL parameter first.
$euaiactready_active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'transparency';

// Handle settings update.
$euaiactready_settings_saved = false;
if ( isset( $_POST['save_settings'] ) && check_admin_referer( 'euaiactready_settings' ) ) {
	$euaiactready_post_data = wp_unslash( $_POST );

	// Get the active tab from POST.
	$euaiactready_active_tab = isset( $euaiactready_post_data['active_tab'] ) ? sanitize_text_field( $euaiactready_post_data['active_tab'] ) : 'transparency';

	// Transparency settings.
	update_option( 'euaiactready_transparency_enabled', ! empty( $euaiactready_post_data['transparency_enabled'] ) ? 1 : 0 );
	if ( isset( $euaiactready_post_data['notice_style'] ) ) {
		update_option( 'euaiactready_notice_style', sanitize_text_field( $euaiactready_post_data['notice_style'] ) );
	}
	if ( isset( $euaiactready_post_data['notice_message'] ) ) {
		update_option( 'euaiactready_notice_message', sanitize_textarea_field( $euaiactready_post_data['notice_message'] ) );
	}
	update_option( 'euaiactready_show_in_excerpts', ! empty( $euaiactready_post_data['show_in_excerpts'] ) ? 1 : 0 );

	// Chatbot transparency settings.
	update_option( 'euaiactready_chatbot_transparency', ! empty( $euaiactready_post_data['chatbot_transparency'] ) ? 1 : 0 );
	if ( isset( $euaiactready_post_data['chatbot_platform'] ) ) {
		update_option( 'euaiactready_chatbot_platform', sanitize_text_field( $euaiactready_post_data['chatbot_platform'] ) );
	}
	if ( isset( $euaiactready_post_data['chatbot_notice_style'] ) ) {
		update_option( 'euaiactready_chatbot_notice_style', sanitize_text_field( $euaiactready_post_data['chatbot_notice_style'] ) );
	}
	if ( isset( $euaiactready_post_data['chatbot_notice_message'] ) ) {
		update_option( 'euaiactready_chatbot_notice_message', sanitize_textarea_field( $euaiactready_post_data['chatbot_notice_message'] ) );
	}

	// Media transparency settings.
	update_option( 'euaiactready_media_transparency', ! empty( $euaiactready_post_data['media_transparency'] ) ? 1 : 0 );
	if ( isset( $euaiactready_post_data['media_label_style'] ) ) {
		update_option( 'euaiactready_media_label_style', sanitize_text_field( $euaiactready_post_data['media_label_style'] ) );
	}
	if ( isset( $euaiactready_post_data['media_confidence_threshold'] ) ) {
		update_option( 'euaiactready_media_confidence_threshold', floatval( $euaiactready_post_data['media_confidence_threshold'] ) );
	}

	// Enabled post types for AI content disclosure.
	$euaiactready_raw_types    = isset( $euaiactready_post_data['enabled_post_types'] ) && is_array( $euaiactready_post_data['enabled_post_types'] )
		? $euaiactready_post_data['enabled_post_types']
		: array();
	$euaiactready_saved_types  = array_values( array_filter( array_map( 'sanitize_key', $euaiactready_raw_types ) ) );
	update_option( 'euaiactready_enabled_post_types', $euaiactready_saved_types );

	$euaiactready_settings_saved = true;
}

// Get current settings.
$euaiactready_enabled_post_types = get_option( 'euaiactready_enabled_post_types', array( 'post', 'page' ) );
if ( ! is_array( $euaiactready_enabled_post_types ) ) {
	$euaiactready_enabled_post_types = array( 'post', 'page' );
}

$euaiactready_transparency_enabled = get_option( 'euaiactready_transparency_enabled', true );
$euaiactready_notice_style         = get_option( 'euaiactready_notice_style', EUAIACTREADY_DEFAULT_NOTICE_STYLE );
$euaiactready_notice_message       = sanitize_text_field( get_option( 'euaiactready_notice_message', '' ) );
$euaiactready_show_in_excerpts     = get_option( 'euaiactready_show_in_excerpts', true );

// Chatbot settings.
$euaiactready_chatbot_transparency   = get_option( 'euaiactready_chatbot_transparency', true );
$euaiactready_chatbot_platform       = get_option( 'euaiactready_chatbot_platform', EUAIACTREADY_DEFAULT_CHATBOT_PLATFORM );
$euaiactready_chatbot_notice_style   = get_option( 'euaiactready_chatbot_notice_style', EUAIACTREADY_DEFAULT_CHATBOT_NOTICE_STYLE );
$euaiactready_chatbot_notice_message = sanitize_text_field( get_option( 'euaiactready_chatbot_notice_message', '' ) );

// Media transparency settings.
$euaiactready_media_transparency         = get_option( 'euaiactready_media_transparency', true );
$euaiactready_media_label_style          = get_option( 'euaiactready_media_label_style', EUAIACTREADY_DEFAULT_MEDIA_LABEL_STYLE );
$euaiactready_media_confidence_threshold = get_option( 'euaiactready_media_confidence_threshold', EUAIACTREADY_DEFAULT_MEDIA_CONFIDENCE_THRESHOLD );

$euaiactready_tab_definitions = array(
	'transparency' => __( 'Content Transparency', 'eu-ai-act-ready' ),
	'media'        => __( 'Media/Image Labels', 'eu-ai-act-ready' ),
	'chatbot'      => __( 'Chatbot Transparency', 'eu-ai-act-ready' ),
);
?>

<div class="wrap euaiactready-settings">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( $euaiactready_settings_saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'eu-ai-act-ready' ); ?></p>
		</div>
	<?php endif; ?>

	<h2 class="nav-tab-wrapper">
		<?php foreach ( $euaiactready_tab_definitions as $euaiactready_tab_key => $euaiactready_tab_label ) : ?>
			<?php
			$euaiactready_tab_url     = add_query_arg(
				array(
					'page' => 'eu-ai-act-ready-settings',
					'tab'  => $euaiactready_tab_key,
				),
				admin_url( 'admin.php' )
			);
			$euaiactready_tab_classes = 'nav-tab';
			if ( $euaiactready_tab_key === $euaiactready_active_tab ) {
				$euaiactready_tab_classes .= ' nav-tab-active';
			}
			?>
			<a href="<?php echo esc_url( $euaiactready_tab_url ); ?>" class="<?php echo esc_attr( $euaiactready_tab_classes ); ?>"><?php echo esc_html( $euaiactready_tab_label ); ?></a>
		<?php endforeach; ?>
	</h2>

	<form method="post" action="">
		<?php wp_nonce_field( 'euaiactready_settings' ); ?>
		<input type="hidden" id="active_tab_field" name="active_tab" value="<?php echo esc_attr( $euaiactready_active_tab ); ?>" />

		<!-- Transparency Settings Tab -->
		<div id="transparency-tab" class="tab-content" style="<?php echo esc_attr( 'transparency' === $euaiactready_active_tab ? '' : 'display:none;' ); ?>">
			<h3><?php esc_html_e( 'Content Transparency', 'eu-ai-act-ready' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Display transparency notices on any content marked as AI-generated.', 'eu-ai-act-ready' ); ?></p>

			<!-- How It Works Section -->
			<div class="how-it-works-section">
				<h4><?php esc_html_e( 'How It Works', 'eu-ai-act-ready' ); ?></h4>
				<p><?php esc_html_e( 'To display transparency notices, you must manually mark your content as AI-generated:', 'eu-ai-act-ready' ); ?></p>
				<ol>
					<li><strong><?php esc_html_e( 'Edit Content:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Open the content item you want to label in the WordPress editor.', 'eu-ai-act-ready' ); ?></li>
					<li><strong><?php esc_html_e( 'Mark as AI:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Look for the "EU AI Act Ready" box in the sidebar (or use Quick Edit) and check "Mark as AI-Generated".', 'eu-ai-act-ready' ); ?></li>
					<li><strong><?php esc_html_e( 'Frontend Display:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'The transparency notice/badge will now appear on that specific content item.', 'eu-ai-act-ready' ); ?></li>
				</ol>

				<div class="warning-box">
					<p><strong><?php esc_html_e( 'Important:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Notices only appear on content explicitly marked as AI. They do not appear automatically.', 'eu-ai-act-ready' ); ?></p>
				</div>
			</div>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="transparency_enabled"><?php esc_html_e( 'Enable Transparency Notices', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
					<input type="checkbox" id="transparency_enabled" name="transparency_enabled" value="1" <?php checked( $euaiactready_transparency_enabled, true ); ?>>
						<p class="description"><?php esc_html_e( 'Automatically display transparency notices on content marked as AI-generated.', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="notice_style"><?php esc_html_e( 'Notice Style', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
						<select id="notice_style" name="notice_style">
						<option value="banner" <?php selected( $euaiactready_notice_style, 'banner' ); ?>><?php esc_html_e( 'Banner (Full Width)', 'eu-ai-act-ready' ); ?></option>
						<option value="inline" <?php selected( $euaiactready_notice_style, 'inline' ); ?>><?php esc_html_e( 'Inline (Subtle)', 'eu-ai-act-ready' ); ?></option>
						<option value="badge" <?php selected( $euaiactready_notice_style, 'badge' ); ?>><?php esc_html_e( 'Badge (Minimal)', 'eu-ai-act-ready' ); ?></option>
						<option value="modal" <?php selected( $euaiactready_notice_style, 'modal' ); ?>><?php esc_html_e( 'Modal (Click to View)', 'eu-ai-act-ready' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose how transparency notices are displayed.', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="notice_message"><?php esc_html_e( 'Custom Notice Message', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
					<textarea id="notice_message" name="notice_message" rows="3" class="large-text"><?php echo esc_textarea( $euaiactready_notice_message ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Leave empty to use the default message. You can customize the transparency notice text.', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="show_in_excerpts"><?php esc_html_e( 'Show in Excerpts', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
					<input type="checkbox" id="show_in_excerpts" name="show_in_excerpts" value="1" <?php checked( $euaiactready_show_in_excerpts, true ); ?>>
						<p class="description"><?php esc_html_e( 'Add AI badge to post excerpts (archives, search results).', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Content Types', 'eu-ai-act-ready' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Select which post types should have the AI Content Disclosure meta box, list column, and bulk actions.', 'eu-ai-act-ready' ); ?></p>

			<?php
			$euaiactready_public_post_types = get_post_types( array( 'public' => true ), 'objects' );
			// Exclude attachment — it is handled by the Media/Image Labels section.
			unset( $euaiactready_public_post_types['attachment'] );
			?>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled Post Types', 'eu-ai-act-ready' ); ?></th>
					<td>
						<?php if ( empty( $euaiactready_public_post_types ) ) : ?>
							<p class="description"><?php esc_html_e( 'No public post types found.', 'eu-ai-act-ready' ); ?></p>
						<?php else : ?>
							<?php foreach ( $euaiactready_public_post_types as $euaiactready_pt_slug => $euaiactready_pt_obj ) : ?>
								<?php
								$euaiactready_pt_label   = $euaiactready_pt_obj->labels->singular_name;
								$euaiactready_pt_checked = in_array( $euaiactready_pt_slug, $euaiactready_enabled_post_types, true );
								?>
								<label style="display:block; margin-bottom:6px;">
									<input type="checkbox"
										name="enabled_post_types[]"
										value="<?php echo esc_attr( $euaiactready_pt_slug ); ?>"
										<?php checked( $euaiactready_pt_checked ); ?>>
									<strong><?php echo esc_html( $euaiactready_pt_label ); ?></strong>
									<code style="font-size:11px; color:#666;"><?php echo esc_html( $euaiactready_pt_slug ); ?></code>
								</label>
							<?php endforeach; ?>
							<p class="description" style="margin-top:8px;">
								<?php esc_html_e( 'Posts and pages are enabled by default. Custom post types from installed themes and plugins appear here once activated.', 'eu-ai-act-ready' ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="save_settings" class="button button-primary">
					<?php esc_html_e( 'Save Settings', 'eu-ai-act-ready' ); ?>
				</button>
			</p>



			<h4><?php esc_html_e( 'Notice Style Examples', 'eu-ai-act-ready' ); ?></h4>
			<div class="notice-preview-container">
				<div class="notice-preview-grid">

					<!-- Banner Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Banner (Full Width):', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<div class="ai-transparency-banner-preview">
								<span class="ai-preview-icon">
									<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 18, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								</span>
							<strong><?php esc_html_e( 'AI Disclosure:', 'eu-ai-act-ready' ); ?></strong>&nbsp;<span id="banner-preview-text"><?php echo ! empty( $euaiactready_notice_message ) ? esc_html( $euaiactready_notice_message ) : esc_html__( 'This content includes AI-generated text.', 'eu-ai-act-ready' ); ?></span>
							</div>
						</div>
					</div>

					<!-- Inline Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Inline (Subtle):', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<p class="ai-transparency-inline-preview">
								<span class="ai-preview-icon">
									<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 16, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								</span>
							<strong><?php esc_html_e( 'AI Disclosure:', 'eu-ai-act-ready' ); ?></strong>&nbsp;<em><span id="inline-preview-text"><?php echo ! empty( $euaiactready_notice_message ) ? esc_html( $euaiactready_notice_message ) : esc_html__( 'This content includes AI-generated text.', 'eu-ai-act-ready' ); ?></span></em>
							</p>
						</div>
					</div>

					<!-- Badge Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Badge (Minimal):', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<span class="ai-transparency-badge-preview">
								<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 14, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								<?php esc_html_e( 'AI Disclosure', 'eu-ai-act-ready' ); ?>
							</span>
						</div>
					</div>

					<!-- Modal Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Modal (Click to View):', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<button class="ai-transparency-modal-trigger-preview" type="button">
								<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 14, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								<?php esc_html_e( 'AI Disclosure', 'eu-ai-act-ready' ); ?>
							</button>
						</div>
					</div>

				</div>
			</div>
		</div>

		<!-- Media/Image Transparency Tab -->
		<div id="media-tab" class="tab-content" style="<?php echo esc_attr( 'media' === $euaiactready_active_tab ? '' : 'display:none;' ); ?>">
			<h3><?php esc_html_e( 'AI Media & Image Transparency', 'eu-ai-act-ready' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Automatically detect and label AI-generated images, photos, and media in your posts.', 'eu-ai-act-ready' ); ?></p>

			<!-- How It Works Section -->
			<div class="how-it-works-section">
				<h4><?php esc_html_e( 'How It Works', 'eu-ai-act-ready' ); ?></h4>
				<p><?php esc_html_e( 'To manage EU AI Act Ready for images, the plugin integrates directly with your Media Library.', 'eu-ai-act-ready' ); ?></p>
				<ol>
					<li><strong><?php esc_html_e( 'Upload or Scan:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Upload new images (auto-scanned) or use the Dashboard scan for existing detection.', 'eu-ai-act-ready' ); ?></li>
					<li><strong><?php esc_html_e( 'Review Detection:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Check the "AI" column in the Media Library for confidence scores or status.', 'eu-ai-act-ready' ); ?></li>
					<li><strong><?php esc_html_e( 'Manual Control:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Use the "AI Meta Box" when editing an image to manually mark/unmark if detection is incorrect.', 'eu-ai-act-ready' ); ?></li>
					<li><strong><?php esc_html_e( 'Frontend Display:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Labels will appear on images identified or manually marked as AI-generated, based on your settings below.', 'eu-ai-act-ready' ); ?></li>
				</ol>

				<div class="warning-box">
					<p><strong><?php esc_html_e( 'Note:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Detection is automatic but not perfect and may produce false positives or misses.. For best results, use consistent naming (e.g., "midjourney", "dall-e") or manually mark images.', 'eu-ai-act-ready' ); ?></p>
				</div>
			</div>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="media_transparency"><?php esc_html_e( 'Enable Media Transparency', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
					<input type="checkbox" id="media_transparency" name="media_transparency" value="1" <?php checked( $euaiactready_media_transparency, true ); ?>>
						<p class="description"><?php esc_html_e( 'Automatically add labels to AI-generated images and media in posts.', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="media_label_style"><?php esc_html_e( 'Label Style', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
						<select id="media_label_style" name="media_label_style">
						<option value="caption" <?php selected( $euaiactready_media_label_style, 'caption' ); ?>><?php esc_html_e( 'Caption', 'eu-ai-act-ready' ); ?></option>
						<option value="badge" <?php selected( $euaiactready_media_label_style, 'badge' ); ?>><?php esc_html_e( 'Badge', 'eu-ai-act-ready' ); ?></option>
						<option value="overlay" <?php selected( $euaiactready_media_label_style, 'overlay' ); ?>><?php esc_html_e( 'Overlay', 'eu-ai-act-ready' ); ?></option>
						<option value="border" <?php selected( $euaiactready_media_label_style, 'border' ); ?>><?php esc_html_e( 'Border', 'eu-ai-act-ready' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose how AI labels appear on images.', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="media_confidence_threshold"><?php esc_html_e( 'Detection Sensitivity', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
						<select id="media_confidence_threshold" name="media_confidence_threshold">
							<option value="0.15" <?php selected( $euaiactready_media_confidence_threshold, 0.15 ); ?>><?php esc_html_e( 'Very Aggressive (15% confidence)', 'eu-ai-act-ready' ); ?></option>
							<option value="0.25" <?php selected( $euaiactready_media_confidence_threshold, 0.25 ); ?>><?php esc_html_e( 'Aggressive (25% confidence)', 'eu-ai-act-ready' ); ?></option>
							<option value="0.3" <?php selected( $euaiactready_media_confidence_threshold, 0.3 ); ?>><?php esc_html_e( 'Balanced (30% confidence)', 'eu-ai-act-ready' ); ?></option>
							<option value="0.4" <?php selected( $euaiactready_media_confidence_threshold, 0.4 ); ?>><?php esc_html_e( 'Conservative (40% confidence) - Recommended', 'eu-ai-act-ready' ); ?></option>
							<option value="0.5" <?php selected( $euaiactready_media_confidence_threshold, 0.5 ); ?>><?php esc_html_e( 'Very Conservative (50% confidence)', 'eu-ai-act-ready' ); ?></option>
							<option value="0.7" <?php selected( $euaiactready_media_confidence_threshold, 0.7 ); ?>><?php esc_html_e( 'Strict (70% confidence)', 'eu-ai-act-ready' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Controls how confident the system needs to be before flagging an image as AI-generated.', 'eu-ai-act-ready' ); ?><br>
							<strong><?php esc_html_e( 'Lower values:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'More images flagged, possible false positives', 'eu-ai-act-ready' ); ?><br>
							<strong><?php esc_html_e( 'Higher values:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Only very confident detections, may miss some AI images', 'eu-ai-act-ready' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="save_settings" class="button button-primary">
					<?php esc_html_e( 'Save Settings', 'eu-ai-act-ready' ); ?>
				</button>
			</p>

			<hr>

			<h4><?php esc_html_e( 'Label Style Examples', 'eu-ai-act-ready' ); ?></h4>
			<div class="media-label-examples-container">
				<div class="media-label-examples-grid">

					<!-- Caption Style -->
					<div class="media-label-example-item">
					<h5><?php esc_html_e( 'Caption Style (Recommended):', 'eu-ai-act-ready' ); ?></h5>
					<div class="media-example-box">
						<div class="media-example-image">
							<?php esc_html_e( 'Sample Image', 'eu-ai-act-ready' ); ?>
						</div>
						<div class="media-caption-label">
							<span>
								<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 18 ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								<strong><?php esc_html_e( 'AI-Generated Image', 'eu-ai-act-ready' ); ?></strong>
								</span>
							</div>
						</div>
					</div>

					<!-- Badge Style -->
					<div class="media-label-example-item">
					<h5><?php esc_html_e( 'Badge Style:', 'eu-ai-act-ready' ); ?></h5>
					<div class="media-example-box">
						<div class="media-example-image">
							<?php esc_html_e( 'Sample Image', 'eu-ai-act-ready' ); ?>
							<div class="media-badge-label">
								<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 18, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								<span><?php esc_html_e( 'AI-Generated Image', 'eu-ai-act-ready' ); ?></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Overlay Style -->
					<div class="media-label-example-item">
					<h5><?php esc_html_e( 'Overlay Style:', 'eu-ai-act-ready' ); ?></h5>
					<div class="media-example-box">
						<div class="media-example-image-with-overflow">
							<?php esc_html_e( 'Sample Image', 'eu-ai-act-ready' ); ?>
							<!-- Overlay gradient -->
							<div class="media-overlay-gradient"></div>
							<!-- Badge on overlay -->
							<div class="media-overlay-badge">
								<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 18, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								<span><?php esc_html_e( 'AI-Generated Image', 'eu-ai-act-ready' ); ?></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Border Style -->
					<div class="media-label-example-item">
					<h5><?php esc_html_e( 'Border Style:', 'eu-ai-act-ready' ); ?></h5>
					<div class="media-border-example-wrapper">
						<div class="media-border-frame">
							<div class="media-border-image">
								<?php esc_html_e( 'Sample Image', 'eu-ai-act-ready' ); ?>
							</div>
							<div class="media-border-label">
								<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 18, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								<?php esc_html_e( 'AI-Generated Image', 'eu-ai-act-ready' ); ?>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>

		<!-- Chatbot Transparency Tab -->
		<div id="chatbot-tab" class="tab-content" style="<?php echo esc_attr( 'chatbot' === $euaiactready_active_tab ? '' : 'display:none;' ); ?>">
			<h3><?php esc_html_e( 'Chatbot AI Transparency Settings', 'eu-ai-act-ready' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Configure transparency notices for AI-powered chatbots on your website (Formilla, Intercom, Drift, etc.).', 'eu-ai-act-ready' ); ?></p>

			<!-- How It Works Section -->
			<div class="how-it-works-section">
				<h4><?php esc_html_e( 'How It Works', 'eu-ai-act-ready' ); ?></h4>
				<p><?php esc_html_e( 'The plugin detects supported AI chatbot widgets and adds transparency notices to ensure compliance.', 'eu-ai-act-ready' ); ?></p>
				<ol>
					<li><strong><?php esc_html_e( 'Select Platform:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Choose your chatbot provider (e.g., Formilla, Intercom) from the list below.', 'eu-ai-act-ready' ); ?></li>
					<li><strong><?php esc_html_e( 'Choose Style:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Pick a notice style (Badge, Banner, etc.) that matches your site design.', 'eu-ai-act-ready' ); ?></li>
					<li><strong><?php esc_html_e( 'Auto-Insertion:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'The plugin detects the chat widget on the frontend and appends the compliance notice.', 'eu-ai-act-ready' ); ?></li>
				</ol>

				<div class="warning-box">
					<p><strong><?php esc_html_e( 'Note:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'The notice relies on the chatbot loading correctly. If you don\'t see it, try clearing your cache or checking the browser console.', 'eu-ai-act-ready' ); ?></p>
				</div>
			</div>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="chatbot_transparency"><?php esc_html_e( 'Enable Chatbot Transparency', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
					<input type="checkbox" id="chatbot_transparency" name="chatbot_transparency" value="1" <?php checked( $euaiactready_chatbot_transparency, true ); ?>>
						<p class="description">
							<?php esc_html_e( 'Display transparency notices when users interact with AI-powered chatbots.', 'eu-ai-act-ready' ); ?><br>
							<strong><?php esc_html_e( 'Note:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Only enable this if you have an active chatbot plugin installed. Selecting a platform without having the chatbot will load unnecessary JavaScript.', 'eu-ai-act-ready' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="chatbot_platform"><?php esc_html_e( 'Chatbot Platform', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
						<select id="chatbot_platform" name="chatbot_platform">
							<option value="formilla" <?php selected( $euaiactready_chatbot_platform, 'formilla' ); ?>>Formilla</option>
							<option value="intercom" <?php selected( $euaiactready_chatbot_platform, 'intercom' ); ?>>Intercom</option>
							<option value="drift" <?php selected( $euaiactready_chatbot_platform, 'drift' ); ?>>Drift</option>
							<option value="tidio" <?php selected( $euaiactready_chatbot_platform, 'tidio' ); ?>>Tidio</option>
							<option value="tawk" <?php selected( $euaiactready_chatbot_platform, 'tawk' ); ?>>Tawk.to</option>
							<option value="zendesk" <?php selected( $euaiactready_chatbot_platform, 'zendesk' ); ?>>Zendesk Chat</option>
							<option value="livechat" <?php selected( $euaiactready_chatbot_platform, 'livechat' ); ?>>LiveChat</option>
							<option value="crisp" <?php selected( $euaiactready_chatbot_platform, 'crisp' ); ?>>Crisp</option>
							<option value="freshchat" <?php selected( $euaiactready_chatbot_platform, 'freshchat' ); ?>>Freshchat</option>

						</select>
						<p class="description"><?php esc_html_e( 'Select your chatbot platform. The plugin will automatically detect and position the notice.', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="chatbot_notice_style"><?php esc_html_e( 'Chatbot Notice Style', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
						<select id="chatbot_notice_style" name="chatbot_notice_style">
						<option value="banner" <?php selected( $euaiactready_chatbot_notice_style, 'banner' ); ?>><?php esc_html_e( 'Banner (Prominent)', 'eu-ai-act-ready' ); ?></option>
						<option value="inline" <?php selected( $euaiactready_chatbot_notice_style, 'inline' ); ?>><?php esc_html_e( 'Inline Message', 'eu-ai-act-ready' ); ?></option>
						<option value="badge" <?php selected( $euaiactready_chatbot_notice_style, 'badge' ); ?>><?php esc_html_e( 'Badge (Recommended)', 'eu-ai-act-ready' ); ?></option>
						<option value="modal" <?php selected( $euaiactready_chatbot_notice_style, 'modal' ); ?>><?php esc_html_e( 'Modal (Click to View)', 'eu-ai-act-ready' ); ?></option>
						<option value="tooltip" <?php selected( $euaiactready_chatbot_notice_style, 'tooltip' ); ?>><?php esc_html_e( 'Tooltip (Hover)', 'eu-ai-act-ready' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Badge style is recommended for chatbots as it\'s subtle yet visible.', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="chatbot_notice_message"><?php esc_html_e( 'Custom Chatbot Message', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
					<textarea id="chatbot_notice_message" name="chatbot_notice_message" rows="3" class="large-text"><?php echo esc_textarea( $euaiactready_chatbot_notice_message ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Default: "This chat uses AI assistance."', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="save_settings" class="button button-primary">
					<?php esc_html_e( 'Save Settings', 'eu-ai-act-ready' ); ?>
				</button>
			</p>

			<h4><?php esc_html_e( 'Chatbot Notice Style Examples', 'eu-ai-act-ready' ); ?></h4>
			<div class="notice-preview-container">
				<div class="notice-preview-grid chatbot-preview-grid-horizontal">

					<!-- Banner Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Banner (Prominent):', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<div class="chatbot-preview-wrapper">
								<div class="chatbot-widget-icon">&#128172;</div>
								<div id="chatbot-preview-banner" class="chatbot-banner-preview">
									<div class="banner-content">
										<span class="ai-preview-icon">
											<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 18, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
										</span>
									<span class="chatbot-preview-message"><?php esc_html_e( 'This chat uses AI assistance.', 'eu-ai-act-ready' ); ?></span>
										<button class="close-button">&times;</button>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Inline Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Inline Message:', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<div class="chatbot-preview-wrapper">
								<div class="chatbot-widget-icon">&#128172;</div>
								<div id="chatbot-preview-inline" class="chatbot-inline-preview">
									<span class="ai-preview-icon chatbot-icon">
										<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 16, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
									</span>
								<span class="chatbot-preview-message"><?php esc_html_e( 'This chat uses AI assistance.', 'eu-ai-act-ready' ); ?></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Badge Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Badge (Recommended):', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<div class="chatbot-preview-wrapper">
								<div class="chatbot-widget-icon">&#128172;</div>
								<div id="chatbot-preview-badge" class="chatbot-badge-preview">
									<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 14, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								<span><?php esc_html_e( 'AI Disclosure', 'eu-ai-act-ready' ); ?></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Modal Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Modal (Click to View):', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<div class="chatbot-preview-wrapper">
								<div class="chatbot-widget-icon">&#128172;</div>
								<div id="chatbot-preview-modal" class="chatbot-modal-preview">
									<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 14, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								<span><?php esc_html_e( 'AI Disclosure', 'eu-ai-act-ready' ); ?></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Tooltip Style -->
					<div class="notice-preview-item">
						<h5><?php esc_html_e( 'Tooltip (Hover):', 'eu-ai-act-ready' ); ?></h5>
						<div class="notice-preview-box">
							<div class="chatbot-preview-wrapper">
								<div class="chatbot-widget-icon">&#128172;</div>
							<div id="chatbot-preview-tooltip" class="chatbot-tooltip-preview" data-tooltip="<?php esc_attr_e( 'This chat uses AI assistance.', 'eu-ai-act-ready' ); ?>">
									<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 24, '#ffffff' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</form>

	<?php require EUAIACTREADY_PLUGIN_DIR . 'admin/partials/bulk-scan-modal.php'; ?>
</div>
<?php
// Enqueue settings preview script.
wp_enqueue_script(
	'euaiactready-settings-preview',
	EUAIACTREADY_PLUGIN_URL . 'build/admin/settings-preview.js',
	array(),
	EUAIACTREADY_VERSION,
	true
);

// Localize script with config data.
wp_localize_script(
	'euaiactready-settings-preview',
	'euaiactreadySettings',
	array(
		'defaultMessage' => __( 'This content includes AI-generated text.', 'eu-ai-act-ready' ),
	)
);
?>
