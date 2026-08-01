/**
 * EU AI Act Ready - Settings Page Preview Updater
 *
 * @package
 */

( function () {
	// Wait for DOM to be ready.
	document.addEventListener( 'DOMContentLoaded', function () {
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
