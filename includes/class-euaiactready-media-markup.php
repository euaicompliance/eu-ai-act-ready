<?php
/**
 * EU AI Act Ready - WordPress-independent markup transformations.
 *
 * Holds the HTML rewriting used to attach AI transparency labels to images. Kept free
 * of WordPress calls so the parsing rules can be unit tested directly.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects AI transparency labels into rendered HTML.
 *
 * Known limitation: the label is kept out of a link only when that link is part of the
 * same HTML being processed - a Bricks image element with its own link, for instance.
 * When a parent element supplies the anchor, as a linked container does, the image was
 * already labelled during its own render pass and the anchor did not exist yet, so the
 * label ends up inside the link. The disclosure is present and visible; it is simply
 * clickable. Hoisting it out is not possible from a per-element filter.
 */
class EUAIACTREADY_Media_Markup {

	/**
	 * Attribute carrying the attachment ID of an image awaiting a label.
	 *
	 * Labelling consumes (removes) it, which makes the transformation idempotent: Bricks
	 * renders bottom-up and passes each parent's HTML - already containing its labelled
	 * children - through the same filter again.
	 */
	const MARKER = 'data-euaiact-id';

	/**
	 * Class on every label container.
	 */
	const CONTAINER_CLASS = 'ai-media-container';

	/**
	 * Added when the container was produced during a builder's render pass.
	 *
	 * The stylesheet forces max-width and height on the images it wraps, so that a theme
	 * cannot leave a labelled image overflowing its container. A builder sizes its own
	 * images - a hero using height:100% with object-fit:cover, say - and that reset would
	 * override it and snap the image back to its natural aspect ratio. The modifier is what
	 * lets the stylesheet skip the reset for exactly those images.
	 */
	const BUILDER_CONTAINER_CLASS = 'ai-media-bricks';

	/**
	 * Attribute naming the attachment behind an element's CSS background image.
	 */
	const BACKGROUND_MARKER = 'data-euaiact-bg';

	/**
	 * Attribute marking a tag whose background has already been considered.
	 */
	const BACKGROUND_DONE = 'data-euaiact-bg-done';

	/**
	 * Tags that may receive a background badge as a child.
	 *
	 * Excludes list, table and media containers, whose content model forbids a stray
	 * <div>, plus void elements that have no children at all.
	 */
	const BACKGROUND_TAGS = 'div|section|article|aside|figure|header|footer|main|li|a';

	/**
	 * How far to look on either side of an image for an enclosing <picture> or <a>.
	 *
	 * Generous enough for Bricks' <source> tags with long srcset lists, but bounded so
	 * malformed markup cannot turn expansion into a full-document scan per image.
	 */
	const EXPAND_WINDOW = 16384;

	/**
	 * How far to look for an enclosing <a>.
	 *
	 * Much smaller than EXPAND_WINDOW: a link sits directly around its image, while a
	 * <picture> can be pushed far away by a long <source> block. Keeping this tight
	 * matters because the cost per image is proportional to the window.
	 */
	const LINK_WINDOW = 4096;

	/**
	 * Matches any <img> tag, marked or not.
	 */
	const ANY_IMG_SUBPATTERN = '<img\b[^>]*>';

	/**
	 * Matches an <img> tag carrying the marker attribute.
	 */
	const MARKED_IMG_SUBPATTERN = '<img\b[^>]*\bdata-euaiact-id\s*=\s*["\']\d+["\'][^>]*>';

	/**
	 * Returns a detection payload for an attachment ID, or null when it is not AI.
	 *
	 * @var callable
	 */
	private $detector;

	/**
	 * Renders label HTML from a detection payload.
	 *
	 * @var callable
	 */
	private $renderer;

	/**
	 * Resolves an image URL to an attachment ID. Optional.
	 *
	 * @var callable|null
	 */
	private $url_resolver;

	/**
	 * Renders badge HTML for a CSS background image. Optional.
	 *
	 * @var callable|null
	 */
	private $background_renderer;

