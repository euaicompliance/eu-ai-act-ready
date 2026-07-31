<?php
/**
 * Tests for the container class that distinguishes builder-placed images.
 *
 * @package EUAIACTREADY
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers EUAIACTREADY_Media_Markup
 */
final class MediaMarkupContainerClassTest extends TestCase {

	/**
	 * Markup helper that treats every attachment as AI-generated.
	 *
	 * @return EUAIACTREADY_Media_Markup
	 */
	private function markup() {
		$detector = static fn( $id ) => array( 'source' => 'Midjourney' );
		$renderer = static fn( $detection ) => '<div class="ai-media-badge">' . $detection['source'] . '</div>';

		return new EUAIACTREADY_Media_Markup( $detector, $renderer, static fn( $url ) => 7 );
	}

	public function test_render_pass_marks_the_container_as_builder_placed() {
		$result = $this->markup()->add_labels( '<img data-euaiact-id="42" src="a.jpg">' );

		$this->assertStringContainsString( 'class="ai-media-container ai-media-bricks"', $result );
	}

	public function test_post_content_keeps_the_plain_container() {
		$result = $this->markup()->add_labels_to_content( '<img class="wp-image-42" src="a.jpg">' );

		$this->assertStringContainsString( 'class="ai-media-container"', $result );
		$this->assertStringNotContainsString( 'ai-media-bricks', $result );
	}

	/**
	 * The modifier drives a stylesheet rule that suppresses the responsive reset, so a
	 * second render pass must not add it to a container the content pass created.
	 */
	public function test_render_pass_does_not_reclassify_an_already_labelled_image() {
		$content = $this->markup()->add_labels_to_content( '<img class="wp-image-42" src="a.jpg">' );

		$result = $this->markup()->add_labels( '<div class="brxe-post-content">' . $content . '</div>' );

		$this->assertStringNotContainsString( 'ai-media-bricks', $result );
	}
}
