<?php
/**
 * EU AI Act Ready - AI Literacy Admin Page (Article 4)
 *
 * @package EUAIACTREADY
 */


if ( ! defined( 'WPINC' ) ) {
	die;
}

// Handle form submission.
if ( isset( $_POST['euaiactready_literacy_nonce'] ) ) {
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['euaiactready_literacy_nonce'] ) ), 'euaiactready_save_literacy' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'eu-ai-act-ready' ) );
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized.', 'eu-ai-act-ready' ) );
	}

	$euaiactready_submitted = array();
	foreach ( Euaiactready_AI_Literacy::get_tasks() as $euaiactready_task ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$euaiactready_submitted[ $euaiactready_task['id'] ] = isset( $_POST[ 'euaiactready_literacy_' . $euaiactready_task['id'] ] ) ? 1 : 0;
	}
	Euaiactready_AI_Literacy::save( $euaiactready_submitted );
	( new Euaiactready_Readiness() )->calculate();

	add_settings_error( 'euaiactready_literacy', 'saved', __( 'Literacy checklist saved.', 'eu-ai-act-ready' ), 'success' );
}

$euaiactready_tasks     = Euaiactready_AI_Literacy::get_tasks();
$euaiactready_completed = Euaiactready_AI_Literacy::get_completed_count();
$euaiactready_total     = Euaiactready_AI_Literacy::get_total_count();
$euaiactready_is_done   = Euaiactready_AI_Literacy::is_complete();
?>