	/**
	 * Constructor.
	 *
	 * @param callable      $detector            Maps attachment ID to detection payload or null.
	 * @param callable      $renderer            Maps detection payload to label HTML.
	 * @param callable|null $url_resolver        Maps an image URL to an attachment ID.
	 * @param callable|null $background_renderer Maps detection payload to background badge HTML.
	 */
	public function __construct( callable $detector, callable $renderer, ?callable $url_resolver = null, ?callable $background_renderer = null ) {
		$this->detector            = $detector;
		$this->renderer            = $renderer;
		$this->url_resolver        = $url_resolver;
		$this->background_renderer = $background_renderer;
	}

	/**
	 * Add badges to elements whose CSS background image is AI-generated.
	 *
	 * Two sources are handled. Backgrounds configured on an element land in a generated
	 * stylesheet rather than the markup, so the Bricks integration marks the root with
	 * data-euaiact-bg. Repeater backgrounds - carousel items, slides, team members - are
	 * written as an inline style instead and are picked up straight from the HTML.
	 *
	 * The badge is inserted as the first child rather than the last. That needs no
	 * matching close tag, which keeps this free of the tag-balancing logic that HTML in
	 * the wild breaks. The badge is absolutely positioned, so it is out of flow and does
	 * not become a flex or grid item; it does shift :first-child selectors by one.
	 *
	 * @param string $html Rendered HTML fragment.
	 * @return string
	 */
	public function add_background_labels( $html ) {
		if ( null === $this->background_renderer ) {
			return $html;
		}

		$has_marker = false !== stripos( $html, self::BACKGROUND_MARKER );
		$has_inline = false !== stripos( $html, 'background-image' );

		if ( ! $has_marker && ! $has_inline ) {
			return $html;
		}

		return preg_replace_callback(
			'/<(' . self::BACKGROUND_TAGS . ')\b[^>]*>/i',
			function ( $matches ) {
				return $this->label_background_tag( $matches[0] );
			},
			$html
		);
	}

	/**
	 * Append a badge to one opening tag when it carries an AI background image.
	 *
	 * @param string $tag Opening tag.
	 * @return string The tag, followed by the badge when one applies.
	 */
	private function label_background_tag( $tag ) {
		// Already handled in an earlier pass. Checked against the attribute names only, so
		// naming the marker inside some other attribute value cannot switch labelling off.
		if ( false !== stripos( $this->attribute_names( $tag ), self::BACKGROUND_DONE ) ) {
			return $tag;
		}

		$attachment_id = $this->background_attachment_id( $tag );

		if ( ! $attachment_id ) {
			return $tag;
		}

		$clean = preg_replace(
			'/\s*\b' . self::BACKGROUND_MARKER . '\s*=\s*["\']\d+["\']/i',
			'',
			$tag
		);

		// Mark the tag as processed so repeated passes leave it alone.
		$clean = preg_replace( '/\s*(\/?)>$/', ' ' . self::BACKGROUND_DONE . '$1>', $clean, 1 );

		$detection = call_user_func( $this->detector, $attachment_id );

		if ( empty( $detection ) ) {
			return $clean;
		}

		$badge = (string) call_user_func( $this->background_renderer, $detection );

		return '' === $badge ? $clean : $clean . $badge;
	}

	/**
	 * Resolve the attachment ID of a tag's background image.
	 *
	 * @param string $tag Opening tag.
	 * @return int Attachment ID, or 0 when there is none.
	 */
	private function background_attachment_id( $tag ) {
		if ( preg_match( '/\b' . self::BACKGROUND_MARKER . '\s*=\s*["\'](\d+)["\']/i', $tag, $matches ) ) {
			return (int) $matches[1];
		}

		if ( null === $this->url_resolver ) {
			return 0;
		}

		// Work inside the style attribute rather than across the whole tag, so a quote in
		// one attribute cannot cut the declaration short.
		if ( ! preg_match_all( '/\b(?:style|data-style)\s*=\s*("[^"]*"|\'[^\']*\')/i', $tag, $attributes ) ) {
			return 0;
		}

		foreach ( $attributes[1] as $raw_value ) {
			// Decode before splitting on ';': Bricks writes the quotes around a URL as
			// &quot;, and that entity ends in a semicolon that would cut a declaration in
			// half.
			$value = html_entity_decode( substr( $raw_value, 1, -1 ) );

			// Several declarations can appear in one style; CSS applies the last one.
			if ( ! preg_match_all( '/background-image\s*:([^;]*)/i', $value, $declarations ) ) {
				continue;
			}

			$attachment_id = $this->ai_layer_attachment_id( (string) end( $declarations[1] ) );

			if ( $attachment_id ) {
				return $attachment_id;
			}
		}

		return 0;
	}

