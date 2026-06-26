<?php
/**
 * EU AI Act Ready - Category Suggester
 *
 * Suggests an AI tool category for an unknown plugin based on keyword
 * matching against the plugin slug and display name.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suggests AI tool categories using keyword heuristics.
 */
class Euaiactready_Category_Suggester {

	/**
	 * Ordered keyword → category map. First match wins.
	 */
	private static $rules = array(
		'seo'             => array( 'seo', 'rank-math', 'rankmath', 'yoast', 'semrush', 'ahrefs', 'slim-seo' ),
		'chatbot'         => array( 'chatbot', 'chat-bot', 'livechat', 'live-chat', 'crisp', 'tawk', 'tidio', 'drift', 'zendesk', 'freshchat', 'formilla', 'hubspot', 'intercom', 'olark', 'chatra', 'chaport', 'userlike', 'smartsupp' ),
		'commerce'        => array( 'woocommerce', 'woo', 'easy-digital-downloads', 'edd', 'shopping-cart', 'add-to-cart', 'checkout', 'payment-gateway', 'payment-method', 'ecommerce', 'e-commerce', 'shopify', 'bigcommerce', 'ecwid' ),
		'image'           => array( 'dall-e', 'dall_e', 'midjourney', 'stable-diffusion', 'image-gen', 'ai-image', 'photo-ai', 'ai-photo' ),
		'translation'     => array( 'translatepress', 'polylang', 'wpml', 'multilingual', 'gtranslate', 'weglot', 'loco-translate', 'translation' ),
		'search'          => array( 'searchwp', 'elastic', 'algolia', 'relevanssi', 'ai-search' ),
		'security'        => array( 'wordfence', 'ithemes-security', 'all-in-one-security', 'sucuri', 'malware', 'firewall', 'spam-shield' ),
		'voice'           => array( 'text-to-speech', 'tts', 'voice', 'speech', 'audio-ai', 'podcast-ai' ),
		'personalisation' => array( 'personaliz', 'personalise', 'recommendation', 'recommender', 'audience-ai', 'segment' ),
		'media'           => array( 'video-ai', 'ai-video', 'youtube-ai', 'vimeo-ai', 'media-ai' ),
		'content'         => array( 'gpt', 'chatgpt', 'openai', 'claude', 'gemini', 'copilot', 'ai-writer', 'ai-content', 'content-ai', 'writebot', 'jasper', 'copy-ai', 'auto-post', 'autopost', 'auto-blog', 'autoblog', 'elementor-ai' ),
	);

	/**
	 * Suggest a category based on plugin slug and display name.
	 *
	 * Iterates the rules map in order; returns 'content' if no keyword matches.
	 *
	 * @param string $slug Plugin directory slug.
	 * @param string $name Plugin display name.
	 * @return string One of the category keys.
	 */
	public static function suggest( $slug, $name ) {
		$haystack = strtolower( $slug . ' ' . $name );

		foreach ( self::$rules as $category => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( false !== strpos( $haystack, $keyword ) ) {
					return $category;
				}
			}
		}

		return 'content';
	}
}