<div class="wrap euaiactready-literacy">
	<h1 class="euaiactready-page-title">
		<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 22, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
		<?php esc_html_e( 'AI Literacy', 'eu-ai-act-ready' ); ?>
	</h1>

	<?php settings_errors( 'euaiactready_literacy' ); ?>

	<div class="euaiactready-literacy-intro euaiactready-card">
		<div class="euaiactready-literacy-intro-header">
			<div>
				<h2><?php esc_html_e( 'Article 4 - AI Literacy Obligations', 'eu-ai-act-ready' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Article 4 of the EU AI Act requires deployers of AI systems to ensure that their staff possess sufficient AI literacy to understand and oversee AI tools in use. Use this checklist to track your compliance with these obligations.', 'eu-ai-act-ready' ); ?>
				</p>
			</div>
			<div class="euaiactready-literacy-progress-wrap">
				<div class="euaiactready-literacy-progress-circle <?php echo esc_attr( $euaiactready_is_done ? 'complete' : 'incomplete' ); ?>">
					<span class="euaiactready-literacy-progress-number"><?php echo esc_html( $euaiactready_completed ); ?></span>
					<span class="euaiactready-literacy-progress-denom">/<?php echo esc_html( $euaiactready_total ); ?></span>
				</div>
				<p class="euaiactready-literacy-progress-label">
					<?php echo $euaiactready_is_done ? esc_html__( 'Complete', 'eu-ai-act-ready' ) : esc_html__( 'In progress', 'eu-ai-act-ready' ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="euaiactready-card">
		<h2><?php esc_html_e( 'Compliance Checklist', 'eu-ai-act-ready' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Check each item when the obligation has been addressed for your organisation. Your answers are saved to this site only and are not transmitted externally.', 'eu-ai-act-ready' ); ?>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'euaiactready_save_literacy', 'euaiactready_literacy_nonce' ); ?>

			<div class="euaiactready-literacy-tasks">
				<?php foreach ( $euaiactready_tasks as $euaiactready_task ) : ?>
				<label class="euaiactready-literacy-task <?php echo esc_attr( $euaiactready_task['checked'] ? 'task-checked' : '' ); ?>">
					<input type="checkbox"
						name="euaiactready_literacy_<?php echo esc_attr( $euaiactready_task['id'] ); ?>"
						value="1"
						<?php checked( $euaiactready_task['checked'], true ); ?>
					/>
					<span class="euaiactready-literacy-task-check">
						<span class="dashicons dashicons-yes"></span>
					</span>
					<span class="euaiactready-literacy-task-label"><?php echo esc_html( $euaiactready_task['label'] ); ?></span>
				</label>
				<?php endforeach; ?>
			</div>

			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Checklist', 'eu-ai-act-ready' ); ?>" />
			</p>
		</form>
	</div>

	<div class="euaiactready-card euaiactready-literacy-info">
		<h2><?php esc_html_e( 'What Article 4 Requires', 'eu-ai-act-ready' ); ?></h2>
		<p>
			<?php esc_html_e( 'Article 4 of the EU AI Act applies to providers and deployers of AI systems. As a website operator using AI tools, you are considered a deployer. The Article requires you to take reasonable steps to ensure that staff and relevant stakeholders have sufficient AI literacy.', 'eu-ai-act-ready' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'This means ensuring that people who work with or are affected by AI tools understand:', 'eu-ai-act-ready' ); ?>
		</p>
		<ul>
			<li><?php esc_html_e( 'What AI systems are in use and what they do', 'eu-ai-act-ready' ); ?></li>
			<li><?php esc_html_e( 'The capabilities and limitations of those AI systems', 'eu-ai-act-ready' ); ?></li>
			<li><?php esc_html_e( 'The impact those systems may have on individuals and decisions', 'eu-ai-act-ready' ); ?></li>
			<li><?php esc_html_e( 'How to identify and escalate concerns about AI behaviour', 'eu-ai-act-ready' ); ?></li>
		</ul>
		<p class="description">
			<strong><?php esc_html_e( 'Note:', 'eu-ai-act-ready' ); ?></strong>
			<?php esc_html_e( 'This plugin provides tools to support AI literacy obligations. It does not provide legal advice and cannot guarantee compliance. Consult a legal professional for formal compliance assessment.', 'eu-ai-act-ready' ); ?>
		</p>
		<p class="description" style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e4e7">
			<strong><?php esc_html_e( 'Creating a dated record:', 'eu-ai-act-ready' ); ?></strong>
			<?php esc_html_e( 'To generate a dated compliance document that includes your AI literacy status alongside your readiness score and AI systems, use the Export Report button on the Dashboard.', 'eu-ai-act-ready' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready' ) ); ?>">
				<?php esc_html_e( 'Go to Dashboard', 'eu-ai-act-ready' ); ?>
			</a>
		</p>
	</div>
</div>

<script>
(function () {
	'use strict';

	var total          = <?php echo (int) $euaiactready_total; ?>;
	var progressNum    = document.querySelector( '.euaiactready-literacy-progress-number' );
	var progressLabel  = document.querySelector( '.euaiactready-literacy-progress-label' );
	var progressCircle = document.querySelector( '.euaiactready-literacy-progress-circle' );

	function countChecked() {
		return document.querySelectorAll( '.euaiactready-literacy-task input[type="checkbox"]:checked' ).length;
	}

	function updateProgress() {
		var count  = countChecked();
		var isDone = ( count === total );

		if ( progressNum ) {
			progressNum.textContent = count;
		}
		if ( progressCircle ) {
			progressCircle.classList.toggle( 'complete', isDone );
			progressCircle.classList.toggle( 'incomplete', ! isDone );
		}
		if ( progressLabel ) {
			progressLabel.textContent = isDone
				? '<?php echo esc_js( __( 'Complete', 'eu-ai-act-ready' ) ); ?>'
				: '<?php echo esc_js( __( 'In progress', 'eu-ai-act-ready' ) ); ?>';
		}
	}

	document.querySelectorAll( '.euaiactready-literacy-task input[type="checkbox"]' ).forEach( function ( checkbox ) {
		checkbox.addEventListener( 'change', function () {
			this.closest( '.euaiactready-literacy-task' ).classList.toggle( 'task-checked', this.checked );
			updateProgress();
		} );
	} );
}());
</script>
