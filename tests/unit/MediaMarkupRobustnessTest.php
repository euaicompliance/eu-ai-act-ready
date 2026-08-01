<?php
/**
 * Robustness tests: malformed markup must not make labelling degrade badly.
 *
 * @package EUAIACTREADY
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers EUAIACTREADY_Media_Markup
 */
final class MediaMarkupRobustnessTest extends TestCase {

	/**
	 * Markup helper that treats the given attachment IDs as AI-generated.
	 *
	 * @param int[] $ai_ids Attachment IDs considered AI-generated.
	 * @return EUAIACTREADY_Media_Markup
	 */
	private function markup( array $ai_ids ) {
		$detector = static fn( $id ) => in_array( $id, $ai_ids, true ) ? array( 'source' => 'AI' ) : null;
		$renderer = static fn( $detection ) => '<div class="ai-media-caption">AI</div>';

		return new EUAIACTREADY_Media_Markup( $detector, $renderer );
	}

	public function test_many_unclosed_picture_tags_do_not_blow_up_the_matcher() {
		// Malformed markup from a Code element or a broken shortcode must not make
		// labelling scan the rest of the document once per unclosed tag.
		$html = str_repeat( '<a href="x"><picture><source srcset="y">', 2000 )
			. '<img data-euaiact-id="1" src="a.jpg">';

		$started = microtime( true );
		$result  = $this->markup( array( 1 ) )->add_labels( $html );
		$elapsed = ( microtime( true ) - $started ) * 1000;

		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
		$this->assertLessThan( 1000, $elapsed, 'Labelling malformed markup took ' . (int) $elapsed . ' ms' );
	}

	public function test_many_well_formed_pictures_stay_linear() {
		$one  = '<a href="x"><picture><source srcset="y"><img src="p.jpg"></picture></a>';
		$html = str_repeat( $one, 1000 ) . '<img data-euaiact-id="1" src="a.jpg">';

		$started = microtime( true );
		$result  = $this->markup( array( 1 ) )->add_labels( $html );
		$elapsed = ( microtime( true ) - $started ) * 1000;

		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
		$this->assertLessThan( 1000, $elapsed, 'Labelling took ' . (int) $elapsed . ' ms' );
	}

	public function test_two_marked_images_in_one_picture_do_not_corrupt_the_html() {
		$html = '<picture><img data-euaiact-id="1" src="a.jpg"><img data-euaiact-id="1" src="b.jpg"></picture>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertStringNotContainsString( '<div clas<', $result );
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
		$this->assertSame( 1, substr_count( $result, 'ai-media-container' ) );
		$this->assertStringContainsString( '<img src="a.jpg">', $result );
		$this->assertStringContainsString( '<img src="b.jpg">', $result );
	}

	public function test_two_identical_marked_image_tags_in_one_picture_do_not_corrupt_the_html() {
		$img  = '<img data-euaiact-id="1" src="a.jpg">';
		$html = '<picture>' . $img . $img . '</picture>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertStringNotContainsString( '<div clas<', $result );
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
		$this->assertSame( 1, substr_count( $result, 'ai-media-container' ) );
		$this->assertSame( 2, substr_count( $result, '<img src="a.jpg">' ) );
	}

	public function test_two_marked_images_in_one_link_keep_the_label_outside_the_link() {
		$html = '<a href="x"><img data-euaiact-id="1" src="a.jpg"><img data-euaiact-id="1" src="b.jpg"></a>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
		$this->assertStringStartsWith( '<div class="ai-media-container ai-media-bricks"><a href="x">', $result );
		$this->assertStringEndsWith( '</a><div class="ai-media-caption">AI</div></div>', $result );
	}

	public function test_never_inserts_a_div_inside_a_picture_when_the_open_tag_is_far_away() {
		// A <source> block longer than the backward search window pushes <picture> out of
		// reach. Wrapping just the <img> there would put a <div> inside <picture>.
		$long_sources = str_repeat( '<source srcset="' . str_repeat( 'u', 200 ) . '">', 120 );
		$html         = '<picture>' . $long_sources . '<img data-euaiact-id="1" src="a.jpg"></picture>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertStringNotContainsString( '<picture><div', $result );
		$this->assertDoesNotMatchRegularExpression( '/<picture>.*<div class="ai-media-container"/s', $result );
	}

	public function test_labels_a_marked_sibling_of_an_already_labelled_image_exactly_once() {
		// Bricks renders bottom-up, so a parent link can already contain a labelled child.
		// The label may then sit inside that link - the anchor belongs to a parent element
		// that did not exist when the image was rendered, so no amount of inspection here
		// can hoist the label out of it. What must hold is that the sibling is labelled
		// exactly once and the earlier label is left untouched.
		$labelled = '<div class="ai-media-container"><img src="old.jpg">'
			. '<div class="ai-media-caption">AI</div></div>';
		$html     = '<a href="x">' . $labelled . '<img data-euaiact-id="1" src="b.jpg"></a>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame( 2, substr_count( $result, 'ai-media-caption' ) );
		$this->assertStringContainsString( $labelled, $result );
		$this->assertStringContainsString( '<img src="b.jpg">', $result );
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
		$this->assertStringNotContainsString( '<div clas<', $result );
	}

	public function test_nested_picture_link_picture_wraps_the_outermost_picture() {
		// Invalid nesting, but alternating picture/link expansion still finds a valid spot:
		// the wrapper goes around the outer <picture>, never inside one.
		$html = '<picture><source srcset="outer.webp">'
			. '<a href="x"><picture><img data-euaiact-id="1" src="a.jpg"></picture></a>'
			. '</picture>';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
		$this->assertStringStartsWith( '<div class="ai-media-container ai-media-bricks"><picture>', $result );
		$this->assertDoesNotMatchRegularExpression(
			'/<picture[^>]*>(?:(?!<\/picture>).)*?<div/s',
			$result
		);
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
	}


	public function test_unclosed_picture_around_the_marked_image_still_labels_it() {
		$html = '<picture><source srcset="y"><img data-euaiact-id="1" src="a.jpg">';

		$result = $this->markup( array( 1 ) )->add_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-caption' ) );
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
	}
}
