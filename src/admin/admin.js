/**
 * EU AI Act Ready Admin JavaScript
 *
 * @package
 */

// Import DataTables.
import 'datatables.net';
import 'datatables.net-dt/css/dataTables.dataTables.css';

( function ( $ ) {
	'use strict';

	// ========================================
	// 1. STATE VARIABLES
	// ========================================
	let currentProgress = 0;
	let currentProcessed = 0;
	let animationInterval = null;

	// ========================================
	// 2. INITIALIZATION
	// ========================================
	$( document ).ready( function () {
		initializeModals();
		initializeEventHandlers();
		initializeDataTables();
		initializeQuickEdit();
		initializeChatbotPreview();
		initializeSettingsTabs();
		initializeBulkActions();
		initializeSelectAllCheckboxes();
		initializeBulkScanResumeModal();
	} );

	// ========================================
	// 3. MODAL MANAGEMENT
	// ========================================

	/**
	 * Initialize transparency and chatbot modals
	 */
	function initializeModals() {
		createTransparencyModal();
		createChatbotModal();
		setupModalEventHandlers();
	}

	/**
	 * Create transparency preview modal
	 */
	function createTransparencyModal() {
		if ( ! $( '#admin-transparency-preview-modal' ).length ) {
			const transparencyModalHtml =
				'<div id="admin-transparency-preview-modal" class="admin-transparency-modal" style="display: none;">' +
				'<div class="admin-transparency-modal-content">' +
				'<span class="admin-transparency-modal-close">&times;</span>' +
				'<h3>' +
				getAiIcon( 20, '#667eea' ) +
				' AI Disclosure</h3>' +
				'<p id="admin-transparency-modal-message">This content includes AI-generated text.</p>' +
				'</div>' +
				'</div>';
			$( 'body' ).append( transparencyModalHtml );
		}
	}

	/**
	 * Create chatbot preview modal
	 */
	function createChatbotModal() {
		if ( ! $( '#admin-chatbot-preview-modal' ).length ) {
			const modalHtml =
				'<div id="admin-chatbot-preview-modal" class="admin-chatbot-modal" style="display: none;">' +
				'<div class="admin-chatbot-modal-content">' +
				'<span class="admin-chatbot-modal-close">&times;</span>' +
				'<h3>' +
				getAiIcon( 20, '#667eea' ) +
				' AI Disclosure</h3>' +
				'<p id="admin-chatbot-modal-message">This chat uses AI assistance.</p>' +
				'</div>' +
				'</div>';
			$( 'body' ).append( modalHtml );
		}
	}

	/**
	 * Setup all modal event handlers
	 */
	function setupModalEventHandlers() {
		// Transparency modal trigger.
		$( document ).on(
			'click',
			'.ai-transparency-modal-trigger-preview',
			function ( e ) {
				e.preventDefault();
				e.stopPropagation();

				const message =
					$( '#notice_message' ).val() ||
					'This content includes AI-generated text.';
				$( '#admin-transparency-modal-message' ).text( message );
				$( '#admin-transparency-preview-modal' ).fadeIn( 300 );
			}
		);

		// Transparency modal close (X button).
		$( document ).on(
			'click',
			'.admin-transparency-modal-close',
			function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				$( '#admin-transparency-preview-modal' ).fadeOut( 300 );
			}
		);

		// Transparency modal close (outside click).
		$( document ).on(
			'click',
			'#admin-transparency-preview-modal',
			function ( e ) {
				if ( e.target === this ) {
					$( this ).fadeOut( 300 );
				}
			}
		);

		// Chatbot modal trigger.
		$( document ).on( 'click', '#chatbot-preview-modal', function ( e ) {
			e.preventDefault();
			e.stopPropagation();

			const message =
				$( '#chatbot_notice_message' ).val() ||
				'This chat uses AI assistance.';
			$( '#admin-chatbot-modal-message' ).text( message );
			$( '#admin-chatbot-preview-modal' ).fadeIn( 300 );
		} );

		// Chatbot modal close (X button).
		$( document ).on(
			'click',
			'.admin-chatbot-modal-close',
			function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				$( '#admin-chatbot-preview-modal' ).fadeOut( 300 );
			}
		);

		// Chatbot modal close (outside click).
		$( document ).on(
			'click',
			'#admin-chatbot-preview-modal',
			function ( e ) {
				if ( e.target === this ) {
					$( this ).fadeOut( 300 );
				}
			}
		);

		// Close all modals on Escape key.
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				$( '#admin-transparency-preview-modal' ).fadeOut( 300 );
				$( '#admin-chatbot-preview-modal' ).fadeOut( 300 );
			}
		} );
	}

	// ========================================
	// 4. SCAN FUNCTIONALITY
	// ========================================

	/**
	 * Initialize scan-related event handlers
	 */
	function initializeEventHandlers() {
		// Scan log toggle.
		$( document ).on( 'click', '.scan-log-toggle', handleScanLogToggle );

		// Scan button click.
		$( '#scan-ajax-button' ).on( 'click', handleScanButtonClick );
	}

	/**
	 * Initialize bulk scan resume modal.
	 */
	function initializeBulkScanResumeModal() {
		if ( typeof euaiactreadyAjax === 'undefined' ) {
			return;
		}

		const $modal = $( '#euaiactready-bulk-scan-modal' );
		if ( ! $modal.length ) {
			return;
		}

		function showNotice( type, message ) {
			const notice = $(
				'<div class="notice notice-' +
					type +
					' is-dismissible"><p></p></div>'
			);
			notice.find( 'p' ).text( message );
			$( '.wrap h1' ).first().after( notice );
		}

		function toggleModal( show ) {
			if ( show ) {
				$modal.addClass( 'is-active' );
			} else {
				$modal.removeClass( 'is-active' );
			}
		}

		function updateMessage( message ) {
			$( '#euaiactready-bulk-scan-message' ).text( message );
		}

		function autoSaveResults() {
			updateMessage( 'Updating information from last scan...' );

			$.post( euaiactreadyAjax.ajax_url, {
				action: 'euaiactready_flush_bulk_scan_buffer',
				nonce: euaiactreadyAjax.nonce,
			} )
				.done( function ( response ) {
					if ( response && response.success ) {
						updateMessage( 'Information updated successfully!' );
						setTimeout( function () {
							toggleModal( false );
							showNotice(
								'success',
								euaiactreadyAjax.i18n.scanSaved ||
									'Saved scan progress.'
							);
						}, 1000 );
					} else {
						updateMessage( 'Error updating information.' );
						setTimeout( function () {
							toggleModal( false );
							showNotice(
								'error',
								euaiactreadyAjax.i18n.errorOccurred ||
									'An error occurred. Please try again.'
							);
						}, 1500 );
					}
				} )
				.fail( function () {
					updateMessage( 'Error updating information.' );
					setTimeout( function () {
						toggleModal( false );
						showNotice(
							'error',
							euaiactreadyAjax.i18n.errorOccurred ||
								'An error occurred. Please try again.'
						);
					}, 1500 );
				} );
		}

		function checkBuffer() {
			$.post( euaiactreadyAjax.ajax_url, {
				action: 'euaiactready_check_bulk_scan_buffer',
				nonce: euaiactreadyAjax.nonce,
			} ).done( function ( response ) {
				if (
					response &&
					response.success &&
					response.data &&
					response.data.count > 0
				) {
					toggleModal( true );
					// Automatically save results after showing modal
					setTimeout( autoSaveResults, 500 );
				}
			} );
		}

		const modalCount = parseInt( $modal.data( 'bufferCount' ), 10 );

		if ( ! isNaN( modalCount ) && modalCount > 0 ) {
			toggleModal( true );
			// Automatically save results after showing modal
			setTimeout( autoSaveResults, 500 );
		} else if ( typeof euaiactreadyAjax.bulkScanBufferCount === 'number' ) {
			if ( euaiactreadyAjax.bulkScanBufferCount > 0 ) {
				toggleModal( true );
				// Automatically save results after showing modal
				setTimeout( autoSaveResults, 500 );
			}
		} else {
			checkBuffer();
		}
	}

	/**
	 * Handle scan log toggle
	 *
	 * @param e The event object.
	 */
	function handleScanLogToggle( e ) {
		e.preventDefault();
		const $content = $( this ).next( '.scan-log-content' );
		const $icon = $( this ).find( '.toggle-icon' );

		$content.slideToggle( 300 );

		if ( $content.is( ':visible' ) ) {
			$icon.html(
				'<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" class="scan-toggle-icon"><path d="M7 10l5 5 5-5z"/></svg>'
			);
		} else {
			$icon.html(
				'<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" class="scan-toggle-icon"><path d="M10 7l5 5-5 5z"/></svg>'
			);
		}
	}

	/**
	 * Handle scan button click - initiates chunked scan
	 *
	 * @param e The event object.
	 */
	function handleScanButtonClick( e ) {
		e.preventDefault();

		const $btn = $( this );
		const $container = $( '#live-scan-container' );
		const $log = $( '#live-scan-log' );

		// Disable button and add loading spinner.
		$btn.prop( 'disabled', true ).html(
			'<span class="dashicons dashicons-update-alt scan-button-icon"></span>Scanning...'
		);

		// Show warning notice.
		$( '.scan-warning-notice' ).slideDown();

		// Reset progress tracking.
		currentProgress = 0;
		currentProcessed = 0;
		if ( animationInterval ) {
			clearInterval( animationInterval );
		}

		// Show and clear log container.
		$container.slideDown();
		$log.html( '' );
		$( '.live-indicator' ).show().text( '● SCANNING' );

		// Add progress bar.
		$( '#scan-progress-wrapper' ).html(
			'<div class="scan-progress-container">' +
				'<div class="scan-progress-bar">' +
				'<div class="scan-progress-fill"></div>' +
				'</div>' +
				'<div class="scan-progress-text">Starting scan...</div>' +
				'</div>'
		);

		addLogEntry( 'info', 'Initiating scan...' );
		processChunk( 0, $btn );
	}

	/**
	 * Process a single chunk of the scan
	 *
	 * @param chunkNumber The current chunk index being processed.
	 * @param $btn        The scan button jQuery object.
	 */
	function processChunk( chunkNumber, $btn ) {
		$.ajax( {
			url: euaiactreadyAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'euaiactready_chunk_scan',
				nonce: euaiactreadyAjax.nonce,
				chunk: chunkNumber,
			},
			success( response ) {
				if ( response.success && response.data ) {
					const data = response.data;

					animateProgress(
						data.progress,
						data.processed,
						data.total
					);

					if ( data.found_in_chunk > 0 ) {
						addLogEntry(
							'detection',
							'Chunk ' +
								( chunkNumber + 1 ) +
								': Found ' +
								data.found_in_chunk +
								' AI images'
						);
					} else {
						addLogEntry(
							'info',
							'Chunk ' +
								( chunkNumber + 1 ) +
								': No AI images found'
						);
					}

					if ( data.done ) {
						handleScanComplete( data, $btn );
					} else {
						processChunk( data.chunk, $btn );
					}
				} else {
					handleScanError(
						response.data ? response.data.message : 'Unknown error',
						$btn
					);
				}
			},
			error( xhr, status, error ) {
				handleScanError( 'Network error: ' + error, $btn );
			},
		} );
	}

	/**
	 * Handle scan completion
	 *
	 * @param data The response data from the scan.
	 * @param $btn The scan button jQuery object.
	 */
	function handleScanComplete( data, $btn ) {
		if ( animationInterval ) {
			clearInterval( animationInterval );
		}

		// Hide warning notice.
		$( '.scan-warning-notice' ).slideUp();

		$( '.live-indicator' )
			.html(
				'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="scan-complete-icon"><path d="M20 6L9 17L4 12" stroke="#388e3c" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>COMPLETE'
			)
			.addClass( 'scan-complete' );
		$( '.scan-progress-fill' ).css( 'width', '100%' ).text( '100%' );
		$( '.scan-progress-text' ).html(
			'Scan complete! <svg width="16" height="16" viewBox="0 0 24 24" fill="none" class="scan-success-icon"><circle cx="12" cy="12" r="10" stroke="#4CAF50" stroke-width="2" fill="none"/><path d="M8 12L11 15L16 9" stroke="#4CAF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
		);

		$btn.prop( 'disabled', false ).html(
			'<span class="dashicons dashicons-update scan-refresh-icon"></span>Scan Again'
		);

		addLogEntry(
			'complete',
			'Scan Complete! Scanned ' +
				data.total_scanned +
				' images. Found ' +
				data.total_found +
				' AI images.'
		);

		showResultsNotice( data.total_found );
	}

	/**
	 * Handle scan error
	 *
	 * @param message The error message.
	 * @param $btn    The scan button jQuery object.
	 */
	function handleScanError( message, $btn ) {
		// Hide warning notice.
		$( '.scan-warning-notice' ).slideUp();

		$( '.live-indicator' ).hide();
		$btn.prop( 'disabled', false ).text( 'Scan Now' );
		addLogEntry( 'error', 'Error: ' + message );
	}

	/**
	 * Animate progress bar smoothly
	 *
	 * @param targetProgress  The target progress percentage.
	 * @param targetProcessed The target number of processed items.
	 * @param total           The total number of items.
	 */
	function animateProgress( targetProgress, targetProcessed, total ) {
		if ( animationInterval ) {
			clearInterval( animationInterval );
		}

		const progressStep = ( targetProgress - currentProgress ) / 20;
		const processedStep = ( targetProcessed - currentProcessed ) / 20;
		let frameCount = 0;

		animationInterval = setInterval( function () {
			frameCount++;
			currentProgress += progressStep;
			currentProcessed += processedStep;

			if ( frameCount >= 20 || currentProgress >= targetProgress ) {
				currentProgress = targetProgress;
				currentProcessed = targetProcessed;
				clearInterval( animationInterval );
			}

			const displayProgress = Math.round( currentProgress );
			const displayProcessed = Math.round( currentProcessed );

			$( '.scan-progress-fill' )
				.css( 'width', displayProgress + '%' )
				.text( displayProgress + '%' );
			$( '.scan-progress-text' ).text(
				'Processing... ' +
					displayProcessed +
					'/' +
					total +
					' items (' +
					displayProgress +
					'%)'
			);
		}, 50 );
	}

	/**
	 * Show results notice after scan
	 *
	 * @param total The total number of AI images found.
	 */
	function showResultsNotice( total ) {
		if ( $( '.live-results-notice' ).length === 0 ) {
			const refreshBtn =
				'<button type="button" class="button button-secondary scan-refresh-button">Refresh Page</button>';

			const notice =
				total > 0
					? '<div class="notice notice-success live-results-notice"><p><strong>Found ' +
					  total +
					  ' AI images!</strong> Refresh to see updated results. ' +
					  refreshBtn +
					  '</p></div>'
					: '<div class="notice notice-info live-results-notice"><p><strong>No AI images detected.</strong> Your media library appears to be AI-free! ' +
					  refreshBtn +
					  '</p></div>';

			$( '.euaiactready-stats' ).before( notice );

			// Attach event handler via delegation.
			$( document ).on( 'click', '.scan-refresh-button', function () {
				location.reload();
			} );
		}
	}

	/**
	 * Add log entry to scan log
	 *
	 * @param type    The log entry type (info, detection, error, complete).
	 * @param message The log message.
	 */
	function addLogEntry( type, message ) {
		const $log = $( '#live-scan-log' );
		const time = new Date().toLocaleTimeString( 'en-US', {
			hour12: false,
		} );
		const $entry = $(
			'<div class="log-entry log-entry-' +
				type +
				'">[' +
				time +
				'] ' +
				message +
				'</div>'
		);
		$log.append( $entry );
		$log.scrollTop( $log[ 0 ].scrollHeight );
	}

	// ========================================
	// 5. DATATABLE INITIALIZATION
	// ========================================

	/**
	 * Initialize all DataTables
	 */
	function initializeDataTables() {
		initializeAIContentTable();
		initializeAIImagesTable();
		initializeUnmarkedTable();
	}

	/**
	 * Initialize AI Content table
	 */
	function initializeAIContentTable() {
		if ( $( '#euaiactready-content-table' ).length ) {
			$( '#euaiactready-content-table' ).DataTable( {
				order: [ [ 5, 'desc' ] ],
				pageLength: 10,
				language: {
					search: 'Search content:',
					lengthMenu: 'Show _MENU_ items per page',
					info: 'Showing _START_ to _END_ of _TOTAL_ AI content items',
					infoEmpty: 'No AI content items found',
					infoFiltered: '(filtered from _MAX_ total items)',
					zeroRecords: 'No matching content found',
					paginate: {
						first: 'First',
						last: 'Last',
						next: 'Next',
						previous: 'Previous',
					},
				},
				columnDefs: [
					{ orderable: false, targets: 0 },
					{ type: 'html-num-fmt', targets: 5, className: 'dt-left' },
				],
			} );
		}
	}

	/**
	 * Initialize AI Images table
	 */
	function initializeAIImagesTable() {
		if ( $( '#euaiactready-images-table' ).length ) {
			$( '#euaiactready-images-table' ).DataTable( {
				order: [ [ 5, 'desc' ] ],
				pageLength: 10,
				language: {
					search: 'Search images:',
					lengthMenu: 'Show _MENU_ items per page',
					info: 'Showing _START_ to _END_ of _TOTAL_ AI images',
					infoEmpty: 'No AI images found',
					infoFiltered: '(filtered from _MAX_ total images)',
					zeroRecords: 'No matching images found',
					paginate: {
						first: 'First',
						last: 'Last',
						next: 'Next',
						previous: 'Previous',
					},
				},
				columnDefs: [ { orderable: false, targets: [ 0, 1 ] } ],
			} );
		}
	}

	/**
	 * Initialize Unmarked table
	 */
	function initializeUnmarkedTable() {
		if ( $( '#euaiactready-unmarked-table' ).length ) {
			$( '#euaiactready-unmarked-table' ).DataTable( {
				order: [ [ 4, 'desc' ] ],
				pageLength: 10,
				autoWidth: false,
				columnDefs: [
					{ orderable: false, targets: [ 0, 1, 5 ] },
					{ width: '40px', targets: 0 },
					{ width: '80px', targets: 1 },
				],
				language: {
					search: 'Search images:',
					lengthMenu: 'Show _MENU_ items per page',
					info: 'Showing _START_ to _END_ of _TOTAL_ AI images',
					infoEmpty: 'No AI images found',
					infoFiltered: '(filtered from _MAX_ total images)',
					zeroRecords: 'No matching images found',
					paginate: {
						first: 'First',
						last: 'Last',
						next: 'Next',
						previous: 'Previous',
					},
				},
			} );
		}
	}

	// ========================================
	// 6. AI CONTENT MANAGEMENT
	// ========================================

	/**
	 * Initialize Quick Edit functionality
	 */
	function initializeQuickEdit() {
		if ( typeof inlineEditPost !== 'undefined' ) {
			const $wpInlineEdit = inlineEditPost.edit;
			inlineEditPost.edit = function ( id ) {
				$wpInlineEdit.apply( this, arguments );

				let postId = 0;
				if ( typeof id === 'object' ) {
					postId = parseInt( this.getId( id ) );
				}

				if ( postId > 0 ) {
					const $row = $( '#post-' + postId );
					let aiContent = $row
						.find( '.ai-content-value' )
						.data( 'ai-content' );
					aiContent = String( aiContent );

					setTimeout( function () {
						const $editRow = $( '#edit-' + postId );
						const $select = $editRow.find(
							'select[name="euaiactready_content"]'
						);
						$select.val( aiContent );
					}, 100 );
				}
			};
		}

		setupContentEventHandlers();
	}

	/**
	 * Setup AI content event handlers
	 */
	function setupContentEventHandlers() {
		// Unmark AI content.
		$( '.euaiactready-unmark-content' ).on( 'click', handleUnmarkContent );

		// Toggle AI status.
		$( document ).on( 'click', '.ai-toggle-status', handleToggleAIStatus );
	}

	/**
	 * Handle unmark AI content
	 */
	function handleUnmarkContent() {
		const button = $( this );
		const postId = button.data( 'post-id' );

		if ( ! confirm( euaiactreadyAjax.i18n.confirmUnmark ) ) {
			return;
		}

		button.prop( 'disabled', true ).text( euaiactreadyAjax.i18n.unmarking );

		$.ajax( {
			url: euaiactreadyAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'euaiactready_unmark_content',
				post_id: postId,
				nonce: euaiactreadyAjax.nonce,
			},
			success( response ) {
				if ( response.success ) {
					button.closest( 'tr' ).fadeOut( 400, function () {
						$( this ).remove();
						const remainingRows = $(
							'#manual-content-container tbody tr'
						).length;
						if ( remainingRows === 0 ) {
							location.reload();
						}
					} );
				} else {
					alert(
						response.data.message ||
							euaiactreadyAjax.i18n.unmarkFailed
					);
					button
						.prop( 'disabled', false )
						.text( euaiactreadyAjax.i18n.unmark );
				}
			},
			error() {
				alert( euaiactreadyAjax.i18n.errorOccurred );
				button
					.prop( 'disabled', false )
					.text( euaiactreadyAjax.i18n.unmark );
			},
		} );
	}

	/**
	 * Handle toggle AI status
	 *
	 * @param e The event object.
	 */
	function handleToggleAIStatus( e ) {
		e.preventDefault();

		const $link = $( this );
		const postId = $link.data( 'post-id' );
		const action = $link.data( 'action' );
		const nonce = $link.data( 'nonce' );

		$link.addClass( 'is-loading' );

		$.ajax( {
			url: euaiactreadyAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'euaiactready_toggle_ai_status',
				post_id: postId,
				action_type: action,
				nonce,
			},
			success( response ) {
				if ( response.success ) {
					location.reload();
				} else {
					alert(
						response.data.message || 'Failed to update AI status.'
					);
					$link.removeClass( 'is-loading' );
				}
			},
			error() {
				alert( 'An error occurred. Please try again.' );
				$link.removeClass( 'is-loading' );
			},
		} );
	}

	// ========================================
	// 7. AI IMAGE MANAGEMENT
	// ========================================

	/**
	 * Setup AI image event handlers
	 */
	function setupImageEventHandlers() {
		// Unmark AI image.
		$( document ).on(
			'click',
			'.euaiactready-unmark-image',
			handleUnmarkImage
		);

		// Mark image as AI.
		$( document ).on(
			'click',
			'.euaiactready-mark-image',
			handleMarkImage
		);

		// Restore image to scan.
		$( document ).on(
			'click',
			'.euaiactready-restore-btn',
			handleRestoreImage
		);
	}

	/**
	 * Handle unmark AI image
	 */
	function handleUnmarkImage() {
		const button = $( this );
		const attachmentId = button.data( 'attachment-id' );

		if (
			! confirm(
				'Are you sure you want to unmark this image as AI-generated?'
			)
		) {
			return;
		}

		button.prop( 'disabled', true ).text( 'Unmarking...' );

		$.ajax( {
			url: euaiactreadyAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'euaiactready_unmark_image',
				attachment_id: attachmentId,
				nonce: euaiactreadyAjax.nonce,
			},
			success( response ) {
				if ( response.success ) {
					button.closest( 'tr' ).fadeOut( 400, function () {
						$( this ).remove();
						const remainingRows = $(
							'#ai-images-container tbody tr'
						).length;

						const currentTotal =
							parseInt(
								$( '.euaiactready-stats .stat-card' )
									.eq( 3 )
									.find( 'h3' )
									.text()
							) || 0;
						if ( currentTotal > 0 ) {
							$( '.euaiactready-stats .stat-card' )
								.eq( 3 )
								.find( 'h3' )
								.text( currentTotal - 1 );
						}

						updateTabCount( 'detected', -1 );
						updateTabCount( 'unmarked', 1 );

						if ( remainingRows === 0 ) {
							location.reload();
						}
					} );
				} else {
					alert( response.data.message || 'Failed to unmark image.' );
					button.prop( 'disabled', false ).text( 'Unmark as AI' );
				}
			},
			error() {
				alert( 'An error occurred. Please try again.' );
				button.prop( 'disabled', false ).text( 'Unmark as AI' );
			},
		} );
	}

	/**
	 * Handle mark image as AI
	 *
	 * @param e The event object.
	 */
	function handleMarkImage( e ) {
		e.preventDefault();
		const button = $( this );
		const attachmentId = button.data( 'attachment-id' );

		if (
			! confirm(
				'Are you sure you want to mark this image as AI-generated?'
			)
		) {
			return;
		}

		button.closest( '.row-actions' ).addClass( 'is-loading' );

		$.ajax( {
			url: euaiactreadyAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'euaiactready_mark_image_as_ai',
				attachment_id: attachmentId,
				nonce: euaiactreadyAjax.nonce,
			},
			success( response ) {
				if ( response.success ) {
					button.closest( 'tr' ).fadeOut( 400, function () {
						$( this ).remove();

						updateTabCount( 'unmarked', -1 );
						updateTabCount( 'detected', 1 );

						const remainingRows = $(
							'#ai-unmarked-table tbody tr'
						).length;
						if ( remainingRows === 0 ) {
							location.reload();
						}
					} );
				} else {
					alert(
						response.data.message || 'Failed to mark image as AI.'
					);
					button
						.closest( '.row-actions' )
						.removeClass( 'is-loading' );
				}
			},
			error() {
				alert( 'An error occurred. Please try again.' );
				button.closest( '.row-actions' ).removeClass( 'is-loading' );
			},
		} );
	}

	/**
	 * Handle restore image to scan
	 */
	function handleRestoreImage() {
		const btn = $( this );
		const id = btn.data( 'id' );
		const nonce = btn.data( 'nonce' );

		if (
			confirm(
				'Are you sure you want to include this image in future AI scans?'
			)
		) {
			btn.prop( 'disabled', true ).text( 'Restoring...' );

			$.ajax( {
				url: euaiactreadyAjax.ajax_url,
				type: 'POST',
				data: {
					action: 'euaiactready_restore_image',
					attachment_id: id,
					nonce,
				},
				success( response ) {
					if ( response.success ) {
						const table = $( '#ai-unmarked-table' ).DataTable();
						const row = btn.closest( 'tr' );

						table.row( row ).remove().draw( false );

						updateTabCount( 'unmarked', -1 );

						if ( table.rows().count() === 0 ) {
							location.reload();
						}
					} else {
						alert( response.data.message || 'Error occurred' );
						btn.prop( 'disabled', false ).text(
							'Include in next Scan'
						);
					}
				},
				error() {
					alert( 'Network error' );
					btn.prop( 'disabled', false ).text(
						'Include in next Scan'
					);
				},
			} );
		}
	}

	// ========================================
	// 8. BULK ACTIONS
	// ========================================

	/**
	 * Initialize bulk action handlers
	 */
	function initializeBulkActions() {
		handleBulkAction(
			'#euaiactready-doaction-content',
			'#euaiactready-bulk-action-content',
			'content_ids[]',
			'content'
		);
		handleBulkAction(
			'#euaiactready-doaction-content-top',
			'#euaiactready-bulk-action-content-top',
			'content_ids[]',
			'content'
		);
		handleBulkAction(
			'#euaiactready-doaction-images',
			'#euaiactready-bulk-action-images',
			'image_ids[]',
			'image'
		);
		handleBulkAction(
			'#euaiactready-doaction-images-top',
			'#euaiactready-bulk-action-images-top',
			'image_ids[]',
			'image'
		);
		handleBulkAction(
			'#euaiactready-doaction-unmarked',
			'#euaiactready-bulk-action-unmarked',
			'post[]',
			'unmarked_image'
		);
		handleBulkAction(
			'#euaiactready-doaction-unmarked-top',
			'#euaiactready-bulk-action-unmarked-top',
			'post[]',
			'unmarked_image'
		);
		setupImageEventHandlers();
	}

	/**
	 * Handle bulk action execution
	 *
	 * @param triggerId    The trigger button selector.
	 * @param selectId     The select dropdown selector.
	 * @param checkboxName The checkbox name attribute.
	 * @param itemType     The item type for the action.
	 */
	function handleBulkAction( triggerId, selectId, checkboxName, itemType ) {
		$( document ).on( 'click', triggerId, function ( e ) {
			e.preventDefault();

			const action = $( selectId ).val();

			if ( action === '-1' ) {
				alert( 'Please select an action.' );
				return;
			}

			const ids = [];
			$( 'input[name="' + checkboxName + '"]:checked' ).each(
				function () {
					ids.push( $( this ).val() );
				}
			);

			if ( ids.length === 0 ) {
				alert( 'Please select items to apply action.' );
				return;
			}

			const actionText = $( selectId + ' option:selected' ).text();
			if (
				! confirm(
					'Are you sure you want to ' +
						actionText +
						' ' +
						ids.length +
						' item(s)?'
				)
			) {
				return;
			}

			const $btn = $( this );
			$btn.prop( 'disabled', true ).text( 'Applying...' );

			$.ajax( {
				url: euaiactreadyAjax.ajax_url,
				type: 'POST',
				data: {
					action: 'euaiactready_bulk_action',
					bulk_action: action,
					item_type: itemType,
					ids,
					nonce: euaiactreadyAjax.nonce,
				},
				success( response ) {
					if ( response.success ) {
						location.reload();
					} else {
						alert( response.data.message || 'Action failed' );
						$btn.prop( 'disabled', false ).text( 'Apply' );
					}
				},
				error() {
					alert( 'Network error' );
					$btn.prop( 'disabled', false ).text( 'Apply' );
				},
			} );
		} );
	}

	/**
	 * Initialize select all checkboxes
	 */
	function initializeSelectAllCheckboxes() {
		$( '#euaiactready-cb-select-all-content' ).on( 'change', function () {
			$( 'input[name="content_ids[]"]' ).prop( 'checked', this.checked );
		} );

		$( '#euaiactready-cb-select-all-images' ).on( 'change', function () {
			$( 'input[name="image_ids[]"]' ).prop( 'checked', this.checked );
		} );

		$( '#euaiactready-cb-select-all-unmarked' ).on( 'change', function () {
			$( 'input[name="post[]"]' ).prop( 'checked', this.checked );
		} );
	}

	// ========================================
	// 9. SETTINGS & PREVIEW
	// ========================================

	/**
	 * Initialize settings page tab switching
	 */
	function initializeSettingsTabs() {
		$( '.nav-tab' ).on( 'click', handleTabSwitch );
	}

	/**
	 * Handle tab switching with URL parameter update
	 *
	 * @param e The event object.
	 */
	function handleTabSwitch( e ) {
		if ( $( '.tab-content' ).length === 0 ) {
			return;
		}

		e.preventDefault();

		const url = $( this ).attr( 'href' );
		const urlParams = new URLSearchParams( url.split( '?' )[ 1 ] );
		const tab = urlParams.get( 'tab' ) || 'transparency';

		$( '.nav-tab' ).removeClass( 'nav-tab-active' );
		$( this ).addClass( 'nav-tab-active' );

		$( '.tab-content' ).hide();
		$( '#' + tab + '-tab' ).show();

		$( '#active_tab_field' ).val( tab );

		const newUrl = new URL( window.location );
		newUrl.searchParams.set( 'tab', tab );
		window.history.pushState( { path: newUrl.href }, '', newUrl.href );
	}

	/**
	 * Initialize chatbot preview functionality
	 */
	function initializeChatbotPreview() {
		$( '#chatbot_notice_message' ).on( 'change keyup', function () {
			updateChatbotPreview();
		} );

		updateChatbotPreview();
	}

	/**
	 * Update chatbot preview with custom message
	 */
	function updateChatbotPreview() {
		const message =
			$( '#chatbot_notice_message' ).val() ||
			'This chat uses AI assistance.';

		$( '#chatbot-preview-banner .chatbot-preview-message' ).text(
			message.substring( 0, 80 )
		);
		$( '#chatbot-preview-inline .chatbot-preview-message' ).text(
			message.substring( 0, 60 )
		);
		$( '#chatbot-preview-badge' ).attr( 'title', message );
		$( '#chatbot-preview-tooltip' ).attr( 'data-tooltip', message );
		$( '#chatbot-preview-modal' ).attr( 'title', message );
		$( '#admin-chatbot-modal-message' ).text( message );
	}

	// ========================================
	// 10. UTILITY FUNCTIONS
	// ========================================

	/**
	 * Update tab count
	 *
	 * @param tabType The tab type identifier.
	 * @param change  The count change (+1 or -1).
	 */
	function updateTabCount( tabType, change ) {
		let $tab = null;

		$( '.nav-tab-wrapper a' ).each( function () {
			const href = $( this ).attr( 'href' );
			if ( href && href.indexOf( 'tab=' + tabType ) !== -1 ) {
				$tab = $( this );
				return false;
			}
		} );

		if ( $tab && $tab.length ) {
			const $countSpan = $tab.find( '.count' );
			const currentText = $countSpan.text().replace( /[()]/g, '' );
			const currentCount = parseInt( currentText ) || 0;
			const newCount = currentCount + change;

			$countSpan.text( '(' + newCount + ')' );
		}
	}

	/**
	 * Get AI icon SVG - matches PHP get_ai_icon
	 *
	 * @param size  The icon size in pixels.
	 * @param color The icon fill color.
	 * @return The SVG icon markup.
	 */
	function getAiIcon( size, color ) {
		return (
			'<svg xmlns="http://www.w3.org/2000/svg" width="' +
			size +
			'" height="' +
			size +
			'" viewBox="0 0 24 24" fill="' +
			color +
			'" stroke="none" class="eu-ai-act-ready-icon"><path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z"/></svg>'
		);
	}
} )( jQuery );
