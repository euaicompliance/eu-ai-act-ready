/**
 * EU AI Act Ready - AI Tools Admin JavaScript
 *
 * Handles: registry refresh, visibility toggles, manual declarations, remove manual.
 *
 * @package EUAIACTREADY
 */

( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		initRefreshButton();
		initVisibilityToggles();
		initManualDeclarations();
		initRemoveManual();
		initNoticeStyleForm();
	} );

	// ==========================================
	// Registry refresh
	// ==========================================

	function initRefreshButton() {
		$( '#euaiactready-refresh-registry' ).on( 'click', function () {
			var $btn = $( this );
			var $msg = $( '#euaiactready-refresh-message' );

			$btn.prop( 'disabled', true ).find( '.dashicons' ).addClass( 'spin' );
			$msg.hide().removeClass( 'notice-success notice-error' );

			$.ajax( {
				url: euaiactreadyAjax.ajax_url,
				method: 'POST',
				data: {
					action: 'euaiactready_refresh_ai_tools_registry',
					nonce: euaiactreadyAjax.nonce,
				},
				success: function ( response ) {
					if ( response.success ) {
						$msg
							.addClass( 'notice-success' )
							.html( '<p>' + escHtml( response.data.message ) + '</p>' )
							.show();
						// Reload the page after a short delay so updated counts show.
						setTimeout( function () {
							window.location.reload();
						}, 1500 );
					} else {
						var errMsg = response.data && response.data.message
							? response.data.message
							: euaiactreadyAiTools.i18n.errorOccurred;
						$msg
							.addClass( 'notice-error' )
							.html( '<p>' + escHtml( errMsg ) + '</p>' )
							.show();
					}
				},
				error: function () {
					$msg
						.addClass( 'notice-error' )
						.html( '<p>' + escHtml( euaiactreadyAiTools.i18n.errorOccurred ) + '</p>' )
						.show();
				},
				complete: function () {
					$btn.prop( 'disabled', false ).find( '.dashicons' ).removeClass( 'spin' );
				},
			} );
		} );
	}

	// ==========================================
	// Visibility toggles
	// ==========================================

	function initVisibilityToggles() {
		$( document ).on( 'change', '.euaiactready-visibility-toggle', function () {
			var $checkbox = $( this );
			var toolId    = $checkbox.data( 'tool-id' );
			var visible   = $checkbox.prop( 'checked' );

			$checkbox.prop( 'disabled', true );

			$.ajax( {
				url: euaiactreadyAjax.ajax_url,
				method: 'POST',
				data: {
					action: 'euaiactready_ai_tools_toggle_visibility',
					nonce: euaiactreadyAjax.nonce,
					tool_id: toolId,
					visible: visible ? '1' : '0',
				},
				success: function ( response ) {
					if ( ! response.success ) {
						// Revert on failure.
						$checkbox.prop( 'checked', ! visible );
					}
				},
				error: function () {
					$checkbox.prop( 'checked', ! visible );
				},
				complete: function () {
					$checkbox.prop( 'disabled', false );
				},
			} );
		} );
	}

	// ==========================================
	// Manual declarations
	// ==========================================

	function initManualDeclarations() {
		$( document ).on( 'click', '.euaiactready-declare-ai', function () {
			var $btn      = $( this );
			var $row      = $btn.closest( 'tr' );
			var slug      = $btn.data( 'slug' );
			var name      = $btn.data( 'name' );
			var category  = $row.find( '.euaiactready-manual-category' ).val();

			$btn.prop( 'disabled', true ).text( euaiactreadyAiTools.i18n.saving );

			$.ajax( {
				url: euaiactreadyAjax.ajax_url,
				method: 'POST',
				data: {
					action: 'euaiactready_ai_tools_add_manual',
					nonce: euaiactreadyAjax.nonce,
					slug: slug,
					name: name,
					category: category,
				},
				success: function ( response ) {
					if ( response.success ) {
						// Remove the row and reload to show tool in detected table.
						$row.fadeOut( 300, function () {
							$row.remove();
							window.location.reload();
						} );
					} else {
						$btn.prop( 'disabled', false ).text( euaiactreadyAiTools.i18n.markAsAi );
					}
				},
				error: function () {
					$btn.prop( 'disabled', false ).text( euaiactreadyAiTools.i18n.markAsAi );
				},
			} );
		} );
	}

	// ==========================================
	// Remove manual declarations
	// ==========================================

	function initRemoveManual() {
		$( document ).on( 'click', '.euaiactready-remove-manual', function () {
			var $btn   = $( this );
			var toolId = $btn.data( 'tool-id' );

			if ( ! window.confirm( euaiactreadyAiTools.i18n.confirmRemove ) ) {
				return;
			}

			$btn.prop( 'disabled', true );

			$.ajax( {
				url: euaiactreadyAjax.ajax_url,
				method: 'POST',
				data: {
					action: 'euaiactready_ai_tools_remove_manual',
					nonce: euaiactreadyAjax.nonce,
					tool_id: toolId,
				},
				success: function ( response ) {
					if ( response.success ) {
						$btn.closest( 'tr' ).fadeOut( 300, function () {
							$( this ).remove();
							window.location.reload();
						} );
					} else {
						$btn.prop( 'disabled', false );
					}
				},
				error: function () {
					$btn.prop( 'disabled', false );
				},
			} );
		} );
	}

	// ==========================================
	// Notice style form (save without page reload needed, WP handles it)
	// ==========================================

	function initNoticeStyleForm() {
		// Register the setting so the WP Settings API form works.
		// (The form POSTs to options.php via standard WP mechanism; no JS needed.)
	}

	// ==========================================
	// Helpers
	// ==========================================

	function escHtml( str ) {
		var div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( String( str ) ) );
		return div.innerHTML;
	}

}( jQuery ) );
