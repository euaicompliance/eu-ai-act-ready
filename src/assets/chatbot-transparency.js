/**
 * EU AI Act Ready - Chatbot Transparency Frontend Script
 *
 * @package
 */

( function () {
	'use strict';

	// Wait for euaiactreadyChatbotTransparencyConfig to be available.
	if ( typeof euaiactreadyChatbotTransparencyConfig === 'undefined' ) {
		return;
	}

	const EUAIACTREAD_CHATBOT_TRANSPARENCY_CONST = {
		platform: euaiactreadyChatbotTransparencyConfig.platform,
		style: euaiactreadyChatbotTransparencyConfig.style,
		injected: false,

		/**
		 * Initialize transparency system
		 */
		init() {
			// Wait for chatbot to load.
			this.waitForChatbot();
		},

		/**
		 * Wait for chatbot widget to load
		 */
		waitForChatbot() {
			const self = this;
			let attempts = 0;
			const maxAttempts = 100; // 20 seconds (longer wait for Formilla).

			const checkInterval = setInterval( function () {
				attempts++;

				if ( self.detectChatbot() ) {
					clearInterval( checkInterval );
					self.injectTransparency();
				} else if ( attempts >= maxAttempts ) {
					clearInterval( checkInterval );
					self.injectFallback();
				}
			}, 200 );
		},

		/**
		 * All known platform detection signatures.
		 */
		PLATFORM_SIGNATURES: {
			formilla:  { globals: [ 'Formilla' ],               selectors: [ '#formilla-chat-button', '#formilla-chat-iframe', 'iframe[src*="formilla"]', '[class*="formilla"]' ], widget: '#formilla-chat-button, #formilla-chat-iframe, [class*="formilla"], [id*="formilla"]' },
			intercom:  { globals: [ 'Intercom' ],               selectors: [ '#intercom-container' ],                widget: '#intercom-container' },
			drift:     { globals: [ 'drift' ],                  selectors: [ '#drift-widget', '#drift-widget-container' ], widget: '#drift-widget, #drift-widget-container' },
			tidio:     { globals: [ 'tidioChatApi' ],           selectors: [ '#tidio-chat' ],                         widget: '#tidio-chat' },
			tawk:      { globals: [ 'Tawk_API' ],               selectors: [ '#tawkchat-container', '.tawk-button' ], widget: '#tawkchat-container, .tawk-button' },
			zendesk:   { globals: [ 'zE' ],                     selectors: [ '#launcher', '.zEWidget-launcher' ],     widget: '#launcher, .zEWidget-launcher' },
			livechat:  { globals: [ 'LiveChatWidget' ],         selectors: [ '#chat-widget-container' ],              widget: '#chat-widget-container' },
			crisp:     { globals: [ '$crisp' ],                 selectors: [ '.crisp-client', '[data-crisp-website-id]' ], widget: '.crisp-client' },
			freshchat: { globals: [ 'fcWidget' ],               selectors: [ '#fc_frame' ],                           widget: '#fc_frame' },
			smartsupp: { globals: [ 'smartsupp', 'Smartsupp' ], selectors: [ '#smartsupp-widget-container', '#chat-application' ], widget: '#smartsupp-widget-container, #chat-application' },
			hubspot:   { globals: [ 'HubSpotConversations' ],   selectors: [ '#hubspot-messages-iframe-container' ],  widget: '#hubspot-messages-iframe-container' },
			chaport:   { globals: [ 'chaport' ],                selectors: [ '#chaport-container', '.chaport-container' ], widget: '#chaport-container, .chaport-container' },
			userlike:  { globals: [ 'userlike' ],               selectors: [ '#userlike-sbc', '.ul-widget' ],         widget: '#userlike-sbc, .ul-widget' },
			olark:     { globals: [ 'olark' ],                  selectors: [ '.olark-launch-button-chat', '#olark-container' ], widget: '.olark-launch-button-chat, #olark-container' },
			chatra:    { globals: [ 'Chatra' ],                 selectors: [ '#chatra', '.chatra--side-button' ],     widget: '#chatra, .chatra--side-button' },
			custom:    { globals: [],                           selectors: [ '.chatbot-widget', '#chat-widget', '[data-chatbot]' ], widget: '.chatbot-widget, #chat-widget, [data-chatbot]' },
		},

		/**
		 * Check if a single platform's signals are present in the page.
		 *
		 * @param {string} platformKey
		 * @return {boolean}
		 */
		detectPlatform( platformKey ) {
			const sig = this.PLATFORM_SIGNATURES[ platformKey ];
			if ( ! sig ) {
				return false;
			}
			for ( let i = 0; i < sig.globals.length; i++ ) {
				if ( typeof window[ sig.globals[ i ] ] !== 'undefined' ) {
					return true;
				}
			}
			for ( let i = 0; i < sig.selectors.length; i++ ) {
				if ( document.querySelector( sig.selectors[ i ] ) ) {
					return true;
				}
			}
			return false;
		},

		/**
		 * Detect if the active chatbot platform is loaded.
		 * When platform is 'auto', probes all known platforms and locks onto the first match.
		 */
		detectChatbot() {
			if ( 'auto' === this.platform ) {
				const keys = Object.keys( this.PLATFORM_SIGNATURES );
				for ( let i = 0; i < keys.length; i++ ) {
					if ( 'custom' === keys[ i ] ) {
						continue;
					}
					if ( this.detectPlatform( keys[ i ] ) ) {
						this.platform = keys[ i ];
						return true;
					}
				}
				return false;
			}
			return this.detectPlatform( this.platform );
		},

		/**
		 * Get the chatbot widget DOM element for the active platform.
		 */
		getChatbotWidget() {
			const sig = this.PLATFORM_SIGNATURES[ this.platform ];
			return sig ? document.querySelector( sig.widget ) : null;
		},

		/**
		 * Inject transparency notice
		 */
		injectTransparency() {
			if ( this.injected ) {
				return;
			}

			const container = document.getElementById(
				'ai-chatbot-transparency-container'
			);
			if ( ! container ) {
				return;
			}

			const widget = this.getChatbotWidget();
			if ( ! widget ) {
				return;
			}

			// Clone and show the notice.
			const notice = container.querySelector( '.ai-chatbot-notice' );
			if ( notice ) {
				const clonedNotice = notice.cloneNode( true );

				// Position near widget (always).
				this.positionNearWidget( clonedNotice, widget );

				// Ensure notice is always visible (backup).
				if (
					clonedNotice.style.display !== 'flex' &&
					clonedNotice.style.display !== 'block'
				) {
					clonedNotice.style.display = 'flex';
				}
				clonedNotice.style.visibility = 'visible';
				clonedNotice.style.opacity = '1';

				// Setup event listeners.
				this.setupEventListeners( clonedNotice );

				this.injected = true;
			}
		},

		/**
		 * Position notice near chatbot widget
		 *
		 * @param notice The transparency notice element to position.
		 * @param widget The chatbot widget element to position near.
		 */
		positionNearWidget( notice, widget ) {
			notice.style.position = 'fixed';
			notice.style.zIndex = '999998'; // Just below typical chatbot z-index.
			notice.style.display = 'flex'; // Ensure visibility.
			notice.style.visibility = 'visible';
			notice.style.opacity = '1';

			widget.getBoundingClientRect();

			// Use simple bottom-right positioning instead of complex calculation.
			// This works better with chat widgets that may be hidden or have unusual positioning.
			notice.style.bottom = '90px'; // Standard position above typical chat button.
			notice.style.right = '20px';

			document.body.appendChild( notice );
		},

		/**
		 * Setup event listeners
		 *
		 * @param notice The transparency notice element to setup listeners for.
		 */
		setupEventListeners( notice ) {
			// Close button.
			const closeBtn = notice.querySelector(
				'.ai-chatbot-notice-close, .ai-chatbot-modal-close'
			);
			if ( closeBtn ) {
				closeBtn.addEventListener( 'click', function () {
					notice.style.display = 'none';
				} );
			}

			// Modal trigger button.
			const modalTrigger = notice.querySelector(
				'.ai-chatbot-disclosure-btn'
			);
			if ( modalTrigger ) {
				modalTrigger.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					e.stopPropagation();

					// Find or create the modal.
					let modal = document.getElementById( 'ai-chatbot-modal' );

					// If modal not found in body, look in container and clone it.
					if (
						! modal ||
						modal.parentElement.id ===
							'ai-chatbot-transparency-container'
					) {
						const container = document.getElementById(
							'ai-chatbot-transparency-container'
						);
						if ( container ) {
							const hiddenModal =
								container.querySelector( '#ai-chatbot-modal' );
							if ( hiddenModal ) {
								// Remove any existing modal in body first.
								const existingModal =
									document.body.querySelector(
										'#ai-chatbot-modal'
									);
								if (
									existingModal &&
									existingModal.parentElement ===
										document.body
								) {
									existingModal.remove();
								}

								// Clone and append modal to body.
								modal = hiddenModal.cloneNode( true );
								modal.id = 'ai-chatbot-modal'; // Ensure ID is set.
								document.body.appendChild( modal );

								// Setup modal close button.
								const modalCloseBtn = modal.querySelector(
									'.ai-chatbot-modal-close'
								);
								if ( modalCloseBtn ) {
									modalCloseBtn.addEventListener(
										'click',
										function ( e ) {
											e.preventDefault();
											e.stopPropagation();
											modal.style.display = 'none';
										}
									);
								}

								// Close on background click.
								modal.addEventListener(
									'click',
									function ( e ) {
										if ( e.target === modal ) {
											modal.style.display = 'none';
										}
									}
								);

								// Close on ESC key.
								document.addEventListener(
									'keydown',
									function ( e ) {
										if (
											e.key === 'Escape' &&
											modal.style.display === 'flex'
										) {
											modal.style.display = 'none';
										}
									}
								);
							}
						}
					}

					if ( modal ) {
						modal.style.display = 'flex';
						modal.style.visibility = 'visible';
						modal.style.opacity = '1';
					}
				} );
			}
		},

		/**
		 * Fallback injection if chatbot not detected
		 */
		injectFallback() {
			if ( this.injected ) {
				return;
			}

			const container = document.getElementById(
				'ai-chatbot-transparency-container'
			);
			if ( ! container ) {
				return;
			}

			const notice = container.querySelector( '.ai-chatbot-notice' );
			if ( notice ) {
				const clonedNotice = notice.cloneNode( true );

				// Position in bottom-right (typical chat location).
				clonedNotice.style.position = 'fixed';
				clonedNotice.style.bottom = '80px';
				clonedNotice.style.right = '20px';
				clonedNotice.style.zIndex = '999998';
				clonedNotice.style.display = 'flex';
				clonedNotice.style.visibility = 'visible';
				clonedNotice.style.opacity = '1';

				document.body.appendChild( clonedNotice );

				this.setupEventListeners( clonedNotice );
				this.injected = true;
			}
		},
	};

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			EUAIACTREAD_CHATBOT_TRANSPARENCY_CONST.init();
		} );
	} else {
		EUAIACTREAD_CHATBOT_TRANSPARENCY_CONST.init();
	}
} )();
