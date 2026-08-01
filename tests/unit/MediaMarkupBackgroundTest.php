<?php
/**
 * Tests for labelling CSS background images.
 *
 * @package EUAIACTREADY
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers EUAIACTREADY_Media_Markup
 */
final class MediaMarkupBackgroundTest extends TestCase {

	/**
	 * Markup helper treating the given IDs as AI, resolving upload URLs to their ID.
	 *
	 * @param int[] $ai_ids Attachment IDs considered AI-generated.
	 * @param array $urls   Map of URL to attachment ID.
	 * @return EUAIACTREADY_Media_Markup
	 */
	private function markup( array $ai_ids, array $urls = array() ) {
		return new EUAIACTREADY_Media_Markup(
			static fn( $id ) => in_array( $id, $ai_ids, true ) ? array( 'source' => 'AI' ) : null,
			static fn( $detection ) => '<div class="ai-media-caption">AI</div>',
			static fn( $url ) => $urls[ $url ] ?? 0,
			static fn( $detection ) => '<div class="ai-media-bg-label">AI</div>'
		);
	}

	public function test_labels_a_root_marked_with_a_background_attachment() {
		$html = '<section data-euaiact-bg="1" class="brxe-section"><p>content</p></section>';

		$result = $this->markup( array( 1 ) )->add_background_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-bg-label' ) );
		$this->assertStringNotContainsString( 'data-euaiact-bg="1"', $result );
		$this->assertStringContainsString( '<p>content</p>', $result );
	}

	public function test_consumes_the_marker_for_a_background_that_is_not_ai() {
		$html = '<section data-euaiact-bg="9"><p>c</p></section>';

		$result = $this->markup( array( 1 ) )->add_background_labels( $html );

		$this->assertStringNotContainsString( 'ai-media-bg-label', $result );
		$this->assertStringNotContainsString( 'data-euaiact-bg="9"', $result );
	}

	public function test_labels_an_inline_style_background_image() {
		$urls = array( 'https://example.com/uploads/a.jpg' => 1 );
		$html = '<div class="brxe-carousel-item" style="background-image: url(&quot;https://example.com/uploads/a.jpg&quot;)"></div>';

		$result = $this->markup( array( 1 ), $urls )->add_background_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-bg-label' ) );
	}

	public function test_labels_a_lazy_loaded_data_style_background_image() {
		$urls = array( 'https://example.com/uploads/a.jpg' => 1 );
		$html = '<div data-style="background-image: url(https://example.com/uploads/a.jpg)"></div>';

		$result = $this->markup( array( 1 ), $urls )->add_background_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-bg-label' ) );
	}

	public function test_ignores_background_images_on_tags_that_may_not_take_a_child_div() {
		$urls = array( 'https://example.com/uploads/a.jpg' => 1 );
		$html = '<ul style="background-image: url(https://example.com/uploads/a.jpg)"><li>x</li></ul>';

		$result = $this->markup( array( 1 ), $urls )->add_background_labels( $html );

		$this->assertStringNotContainsString( 'ai-media-bg-label', $result );
	}

	public function test_ignores_a_background_that_is_not_an_attachment() {
		$html = '<div style="background-image: url(https://cdn.example.net/external.jpg)"></div>';

		$result = $this->markup( array( 1 ) )->add_background_labels( $html );

		$this->assertStringNotContainsString( 'ai-media-bg-label', $result );
	}

	public function test_is_idempotent_across_repeated_passes() {
		$urls   = array( 'https://example.com/uploads/a.jpg' => 1 );
		$markup = $this->markup( array( 1 ), $urls );
		$html   = '<div style="background-image: url(https://example.com/uploads/a.jpg)"></div>';

		$once  = $markup->add_background_labels( $html );
		$twice = $markup->add_background_labels( '<section>' . $once . '</section>' );

		$this->assertSame( 1, substr_count( $twice, 'ai-media-bg-label' ) );
	}

	public function test_labels_each_of_several_background_nodes() {
		$urls = array(
			'https://example.com/uploads/a.jpg' => 1,
			'https://example.com/uploads/b.jpg' => 1,
		);
		$html = '<div style="background-image: url(https://example.com/uploads/a.jpg)"></div>'
			. '<div style="background-image: url(https://example.com/uploads/b.jpg)"></div>';

		$result = $this->markup( array( 1 ), $urls )->add_background_labels( $html );

		$this->assertSame( 2, substr_count( $result, 'ai-media-bg-label' ) );
	}

	public function test_a_marker_string_inside_another_attribute_value_does_not_suppress_the_badge() {
		// Anyone who can set Bricks custom attributes could otherwise switch labelling off
		// for an element by naming the done-marker in some unrelated attribute value.
		$html = '<div data-note="data-euaiact-bg-done" data-euaiact-bg="1"></div>';

		$result = $this->markup( array( 1 ) )->add_background_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-bg-label' ) );
		$this->assertStringNotContainsString( 'data-euaiact-bg="1"', $result );
	}

	public function test_uses_the_last_background_image_declaration_like_the_css_cascade() {
		$urls = array(
			'https://example.com/uploads/plain.jpg' => 5,
			'https://example.com/uploads/ai.jpg'    => 1,
		);
		$html = '<div style="background-image: url(https://example.com/uploads/plain.jpg);'
			. ' background-image: url(https://example.com/uploads/ai.jpg)"></div>';

		$result = $this->markup( array( 1 ), $urls )->add_background_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-bg-label' ) );
	}

	public function test_an_overridden_ai_declaration_does_not_produce_a_false_label() {
		$urls = array(
			'https://example.com/uploads/ai.jpg'    => 1,
			'https://example.com/uploads/plain.jpg' => 5,
		);
		$html = '<div style="background-image: url(https://example.com/uploads/ai.jpg);'
			. ' background-image: url(https://example.com/uploads/plain.jpg)"></div>';

		$result = $this->markup( array( 1 ), $urls )->add_background_labels( $html );

		$this->assertStringNotContainsString( 'ai-media-bg-label', $result );
	}

	public function test_labels_a_multi_layer_background_when_any_layer_is_ai() {
		$urls = array(
			'https://example.com/uploads/overlay.png' => 5,
			'https://example.com/uploads/ai.jpg'      => 1,
		);
		$html = '<div style="background-image: url(https://example.com/uploads/overlay.png),'
			. ' url(https://example.com/uploads/ai.jpg)"></div>';

		$result = $this->markup( array( 1 ), $urls )->add_background_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-bg-label' ) );
	}

	public function test_handles_single_quoted_urls() {
		$urls = array( 'https://example.com/uploads/a.jpg' => 1 );
		$html = "<div style=\"background-image: url('https://example.com/uploads/a.jpg')\"></div>";

		$result = $this->markup( array( 1 ), $urls )->add_background_labels( $html );

		$this->assertSame( 1, substr_count( $result, 'ai-media-bg-label' ) );
	}

	public function test_leaves_html_untouched_when_no_background_is_ai() {
		$html = '<section class="brxe-section"><p>content</p></section>';

		$this->assertSame( $html, $this->markup( array( 1 ) )->add_background_labels( $html ) );
	}
}
