<?php
/**
 * Tests for injecting AI transparency labels into rendered HTML.
 *
 * @package EUAIACTREADY
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers EUAIACTREADY_Media_Markup
 */
final class MediaMarkupAddLabelsTest extends TestCase {

	/**
	 * Markup helper that treats the given attachment IDs as AI-generated.
	 *
	 * @param int[] $ai_ids Attachment IDs considered AI-generated.
	 * @return EUAIACTREADY_Media_Markup
	 */
	private function markup( array $ai_ids ) {
		$detector = static fn( $id ) => in_array( $id, $ai_ids, true )
			? array( 'source' => 'Midjourney' )
			: null;
		$renderer = static fn( $detection ) => '<div class="ai-media-caption">' . $detection['source'] . '</div>';

		return new EUAIACTREADY_Media_Markup( $detector, $renderer );
	}

	public function test_wraps_a_marked_image_and_appends_the_label() {
		$html = '<img data-euaiact-id="42" src="a.jpg" alt="">';

		$result = $this->markup( array( 42 ) )->add_labels( $html );

		$this->assertSame(
			'<div class="ai-media-container ai-media-bricks"><img src="a.jpg" alt=""><div class="ai-media-caption">Midjourney</div></div>',
			$result
		);
	}

	public function test_labels_every_marked_image_in_the_same_container() {
		$html = '<div class="brxe-container">'
			. '<img data-euaiact-id="1" src="a.jpg">'
			. '<img data-euaiact-id="2" src="b.jpg">'
			. '</div>';

		$result = $this->markup( array( 1, 2 ) )->add_labels( $html );

		$this->assertSame( 2, substr_count( $result, 'ai-media-caption' ) );
		$this->assertSame( 2, substr_count( $result, 'ai-media-container' ) );
	}

	public function test_is_idempotent_when_parent_html_is_filtered_again() {
		$child = $this->markup( array( 1 ) )->add_labels( '<img data-euaiact-id="1" src="a.jpg">' );

		$parent = $this->markup( array( 1 ) )->add_labels( '<section>' . $child . '</section>' );

		$this->assertSame( '<section>' . $child . '</section>', $parent );
		$this->assertSame( 1, substr_count( $parent, 'ai-media-caption' ) );
	}

	public function test_labels_an_unprocessed_sibling_of_an_already_labelled_image() {
		$labelled = $this->markup( array( 1 ) )->add_labels( '<img data-euaiact-id="1" src="a.jpg">' );
		$html     = '<section>' . $labelled . '<img data-euaiact-id="2" src="b.jpg"></section>';

		$result = $this->markup( array( 1, 2 ) )->add_labels( $html );

		$this->assertSame( 2, substr_count( $result, 'ai-media-caption' ) );
	}

	public function test_leaves_unmarked_images_byte_identical() {
		$html = '<div><img class="wp-image-42" src="a.jpg" alt="cat"><img data-euaiact-id="7" src="b.jpg"></div>';

		$result = $this->markup( array( 42, 7 ) )->add_labels( $html );

		$this->assertStringContainsString( '<img class="wp-image-42" src="a.jpg" alt="cat">', $result );
		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
	}

	public function test_wraps_the_enclosing_link_so_the_label_is_not_clickable() {
		$html = '<a href="full.jpg" class="bricks-lightbox" data-pswp-width="1200">'
			. '<img data-euaiact-id="1" src="a.jpg">'
			. '</a>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame(
			'<div class="ai-media-container ai-media-bricks">'
				. '<a href="full.jpg" class="bricks-lightbox" data-pswp-width="1200"><img src="a.jpg"></a>'
				. '<div class="ai-media-caption">Midjourney</div>'
				. '</div>',
			$result
		);
	}

	public function test_wraps_the_picture_element_instead_of_inserting_a_div_into_it() {
		$html = '<picture><source srcset="a.webp" type="image/webp"><img data-euaiact-id="1" src="a.jpg"></picture>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame(
			'<div class="ai-media-container ai-media-bricks">'
				. '<picture><source srcset="a.webp" type="image/webp"><img src="a.jpg"></picture>'
				. '<div class="ai-media-caption">Midjourney</div>'
				. '</div>',
			$result
		);
		$this->assertStringNotContainsString( '<picture><div', $result );
	}

	public function test_wraps_a_linked_picture_outside_the_link() {
		$html = '<a href="full.jpg" class="bricks-lightbox">'
			. '<picture><source srcset="a.webp"><img data-euaiact-id="1" src="a.jpg"></picture>'
			. '</a>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertStringStartsWith( '<div class="ai-media-container ai-media-bricks"><a href="full.jpg"', $result );
		$this->assertStringEndsWith( '</a><div class="ai-media-caption">Midjourney</div></div>', $result );
		$this->assertSame( 1, substr_count( $result, 'ai-media-container' ) );
	}

	public function test_labels_two_separate_pictures_without_merging_them() {
		$html = '<picture><img data-euaiact-id="1" src="a.jpg"></picture>'
			. '<p>text</p>'
			. '<picture><img data-euaiact-id="2" src="b.jpg"></picture>';

		$result = $this->markup( array( 1, 2 ) )->add_labels( $html );

		$this->assertSame( 2, substr_count( $result, 'ai-media-container' ) );
		$this->assertStringContainsString( '<p>text</p>', $result );
	}

	public function test_labels_a_marked_picture_that_follows_an_unmarked_one() {
		$html = '<picture><img src="plain.jpg"></picture>'
			. '<p>x</p>'
			. '<picture><img data-euaiact-id="1" src="ai.jpg"></picture>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
		$this->assertStringContainsString( '<picture><img src="plain.jpg"></picture>', $result );
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
	}

	public function test_labels_a_marked_linked_picture_that_follows_an_unmarked_one() {
		$html = '<a href="plain"><picture><img src="plain.jpg"></picture></a>'
			. '<p>x</p>'
			. '<a href="ai"><picture><img data-euaiact-id="1" src="ai.jpg"></picture></a>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
		$this->assertStringContainsString( '<a href="plain"><picture><img src="plain.jpg"></picture></a>', $result );
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
	}

	public function test_labels_a_marked_linked_image_that_follows_an_unmarked_one() {
		$html = '<a href="plain"><img src="plain.jpg"></a>'
			. '<a href="ai"><img data-euaiact-id="1" src="ai.jpg"></a>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
		$this->assertStringContainsString( '<a href="plain"><img src="plain.jpg"></a>', $result );
	}

	public function test_handles_single_quoted_and_self_closing_markup() {
		$html = "<img data-euaiact-id='1' src='a.jpg' />";

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
	}

	public function test_leaves_marked_images_that_are_not_ai_untouched_but_consumes_the_marker() {
		$html = '<img data-euaiact-id="99" src="a.jpg" alt="">';

		$result = $this->markup( array( 42 ) )->add_labels( $html );

		$this->assertSame( '<img src="a.jpg" alt="">', $result );
	}
}
