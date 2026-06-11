<?php
/**
 * EU AI Act Ready - AI Content Page (Posts/Pages)
 *
 * @package EUAIACTREADY
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Query for all enabled post types with any AI disclosure level (new values + legacy '1').
$euaiactready_manually_marked_args = array(
	'post_type'      => EUAIACTREADY_Post_Meta_Box::euaiactready_get_all_enabled_post_types(),
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'orderby'        => 'modified',
	'order'          => 'DESC',
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Filtering flagged posts requires metadata lookup.
	'meta_query'     => array(
		'relation' => 'OR',
		array(
			'key'     => '_euaiactready_ai_content',
			'value'   => array( 'assisted', 'generated', 'generated_reviewed' ),
			'compare' => 'IN',
		),
		// Backward compatibility: old posts stored '1' for AI content.
		array(
			'key'     => '_euaiactready_ai_content',
			'value'   => '1',
			'compare' => '=',
		),
	),
);

$euaiactready_manually_marked_query   = new WP_Query( $euaiactready_manually_marked_args );
$euaiactready_manually_marked_content = $euaiactready_manually_marked_query->posts;

wp_reset_postdata();

$euaiactready_disclosure_levels = EUAIACTREADY_Post_Meta_Box::euaiactready_get_disclosure_levels();
?>

<div class="wrap euaiactready-dashboard">
	<div class="euaiactready-header">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<p><?php esc_html_e( 'Manage content that has been marked with an AI disclosure level.', 'eu-ai-act-ready' ); ?></p>
	</div>

	<div class="euaiactready-detections euaiactready-section-content">
		<?php if ( empty( $euaiactready_manually_marked_content ) ) : ?>
			<div class="euaiactready-empty-state">
				<span class="dashicons dashicons-media-text"></span>
				<p><?php esc_html_e( 'No content has been marked with an AI disclosure level yet.', 'eu-ai-act-ready' ); ?></p>
				<p class="hint">
					<?php esc_html_e( 'You can set the disclosure level in the editor sidebar when editing any content.', 'eu-ai-act-ready' ); ?>
				</p>
			</div>
		<?php else : ?>
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select id="euaiactready-bulk-action-content-top">
						<option value="-1"><?php esc_html_e( 'Bulk actions', 'eu-ai-act-ready' ); ?></option>
						<option value="unmark_ai"><?php esc_html_e( 'Unmark as AI', 'eu-ai-act-ready' ); ?></option>
					</select>
					<button type="button" id="euaiactready-doaction-content-top" class="button action"><?php esc_html_e( 'Apply', 'eu-ai-act-ready' ); ?></button>
				</div>
			</div>
			<table id="euaiactready-content-table" class="euaiactready-table">
				<thead>
					<tr>
						<th class="check-column"><input type="checkbox" id="euaiactready-cb-select-all-content" /></th>
						<th class="column-title"><?php esc_html_e( 'Title', 'eu-ai-act-ready' ); ?></th>
						<th class="column-type"><?php esc_html_e( 'Type', 'eu-ai-act-ready' ); ?></th>
						<th class="column-status"><?php esc_html_e( 'Status', 'eu-ai-act-ready' ); ?></th>
						<th class="column-author"><?php esc_html_e( 'Author', 'eu-ai-act-ready' ); ?></th>
						<th class="column-disclosure"><?php esc_html_e( 'Disclosure Level', 'eu-ai-act-ready' ); ?></th>
						<th class="column-date"><?php esc_html_e( 'Marked Date', 'eu-ai-act-ready' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $euaiactready_manually_marked_content as $euaiactready_content ) : ?>
						<?php
						$euaiactready_type_object  = get_post_type_object( $euaiactready_content->post_type );
						$euaiactready_type_label   = $euaiactready_type_object ? $euaiactready_type_object->labels->singular_name : $euaiactready_content->post_type;
						$euaiactready_status_obj   = get_post_status_object( $euaiactready_content->post_status );
						$euaiactready_status_label = $euaiactready_status_obj ? $euaiactready_status_obj->label : $euaiactready_content->post_status;
						$euaiactready_marked_date  = (int) get_post_meta( $euaiactready_content->ID, '_euaiactready_ai_content_marked_date', true );
						$euaiactready_raw_value    = get_post_meta( $euaiactready_content->ID, '_euaiactready_ai_content', true );
						$euaiactready_disclosure   = EUAIACTREADY_Post_Meta_Box::euaiactready_normalize_disclosure_value( $euaiactready_raw_value );
						$euaiactready_disc_label   = isset( $euaiactready_disclosure_levels[ $euaiactready_disclosure ] )
							? $euaiactready_disclosure_levels[ $euaiactready_disclosure ]
							: $euaiactready_disclosure;
						?>
						<tr id="post-<?php echo esc_attr( $euaiactready_content->ID ); ?>">
							<td class="check-column">
								<input type="checkbox" name="content_ids[]" value="<?php echo esc_attr( $euaiactready_content->ID ); ?>" />
							</td>
							<td class="column-title">
								<a href="<?php echo esc_url( get_edit_post_link( $euaiactready_content->ID ) ); ?>">
									<?php echo esc_html( get_the_title( $euaiactready_content ) ); ?>
								</a>
								<div class="row-actions">
									<a href="<?php echo esc_url( get_permalink( $euaiactready_content->ID ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'eu-ai-act-ready' ); ?></a>
									<span class="separator">|</span>
									<a href="<?php echo esc_url( get_edit_post_link( $euaiactready_content->ID ) ); ?>"><?php esc_html_e( 'Edit', 'eu-ai-act-ready' ); ?></a>
									<span class="separator">|</span>
									<button type="button" class="euaiactready-unmark-content action-danger" data-post-id="<?php echo esc_attr( $euaiactready_content->ID ); ?>">
										<?php esc_html_e( 'Unmark as AI content', 'eu-ai-act-ready' ); ?>
									</button>
								</div>
							</td>
							<td class="column-type">
								<?php
								if ( 'page' === $euaiactready_content->post_type ) {
									$euaiactready_dashicon = 'admin-page';
								} elseif ( 'post' === $euaiactready_content->post_type ) {
									$euaiactready_dashicon = 'admin-post';
								} else {
									$euaiactready_pt_obj_icon = get_post_type_object( $euaiactready_content->post_type );
									$euaiactready_dashicon    = ( $euaiactready_pt_obj_icon && ! empty( $euaiactready_pt_obj_icon->menu_icon ) && str_starts_with( $euaiactready_pt_obj_icon->menu_icon, 'dashicons-' ) )
										? ltrim( $euaiactready_pt_obj_icon->menu_icon, 'dashicons-' )
										: 'admin-post';
								}
								?>
								<span class="type-indicator">
									<span class="dashicons dashicons-<?php echo esc_attr( $euaiactready_dashicon ); ?>"></span>
									<?php echo esc_html( $euaiactready_type_label ); ?>
								</span>
							</td>
							<td class="column-status">
								<?php echo esc_html( $euaiactready_status_label ); ?>
							</td>
							<td class="column-author">
								<?php echo esc_html( get_the_author_meta( 'display_name', $euaiactready_content->post_author ) ); ?>
							</td>
							<td class="column-disclosure">
								<?php echo esc_html( $euaiactready_disc_label ); ?>
							</td>
							<td class="column-date" data-order="<?php echo esc_attr( $euaiactready_marked_date ); ?>">
								<?php
								if ( $euaiactready_marked_date ) {
									echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $euaiactready_marked_date ) );
								} else {
									printf( '<span class="no-date">%s</span>', esc_html__( 'Not set', 'eu-ai-act-ready' ) );
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( ! empty( $euaiactready_manually_marked_content ) ) : ?>
			<div class="tablenav bottom">
				<div class="alignleft actions bulkactions">
					<select id="euaiactready-bulk-action-content">
						<option value="-1"><?php esc_html_e( 'Bulk actions', 'eu-ai-act-ready' ); ?></option>
						<option value="unmark_ai"><?php esc_html_e( 'Unmark as AI', 'eu-ai-act-ready' ); ?></option>
					</select>
					<button type="button" id="euaiactready-doaction-content" class="button action"><?php esc_html_e( 'Apply', 'eu-ai-act-ready' ); ?></button>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<?php require EUAIACTREADY_PLUGIN_DIR . 'admin/partials/bulk-scan-modal.php'; ?>
</div>
