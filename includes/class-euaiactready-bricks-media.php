<?php
/**
 * EU AI Act Ready - Bricks Builder media transparency integration.
 *
 * Bricks renders its own element tree instead of running post content through
 * 'the_content', so the default labelling never reaches Bricks-rendered images. This
 * class marks AI images while WordPress builds their markup and injects the labels into
 * each rendered Bricks element.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Labels AI-generated images inside Bricks Builder output.
 */
class EUAIACTREADY_Bricks_Media {

	/**
	 * Media transparency instance providing detection data and label markup.
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

		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'euaiactready_mark_ai_image' ), 10, 2 );
		add_filter( 'bricks/frontend/render_element', array( $this, 'euaiactready_label_element' ), 10, 2 );

		if ( $this->euaiactready_background_labels_enabled() ) {
			add_filter( 'bricks/element/render_attributes', array( $this, 'euaiactready_mark_ai_background' ), 10, 3 );
		}
	}

	/**
	 * Whether CSS background images should be labelled too.
	 *
	 * @return bool
	 */
	private function euaiactready_background_labels_enabled() {
		return (bool) get_option( 'euaiactready_bricks_background_labels', true );
	}

	/**
	 * Mark an element root whose CSS background image is AI-generated.
	 *
	 * Background images are written to a generated stylesheet keyed by the element ID, so
	 * unlike <img> markup they leave no trace in the HTML. The marker carries the
	 * attachment ID over to the render pass.
	 *
	 * @param array  $attributes Element attributes, keyed by attribute group.
	 * @param string $key        Attribute group being rendered.
	 * @param object $element    Bricks element instance.
	 * @return array
	 */
	public function euaiactready_mark_ai_background( $attributes, $key, $element = null ) {
		if ( '_root' !== $key || ! $this->euaiactready_should_run() ) {
			return $attributes;
		}

		// Bricks applies this filter more than once per element, so never append twice.
		if ( isset( $attributes['_root'][ EUAIACTREADY_Media_Markup::BACKGROUND_MARKER ] ) ) {
			return $attributes;
		}

		$settings = is_object( $element ) && isset( $element->settings ) && is_array( $element->settings )
			? $element->settings
			: array();

		if ( ! $settings ) {
			return $attributes;
		}

		$attachment_id = $this->euaiactready_find_ai_background( $settings );

		if ( ! $attachment_id ) {
			return $attributes;
		}

		$attributes['_root'][ EUAIACTREADY_Media_Markup::BACKGROUND_MARKER ] = (string) $attachment_id;

		return $attributes;
	}

	/**
	 * Whether a settings key holds an element background.
	 *
	 * @param string $setting_key Settings key.
	 * @return bool
	 */
	private static function euaiactready_is_background_key( $setting_key ) {
		$setting_key = (string) $setting_key;

		// '_background' itself and its breakpoint variants only. A prefix match would also
		// catch unrelated controls such as a hypothetical '_backgroundOverlay'.
		return '_background' === $setting_key || 0 === strpos( $setting_key, '_background:' );
	}

	/**
	 * Find an AI-generated background image in element settings.
	 *
	 * Covers the base '_background' control, its breakpoint variants such as
	 * '_background:tablet_portrait', and any global class applied to the element.
	 *
	 * @param array $settings Element settings.
	 * @return int Attachment ID, or 0 when none is AI-generated.
	 */
	private function euaiactready_find_ai_background( $settings ) {
		$has_own_background = false;

		foreach ( $settings as $setting_key => $value ) {
			if ( ! self::euaiactready_is_background_key( $setting_key ) ) {
				continue;
			}

			// A background counts as the element's own whenever it names an image at all.
			// An external URL or a dynamic data tag carries no attachment ID, but it still
			// overrides the class, so it has to suppress the fallback below just the same.
			if ( empty( $value['image'] ) || ! is_array( $value['image'] ) ) {
				continue;
			}

			if ( ! empty( $value['image']['id'] ) || ! empty( $value['image']['url'] ) || ! empty( $value['image']['useDynamicData'] ) ) {
				$has_own_background = true;
			}

			$attachment_id = isset( $value['image']['id'] ) ? absint( $value['image']['id'] ) : 0;

			if ( $attachment_id && null !== $this->media->euaiactready_get_label_detection( $attachment_id ) ) {
				return $attachment_id;
			}
		}

		// Bricks styles an element by ID, which outranks the class it also carries, so an
		// element that brings its own background image is not showing the class's one.
		// Looking further would label an element for an image it never displays.
		if ( $has_own_background ) {
			return 0;
		}

		if ( empty( $settings['_cssGlobalClasses'] ) || ! is_array( $settings['_cssGlobalClasses'] ) ) {
			return 0;
		}

		$by_class = $this->euaiactready_global_class_backgrounds();

		foreach ( $settings['_cssGlobalClasses'] as $class_id ) {
			if ( ! empty( $by_class[ $class_id ] ) ) {
				return $by_class[ $class_id ];
			}
		}

		return 0;
	}

