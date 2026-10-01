<?php
/**
 * EU AI Act Ready - Elementor background image labels.
 *
 * Elementor puts background images in a generated stylesheet, not in the HTML, so the
 * normal image labelling never sees them (container backgrounds, Slides widget slides, etc).
 *
 * So we look at Elementor's settings while it renders, tag the element that has the
 * background, and let the markup engine swap that tag for a badge at the end.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Labels AI-generated background images inside Elementor output.
 */
class EUAIACTREADY_Elementor_Media {

	/**
	 * Elementor's background control groups.
	 *
	 * @var string[]
	 */
	const BACKGROUND_GROUPS = array( 'background', 'background_overlay', '_background', '_background_overlay' );

	/**
	 * Media transparency instance, for detection data and label markup.
	 *
	 * @var EUAIACTREADY_Media_Transparency
	 */
	private $media;

	/**
	 * Constructor.
	 *
	 * @param EUAIACTREADY_Media_Transparency $media Media transparency instance.
	 */
	public function __construct( EUAIACTREADY_Media_Transparency $media ) {
		$this->media = $media;

		if ( ! self::euaiactready_background_labels_enabled() ) {
			return;
		}

		add_action( 'elementor/frontend/before_render', array( $this, 'euaiactready_mark_ai_background' ) );
		add_filter( 'elementor/widget/render_content', array( $this, 'euaiactready_mark_ai_repeater_items' ), 10, 2 );
		add_filter( 'elementor/frontend/the_content', array( $this, 'euaiactready_label_content' ) );
	}

	/**
	 * Whether background images should be labelled.
	 *
	 * @return bool
	 */
	public static function euaiactready_background_labels_enabled() {
		return (bool) get_option( 'euaiactready_elementor_background_labels', true );
	}

	/**
	 * Tag an element if its background image is AI-generated.
	 *
	 * @param object $element Elementor element instance.
	 * @return void
	 */
	public function euaiactready_mark_ai_background( $element ) {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_settings' ) || ! method_exists( $element, 'add_render_attribute' ) ) {
			return;
		}

		if ( ! $this->euaiactready_should_run() ) {
			return;
		}

		$settings = $element->get_settings();

		if ( ! is_array( $settings ) || ! $settings ) {
			return;
		}

		$attachment_id = $this->euaiactready_find_ai_background( $settings, $element );

		if ( ! $attachment_id ) {
			return;
		}

