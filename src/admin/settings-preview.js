/**
 * EU AI Act Ready - Settings Page Preview Updater
 *
 * @package
 */

( function () {
	/**
	 * Show the image badge placement only for the styles that can act on it.
	 *
	 * The Caption style sits below the image rather than on it, so a corner is
	 * meaningless there. Background images are unaffected: a caption has no image box to
	 * sit below, so those fall back to a badge and always have a corner.
	 */
	function bindLabelPlacementVisibility() {
		const styleField = document.getElementById( 'media_label_style' );
		const positionRow = document.getElementById(
			'euaiactready-image-position-row'
		);

		if ( ! styleField || ! positionRow ) {
			return;
		}

		const sync = function () {
			positionRow.style.display =
				styleField.value === 'caption' ? 'none' : '';
		};

		styleField.addEventListener( 'change', sync );
		sync();
	}

	// Wait for DOM to be ready.
	document.addEventListener( 'DOMContentLoaded', function () {
		bindLabelPlacementVisibility();

		if ( typeof euaiactreadySettings === 'undefined' ) {
			return;
		}

		const noticeMessageField = document.getElementById( 'notice_message' );
		const bannerPreviewText = document.getElementById(
			'banner-preview-text'
		);
		const inlinePreviewText = document.getElementById(
			'inline-preview-text'
		);
		const defaultMessage = euaiactreadySettings.defaultMessage;

		if ( noticeMessageField && bannerPreviewText && inlinePreviewText ) {
			noticeMessageField.addEventListener( 'input', function () {
				const customMessage = this.value.trim();
				const displayMessage = customMessage || defaultMessage;

				bannerPreviewText.textContent = displayMessage;
				inlinePreviewText.textContent = displayMessage;
			} );
		}
	} );
} )();
