<?php
/**
 * EU AI Act Ready - Handles AI disclosure meta box and related admin workflows.
 *
 * @package EUAIACTREADY
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides admin UI integrations for AI content disclosure.
 */
class EUAIACTREADY_Post_Meta_Box {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'euaiactready_add_ai_disclosure_meta_box' ) );
		add_action( 'save_post', array( $this, 'euaiactready_save_ai_disclosure_meta_box' ) );

		// Register post-type-specific hooks on admin_init so CPTs declared on init are available.
		add_action( 'admin_init', array( $this, 'euaiactready_register_post_type_hooks' ) );

		// Filter posts_clauses for proper sorting.
		add_filter( 'posts_clauses', array( $this, 'euaiactready_ai_column_clauses' ), 10, 2 );

		// Filter dropdown and query filter are universal; they check $typenow dynamically.
		add_action( 'restrict_manage_posts', array( $this, 'euaiactready_add_ai_content_filter' ) );
		add_filter( 'parse_query', array( $this, 'euaiactready_filter_by_ai_content' ) );

		add_action( 'admin_notices', array( $this, 'euaiactready_bulk_action_notices' ) );

		// Quick Edit field is universal; it checks $post_type dynamically.
		add_action( 'quick_edit_custom_box', array( $this, 'euaiactready_add_quick_edit_field' ), 10, 2 );
		add_action( 'save_post', array( $this, 'euaiactready_save_quick_edit_data' ) );

		// Handle AJAX for row actions.
		add_action( 'wp_ajax_euaiactready_toggle_ai_status', array( $this, 'euaiactready_ajax_toggle_ai_status' ) );
	}

	/**
	 * Return the post type slugs that have the AI disclosure meta box enabled.
	 *
	 * Falls back to ['post', 'page'] when the option is missing or invalid.
	 *
	 * @return string[]
	 */
	public static function euaiactready_get_all_enabled_post_types(): array {
		$saved = get_option( 'euaiactready_enabled_post_types', array( 'post', 'page' ) );
		if ( ! is_array( $saved ) || empty( $saved ) ) {
			return array( 'post', 'page' );
		}
		return array_values( array_filter( array_map( 'sanitize_key', $saved ) ) );
	}

	/**
	 * Register column, sortable, bulk-action, and row-action hooks for every enabled post type.
	 *
	 * Runs on admin_init so that CPTs registered on the init hook are already available.
	 */
	public function euaiactready_register_post_type_hooks(): void {
		$post_types = self::euaiactready_get_all_enabled_post_types();

		foreach ( $post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'euaiactready_add_ai_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'euaiactready_render_ai_column' ), 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'euaiactready_make_ai_column_sortable' ) );
			add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'euaiactready_add_bulk_actions' ) );
			add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'euaiactready_handle_bulk_actions' ), 10, 3 );
			add_filter( "{$post_type}_row_actions", array( $this, 'euaiactready_add_row_actions' ), 10, 2 );
		}
	}

	/**
	 * Return the four disclosure levels with their labels.
	 *
	 * @return array<string,string> Keyed by value, value is translated label.
	 */
	public static function euaiactready_get_disclosure_levels() {
		return array(
			'none'               => __( 'No AI used', 'eu-ai-act-ready' ),
			'assisted'           => __( 'AI-assisted content', 'eu-ai-act-ready' ),
			'generated'          => __( 'AI-generated content', 'eu-ai-act-ready' ),
			'generated_reviewed' => __( 'AI-generated, human reviewed (exempt from labeling under Article 50(4))', 'eu-ai-act-ready' ),
		);
	}

	/**
	 * Normalize a raw meta value to one of the four canonical disclosure levels.
	 *
	 * Handles legacy binary values ('1' --> 'generated', '0'/'' --> 'none').
	 *
	 * @param mixed $value Raw value from post meta.
	 * @return string One of: 'none', 'assisted', 'generated', 'generated_reviewed'.
	 */
	public static function euaiactready_normalize_disclosure_value( $value ) {
		if ( '1' === $value ) {
			return 'generated';
		}
		$valid = array( 'none', 'assisted', 'generated', 'generated_reviewed' );
		if ( in_array( $value, $valid, true ) ) {
			return $value;
		}
		return 'none';
	}

	/**
	 * Add meta box to all enabled post types.
	 */
	public function euaiactready_add_ai_disclosure_meta_box() {
		$post_types = self::euaiactready_get_all_enabled_post_types();

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'euaiactready_disclosure',
				__( 'AI Content Disclosure', 'eu-ai-act-ready' ),
				array( $this, 'euaiactready_render_ai_disclosure_meta_box' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	/**
	 * Render the meta box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function euaiactready_render_ai_disclosure_meta_box( $post ) {
		wp_nonce_field( 'euaiactready_disclosure_nonce', 'euaiactready_disclosure_nonce_field' );

		$ai_content = get_post_meta( $post->ID, '_euaiactready_ai_content', true );
		$current    = self::euaiactready_normalize_disclosure_value( $ai_content );
		$levels     = self::euaiactready_get_disclosure_levels();

		?>
		<div class="eu-ai-act-ready-meta-box">
			<p class="description">
				<?php esc_html_e( 'Select the AI disclosure level for this content.', 'eu-ai-act-ready' ); ?>
			</p>
			<fieldset>
				<legend class="screen-reader-text">
					<?php esc_html_e( 'AI Content Disclosure', 'eu-ai-act-ready' ); ?>
				</legend>
				<?php foreach ( $levels as $value => $label ) : ?>
				<p>
					<label>
						<input type="radio"
							name="euaiactready_content"
							value="<?php echo esc_attr( $value ); ?>"
							<?php checked( $current, $value ); ?> />
						<?php echo esc_html( $label ); ?>
					</label>
				</p>
				<?php endforeach; ?>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * Save the meta box data.
	 *
	 * @param int $post_id The post ID.
	 */
	public function euaiactready_save_ai_disclosure_meta_box( $post_id ) {
		// Check if nonce is set.
		if ( ! isset( $_POST['euaiactready_disclosure_nonce_field'] ) ) {
			return;
		}

		// Verify nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['euaiactready_disclosure_nonce_field'] ) );
		if ( ! wp_verify_nonce( $nonce, 'euaiactready_disclosure_nonce' ) ) {
			return;
		}

		// Check if this is an autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user permissions.
		if ( isset( $_POST['post_type'] ) && 'page' === sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Get and validate the submitted disclosure level.
		$new_value = isset( $_POST['euaiactready_content'] )
			? sanitize_key( wp_unslash( $_POST['euaiactready_content'] ) )
			: 'none';

		// Legacy value migration: '1' --> 'generated', '0' --> 'none'.
		if ( '1' === $new_value ) {
			$new_value = 'generated';
		} elseif ( '0' === $new_value ) {
			$new_value = 'none';
		}

		$allowed = array_keys( self::euaiactready_get_disclosure_levels() );
		if ( ! in_array( $new_value, $allowed, true ) ) {
			$new_value = 'none';
		}

		$was_ai = self::euaiactready_normalize_disclosure_value(
			get_post_meta( $post_id, '_euaiactready_ai_content', true )
		);

		update_post_meta( $post_id, '_euaiactready_ai_content', $new_value );

		// Set timestamp when content is AI and no date exists yet.
		// This also backfills legacy posts that were marked before the date feature existed.
		if ( 'none' !== $new_value ) {
			$existing_date = get_post_meta( $post_id, '_euaiactready_ai_content_marked_date', true );
			if ( empty( $existing_date ) ) {
				$marked_datetime = current_datetime();
				update_post_meta( $post_id, '_euaiactready_ai_content_marked_date', $marked_datetime->getTimestamp() );
			}
		}
	}

	/**
	 * Add AI Content column to posts/pages list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function euaiactready_add_ai_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Add AI column after the title column.
			if ( 'title' === $key ) {
				$new_columns['ai_content'] = __( 'AI Content', 'eu-ai-act-ready' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render the AI Content column.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id Post ID.
	 */
	public function euaiactready_render_ai_column( $column_name, $post_id ) {
		if ( 'ai_content' !== $column_name ) {
			return;
		}

		$ai_content = get_post_meta( $post_id, '_euaiactready_ai_content', true );
		$disclosure = self::euaiactready_normalize_disclosure_value( $ai_content );

		switch ( $disclosure ) {
			case 'assisted':
				echo '<span class="dashicons dashicons-edit ai-column-yes-icon" title="' . esc_attr__( 'AI-assisted content', 'eu-ai-act-ready' ) . '"></span>';
				echo '<span class="ai-column-label"> ' . esc_html__( 'AI-assisted', 'eu-ai-act-ready' ) . '</span>';
				break;

			case 'generated':
				echo '<span class="dashicons dashicons-yes-alt ai-column-yes-icon" title="' . esc_attr__( 'AI-generated content', 'eu-ai-act-ready' ) . '"></span>';
				echo '<span class="ai-column-label"> ' . esc_html__( 'AI', 'eu-ai-act-ready' ) . '</span>';
				break;

			case 'generated_reviewed':
				echo '<span class="dashicons dashicons-yes ai-column-yes-icon" title="' . esc_attr__( 'AI-generated, human reviewed', 'eu-ai-act-ready' ) . '"></span>';
				echo '<span class="ai-column-label"> ' . esc_html__( 'AI (reviewed)', 'eu-ai-act-ready' ) . '</span>';
				break;

			default: // 'none'
				echo '<span class="dashicons dashicons-minus ai-column-minus-icon" title="' . esc_attr__( 'No AI used', 'eu-ai-act-ready' ) . '"></span>';
				break;
		}

		// Hidden data attribute for Quick Edit - stores the normalized disclosure level.
		echo '<div class="hidden ai-content-value" data-ai-content="' . esc_attr( $disclosure ) . '"></div>';
	}

	/**
	 * Make AI Content column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function euaiactready_make_ai_column_sortable( $columns ) {
		$columns['ai_content'] = 'ai_content';
		return $columns;
	}

	/**
	 * Modify SQL clauses to include posts without meta key.
	 *
	 * @param array    $clauses Query clauses.
	 * @param WP_Query $query The query object.
	 * @return array Modified clauses.
	 */
	public function euaiactready_ai_column_clauses( $clauses, $query ) {
		global $wpdb;

		if ( ! is_admin() || ! $query->is_main_query() ) {
			return $clauses;
		}

		if ( 'ai_content' === $query->get( 'orderby' ) ) {
			// Add LEFT JOIN for the meta table.
			$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS ai_meta ON ({$wpdb->posts}.ID = ai_meta.post_id AND ai_meta.meta_key = '_euaiactready_ai_content')";

			// Sort: any AI value (assisted/generated/generated_reviewed or legacy '1') before no-AI.
			$order              = $query->get( 'order' ) ? strtoupper( $query->get( 'order' ) ) : 'ASC';
			$clauses['orderby'] = "CASE WHEN COALESCE(ai_meta.meta_value, 'none') IN ('assisted', 'generated', 'generated_reviewed', '1') THEN 0 ELSE 1 END " . $order;
		}

		return $clauses;
	}

	/**
	 * Add AI Content filter dropdown to posts list.
	 */
	public function euaiactready_add_ai_content_filter() {
		global $typenow;

		if ( in_array( $typenow, self::euaiactready_get_all_enabled_post_types(), true ) ) {
			$selected    = isset( $_GET['euaiactready_ai_content_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['euaiactready_ai_content_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter.
			$nonce_field = wp_nonce_field( 'euaiactready_content_filter', 'euaiactready_content_filter_nonce', true, false );
			echo wp_kses_post( $nonce_field );

			$levels = self::euaiactready_get_disclosure_levels();
			?>
			<select name="euaiactready_ai_content_filter">
				<option value=""><?php esc_html_e( 'All AI Status', 'eu-ai-act-ready' ); ?></option>
				<?php foreach ( $levels as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}

	/**
	 * Filter posts by AI content status.
	 *
	 * @param WP_Query $query The query object.
	 */
	public function euaiactready_filter_by_ai_content( $query ) {
		global $pagenow, $typenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! in_array( $typenow, self::euaiactready_get_all_enabled_post_types(), true ) ) {
			return $query;
		}

		if ( ! isset( $_GET['euaiactready_ai_content_filter'] ) || '' === $_GET['euaiactready_ai_content_filter'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Value is verified by nonce below.
			return $query;
		}

		if ( ! isset( $_GET['euaiactready_content_filter_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence check before sanitizing.
			return $query;
		}

		$nonce = sanitize_text_field( wp_unslash( $_GET['euaiactready_content_filter_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'euaiactready_content_filter' ) ) {
			return $query;
		}

		$filter_value = sanitize_text_field( wp_unslash( $_GET['euaiactready_ai_content_filter'] ) );

		// Build meta queries per disclosure level; handle legacy '1'/'0' values for backward compat.
		$meta_queries = array(
			'none'               => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_euaiactready_ai_content',
					'value'   => 'none',
					'compare' => '=',
				),
				array(
					'key'     => '_euaiactready_ai_content',
					'value'   => '0',
					'compare' => '=',
				),
				array(
					'key'     => '_euaiactready_ai_content',
					'compare' => 'NOT EXISTS',
				),
			),
			'assisted'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_euaiactready_ai_content',
					'value'   => 'assisted',
					'compare' => '=',
				),
			),
			'generated'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_euaiactready_ai_content',
					'value'   => 'generated',
					'compare' => '=',
				),
				array(
					'key'     => '_euaiactready_ai_content',
					'value'   => '1',
					'compare' => '=',
				),
			),
			'generated_reviewed' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_euaiactready_ai_content',
					'value'   => 'generated_reviewed',
					'compare' => '=',
				),
			),
		);

		if ( isset( $meta_queries[ $filter_value ] ) ) {
			$query->set( 'meta_query', $meta_queries[ $filter_value ] );
		}

		return $query;
	}

	/**
	 * Add custom bulk actions.
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function euaiactready_add_bulk_actions( $bulk_actions ) {
		$bulk_actions['mark_as_ai']   = __( 'Mark as AI Content', 'eu-ai-act-ready' );
		$bulk_actions['unmark_as_ai'] = __( 'Unmark as AI Content', 'eu-ai-act-ready' );
		return $bulk_actions;
	}

	/**
	 * Handle custom bulk actions.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction Action name.
	 * @param array  $post_ids Post IDs.
	 * @return string Modified redirect URL.
	 */
	public function euaiactready_handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
		if ( 'mark_as_ai' === $doaction ) {
			foreach ( $post_ids as $post_id ) {
				update_post_meta( $post_id, '_euaiactready_ai_content', 'generated' );
				// Set timestamp if not already set (also backfills legacy posts without a date).
				$existing_date = get_post_meta( $post_id, '_euaiactready_ai_content_marked_date', true );
				if ( empty( $existing_date ) ) {
					$marked_datetime = current_datetime();
					update_post_meta( $post_id, '_euaiactready_ai_content_marked_date', $marked_datetime->getTimestamp() );
				}
			}
			// Remove the opposite action parameter if it exists.
			$redirect_to = remove_query_arg( 'euaiactready_bulk_ai_unmarked', $redirect_to );
			$redirect_to = add_query_arg( 'euaiactready_bulk_ai_marked', count( $post_ids ), $redirect_to );
		} elseif ( 'unmark_as_ai' === $doaction ) {
			foreach ( $post_ids as $post_id ) {
				update_post_meta( $post_id, '_euaiactready_ai_content', 'none' );
			}
			// Remove the opposite action parameter if it exists.
			$redirect_to = remove_query_arg( 'euaiactready_bulk_ai_marked', $redirect_to );
			$redirect_to = add_query_arg( 'euaiactready_bulk_ai_unmarked', count( $post_ids ), $redirect_to );
		}

		return $redirect_to;
	}

	/**
	 * Display admin notices for bulk actions.
	 */
	public function euaiactready_bulk_action_notices() {
		$marked_param = filter_input( INPUT_GET, 'euaiactready_bulk_ai_marked', FILTER_SANITIZE_NUMBER_INT );
		if ( null !== $marked_param && '' !== $marked_param ) {
			$count = absint( $marked_param );
			if ( $count > 0 ) {
				/* translators: %s: Number of items marked as AI. */
				$message = _n( '%s item marked as AI content.', '%s items marked as AI content.', $count, 'eu-ai-act-ready' );
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( sprintf( $message, number_format_i18n( $count ) ) )
				);
			}
		}

		$unmarked_param = filter_input( INPUT_GET, 'euaiactready_bulk_ai_unmarked', FILTER_SANITIZE_NUMBER_INT );
		if ( null !== $unmarked_param && '' !== $unmarked_param ) {
			$count = absint( $unmarked_param );
			if ( $count > 0 ) {
				/* translators: %s: Number of items unmarked as AI. */
				$message = _n( '%s item unmarked as AI content.', '%s items unmarked as AI content.', $count, 'eu-ai-act-ready' );
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( sprintf( $message, number_format_i18n( $count ) ) )
				);
			}
		}
	}

	/**
	 * Add AI Content field to Quick Edit.
	 *
	 * @param string $column_name Column name.
	 * @param string $post_type Post type.
	 */
	public function euaiactready_add_quick_edit_field( $column_name, $post_type ) {
		if ( 'ai_content' !== $column_name || ! in_array( $post_type, self::euaiactready_get_all_enabled_post_types(), true ) ) {
			return;
		}
		$levels = self::euaiactready_get_disclosure_levels();
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php esc_html_e( 'AI Content', 'eu-ai-act-ready' ); ?></span>
					<select name="euaiactready_content">
						<?php foreach ( $levels as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Save Quick Edit data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function euaiactready_save_quick_edit_data( $post_id ) {
		// Check if this is a quick edit save.
		if ( ! isset( $_POST['_inline_edit'] ) ) {
			return;
		}

		$inline_nonce = sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) );

		if ( ! wp_verify_nonce( $inline_nonce, 'inlineeditnonce' ) ) {
			return;
		}

		// Check if our field was submitted.
		if ( ! isset( $_POST['euaiactready_content'] ) ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$new_value = sanitize_key( wp_unslash( $_POST['euaiactready_content'] ) );

		// Legacy value migration: '1' --> 'generated', '0' --> 'none'.
		if ( '1' === $new_value ) {
			$new_value = 'generated';
		} elseif ( '0' === $new_value ) {
			$new_value = 'none';
		}

		$allowed = array_keys( self::euaiactready_get_disclosure_levels() );
		if ( ! in_array( $new_value, $allowed, true ) ) {
			$new_value = 'none';
		}

		$was_ai = self::euaiactready_normalize_disclosure_value(
			get_post_meta( $post_id, '_euaiactready_ai_content', true )
		);

		update_post_meta( $post_id, '_euaiactready_ai_content', $new_value );

		// Set timestamp when content is AI and no date exists yet (also backfills legacy posts).
		if ( 'none' !== $new_value ) {
			$existing_date = get_post_meta( $post_id, '_euaiactready_ai_content_marked_date', true );
			if ( empty( $existing_date ) ) {
				$marked_datetime = current_datetime();
				update_post_meta( $post_id, '_euaiactready_ai_content_marked_date', $marked_datetime->getTimestamp() );
			}
		}
	}

	/**
	 * Add custom row actions.
	 *
	 * @param array   $actions Existing actions.
	 * @param WP_Post $post Post object.
	 * @return array Modified actions.
	 */
	public function euaiactready_add_row_actions( $actions, $post ) {
		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$ai_content = get_post_meta( $post->ID, '_euaiactready_ai_content', true );
		$disclosure = self::euaiactready_normalize_disclosure_value( $ai_content );

		if ( 'none' !== $disclosure ) {
			// Currently marked as AI; show unmark option.
			$actions['ai_unmark'] = sprintf(
				'<a href="#" class="ai-toggle-status" data-post-id="%d" data-action="unmark" data-nonce="%s">%s</a>',
				$post->ID,
				esc_attr( wp_create_nonce( 'euaiactready_ai_toggle_' . $post->ID ) ),
				esc_html__( 'Unmark as AI Content', 'eu-ai-act-ready' )
			);
		} else {
			// Not marked as AI; show mark option (defaults to 'generated').
			$actions['ai_mark'] = sprintf(
				'<a href="#" class="ai-toggle-status" data-post-id="%d" data-action="mark" data-nonce="%s">%s</a>',
				$post->ID,
				esc_attr( wp_create_nonce( 'euaiactready_ai_toggle_' . $post->ID ) ),
				esc_html__( 'Mark as AI Content', 'eu-ai-act-ready' )
			);
		}

		return $actions;
	}

	/**
	 * Handle AJAX toggle of AI status.
	 */
	public function euaiactready_ajax_toggle_ai_status() {
		// Check nonce.
		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$nonce   = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if (
			empty( $post_id ) ||
			empty( $nonce ) ||
			! wp_verify_nonce( $nonce, 'euaiactready_ai_toggle_' . (int) $post_id )
		) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid request.', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Permission denied.', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$action = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';

		if ( 'mark' === $action ) {
			update_post_meta( $post_id, '_euaiactready_ai_content', 'generated' );
			// Set timestamp if not already set (also backfills legacy posts without a date).
			$existing_date = get_post_meta( $post_id, '_euaiactready_ai_content_marked_date', true );
			if ( empty( $existing_date ) ) {
				$marked_datetime = current_datetime();
				update_post_meta( $post_id, '_euaiactready_ai_content_marked_date', $marked_datetime->getTimestamp() );
			}
			wp_send_json_success(
				array(
					'message'    => esc_html__( 'Marked as AI content.', 'eu-ai-act-ready' ),
					'new_status' => 'generated',
				)
			);
		} elseif ( 'unmark' === $action ) {
			update_post_meta( $post_id, '_euaiactready_ai_content', 'none' );
			wp_send_json_success(
				array(
					'message'    => esc_html__( 'Unmarked as AI content.', 'eu-ai-act-ready' ),
					'new_status' => 'none',
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid action.', 'eu-ai-act-ready' ),
				)
			);
		}
	}
}
