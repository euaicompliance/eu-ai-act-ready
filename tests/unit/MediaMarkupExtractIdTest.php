<?php
/**
 * Tests for attachment ID extraction from image tags.
 *
 * @package EUAIACTREADY
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers EUAIACTREADY_Media_Markup
 */
final class MediaMarkupExtractIdTest extends TestCase {

	/**
	 * Build a markup helper that labels nothing (not used by these tests).
	 *
	 * @param callable|null $url_resolver Optional URL to attachment ID resolver.
	 * @return EUAIACTREADY_Media_Markup
	 */
	private function markup( ?callable $url_resolver = null ) {
		return new EUAIACTREADY_Media_Markup(
			static fn( $id ) => null,
			static fn( $detection ) => '',
			$url_resolver
		);
	}

	public function test_extracts_id_from_marker_attribute() {
		$img = '<img data-euaiact-id="42" src="https://example.com/a.jpg" alt="">';

		$this->assertSame( 42, $this->markup()->extract_attachment_id( $img ) );
	}

	public function test_extracts_id_from_wp_image_class() {
		$img = '<img class="alignnone size-large wp-image-77" src="https://example.com/a.jpg" alt="">';

		$this->assertSame( 77, $this->markup()->extract_attachment_id( $img ) );
	}

	public function test_extracts_id_from_data_id_attribute() {
		$img = '<img data-id="9" src="https://example.com/a.jpg" alt="">';

		$this->assertSame( 9, $this->markup()->extract_attachment_id( $img ) );
	}

	public function test_falls_back_to_resolving_the_src_url() {
		$seen     = array();
		$resolver = static function ( $url ) use ( &$seen ) {
			$seen[] = $url;
			return 'https://example.com/wp-content/uploads/2026/07/pic.jpg' === $url ? 123 : 0;
		};
		$img = '<img src="https://example.com/wp-content/uploads/2026/07/pic.jpg" alt="">';

		$this->assertSame( 123, $this->markup( $resolver )->extract_attachment_id( $img ) );
		$this->assertSame( array( 'https://example.com/wp-content/uploads/2026/07/pic.jpg' ), $seen );
	}

	public function test_strips_size_suffix_before_resolving_the_src_url() {
		$resolver = static fn( $url ) => 'https://example.com/wp-content/uploads/2026/07/pic.jpg' === $url ? 123 : 0;
		$img      = '<img src="https://example.com/wp-content/uploads/2026/07/pic-1024x768.jpg" alt="">';

		$this->assertSame( 123, $this->markup( $resolver )->extract_attachment_id( $img ) );
	}

	public function test_ignores_srcset_when_looking_for_src() {
		$resolver = static fn( $url ) => 'https://example.com/uploads/real.jpg' === $url ? 5 : 0;
		$img      = '<img srcset="https://example.com/uploads/decoy.jpg 800w" src="https://example.com/uploads/real.jpg" alt="">';

		$this->assertSame( 5, $this->markup( $resolver )->extract_attachment_id( $img ) );
	}

	public function test_returns_zero_when_nothing_resolves() {
		$img = '<img src="https://example.com/external.jpg" alt="">';

		$this->assertSame( 0, $this->markup()->extract_attachment_id( $img ) );
	}
}
