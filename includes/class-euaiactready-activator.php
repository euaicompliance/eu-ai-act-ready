<?php
/**
 * EU AI Act Ready - Fired during plugin activation.
 *
 * @package EUAIACTREADY
 */

/**
 * Handles plugin activation tasks.
 */
class EUAIACTREADY_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		// Create database tables if needed.
		global $wpdb;

		$euaiactready_content_state_table = $wpdb->prefix . EUAIACTREADY_CONTENT_STATE_TABLE;
		$euaiactready_media_state_table   = $wpdb->prefix . EUAIACTREADY_MEDIA_STATE_TABLE;
		$euaiactready_media_scans_table   = $wpdb->prefix . EUAIACTREADY_MEDIA_SCANS_TABLE;
		$charset_collate                  = $wpdb->get_charset_collate();

		$content_state_sql = "CREATE TABLE IF NOT EXISTS $euaiactready_content_state_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			item_type VARCHAR(20) NOT NULL,
			item_id BIGINT(20) UNSIGNED NOT NULL,
			ai_content TINYINT(1) NULL,
			user_id BIGINT(20) UNSIGNED NULL,
			ai_content_marked_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY item_unique (item_type, item_id),
			KEY ai_content (ai_content),
			KEY user_id (user_id),
			KEY ai_content_marked_at (ai_content_marked_at)
		) $charset_collate;";

		$media_state_sql = "CREATE TABLE IF NOT EXISTS $euaiactready_media_state_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			item_type VARCHAR(20) NOT NULL,
			item_id BIGINT(20) UNSIGNED NOT NULL,
			ai_generated TINYINT(1) NULL,
			ai_marked_method VARCHAR(20) NULL,
			ai_detection_source LONGTEXT NULL,
			ai_manually_unmarked TINYINT(1) NULL,
			last_scanned_at DATETIME NULL,
			scan_count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT(20) UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY item_unique (item_type, item_id),
			KEY ai_generated (ai_generated),
			KEY user_id (user_id),
			KEY last_scanned_at (last_scanned_at)
		) $charset_collate;";

		$media_scans_sql = "CREATE TABLE IF NOT EXISTS $euaiactready_media_scans_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			attachment_id BIGINT(20) UNSIGNED NOT NULL,
			is_ai TINYINT(1) NOT NULL,
			confidence_score FLOAT NOT NULL,
			indicators LONGTEXT NULL,
			source VARCHAR(255) NULL,
			scan_trigger VARCHAR(50) NOT NULL DEFAULT 'auto',
			detection_data LONGTEXT NULL,
			scanned_by BIGINT(20) UNSIGNED NULL,
			scanned_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY attachment_id (attachment_id),
			KEY scanned_at (scanned_at),
			KEY scan_trigger (scan_trigger)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $content_state_sql );
		dbDelta( $media_state_sql );
		dbDelta( $media_scans_sql );

		// Set default options.
		add_option( 'euaiactready_version', EUAIACTREADY_VERSION );

		// Set transparency features to disabled by default.
		add_option( 'euaiactready_transparency_enabled', 0 );
		add_option( 'euaiactready_chatbot_transparency', 0 );
		add_option( 'euaiactready_media_transparency', 0 );
		add_option( 'euaiactready_show_in_excerpts', 0 );

		// Refines media transparency rather than enabling disclosures on its own, so it
		// follows along once that feature is switched on.
		add_option( 'euaiactready_bricks_background_labels', 1 );
		add_option( 'euaiactready_media_label_position', '' );
		add_option( 'euaiactready_bricks_bg_label_position', 'top-right' );

		// Both defaults reproduce the label exactly as it rendered before these settings
		// existed, so upgrading never changes an existing site.
		add_option( 'euaiactready_media_label_tooltip', 'full' );
		add_option( 'euaiactready_media_label_size', 'normal' );

		// RSS & Feed disclosure defaults (opt-in).
		add_option( 'euaiactready_rss_disclosure_enabled', 0 );
		add_option( 'euaiactready_rss_title_prefix', 0 );

		// Posts and pages are enabled by default for AI disclosure meta box.
		add_option( 'euaiactready_enabled_post_types', array( 'post', 'page' ) );
	}
}
