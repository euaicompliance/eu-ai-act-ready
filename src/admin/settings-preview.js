/**
 * EU AI Act Ready - Settings Page Preview Updater
 *
 * @package
 */

( function () {
	/**
	 * Show the rows that only apply to a label drawn on the image itself.
	 *
	 * The Caption style sits below the image rather than on it, so a corner is
	 * meaningless there and it has never carried a tooltip. Background images are
	 * unaffected: a caption has no image box to sit below, so those fall back to a badge
	 * and always have both.
	 *
	 * Label Size is deliberately absent - a caption can be compact too.
	 */
	function bindLabelStyleRowVisibility() {
		const styleField = document.getElementById( 'media_label_style' );
		const rows = [
			'euaiactready-image-position-row',
			'euaiactready-image-tooltip-row',
		]
			.map( ( id ) => document.getElementById( id ) )
			.filter( Boolean );

		if ( ! styleField || ! rows.length ) {
			return;
		}

		const sync = function () {
			const hidden = styleField.value === 'caption';

			rows.forEach( ( row ) => {
				row.style.display = hidden ? 'none' : '';
			} );
		};

		styleField.addEventListener( 'change', sync );
		sync();
	}

	// Wait for DOM to be ready.
	document.addEventListener( 'DOMContentLoaded', function () {
		bindLabelStyleRowVisibility();

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
