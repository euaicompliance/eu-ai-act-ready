/**
 * EU AI Act Ready - AI Tools Frontend Notice
 *
 * Renders a configurable notice disclosing which AI tools are active on this site.
 *
 * @package EUAIACTREADY
 */

( function () {
	'use strict';

	if ( typeof euaiactreadyAiToolsConfig === 'undefined' ) {
		return;
	}

	const config = euaiactreadyAiToolsConfig;

	if ( ! config.tools || config.tools.length === 0 ) {
		return;
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		showNotice();
	} );

	/**
	 * Show the notice once the DOM is ready.
	 */
	function showNotice() {
		const el = document.getElementById( 'euaiactready-ai-tools-notice' );
		if ( ! el ) {
			return;
		}

		const style = config.style || 'banner';

		if ( 'modal' === style ) {
			renderModal( el );
		} else {
			el.style.display = '';
			el.classList.add( 'euaiactready-notice-visible' );
		}
	}

	/**
	 * Render the notice as a modal with a trigger button.
	 *
	 * @param {HTMLElement} el - The notice container element.
	 */
	function renderModal( el ) {
		var trigger = document.createElement( 'button' );
		trigger.className = 'euaiactready-modal-trigger';
		trigger.setAttribute( 'aria-haspopup', 'dialog' );
		trigger.textContent = euaiactreadyAiToolsConfig.i18n
			? euaiactreadyAiToolsConfig.i18n.openModal
			: 'AI Tools disclosure';

		var overlay = document.createElement( 'div' );
		overlay.className = 'euaiactready-modal-overlay';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.setAttribute( 'aria-label', trigger.textContent );

		var inner = document.createElement( 'div' );
		inner.className = 'euaiactready-modal-inner';
		inner.innerHTML = el.innerHTML;

		var closeBtn = document.createElement( 'button' );
		closeBtn.className = 'euaiactready-modal-close';
		closeBtn.setAttribute( 'aria-label', 'Close' );
		closeBtn.innerHTML = '&times;';

		inner.prepend( closeBtn );
		overlay.appendChild( inner );
		document.body.appendChild( trigger );
		document.body.appendChild( overlay );

		trigger.addEventListener( 'click', function () {
			overlay.classList.add( 'euaiactready-modal-open' );
			closeBtn.focus();
		} );

		closeBtn.addEventListener( 'click', function () {
			overlay.classList.remove( 'euaiactready-modal-open' );
			trigger.focus();
		} );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				overlay.classList.remove( 'euaiactready-modal-open' );
				trigger.focus();
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && overlay.classList.contains( 'euaiactready-modal-open' ) ) {
				overlay.classList.remove( 'euaiactready-modal-open' );
				trigger.focus();
			}
		} );
	}

}() );