	/**
	 * Find an AI-generated image among the layers of one background-image declaration.
	 *
	 * @param string $declaration Declaration value, without the property name.
	 * @return int Attachment ID, or 0 when no layer is AI-generated.
	 */
	private function ai_layer_attachment_id( $declaration ) {
		if ( ! preg_match_all( '/url\(\s*(?:&quot;|["\'])?(.+?)(?:&quot;|["\'])?\s*\)/i', $declaration, $layers ) ) {
			return 0;
		}

		foreach ( $layers[1] as $url ) {
			$attachment_id = (int) call_user_func( $this->url_resolver, html_entity_decode( trim( $url ) ) );

			if ( $attachment_id && ! empty( call_user_func( $this->detector, $attachment_id ) ) ) {
				return $attachment_id;
			}
		}

		return 0;
	}

	/**
	 * Reduce a tag to its attribute names, dropping every quoted value.
	 *
	 * Lets attribute names be tested without a value being able to impersonate one.
	 *
	 * @param string $tag Opening tag.
	 * @return string
	 */
	private function attribute_names( $tag ) {
		return (string) preg_replace( '/=\s*("[^"]*"|\'[^\']*\'|[^\s>]*)/', '=', $tag );
	}

	/**
	 * Wrap every marked AI image in the given HTML with a transparency label.
	 *
	 * Images without the marker are left byte-identical.
	 *
	 * @param string $html Rendered HTML fragment.
	 * @return string HTML with labels injected and markers consumed.
	 */
	public function add_labels( $html ) {
		if ( false === stripos( $html, self::MARKER ) ) {
			return $html;
		}

		return $this->label_all(
			$html,
			self::MARKED_IMG_SUBPATTERN,
			self::CONTAINER_CLASS . ' ' . self::BUILDER_CONTAINER_CLASS
		);
	}

	/**
	 * Label images in post content, which carry no marker attribute.
	 *
	 * Unlike add_labels() this also resolves images by wp-image class, data-id or src,
	 * so it must only be used on content that is filtered exactly once. It consumes any
	 * marker it finds, so a later add_labels() pass leaves the result alone.
	 *
	 * @param string $html Post content.
	 * @return string
	 */
	public function add_labels_to_content( $html ) {
		if ( false === stripos( $html, '<img' ) ) {
			return $html;
		}

		// Stored content never legitimately carries the marker - it is added while
		// WordPress builds image markup and consumed again before output. One that
		// reaches us here is author input, so drop it rather than trust it to name an
		// attachment. Images are resolved from wp-image / data-id / src instead.
		$html = $this->remove_marker( $html );

		return $this->label_all( $html, self::ANY_IMG_SUBPATTERN, self::CONTAINER_CLASS );
	}

	/**
	 * Wrap every image construct matched by the given image subpattern.
	 *
	 * @param string $html            HTML to transform.
	 * @param string $img_subpattern  Regex fragment matching the images to consider.
	 * @param string $container_class Class list for the container the label is wrapped in.
	 * @return string
	 */
	private function label_all( $html, $img_subpattern, $container_class ) {
		$found = preg_match_all( '/' . $img_subpattern . '/i', $html, $matches, PREG_OFFSET_CAPTURE );

		if ( ! $found ) {
			return $html;
		}

		$jobs = array();

		foreach ( $matches[0] as $match ) {
			list( $img_tag, $offset ) = $match;
			$offset                   = (int) $offset;

			$range = $this->construct_range( $html, $offset, $offset + strlen( $img_tag ) );

			// No range means no safe place for a label; leave the image alone.
			if ( null === $range ) {
				continue;
			}

			$attachment_id = $this->extract_attachment_id( $img_tag );
			$detection     = $attachment_id ? call_user_func( $this->detector, $attachment_id ) : null;

			$jobs[] = array(
				'start'     => $range[0],
				'end'       => $range[1],
				'detection' => empty( $detection ) ? null : $detection,
			);
		}

		return $this->apply_jobs( $html, $jobs, $container_class );
	}

