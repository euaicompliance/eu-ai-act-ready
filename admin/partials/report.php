<?php
/**
 * EU AI Act Ready - Compliance Report (Print-Friendly)
 *
 * This file is loaded directly when admin-post action 'euaiactready_print_report' is triggered.
 * It outputs a self-contained HTML page suitable for printing.
 *
 * @package EUAIACTREADY
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Security: verify nonce and capability.
if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'euaiactready_print_report' ) ) {
	wp_die( esc_html__( 'Security check failed.', 'eu-ai-act-ready' ) );
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Unauthorized.', 'eu-ai-act-ready' ) );
}

// Gather data.
$euaiactready_readiness      = new Euaiactready_Readiness();
$euaiactready_score          = $euaiactready_readiness->calculate();
$euaiactready_traffic_light  = Euaiactready_Readiness::get_traffic_light( $euaiactready_score );
$euaiactready_items          = $euaiactready_readiness->get_items();

$euaiactready_detector       = new Euaiactready_AI_Tools_Detector( new Euaiactready_AI_Tools_Registry() );
$euaiactready_detected_tools = $euaiactready_detector->get_detected();

$euaiactready_assessment_complete = Euaiactready_Assessment::is_complete();
$euaiactready_answers             = Euaiactready_Assessment::get_answers();
$euaiactready_questions           = Euaiactready_Assessment::get_questions();
$euaiactready_applicable          = Euaiactready_Assessment::get_applicable_questions();

$euaiactready_literacy_complete   = Euaiactready_AI_Literacy::is_complete();
$euaiactready_literacy_tasks      = Euaiactready_AI_Literacy::get_tasks();

// AI content count.
$euaiactready_content_query = new WP_Query( array(
	'post_type'      => 'any',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		array(
			'key'     => '_euaiactready_ai_content',
			'value'   => array( '1', 'assisted', 'generated', 'generated_reviewed' ),
			'compare' => 'IN',
		),
	),
) );
$euaiactready_ai_content_count = $euaiactready_content_query->found_posts;

// AI images count.
$euaiactready_images_query = new WP_Query( array(
	'post_type'      => 'attachment',
	'post_mime_type' => 'image',
	'post_status'    => 'inherit',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		array(
			'key'     => '_euaiactready_ai_generated',
			'value'   => '1',
			'compare' => '=',
		),
	),
) );
$euaiactready_ai_images_count = $euaiactready_images_query->found_posts;

$euaiactready_site_name = get_bloginfo( 'name' );
$euaiactready_site_url  = get_bloginfo( 'url' );
$euaiactready_date      = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

// Disclosure notice statuses.
$euaiactready_content_notice_enabled  = get_option( 'euaiactready_transparency_enabled', true );
$euaiactready_chatbot_notice_enabled  = get_option( 'euaiactready_chatbot_transparency', true );
$euaiactready_media_notice_enabled    = get_option( 'euaiactready_media_transparency', true );
$euaiactready_ai_tools_notice_enabled = (bool) get_option( Euaiactready_AI_Tools::OPTION_ENABLED, true );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php
		/* translators: %s: website name */
		printf( esc_html__( 'EU AI Act Compliance Report - %s', 'eu-ai-act-ready' ), esc_html( $euaiactready_site_name ) );
	?></title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #1d2327; background: #fff; padding: 40px; max-width: 900px; margin: 0 auto; }
		h1 { font-size: 24px; margin-bottom: 4px; color: #1d2327; }
		h2 { font-size: 18px; margin: 28px 0 12px; color: #1d2327; border-bottom: 2px solid #e2e4e7; padding-bottom: 6px; }
		h3 { font-size: 15px; margin: 16px 0 8px; }
		p { margin-bottom: 10px; line-height: 1.6; }
		.meta { color: #646970; font-size: 13px; margin-bottom: 24px; }
		.score-wrap { display: flex; align-items: center; gap: 20px; margin: 16px 0; }
		.score-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
		.score-circle.green { background: #e6f6ec; border: 3px solid #00a32a; color: #00a32a; }
		.score-circle.amber { background: #fcf9e8; border: 3px solid #f0b849; color: #855A00; }
		.score-circle.red { background: #fcebec; border: 3px solid #d63638; color: #d63638; }
		.score-number { font-size: 28px; line-height: 1; }
		.score-denom { font-size: 12px; }
		.factor-list { list-style: none; padding: 0; }
		.factor-list li { padding: 8px 0; border-bottom: 1px solid #f0f0f1; display: flex; gap: 10px; align-items: flex-start; }
		.factor-list li:last-child { border-bottom: 0; }
		.icon-met { color: #00a32a; }
		.icon-unmet { color: #d63638; }
		.tools-table { width: 100%; border-collapse: collapse; font-size: 13px; }
		.tools-table th { background: #f6f7f7; padding: 8px 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e4e7; }
		.tools-table td { padding: 8px 12px; border-bottom: 1px solid #f0f0f1; vertical-align: top; }
		.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
		.badge-chatbot { background: #e6f6ec; color: #00a32a; }
		.badge-content { background: #e8f0fe; color: #1a56db; }
		.badge-commerce { background: #fce8e8; color: #d63638; }
		.badge-seo { background: #fef9e7; color: #856404; }
		.badge-other { background: #f3f4f6; color: #646970; }
		.answer-yes { color: #00a32a; font-weight: 600; }
		.answer-no { color: #646970; }
		.answer-sometimes { color: #f0b849; font-weight: 600; }
		.checklist { list-style: none; padding: 0; }
		.checklist li { padding: 6px 0; display: flex; gap: 10px; border-bottom: 1px solid #f0f0f1; }
		.checklist li:last-child { border-bottom: 0; }
		.stats-row { display: flex; gap: 20px; margin: 16px 0; flex-wrap: wrap; }
		.stat-box { flex: 1; min-width: 120px; border: 1px solid #e2e4e7; border-radius: 6px; padding: 12px 16px; text-align: center; }
		.stat-box .num { font-size: 28px; font-weight: 700; color: #1d2327; }
		.stat-box .lbl { font-size: 12px; color: #646970; margin-top: 2px; }
		.intro { color: #646970; margin-bottom: 24px; line-height: 1.7; border-left: 3px solid #e2e4e7; padding: 10px 14px; background: #f9f9f9; }
		.status-enabled { color: #00a32a; font-weight: 600; }
		.status-disabled { color: #d63638; font-weight: 600; }
		.footer { margin-top: 40px; padding-top: 16px; border-top: 2px solid #e2e4e7; color: #646970; font-size: 12px; }
		.footer a { color: #646970; }
		.print-btn { position: fixed; top: 20px; right: 20px; background: #667eea; color: #fff; border: 0; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
		.no-print { }
		@media print {
			.no-print { display: none !important; }
			body { padding: 20px; }
		}
	</style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()"><?php esc_html_e( 'Print / Save PDF', 'eu-ai-act-ready' ); ?></button>

<h1><?php esc_html_e( 'EU AI Act Compliance Report', 'eu-ai-act-ready' ); ?></h1>
<p class="meta">
	<?php echo esc_html( $euaiactready_site_name ); ?> &middot;
	<a href="<?php echo esc_url( $euaiactready_site_url ); ?>" target="_blank"><?php echo esc_url( $euaiactready_site_url ); ?></a> &middot;
	<?php echo esc_html( $euaiactready_date ); ?>
</p>
<p class="intro">
	<?php esc_html_e( 'This report summarises the AI transparency and compliance measures active on this site as of the date shown above. It covers the readiness score, registered AI systems, self-assessment answers, and AI literacy obligations under the EU AI Act. It is provided for informational purposes and does not constitute legal advice.', 'eu-ai-act-ready' ); ?>
</p>

<h2><?php esc_html_e( 'Readiness Score', 'eu-ai-act-ready' ); ?></h2>
<div class="score-wrap">
	<div class="score-circle <?php echo esc_attr( $euaiactready_traffic_light ); ?>">
		<span class="score-number"><?php echo esc_html( $euaiactready_score ); ?></span>
		<span class="score-denom">/100</span>
	</div>
	<div>
		<strong><?php echo esc_html( Euaiactready_Readiness::get_traffic_light_label( $euaiactready_traffic_light ) ); ?></strong>
		<p><?php esc_html_e( 'Score based on completed transparency obligations.', 'eu-ai-act-ready' ); ?></p>
	</div>
</div>

<?php if ( ! empty( $euaiactready_items['met'] ) ) : ?>
<ul class="factor-list">
	<?php foreach ( $euaiactready_items['met'] as $euaiactready_item ) : ?>
	<li><span class="icon-met">&#10003;</span> <?php echo esc_html( $euaiactready_item['label'] ); ?></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php if ( ! empty( $euaiactready_items['unmet'] ) ) : ?>
<ul class="factor-list" style="margin-top:8px">
	<?php foreach ( $euaiactready_items['unmet'] as $euaiactready_item ) : ?>
	<li><span class="icon-unmet">&#10007;</span> <?php echo esc_html( $euaiactready_item['label'] ); ?></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>

<h2><?php esc_html_e( 'AI Content Statistics', 'eu-ai-act-ready' ); ?></h2>
<div class="stats-row">
	<div class="stat-box">
		<div class="num"><?php echo esc_html( number_format_i18n( count( $euaiactready_detected_tools ) ) ); ?></div>
		<div class="lbl"><?php esc_html_e( 'AI Systems registered', 'eu-ai-act-ready' ); ?></div>
	</div>
	<div class="stat-box">
		<div class="num"><?php echo esc_html( number_format_i18n( $euaiactready_ai_content_count ) ); ?></div>
		<div class="lbl"><?php esc_html_e( 'AI content items', 'eu-ai-act-ready' ); ?></div>
	</div>
	<div class="stat-box">
		<div class="num"><?php echo esc_html( number_format_i18n( $euaiactready_ai_images_count ) ); ?></div>
		<div class="lbl"><?php esc_html_e( 'AI images', 'eu-ai-act-ready' ); ?></div>
	</div>
</div>

<h2><?php esc_html_e( 'Disclosure Notices', 'eu-ai-act-ready' ); ?></h2>
<table class="tools-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Notice', 'eu-ai-act-ready' ); ?></th>
			<th><?php esc_html_e( 'Status', 'eu-ai-act-ready' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php esc_html_e( 'Content Transparency', 'eu-ai-act-ready' ); ?></td>
			<td class="<?php echo esc_attr( $euaiactready_content_notice_enabled ? 'status-enabled' : 'status-disabled' ); ?>">
				<?php echo $euaiactready_content_notice_enabled ? esc_html__( '&#10003; Enabled', 'eu-ai-act-ready' ) : esc_html__( '&#10007; Disabled', 'eu-ai-act-ready' ); ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Chatbot Transparency', 'eu-ai-act-ready' ); ?></td>
			<td class="<?php echo esc_attr( $euaiactready_chatbot_notice_enabled ? 'status-enabled' : 'status-disabled' ); ?>">
				<?php echo $euaiactready_chatbot_notice_enabled ? esc_html__( '&#10003; Enabled', 'eu-ai-act-ready' ) : esc_html__( '&#10007; Disabled', 'eu-ai-act-ready' ); ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Media / Image Labels', 'eu-ai-act-ready' ); ?></td>
			<td class="<?php echo esc_attr( $euaiactready_media_notice_enabled ? 'status-enabled' : 'status-disabled' ); ?>">
				<?php echo $euaiactready_media_notice_enabled ? esc_html__( '&#10003; Enabled', 'eu-ai-act-ready' ) : esc_html__( '&#10007; Disabled', 'eu-ai-act-ready' ); ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'AI Systems Notice', 'eu-ai-act-ready' ); ?></td>
			<td class="<?php echo esc_attr( $euaiactready_ai_tools_notice_enabled ? 'status-enabled' : 'status-disabled' ); ?>">
				<?php echo $euaiactready_ai_tools_notice_enabled ? esc_html__( '&#10003; Enabled', 'eu-ai-act-ready' ) : esc_html__( '&#10007; Disabled', 'eu-ai-act-ready' ); ?>
			</td>
		</tr>
	</tbody>
</table>

<?php if ( ! empty( $euaiactready_detected_tools ) ) : ?>
<h2><?php esc_html_e( 'Registered AI Systems', 'eu-ai-act-ready' ); ?></h2>
<table class="tools-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Name', 'eu-ai-act-ready' ); ?></th>
			<th><?php esc_html_e( 'Category', 'eu-ai-act-ready' ); ?></th>
			<th><?php esc_html_e( 'Article', 'eu-ai-act-ready' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $euaiactready_detected_tools as $euaiactready_tool ) : ?>
		<tr>
			<td><?php echo esc_html( $euaiactready_tool['name'] ?? '' ); ?></td>
			<td><span class="badge badge-<?php echo esc_attr( $euaiactready_tool['category'] ?? 'other' ); ?>"><?php echo esc_html( $euaiactready_tool['category'] ?? '' ); ?></span></td>
			<td><?php echo esc_html( $euaiactready_tool['eu_ai_act_article'] ?? ( $euaiactready_tool['article'] ?? '' ) ); ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>

<h2><?php esc_html_e( 'Compliance Assessment', 'eu-ai-act-ready' ); ?></h2>
<?php if ( $euaiactready_assessment_complete ) : ?>
<table class="tools-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Question', 'eu-ai-act-ready' ); ?></th>
			<th><?php esc_html_e( 'Answer', 'eu-ai-act-ready' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $euaiactready_questions as $euaiactready_q ) :
			$euaiactready_ans = $euaiactready_answers[ $euaiactready_q['id'] ] ?? '';
		?>
		<tr>
			<td><?php echo esc_html( $euaiactready_q['text'] ); ?></td>
			<td class="answer-<?php echo esc_attr( $euaiactready_ans ); ?>">
				<?php
				if ( 'yes' === $euaiactready_ans ) {
					esc_html_e( 'Yes', 'eu-ai-act-ready' );
				} elseif ( 'no' === $euaiactready_ans ) {
					esc_html_e( 'No', 'eu-ai-act-ready' );
				} elseif ( 'sometimes' === $euaiactready_ans ) {
					esc_html_e( 'Sometimes', 'eu-ai-act-ready' );
				}
				?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php if ( ! empty( $euaiactready_applicable ) ) : ?>
<h3 style="margin-top:16px"><?php esc_html_e( 'Applicable obligations:', 'eu-ai-act-ready' ); ?></h3>
<ul class="checklist">
	<?php foreach ( $euaiactready_applicable as $euaiactready_aq ) : ?>
	<li>
		<span>&#9654;</span>
		<div>
			<strong><?php echo esc_html( implode( ', ', $euaiactready_aq['yes_articles'] ) ); ?></strong> &mdash;
			<?php echo esc_html( $euaiactready_aq['action'] ); ?>
		</div>
	</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>
<?php else : ?>
<p><?php esc_html_e( 'Assessment not yet completed.', 'eu-ai-act-ready' ); ?></p>
<?php endif; ?>

<h2><?php esc_html_e( 'AI Literacy Checklist (Article 4)', 'eu-ai-act-ready' ); ?></h2>
<ul class="checklist">
	<?php foreach ( $euaiactready_literacy_tasks as $euaiactready_task ) : ?>
	<li>
		<span class="<?php echo esc_attr( $euaiactready_task['checked'] ? 'icon-met' : 'icon-unmet' ); ?>">
			<?php echo $euaiactready_task['checked'] ? '&#10003;' : '&#9675;'; ?>
		</span>
		<?php echo esc_html( $euaiactready_task['label'] ); ?>
	</li>
	<?php endforeach; ?>
</ul>

<?php if ( ! empty( $euaiactready_items['unmet'] ) ) : ?>
<h2><?php esc_html_e( 'Next Steps', 'eu-ai-act-ready' ); ?></h2>
<p><?php esc_html_e( 'The following obligations have not yet been addressed. Completing these will improve your readiness score:', 'eu-ai-act-ready' ); ?></p>
<ul class="checklist">
	<?php foreach ( $euaiactready_items['unmet'] as $euaiactready_item ) : ?>
	<li>
		<span class="icon-unmet">&#10007;</span>
		<div><?php echo esc_html( $euaiactready_item['label'] ); ?></div>
	</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="footer">
	<p>
		<?php esc_html_e( 'Generated by', 'eu-ai-act-ready' ); ?>
		<a href="<?php echo esc_url( 'https://eu-ai-act-ready.com/' ); ?>" target="_blank">EU AI Act Ready</a>.
		<?php esc_html_e( 'This report is for informational purposes only and does not constitute legal advice or guarantee compliance with the EU AI Act.', 'eu-ai-act-ready' ); ?>
	</p>
	<p><?php echo esc_html( $euaiactready_site_name ); ?> &mdash; <?php echo esc_html( $euaiactready_date ); ?></p>
</div>

</body>
</html>
<?php
// Prevent WordPress from adding anything after our output.
exit;