		$element->add_render_attribute( '_wrapper', EUAIACTREADY_Media_Markup::BACKGROUND_MARKER, (string) $attachment_id );
	}

	/**
	 * Tag repeater items (like Slides widget slides) with AI-generated backgrounds.
	 *
	 * @param string $content Rendered widget HTML.
	 * @param object $widget  Elementor widget instance.
	 * @return string
	 */
	public function euaiactready_mark_ai_repeater_items( $content, $widget = null ) {
		if ( ! is_string( $content ) || '' === $content || false === strpos( $content, 'elementor-repeater-item-' ) ) {
			return $content;
		}

		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_settings' ) || ! $this->euaiactready_should_run() ) {
			return $content;
		}

		$settings = $widget->get_settings();

		if ( ! is_array( $settings ) ) {
			return $content;
		}

		foreach ( $settings as $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			foreach ( $value as $item ) {
				if ( ! is_array( $item ) || empty( $item['_id'] ) || ! is_string( $item['_id'] ) ) {
					continue;
				}

				$attachment_id = $this->euaiactready_find_ai_background( $item );

				if ( $attachment_id ) {
					$content = $this->euaiactready_mark_repeater_tag( $content, $item['_id'], $attachment_id );
				}
			}
		}

		return $content;
	}

	/**
	 * Add the marker to one repeater item's opening tag.
	 *
	 * @param string $content       Widget HTML.
	 * @param string $item_id       Repeater item ID.
	 * @param int    $attachment_id Attachment ID of the background image.
	 * @return string
	 */
	private function euaiactready_mark_repeater_tag( $content, $item_id, $attachment_id ) {
		$class   = 'elementor-repeater-item-' . $item_id;
		$pattern = '/<(' . EUAIACTREADY_Media_Markup::BACKGROUND_TAGS . ')\b(?=[^>]*\bclass\s*=\s*["\'][^"\']*(?<![\w-])' . preg_quote( $class, '/' ) . '(?![\w-])[^"\']*["\'])([^>]*)>/i';

		return (string) preg_replace_callback(
			$pattern,
			static function ( $matches ) use ( $attachment_id ) {
				if ( false !== stripos( $matches[2], EUAIACTREADY_Media_Markup::BACKGROUND_MARKER ) ) {
					return $matches[0];
				}

				return '<' . $matches[1] . $matches[2] . ' ' . EUAIACTREADY_Media_Markup::BACKGROUND_MARKER . '="' . absint( $attachment_id ) . '">';
			},
			$content,
			1
		);
	}

	/**
	 * Swap the markers for badges once the Elementor HTML is done.
	 *
	 * @param string $html Rendered document HTML.
	 * @return string
	 */
	public function euaiactready_label_content( $html ) {
		if ( ! is_string( $html ) || '' === $html || ! $this->euaiactready_should_run() ) {
			return $html;
		}

		return $this->media->euaiactready_get_markup_engine()->add_background_labels( $html );
	}

	/**
	 * Look for an AI-generated background image in a settings array.
	 *
	 * @param array       $settings Element or repeater item settings.
	 * @param object|null $element  Element instance, needed for dynamic images.
	 * @return int Attachment ID, or 0 if none is AI-generated.
	 */
	private function euaiactready_find_ai_background( array $settings, $element = null ) {
		foreach ( self::BACKGROUND_GROUPS as $group ) {
			$has_type = isset( $settings[ $group . '_background' ] );

			// Slides have a plain 'background_image' with no type control.
			if ( $has_type && 'classic' !== $settings[ $group . '_background' ] ) {
				continue;
			}

			$image_key = $group . '_image';

			foreach ( $settings as $key => $value ) {
				$key = (string) $key;

				if ( $key !== $image_key && 0 !== strpos( $key, $image_key . '_' ) ) {
					continue;
				}

				$attachment_id = $this->euaiactready_image_id( $key, $value, $settings, $element );

				if ( $attachment_id && null !== $this->media->euaiactready_get_label_detection( $attachment_id ) ) {
					return $attachment_id;
				}
			}
		}

		return 0;
	}

	/**
	 * Get the attachment ID from one background image control.
	 *
	 * @param string      $key      Control key.
	 * @param mixed       $value    Control value.
	 * @param array       $settings Settings the control belongs to.
	 * @param object|null $element  Element instance.
	 * @return int
	 */
	private function euaiactready_image_id( $key, $value, array $settings, $element ) {
		if ( ! empty( $settings['__dynamic__'][ $key ] ) && is_object( $element ) && method_exists( $element, 'get_settings_for_display' ) ) {
			$value = $element->get_settings_for_display( $key );
		}

		if ( is_array( $value ) && ! empty( $value['id'] ) ) {
			return absint( $value['id'] );
		}

		return 0;
	}

	/**
	 * Whether to add labels on this request.
	 *
	 * @return bool
	 */
	private function euaiactready_should_run() {
		$should_run = ! is_admin()
			&& ! $this->euaiactready_is_editor_context()
			&& (bool) get_option( 'euaiactready_media_transparency', true );

		/**
		 * Filters whether AI labels are added to Elementor output.
		 *
		 * @param bool $should_run Whether to label Elementor backgrounds.
		 */
		return (bool) apply_filters( 'euaiactready_elementor_label_enabled', $should_run );
	}

	/**
	 * Whether Elementor is in the editor or preview, not the frontend.
	 *
	 * @return bool
	 */
	private function euaiactready_is_editor_context() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$plugin = \Elementor\Plugin::$instance;

		if ( isset( $plugin->editor ) && is_object( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
			return true;
		}

		if ( isset( $plugin->preview ) && is_object( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
			return true;
		}

		return false;
	}
}
