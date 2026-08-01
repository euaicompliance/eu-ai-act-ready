<?php
/**
 * EU AI Act Ready - Cleanup on plugin deletion.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove custom database tables.
global $wpdb;

$euaiactready_content_state_table = $wpdb->prefix . 'euaiactready_content_state';
$euaiactready_media_state_table   = $wpdb->prefix . 'euaiactready_media_state';
$euaiactready_media_scans_table   = $wpdb->prefix . 'euaiactready_media_scans';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom table drop, caching not applicable.
$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $euaiactready_content_state_table ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom table drop, caching not applicable.
$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $euaiactready_media_state_table ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom table drop, caching not applicable.
$wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $euaiactready_media_scans_table ) );

// Delete plugin options.
$euaiactready_options = array(
	'euaiactready_version',
	'euaiactready_show_in_excerpts',
	'euaiactready_transparency_enabled',
	'euaiactready_notice_style',
	'euaiactready_notice_message',
	'euaiactready_chatbot_transparency',
	'euaiactready_chatbot_platform',
	'euaiactready_chatbot_notice_style',
	'euaiactready_chatbot_notice_message',
	'euaiactready_media_transparency',
	'euaiactready_media_label_style',
	'euaiactready_media_confidence_threshold',
);


foreach ( $euaiactready_options as $euaiactready_option ) {
	delete_option( $euaiactready_option );
}

// Delete all plugin-related post meta.
$euaiactready_meta_keys = array(
	'_euaiactready_ai_content',
	'_euaiactready_ai_content_marked_date',
	'_euaiactready_ai_generated',
	'_euaiactready_ai_marked_method',
	'_euaiactready_ai_detection_source',
	'_euaiactready_ai_manually_unmarked',
);

foreach ( $euaiactready_meta_keys as $euaiactready_meta_key ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom postmeta cleanup, caching not applicable.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $euaiactready_meta_key ) );
}
