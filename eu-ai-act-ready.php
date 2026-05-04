<?php
/**
 * Plugin Name: EU AI Act Ready
 * Plugin URI: https://eu-ai-act-ready.com/
 * Description: Disclose AI-generated content, media, and chatbots with transparent visitor notices, supporting transparency under Article 50 of the EU AI Act.
 * Version: 1.0.1
 * Author: EU AI Act Ready
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 * Stable tag: 1.0.1
 * Text Domain: eu-ai-act-ready
 * Domain Path: /languages
 *
 * @package EUAIACTREADY
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants.
define( 'EUAIACTREADY_PLUGIN_SLUG', 'eu-ai-act-ready' );
define( 'EUAIACTREADY_VERSION', '1.0.1' );
define( 'EUAIACTREADY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EUAIACTREADY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EUAIACTREADY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'EUAIACTREADY_CONTENT_STATE_TABLE', 'euaiactready_content_state' );
define( 'EUAIACTREADY_MEDIA_STATE_TABLE', 'euaiactready_media_state' );
define( 'EUAIACTREADY_MEDIA_SCANS_TABLE', 'euaiactready_media_scans' );
define( 'EUAIACTREADY_DEFAULT_NOTICE_STYLE', 'banner' );
define( 'EUAIACTREADY_DEFAULT_CHATBOT_PLATFORM', 'formilla' );
define( 'EUAIACTREADY_DEFAULT_CHATBOT_NOTICE_STYLE', 'badge' );
define( 'EUAIACTREADY_DEFAULT_MEDIA_LABEL_STYLE', 'caption' );
define( 'EUAIACTREADY_DEFAULT_MEDIA_CONFIDENCE_THRESHOLD', 0.4 );
define( 'EUAIACTREADY_MEDIA_BULK_SCAN_BUFFER_KEY', 'euaiactready_bulk_scan_buffer' );
define( 'EUAIACTREADY_MEDIA_BULK_SCAN_LIMIT', -1 );
define( 'EUAIACTREADY_MEDIA_BULK_SCAN_BATCH_SIZE', 20 );

/**
 * The code that runs during plugin activation.
 */
function euaiactready_activate() {
	require_once EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready-activator.php';
	EUAIACTREADY_Activator::activate();
}

register_activation_hook( __FILE__, 'euaiactready_activate' );

/**
 * The core plugin class.
 */
require EUAIACTREADY_PLUGIN_DIR . 'includes/class-euaiactready.php';
/**
 * Begins execution of the plugin.
 */
function euaiactready_run() {
	$plugin = new EUAIACTREADY();
	$plugin->euaiactready_run();
}

euaiactready_run();
