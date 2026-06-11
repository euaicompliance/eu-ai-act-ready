<?php
/**
 * EU AI Act Ready - Dashboard Page
 *
 * @package EUAIACTREADY
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get manually marked AI content count (all enabled post types, all canonical + legacy values).
$euaiactready_manually_marked_args = array(
	'post_type'      => EUAIACTREADY_Post_Meta_Box::euaiactready_get_all_enabled_post_types(),
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids', // Only get IDs for counting.
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Meta query required to target manually flagged AI content.
	'meta_query'     => array(
		'relation' => 'OR',
		array(
			'key'     => '_euaiactready_ai_content',
			'value'   => array( 'assisted', 'generated', 'generated_reviewed' ),
			'compare' => 'IN',
		),
		// Backward compatibility: legacy posts stored '1' for AI content.
		array(
			'key'     => '_euaiactready_ai_content',
			'value'   => '1',
			'compare' => '=',
		),
	),
);
$euaiactready_manually_marked_query = new WP_Query( $euaiactready_manually_marked_args );
$euaiactready_manually_marked_posts = $euaiactready_manually_marked_query->posts; // IDs only.

// Count posts and pages separately for the stat cards; CPTs add to the total.
$euaiactready_posts_count   = 0;
$euaiactready_pages_count   = 0;
$euaiactready_content_count = 0;

foreach ( $euaiactready_manually_marked_posts as $euaiactready_marked_post_id ) {
	++$euaiactready_content_count;
	$euaiactready_marked_type = get_post_type( $euaiactready_marked_post_id );
	if ( 'post' === $euaiactready_marked_type ) {
		++$euaiactready_posts_count;
	} elseif ( 'page' === $euaiactready_marked_type ) {
		++$euaiactready_pages_count;
	}
}

// Get AI images count.
$euaiactready_ai_images_args  = array(
	'post_type'      => 'attachment',
	'post_mime_type' => 'image',
	'post_status'    => 'inherit',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filtering relies on stored AI detection metadata.
	'meta_query'     => array(
		array(
			'key'     => '_euaiactready_ai_generated',
			'value'   => '1',
			'compare' => '=',
		),
	),
);
$euaiactready_ai_images_query = new WP_Query( $euaiactready_ai_images_args );
$euaiactready_ai_images_count = (int) $euaiactready_ai_images_query->post_count;

// Calculate total AI content count (all enabled post types + images).
$euaiactready_total_count = $euaiactready_content_count + $euaiactready_ai_images_count;

// Get manually unmarked images count.
$euaiactready_manually_unmarked_args  = array(
	'post_type'      => 'attachment',
	'post_mime_type' => 'image',
	'post_status'    => 'inherit',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Needed to locate images manually marked as compliant.
	'meta_query'     => array(
		array(
			'key'     => '_euaiactready_ai_manually_unmarked',
			'value'   => '1',
			'compare' => '=',
		),
	),
);
$euaiactready_manually_unmarked_query = new WP_Query( $euaiactready_manually_unmarked_args );
$euaiactready_manually_unmarked_count = (int) $euaiactready_manually_unmarked_query->post_count;

wp_reset_postdata();
?>

<div class="wrap euaiactready-dashboard">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="euaiactready-scan-cta">
		<div class="scan-cta-icon">
			<span class="dashicons dashicons-search"></span>
		</div>
		<div class="scan-cta-content">
			<h2><?php esc_html_e( 'Scan Your Media Library', 'eu-ai-act-ready' ); ?></h2>
			<p><?php esc_html_e( 'Automatically detect AI-generated images in your WordPress media library to ensure compliance with transparency regulations.', 'eu-ai-act-ready' ); ?></p>
		</div>
		<form method="post" action="" id="scan-form" class="scan-cta-form">
			<?php wp_nonce_field( 'euaiactready_scan' ); ?>
			<button type="button" class="button button-primary button-hero" id="scan-ajax-button">
				<span class="dashicons dashicons-images-alt2"></span>
				<?php esc_html_e( 'Start Scan Now', 'eu-ai-act-ready' ); ?>
			</button>
			<span class="scan-cta-hint"><?php esc_html_e( 'This may take a few minutes depending on library size', 'eu-ai-act-ready' ); ?></span>
		</form>
	</div>

	<!-- Live Scan Progress Container -->
	<div id="live-scan-container" style="display: none;">
		<div class="euaiactready-scan-log live-scan">
			<a href="#" class="scan-log-toggle">
				<h3>
					<?php esc_html_e( 'Image scan', 'eu-ai-act-ready' ); ?>
					<span class="live-indicator">&#9679; <?php esc_html_e( 'SCANNING', 'eu-ai-act-ready' ); ?></span>
				</h3>
			</a>
			<div class="scan-warning-notice" style="display: none; margin-top: 12px; padding: 8px 12px; background: #fff3cd; border-left: 4px solid #ffc107; font-size: 13px; color: #856404;">
				<strong><svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="vertical-align: middle; margin-right: 4px;"><path d="M12 2L2 20h20L12 2z" fill="#ffc107"/><path d="M12 8v6M12 17v.01" stroke="#856404" stroke-width="2" stroke-linecap="round"/></svg><?php esc_html_e( 'Warning:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Do not refresh or leave this page until the scan is complete. If you interrupt it, scanned images are saved and you can restart anytime to continue.', 'eu-ai-act-ready' ); ?>
			</div>
			<!-- Progress bar - stays visible -->
			<div id="scan-progress-wrapper"></div>
			<!-- Scrollable log content -->
			<div class="scan-log-content" id="live-scan-log">
				<!-- Live log entries will appear here -->
			</div>
		</div>
	</div>

	<!-- Statistics Cards -->
	<div class="euaiactready-stats">
		<div class="stat-card">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-content' ) ); ?>">
				<div class="stat-icon">
					<span class="dashicons dashicons-chart-bar"></span>
				</div>
				<div class="stat-content">
				<h3><?php echo esc_html( number_format_i18n( $euaiactready_total_count ) ); ?></h3>
					<p><?php esc_html_e( 'Total AI Content Detected', 'eu-ai-act-ready' ); ?></p>
				</div>
			</a>
		</div>

		<div class="stat-card">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-content' ) ); ?>">
				<div class="stat-icon">
					<span class="dashicons dashicons-admin-post"></span>
				</div>
				<div class="stat-content">
				<h3><?php echo esc_html( number_format_i18n( $euaiactready_posts_count ) ); ?></h3>
					<p><?php esc_html_e( 'AI Posts Detected', 'eu-ai-act-ready' ); ?></p>
				</div>
			</a>
		</div>

		<div class="stat-card">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-content' ) ); ?>">
				<div class="stat-icon">
					<span class="dashicons dashicons-admin-page"></span>
				</div>
				<div class="stat-content">
				<h3><?php echo esc_html( number_format_i18n( $euaiactready_pages_count ) ); ?></h3>
					<p><?php esc_html_e( 'AI Pages Detected', 'eu-ai-act-ready' ); ?></p>
				</div>
			</a>
		</div>

		<div class="stat-card">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-images&tab=detected' ) ); ?>">
				<div class="stat-icon">
					<span class="dashicons dashicons-format-image"></span>
				</div>
				<div class="stat-content">
				<h3><?php echo esc_html( number_format_i18n( $euaiactready_ai_images_count ) ); ?></h3>
					<p><?php esc_html_e( 'AI Images Detected', 'eu-ai-act-ready' ); ?></p>
				</div>
			</a>
		</div>

		<div class="stat-card">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-images&tab=unmarked' ) ); ?>">
				<div class="stat-icon deactivated">
					<span class="dashicons dashicons-editor-unlink"></span>
				</div>
				<div class="stat-content">
				<h3 class="deactivated"><?php echo esc_html( number_format_i18n( $euaiactready_manually_unmarked_count ) ); ?></h3>
					<p class="deactivated"><?php esc_html_e( 'Manually Unmarked', 'eu-ai-act-ready' ); ?></p>
				</div>
			</a>
		</div>
	</div>

	<!-- Feature Activation Status -->
	<?php
	$euaiactready_content_transparency_enabled = get_option( 'euaiactready_transparency_enabled', true );
	$euaiactready_chatbot_transparency_enabled = get_option( 'euaiactready_chatbot_transparency', true );
	$euaiactready_media_transparency_enabled   = get_option( 'euaiactready_media_transparency', true );
	?>
	<div class="euaiactready-transparency-status">
		<h2 class="section-header">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Transparency Status', 'eu-ai-act-ready' ); ?>
		</h2>

		<?php
		// Check if all transparency features are disabled.
		$euaiactready_all_disabled = ! $euaiactready_content_transparency_enabled
			&& ! $euaiactready_chatbot_transparency_enabled
			&& ! $euaiactready_media_transparency_enabled;

		if ( $euaiactready_all_disabled ) :
			?>
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'EU AI Act Ready Plugin is Active, but Transparency Notices are Disabled', 'eu-ai-act-ready' ); ?></strong><br>
					<?php esc_html_e( 'The plugin is installed and running, but no transparency notices will appear on your website\'s frontend until you enable at least one feature below. Visit Settings to activate the transparency features you need.', 'eu-ai-act-ready' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="euaiactready-stats">
			<div class="stat-card <?php echo esc_attr( $euaiactready_content_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-settings&tab=transparency' ) ); ?>">
					<div class="stat-icon <?php echo esc_attr( $euaiactready_content_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
						<span class="dashicons dashicons-admin-page"></span>
					</div>
					<div class="stat-content">
						<h3 class="<?php echo esc_attr( $euaiactready_content_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
							<?php echo esc_html( $euaiactready_content_transparency_enabled ? __( 'Activated', 'eu-ai-act-ready' ) : __( 'Deactivated', 'eu-ai-act-ready' ) ); ?>
						</h3>
						<p><?php esc_html_e( 'Content Transparency', 'eu-ai-act-ready' ); ?></p>
					</div>
				</a>
			</div>

			<div class="stat-card <?php echo esc_attr( $euaiactready_chatbot_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-settings&tab=chatbot' ) ); ?>">
					<div class="stat-icon <?php echo esc_attr( $euaiactready_chatbot_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
						<span class="dashicons dashicons-format-chat"></span>
					</div>
					<div class="stat-content">
						<h3 class="<?php echo esc_attr( $euaiactready_chatbot_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
							<?php echo esc_html( $euaiactready_chatbot_transparency_enabled ? __( 'Activated', 'eu-ai-act-ready' ) : __( 'Deactivated', 'eu-ai-act-ready' ) ); ?>
						</h3>
						<p><?php esc_html_e( 'Chatbot Transparency', 'eu-ai-act-ready' ); ?></p>
					</div>
				</a>
			</div>

			<div class="stat-card <?php echo esc_attr( $euaiactready_media_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-settings&tab=media' ) ); ?>">
					<div class="stat-icon <?php echo esc_attr( $euaiactready_media_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
						<span class="dashicons dashicons-format-image"></span>
					</div>
					<div class="stat-content">
						<h3 class="<?php echo esc_attr( $euaiactready_media_transparency_enabled ? 'activated' : 'deactivated' ); ?>">
							<?php echo esc_html( $euaiactready_media_transparency_enabled ? __( 'Activated', 'eu-ai-act-ready' ) : __( 'Deactivated', 'eu-ai-act-ready' ) ); ?>
						</h3>
						<p><?php esc_html_e( 'Media/Image Labels', 'eu-ai-act-ready' ); ?></p>
					</div>
				</a>
			</div>
		</div>
	</div>

	<!-- Explanation & Tips -->
	<div class="euaiactready-explanation">
		<h3><?php esc_html_e( 'How AI Image Analysis Works', 'eu-ai-act-ready' ); ?></h3>
		<p><?php esc_html_e( 'The plugin uses multiple heuristic signals combined into a confidence scoring system:', 'eu-ai-act-ready' ); ?></p>
		<ol>
			<li><strong><?php esc_html_e( 'AI Tool Names:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Scans filenames and metadata for known AI tool names (e.g. DALL-E, Midjourney, Stable Diffusion).', 'eu-ai-act-ready' ); ?></li>
			<li><strong><?php esc_html_e( 'Metadata Analysis:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Checks filename, title, alt text, caption, description, and EXIF data.', 'eu-ai-act-ready' ); ?></li>
			<li><strong><?php esc_html_e( 'Technical Patterns:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Analyzes common technical patterns such as image dimensions, file sizes, and compression ratios.', 'eu-ai-act-ready' ); ?></li>
			<li><strong><?php esc_html_e( 'EXIF Markers:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Analyzes EXIF metadata for software indicators or missing camera information commonly found in AI-generated images.', 'eu-ai-act-ready' ); ?></li>
			<li><strong><?php esc_html_e( 'Filename Patterns:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Identifies common filename patterns such as hashes, UUIDs, or generic names.', 'eu-ai-act-ready' ); ?></li>
			<li><strong><?php esc_html_e( 'Manual Override:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Manually mark any image as AI-generated in the Media Library (manual override)', 'eu-ai-act-ready' ); ?></li>
			<li><strong><?php esc_html_e( 'Smart Labeling:', 'eu-ai-act-ready' ); ?></strong> <?php esc_html_e( 'Only labels images meeting your confidence threshold.', 'eu-ai-act-ready' ); ?></li>
		</ol>

		<div class="tips-box">
			<h4>&#128161; <?php esc_html_e( 'Tips for Best Results:', 'eu-ai-act-ready' ); ?></h4>
			<ul>
				<li><?php esc_html_e( 'When possible, name AI images with tool names (for example, "sunset-midjourney.jpg").', 'eu-ai-act-ready' ); ?></li>
				<li><?php esc_html_e( 'Add AI tool names to alt text or captions.', 'eu-ai-act-ready' ); ?></li>
				<li><?php esc_html_e( 'Manually mark images in the Media Library editor.', 'eu-ai-act-ready' ); ?></li>
				<li><?php esc_html_e( 'Use Caption or Badge style for best visibility.', 'eu-ai-act-ready' ); ?></li>
			</ul>
		</div>
	</div>

	<?php require EUAIACTREADY_PLUGIN_DIR . 'admin/partials/bulk-scan-modal.php'; ?>

</div>
