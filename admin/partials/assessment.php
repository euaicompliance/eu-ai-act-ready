<?php
/**
 * EU AI Act Ready - Compliance Self-Assessment Wizard
 *
 * @package EUAIACTREADY
 */


if ( ! defined( 'WPINC' ) ) {
	die;
}

// Handle form submission.
if ( isset( $_POST['euaiactready_assessment_nonce'] ) ) {
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['euaiactready_assessment_nonce'] ) ), 'euaiactready_save_assessment' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'eu-ai-act-ready' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'eu-ai-act-ready' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
	$euaiactready_raw = isset( $_POST['euaiactready_q'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['euaiactready_q'] ) ) : array();
	Euaiactready_Assessment::save( $euaiactready_raw );
	( new Euaiactready_Readiness() )->calculate();

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
	$euaiactready_redirect_to = isset( $_POST['euaiactready_redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['euaiactready_redirect_to'] ) ) : '';
	if ( ! empty( $euaiactready_redirect_to ) && strpos( $euaiactready_redirect_to, admin_url() ) === 0 ) {
		echo '<script>window.location.href=' . wp_json_encode( $euaiactready_redirect_to ) . ';</script>';
		return;
	}

	add_settings_error( 'euaiactready_assessment', 'saved', __( 'Assessment saved.', 'eu-ai-act-ready' ), 'success' );
}

$euaiactready_questions   = Euaiactready_Assessment::get_questions();
$euaiactready_answers     = Euaiactready_Assessment::get_answers();
$euaiactready_is_complete = Euaiactready_Assessment::is_complete();
$euaiactready_applicable  = Euaiactready_Assessment::get_applicable_questions();
$euaiactready_total       = count( $euaiactready_questions );

$euaiactready_questions_for_js = array();
foreach ( $euaiactready_questions as $euaiactready_q ) {
	$euaiactready_questions_for_js[] = array(
		'id'           => $euaiactready_q['id'],
		'yes_articles' => $euaiactready_q['yes_articles'],
		'action'       => $euaiactready_q['action'],
		'settings_link' => $euaiactready_q['settings_link'],
	);
}
?>
<div class="wrap euaiactready-assessment">
	<h1 class="euaiactready-page-title">
		<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 22, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
		<?php esc_html_e( 'Compliance Assessment', 'eu-ai-act-ready' ); ?>
	</h1>

	<?php settings_errors( 'euaiactready_assessment' ); ?>

	<div class="euaiactready-card euaiactready-assessment-intro">
		<h2><?php esc_html_e( 'EU AI Act Self-Assessment', 'eu-ai-act-ready' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Answer the questions below to identify which EU AI Act transparency obligations apply to your site. Your answers are used to tailor the readiness score and recommended actions.', 'eu-ai-act-ready' ); ?>
		</p>
		<?php if ( $euaiactready_is_complete ) : ?>
		<p class="euaiactready-assessment-complete-notice">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'Assessment complete. You can update your answers at any time.', 'eu-ai-act-ready' ); ?>
		</p>
		<?php endif; ?>
	</div>

	<?php if ( $euaiactready_is_complete ) : ?>
	<!-- Segmented tab control -->
	<div class="euaiactready-tabs-segmented" role="tablist">
		<button type="button" role="tab" class="euaiactready-tab-seg euaiactready-tab-seg--active" data-panel="euaiactready-panel-results" aria-selected="true">
			<span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
			<?php esc_html_e( 'Your Results', 'eu-ai-act-ready' ); ?>
		</button>
		<button type="button" role="tab" class="euaiactready-tab-seg" data-panel="euaiactready-panel-questions" aria-selected="false">
			<span class="dashicons dashicons-edit" aria-hidden="true"></span>
			<?php esc_html_e( 'Update Answers', 'eu-ai-act-ready' ); ?>
		</button>
	</div>

	<!-- Results panel -->
	<div class="euaiactready-wizard-panel" id="euaiactready-panel-results" role="tabpanel">
		<div class="euaiactready-card">
			<h2><?php esc_html_e( 'Your Applicable Obligations', 'eu-ai-act-ready' ); ?></h2>
			<?php if ( ! empty( $euaiactready_applicable ) ) : ?>
			<div class="euaiactready-wizard-results-list">
				<?php foreach ( $euaiactready_applicable as $euaiactready_aq ) : ?>
				<div class="euaiactready-wizard-result-item">
					<div class="euaiactready-wizard-result-articles">
						<?php foreach ( $euaiactready_aq['yes_articles'] as $euaiactready_article ) : ?>
						<span class="euaiactready-article-badge"><?php echo esc_html( $euaiactready_article ); ?></span>
						<?php endforeach; ?>
					</div>
					<p class="euaiactready-wizard-result-action">
						<?php echo esc_html( $euaiactready_aq['action'] ); ?>
						<a href="<?php echo esc_url( $euaiactready_aq['settings_link'] ); ?>" class="button button-small">
							<?php esc_html_e( 'Configure', 'eu-ai-act-ready' ); ?>
						</a>
					</p>
				</div>
				<?php endforeach; ?>
			</div>
			<?php else : ?>
			<p><?php esc_html_e( 'Based on your current answers, no specific Article 50 obligations were flagged. Your answers are saved for reference.', 'eu-ai-act-ready' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Questions panel -->
	<div class="euaiactready-wizard-panel" id="euaiactready-panel-questions" style="display:none" role="tabpanel">
	<?php endif; ?>

	<form method="post" action="" id="euaiactready-assessment-form">
		<?php wp_nonce_field( 'euaiactready_save_assessment', 'euaiactready_assessment_nonce' ); ?>
		<input type="hidden" name="euaiactready_redirect_to" id="euaiactready-redirect-to" value="">

		<div class="euaiactready-wizard" id="euaiactready-wizard">

			<!-- Step dots -->
			<div class="euaiactready-wizard-dots" id="wizard-dots">
				<?php for ( $euaiactready_i = 0; $euaiactready_i <= $euaiactready_total; $euaiactready_i++ ) : ?>
				<div class="euaiactready-wizard-dot" id="wizard-dot-<?php echo esc_attr( $euaiactready_i ); ?>">
					<?php if ( $euaiactready_i < $euaiactready_total ) : ?>
					<?php echo esc_html( $euaiactready_i + 1 ); ?>
					<?php else : ?>
					<span class="dashicons dashicons-yes" aria-hidden="true"></span>
					<?php endif; ?>
				</div>
				<?php if ( $euaiactready_i < $euaiactready_total ) : ?>
				<div class="euaiactready-wizard-conn" id="wizard-conn-<?php echo esc_attr( $euaiactready_i ); ?>"></div>
				<?php endif; ?>
				<?php endfor; ?>
			</div>
			<span class="euaiactready-wizard-step-label" id="wizard-step-label"></span>

			<!-- Question steps -->
			<?php foreach ( $euaiactready_questions as $euaiactready_index => $euaiactready_q ) : ?>
			<div class="euaiactready-wizard-step<?php echo 0 === $euaiactready_index ? ' euaiactready-wizard-step--active' : ''; ?>"
				data-step="<?php echo esc_attr( $euaiactready_index ); ?>"
				id="wizard-step-<?php echo esc_attr( $euaiactready_index ); ?>">

				<div class="euaiactready-card">
					<div class="euaiactready-wizard-question">
						<div class="euaiactready-wizard-question-number">
							<?php
							/* translators: %d: question number */
							printf( esc_html__( 'Question %d', 'eu-ai-act-ready' ), absint( $euaiactready_index ) + 1 );
							?>
						</div>
						<h2 class="euaiactready-wizard-question-text"><?php echo esc_html( $euaiactready_q['text'] ); ?></h2>
						<p class="euaiactready-wizard-question-hint description"><?php echo esc_html( $euaiactready_q['hint'] ); ?></p>

						<div class="euaiactready-wizard-options">
							<?php
							$euaiactready_current_answer = $euaiactready_answers[ $euaiactready_q['id'] ] ?? '';
							foreach ( array( 'yes' => __( 'Yes', 'eu-ai-act-ready' ), 'sometimes' => __( 'Sometimes', 'eu-ai-act-ready' ), 'no' => __( 'No', 'eu-ai-act-ready' ) ) as $euaiactready_val => $euaiactready_label_text ) :
							?>
							<label class="euaiactready-wizard-option<?php echo ( $euaiactready_current_answer === $euaiactready_val ) ? ' euaiactready-wizard-option--selected' : ''; ?>">
								<input type="radio"
									name="euaiactready_q[<?php echo esc_attr( $euaiactready_q['id'] ); ?>]"
									value="<?php echo esc_attr( $euaiactready_val ); ?>"
									<?php checked( $euaiactready_current_answer, $euaiactready_val ); ?>
								/>
								<span class="euaiactready-wizard-option-label"><?php echo esc_html( $euaiactready_label_text ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
			<?php endforeach; ?>

			<!-- Results step  -->
			<div class="euaiactready-wizard-step euaiactready-wizard-step--results"
				data-step="<?php echo esc_attr( $euaiactready_total ); ?>"
				id="wizard-step-<?php echo esc_attr( $euaiactready_total ); ?>">

				<!-- Top nav -->
				<div class="euaiactready-wizard-nav euaiactready-wizard-nav--results-top">
					<button type="button" class="ewiz-btn ewiz-btn-back" disabled>
						<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Back', 'eu-ai-act-ready' ); ?>
					</button>
					<div class="euaiactready-wizard-nav-right">
						<button type="submit" class="ewiz-btn ewiz-btn-primary">
							<span class="dashicons dashicons-yes" aria-hidden="true"></span>
							<?php esc_html_e( 'Save Assessment', 'eu-ai-act-ready' ); ?>
						</button>
					</div>
				</div>
				<p class="euaiactready-wizard-unsaved-notice">
					<span class="dashicons dashicons-warning" aria-hidden="true"></span>
					<?php esc_html_e( 'These results are not yet saved. Click Save Assessment to record them, or click Configure on any obligation below - both will save your assessment automatically.', 'eu-ai-act-ready' ); ?>
				</p>

				<div class="euaiactready-card">
					<h2><?php esc_html_e( 'Your Applicable Obligations', 'eu-ai-act-ready' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Based on your answers, here are the EU AI Act obligations that apply to your site. Save the assessment to record these results and update your readiness score.', 'eu-ai-act-ready' ); ?></p>
					<div id="wizard-results-content">
						<p class="description"><?php esc_html_e( 'Calculating your obligations...', 'eu-ai-act-ready' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Navigation -->
			<div class="euaiactready-wizard-nav">
				<button type="button" class="ewiz-btn ewiz-btn-back" id="wizard-back" disabled>
					<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
					<?php esc_html_e( 'Back', 'eu-ai-act-ready' ); ?>
				</button>
				<div class="euaiactready-wizard-nav-right">
					<button type="button" class="ewiz-btn ewiz-btn-primary" id="wizard-next">
						<?php esc_html_e( 'Next', 'eu-ai-act-ready' ); ?>
						<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
					</button>
					<button type="submit" class="ewiz-btn ewiz-btn-primary euaiactready-hidden" id="wizard-submit">
						<span class="dashicons dashicons-yes" aria-hidden="true"></span>
						<?php esc_html_e( 'Save Assessment', 'eu-ai-act-ready' ); ?>
					</button>
				</div>
			</div>
		</div>
	</form>

	<?php if ( $euaiactready_is_complete ) : ?>
	</div><!-- /euaiactready-panel-questions -->
	<?php endif; ?>
</div>

<?php
/* translators: %1$d: current step number, %2$d: total number of steps */
$euaiactready_step_label = __( 'Step %1$d of %2$d', 'eu-ai-act-ready' );
?>
<script>
(function () {
	'use strict';

	var total     = <?php echo (int) $euaiactready_total; ?>;
	var current   = 0;
	var backBtn   = document.getElementById( 'wizard-back' );
	var nextBtn   = document.getElementById( 'wizard-next' );
	var submitBtn = document.getElementById( 'wizard-submit' );

	// Question data for dynamic results rendering.
	var questions = <?php echo wp_json_encode( $euaiactready_questions_for_js ); ?>;

	var i18n = {
		no_obligations:    '<?php echo esc_js( __( 'Based on your current answers, no specific Article 50 obligations were flagged. Save your answers to record them for reference.', 'eu-ai-act-ready' ) ); ?>',
		obligations_intro: '<?php echo esc_js( __( 'Based on your answers, the following EU AI Act obligations apply to your site:', 'eu-ai-act-ready' ) ); ?>',
		configure:         '<?php echo esc_js( __( 'Configure', 'eu-ai-act-ready' ) ); ?>',
		step_label:        '<?php echo esc_js( $euaiactready_step_label ); ?>',
		results:           '<?php echo esc_js( __( 'Results', 'eu-ai-act-ready' ) ); ?>',
	};

	// Tab handling
	document.querySelectorAll( '.euaiactready-tab-seg' ).forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {
			document.querySelectorAll( '.euaiactready-tab-seg' ).forEach( function ( t ) {
				t.classList.remove( 'euaiactready-tab-seg--active' );
				t.setAttribute( 'aria-selected', 'false' );
			} );
			this.classList.add( 'euaiactready-tab-seg--active' );
			this.setAttribute( 'aria-selected', 'true' );
			var panelId = this.getAttribute( 'data-panel' );
			[ 'euaiactready-panel-results', 'euaiactready-panel-questions' ].forEach( function ( id ) {
				var el = document.getElementById( id );
				if ( el ) {
					el.style.display = ( id === panelId ) ? 'block' : 'none';
				}
			} );
		} );
	} );

	// ---- Step dots ----------------------------------------------------------
	function updateStepDots( n ) {
		for ( var i = 0; i <= total; i++ ) {
			var dot = document.getElementById( 'wizard-dot-' + i );
			if ( dot ) {
				dot.classList.remove( 'euaiactready-wizard-dot--active', 'euaiactready-wizard-dot--done' );
				if ( i < n ) {
					dot.classList.add( 'euaiactready-wizard-dot--done' );
				} else if ( i === n ) {
					dot.classList.add( 'euaiactready-wizard-dot--active' );
				}
			}
			if ( i < total ) {
				var conn = document.getElementById( 'wizard-conn-' + i );
				if ( conn ) {
					conn.classList.toggle( 'euaiactready-wizard-conn--done', i < n );
				}
			}
		}
	}

	function updateStepLabel( n ) {
		var label = document.getElementById( 'wizard-step-label' );
		if ( ! label ) {
			return;
		}
		label.textContent = ( n < total )
			? i18n.step_label.replace( '%1$d', n + 1 ).replace( '%2$d', total )
			: i18n.results;
	}

	// ---- Dynamic results rendering ------------------------------------------
	function escHtml( str ) {
		var d = document.createElement( 'div' );
		d.textContent = str;
		return d.innerHTML;
	}

	function computeAndRenderResults() {
		var container = document.getElementById( 'wizard-results-content' );
		if ( ! container ) {
			return;
		}

		var applicable = [];
		for ( var i = 0; i < questions.length; i++ ) {
			var answer = getSelectedAnswer( i );
			if ( answer === 'yes' || answer === 'sometimes' ) {
				applicable.push( questions[ i ] );
			}
		}

		if ( applicable.length === 0 ) {
			container.innerHTML = '<p>' + escHtml( i18n.no_obligations ) + '</p>';
			return;
		}

		var html = '<div class="euaiactready-wizard-results-list">';
		for ( var j = 0; j < applicable.length; j++ ) {
			var q = applicable[ j ];
			html += '<div class="euaiactready-wizard-result-item">';
			html += '<div class="euaiactready-wizard-result-articles">';
			for ( var k = 0; k < q.yes_articles.length; k++ ) {
				html += '<span class="euaiactready-article-badge">' + escHtml( q.yes_articles[ k ] ) + '</span> ';
			}
			html += '</div>';
			html += '<p class="euaiactready-wizard-result-action">';
			html += escHtml( q.action ) + ' ';
			html += '<a href="' + escHtml( q.settings_link ) + '" class="button button-small">' + escHtml( i18n.configure ) + '</a>';
			html += '</p>';
			html += '</div>';
		}
		html += '</div>';
		container.innerHTML = html;
	}

	// ---- Wizard navigation --------------------------------------------------
	function getStep( n ) {
		return document.getElementById( 'wizard-step-' + n );
	}

	function getSelectedAnswer( n ) {
		var radios = document.querySelectorAll( '#wizard-step-' + n + ' input[type="radio"]' );
		for ( var i = 0; i < radios.length; i++ ) {
			if ( radios[ i ].checked ) {
				return radios[ i ].value;
			}
		}
		return '';
	}

	function updateNav() {
		backBtn.disabled = ( current === 0 || current >= total );
		if ( current >= total ) {
			nextBtn.classList.add( 'euaiactready-hidden' );
			submitBtn.classList.remove( 'euaiactready-hidden' );
		} else {
			nextBtn.classList.remove( 'euaiactready-hidden' );
			submitBtn.classList.add( 'euaiactready-hidden' );
			nextBtn.disabled = ( getSelectedAnswer( current ) === '' );
		}
	}

	function showStep( n ) {
		for ( var i = 0; i <= total; i++ ) {
			var s = getStep( i );
			if ( s ) {
				s.classList.toggle( 'euaiactready-wizard-step--active', i === n );
			}
		}
		current = n;
		updateStepDots( n );
		updateStepLabel( n );
		updateNav();

		// Render results from current answers when reaching the results step.
		if ( n === total ) {
			computeAndRenderResults();
		}

		window.scrollTo( 0, 0 );
	}

	// Highlight selected option card when radio changes.
	document.addEventListener( 'change', function ( e ) {
		if ( e.target && e.target.type === 'radio' && e.target.name.indexOf( 'euaiactready_q' ) === 0 ) {
			var options = e.target.closest( '.euaiactready-wizard-options' ).querySelectorAll( '.euaiactready-wizard-option' );
			for ( var i = 0; i < options.length; i++ ) {
				options[ i ].classList.remove( 'euaiactready-wizard-option--selected' );
			}
			e.target.closest( '.euaiactready-wizard-option' ).classList.add( 'euaiactready-wizard-option--selected' );
			nextBtn.disabled = false;
		}
	} );

	nextBtn.addEventListener( 'click', function () {
		if ( current < total ) {
			showStep( current + 1 );
		}
	} );

	backBtn.addEventListener( 'click', function () {
		if ( current > 0 ) {
			showStep( current - 1 );
		}
	} );

	// Auto-save when the user clicks a Configure link on the results step.
	// Sets the redirect target on the form so PHP saves and then redirects.
	document.getElementById( 'euaiactready-wizard' ).addEventListener( 'click', function ( e ) {
		var link = e.target.closest( '#wizard-step-' + total + ' a.button' );
		if ( ! link ) {
			return;
		}
		var redirectField = document.getElementById( 'euaiactready-redirect-to' );
		if ( ! redirectField ) {
			return;
		}
		e.preventDefault();
		redirectField.value = link.href;
		document.getElementById( 'euaiactready-assessment-form' ).submit();
	} );

	// Init.
	showStep( 0 );
}());
</script>