	/**
	 * Rewrite the HTML for a set of label jobs.
	 *
	 * Two images in one <picture> or one <a> expand to the same construct. Their ranges
	 * are merged so the construct is wrapped once, because applying them separately would
	 * make the second rewrite operate on offsets the first one already invalidated.
	 *
	 * @param string  $html            Full HTML.
	 * @param array[] $jobs            Ranges with their detection payload.
	 * @param string  $container_class Class list for the container the label is wrapped in.
	 * @return string
	 */
	private function apply_jobs( $html, $jobs, $container_class ) {
		if ( ! $jobs ) {
			return $html;
		}

		usort(
			$jobs,
			static function ( $a, $b ) {
				return $a['start'] <=> $b['start'];
			}
		);

		$merged = array();

		foreach ( $jobs as $job ) {
			$last = count( $merged ) - 1;

			if ( $last >= 0 && $job['start'] < $merged[ $last ]['end'] ) {
				$merged[ $last ]['end'] = max( $merged[ $last ]['end'], $job['end'] );

				if ( null === $merged[ $last ]['detection'] ) {
					$merged[ $last ]['detection'] = $job['detection'];
				}

				continue;
			}

			$merged[] = $job;
		}

		// Ranges no longer overlap, so the result can be assembled in one forward pass
		// rather than rewriting the whole string once per job.
		$out    = '';
		$cursor = 0;

		foreach ( $merged as $job ) {
			$length = $job['end'] - $job['start'];

			// Strip markers across the whole construct: any image inside it is covered by
			// the single label this construct receives.
			$clean = $this->remove_marker( substr( $html, $job['start'], $length ) );

			if ( null !== $job['detection'] ) {
				$label = (string) call_user_func( $this->renderer, $job['detection'] );

				if ( '' !== $label ) {
					$clean = '<div class="' . $container_class . '">' . $clean . $label . '</div>';
				}
			}

			$out   .= substr( $html, $cursor, $job['start'] - $cursor ) . $clean;
			$cursor = $job['end'];
		}

		return $out . substr( $html, $cursor );
	}

	/**
	 * Determine the range that should carry the label for an image.
	 *
	 * A <div> may not be inserted inside <picture>, and the label must not become part of
	 * a link, so the range grows outwards from the image.
	 *
	 * @param string $html  Full HTML.
	 * @param int    $start Image start offset.
	 * @param int    $end   Image end offset.
	 * @return int[]|null The range, or null when no safe range exists.
	 */
	private function construct_range( $html, $start, $end ) {
		// Link expansion can move the range into an enclosing picture and vice versa, so
		// alternate until it stops growing. Nesting deeper than a couple of levels only
		// occurs in invalid markup, hence the small cap.
		for ( $pass = 0; $pass < 3; $pass++ ) {
			$picture = $this->expand_to_picture( $html, $start, $end );

			if ( null === $picture ) {
				return null;
			}

			$grown = $this->expand_to_link( $html, $picture[0], $picture[1] );

			if ( $grown[0] === $start && $grown[1] === $end ) {
				return $grown;
			}

			list( $start, $end ) = $grown;
		}

		// Expansion never settled, which only happens for invalid alternating nesting.
		// Wrapping the range now could put a <div> inside a <picture>, so drop the label
		// instead of emitting broken markup.
		$final = $this->expand_to_picture( $html, $start, $end );

		if ( null === $final || $final[0] !== $start || $final[1] !== $end ) {
			return null;
		}

		return array( $start, $end );
	}

	/**
	 * Grow a range to the enclosing <picture>, when the image sits inside one.
	 *
	 * @param string $html  Full HTML.
	 * @param int    $start Range start.
	 * @param int    $end   Range end.
	 * @return int[]|null The possibly widened range, or null if it cannot be determined.
	 */
	private function expand_to_picture( $html, $start, $end ) {
		$window = max( 0, $start - self::EXPAND_WINDOW );
		$before = substr( $html, $window, $start - $window );

		$open  = strripos( $before, '<picture' );
		$close = strripos( $before, '</picture>' );

		$inside = false !== $open && ( false === $close || $open > $close );

		if ( ! $inside ) {
			if ( false !== $open || false !== $close ) {
				return array( $start, $end );
			}

			// Nothing within the window. A </picture> ahead of the next <picture> means
			// the image is inside a picture whose opening tag is further back than the
			// window reaches - a long <source> block, for instance.
			$ahead = substr( $html, $end, self::EXPAND_WINDOW );

			$next_close = stripos( $ahead, '</picture>' );
			$next_open  = stripos( $ahead, '<picture' );

			if ( false === $next_close || ( false !== $next_open && $next_open < $next_close ) ) {
				return array( $start, $end );
			}

			// Rare and only for oversized pictures, so the unbounded scan is acceptable.
			$open = strripos( substr( $html, 0, $start ), '<picture' );

			if ( false === $open ) {
				return null;
			}

			$window = 0;
		}

		$closing = stripos( $html, '</picture>', $end );

		if ( false === $closing ) {
			// Malformed: no closing tag to delimit the picture.
			return array( $start, $end );
		}

		return array( $window + $open, $closing + strlen( '</picture>' ) );
	}

