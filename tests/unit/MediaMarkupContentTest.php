<?php
/**
 * Tests for labelling post content, where images carry no marker attribute.
 *
 * @package EUAIACTREADY
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers EUAIACTREADY_Media_Markup
 */
final class MediaMarkupContentTest extends TestCase {

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

	public function test_labels_an_image_identified_only_by_its_wp_image_class() {
		$html = '<p>Intro</p><img class="wp-image-42" src="a.jpg" alt="">';

		$result = $this->markup( array( 42 ) )->add_labels_to_content( $html );

		$this->assertSame(
			'<p>Intro</p><div class="ai-media-container"><img class="wp-image-42" src="a.jpg" alt="">'
				. '<div class="ai-media-caption">Midjourney</div></div>',
			$result
		);
	}

	public function test_labels_each_of_two_identical_image_tags_exactly_once() {
		$img  = '<img class="wp-image-42" src="a.jpg" alt="">';
		$html = $img . '<p>between</p>' . $img;

		$result = $this->markup( array( 42 ) )->add_labels_to_content( $html );

		$this->assertSame( 2, substr_count( $result, 'ai-media-container' ) );
		$this->assertSame( 2, substr_count( $result, 'ai-media-caption' ) );
	}

	public function test_leaves_non_ai_images_in_content_untouched() {
		$html = '<img class="wp-image-7" src="a.jpg" alt="">';

		$result = $this->markup( array( 42 ) )->add_labels_to_content( $html );

		$this->assertSame( $html, $result );
	}

	public function test_ignores_a_marker_hand_written_into_post_content() {
		// Stored content never legitimately carries the marker: it is added while
		// WordPress builds image markup, never saved. One in post_content is author
		// input and must not be able to claim another attachment's AI status.
		$html = '<img data-euaiact-id="42" src="https://example.com/unrelated.jpg" alt="">';

		$result = $this->markup( array( 42 ) )->add_labels_to_content( $html );

		$this->assertStringNotContainsString( 'ai-media-caption', $result );
		$this->assertStringNotContainsString( 'data-euaiact-id', $result );
	}

	public function test_content_pass_consumes_markers_so_a_later_pass_skips_the_image() {
		$html = '<img data-euaiact-id="42" class="wp-image-42" src="a.jpg" alt="">';

		$once  = $this->markup( array( 42 ) )->add_labels_to_content( $html );
		$twice = $this->markup( array( 42 ) )->add_labels( $once );

		$this->assertSame( 1, substr_count( $once, 'ai-media-caption' ) );
		$this->assertSame( $once, $twice );
	}
}