	/**
	 * Map global class IDs to the AI attachment behind their background image.
	 *
	 * Built once per request: a class used on many elements is inspected a single time.
	 *
	 * @return array<string,int>
	 */
	private function euaiactready_global_class_backgrounds() {
		static $maps = array();

		// Global classes are a per-site option, so the map cannot be shared across a
		// switched blog.
		$blog = (int) ( function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0 );

		if ( isset( $maps[ $blog ] ) ) {
			return $maps[ $blog ];
		}

		$maps[ $blog ] = array();
		$map           = &$maps[ $blog ];

		if ( ! class_exists( '\Bricks\Database' ) || empty( \Bricks\Database::$global_data['globalClasses'] ) ) {
			return $map;
		}

		foreach ( \Bricks\Database::$global_data['globalClasses'] as $global_class ) {
			if ( empty( $global_class['id'] ) || empty( $global_class['settings'] ) ) {
				continue;
			}

			foreach ( $global_class['settings'] as $setting_key => $value ) {
				if ( ! self::euaiactready_is_background_key( $setting_key ) ) {
					continue;
				}

				$attachment_id = isset( $value['image']['id'] ) ? absint( $value['image']['id'] ) : 0;

				if ( $attachment_id && null !== $this->media->euaiactready_get_label_detection( $attachment_id ) ) {
					$map[ $global_class['id'] ] = $attachment_id;
					break;
				}
			}
		}

		return $map;
	}

	/**
	 * Whether labels should be injected in the current request.
	 *
	 * @return bool
	 */
	private function euaiactready_should_run() {
		static $per_blog = array();

		// The option behind this is per site.
		$blog = (int) ( function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0 );

		if ( isset( $per_blog[ $blog ] ) ) {
			return $per_blog[ $blog ];
		}

		$should_run = ! is_admin()
			&& ! $this->euaiactready_is_builder_context()
			&& (bool) get_option( 'euaiactready_media_transparency', true );

		/**
		 * Filters whether AI labels are injected into Bricks output.
		 *
		 * @param bool $should_run Whether to label Bricks-rendered images.
		 */
		$per_blog[ $blog ] = (bool) apply_filters( 'euaiactready_bricks_label_enabled', $should_run );

		return $per_blog[ $blog ];
	}

	/**
	 * Whether Bricks is currently rendering inside the builder rather than the frontend.
	 *
	 * @return bool
	 */
	private function euaiactready_is_builder_context() {
		if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
			return true;
		}

		if ( function_exists( 'bricks_is_builder_iframe' ) && bricks_is_builder_iframe() ) {
			return true;
		}

		if ( function_exists( 'bricks_is_builder_call' ) && bricks_is_builder_call() ) {
			return true;
		}

		return false;
	}

	/**
	 * Carry the attachment ID into the markup of AI-generated images.
	 *
	 * The marker is what the element pass looks for, and labelling removes it again.
	 *
	 * @param array   $attr       Image tag attributes.
	 * @param WP_Post $attachment Attachment post object.
	 * @return array
	 */
	public function euaiactready_mark_ai_image( $attr, $attachment ) {
		if ( ! $this->euaiactready_should_run() ) {
			return $attr;
		}

		if ( ! $attachment instanceof WP_Post ) {
			return $attr;
		}

		if ( null === $this->media->euaiactready_get_label_detection( $attachment->ID ) ) {
			return $attr;
		}

		$attr[ EUAIACTREADY_Media_Markup::MARKER ] = (string) absint( $attachment->ID );

		return $attr;
	}

	/**
	 * Inject labels into a rendered Bricks element.
	 *
	 * Bricks renders bottom-up and passes each parent's HTML - already containing its
	 * labelled children - through this filter again. Labelling consumes the marker, so
	 * repeated passes are a no-op while unlabelled siblings still get processed.
	 *
	 * @param string $html    Rendered element HTML.
	 * @param object $element Bricks element instance.
	 * @return string
	 */
	public function euaiactready_label_element( $html, $element = null ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		if ( ! $this->euaiactready_should_run() ) {
			return $html;
		}

		$element_name = is_object( $element ) && isset( $element->name ) ? $element->name : '';

		/**
		 * Filters whether a specific Bricks element is skipped when labelling.
		 *
		 * @param bool   $skip         Whether to skip this element.
		 * @param string $element_name Bricks element name, e.g. 'image'.
		 * @param string $html         Rendered element HTML.
		 */
		if ( apply_filters( 'euaiactready_bricks_skip_element', false, $element_name, $html ) ) {
			return $html;
		}

		$engine = $this->media->euaiactready_get_markup_engine();
		$html   = $engine->add_labels( $html );

		if ( $this->euaiactready_background_labels_enabled() ) {
			$html = $engine->add_background_labels( $html );
		}

		return $html;
	}
}