	/**
	 * Grow a range to an enclosing link, when the link wraps nothing but media.
	 *
	 * @param string $html  Full HTML.
	 * @param int    $start Range start.
	 * @param int    $end   Range end.
	 * @return int[] The possibly widened [start, end].
	 */
	private function expand_to_link( $html, $start, $end ) {
		if ( ! preg_match( '/^(.*?)<\/a\s*>/is', substr( $html, $end, self::LINK_WINDOW ), $tail ) ) {
			return array( $start, $end );
		}

		if ( ! $this->is_media_only( $tail[1] ) ) {
			return array( $start, $end );
		}

		$window = max( 0, $start - self::LINK_WINDOW );
		$before = substr( $html, $window, $start - $window );

		if ( ! preg_match_all( '/<a(?=[\s>])[^>]*>/i', $before, $openings, PREG_OFFSET_CAPTURE ) ) {
			return array( $start, $end );
		}

		list( $tag, $tag_offset ) = end( $openings[0] );

		$between = substr( $before, $tag_offset + strlen( $tag ) );

		// A </a> in between means that opening tag belongs to an earlier link.
		if ( false !== stripos( $between, '</a' ) || ! $this->is_media_only( $between ) ) {
			return array( $start, $end );
		}

		return array( $window + $tag_offset, $end + strlen( $tail[0] ) );
	}

	/**
	 * Whether a fragment holds nothing but image markup and whitespace.
	 *
	 * @param string $html Fragment.
	 * @return bool
	 */
	private function is_media_only( $html ) {
		$stripped = preg_replace( '/<(?:img|source)\b[^>]*>|<\/?picture\b[^>]*>/i', '', (string) $html );

		return '' === trim( (string) $stripped );
	}

	/**
	 * Strip the marker attribute from a tag.
	 *
	 * @param string $tag HTML tag.
	 * @return string
	 */
	private function remove_marker( $tag ) {
		return preg_replace( '/\s*\bdata-euaiact-id\s*=\s*["\']\d+["\']/i', '', $tag );
	}

	/**
	 * Resolve the attachment ID an image tag refers to.
	 *
	 * @param string $img_tag Image HTML tag.
	 * @return int Attachment ID, or 0 when it cannot be resolved.
	 */
	public function extract_attachment_id( $img_tag ) {
		if ( preg_match( '/\bdata-euaiact-id\s*=\s*["\'](\d+)["\']/i', $img_tag, $matches ) ) {
			return (int) $matches[1];
		}

		if ( preg_match( '/\bwp-image-(\d+)/i', $img_tag, $matches ) ) {
			return (int) $matches[1];
		}

		if ( preg_match( '/\bdata-id\s*=\s*["\'](\d+)["\']/i', $img_tag, $matches ) ) {
			return (int) $matches[1];
		}

		if ( null === $this->url_resolver ) {
			return 0;
		}

		if ( ! preg_match( '/\ssrc\s*=\s*["\']([^"\']+)["\']/i', $img_tag, $matches ) ) {
			return 0;
		}

		$url = $matches[1];

		$attachment_id = (int) call_user_func( $this->url_resolver, $url );
		if ( $attachment_id ) {
			return $attachment_id;
		}

		// Scaled sizes such as pic-1024x768.jpg are not attachments themselves.
		$full_size_url = preg_replace( '/-\d+x\d+(\.[A-Za-z0-9]+)$/', '$1', $url );

		if ( $full_size_url === $url ) {
			return 0;
		}

		return (int) call_user_func( $this->url_resolver, $full_size_url );
	}
}
