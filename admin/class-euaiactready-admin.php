<?php
/**
 * EU AI Act Ready - Admin area bootstrap
 *
 * @package EUAIACTREADY
 */

/**
 * The admin-specific functionality of the plugin.
 */
class EUAIACTREADY_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The scan limit for content scanning.
	 *
	 * @var int
	 */
	private $scan_limit;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_slug Unique plugin identifier.
	 * @param string $version     Current plugin version.
	 * @param int    $scan_limit  Limit for bulk scanning (-1 for no limit).
	 */
	public function __construct( $plugin_slug, $version, $scan_limit ) {
		$this->plugin_slug = $plugin_slug;
		$this->version     = $version;
		$this->scan_limit  = $scan_limit;
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function euaiactready_enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_slug,
			EUAIACTREADY_PLUGIN_URL . 'build/admin/admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function euaiactready_enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_slug,
			EUAIACTREADY_PLUGIN_URL . 'build/admin/admin.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		// Localize script for AJAX.
		$euaiactready_buffer_count = null;
		$current_page              = '';
		if ( ! empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check.
			$current_page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check.
			if ( 0 === strpos( $current_page, $this->plugin_slug ) ) {
				$euaiactready_buffer_count = EUAIACTREADY_Data_Store::get_bulk_scan_buffer_count();
			}
		}

		// Enqueue AI Systems page JS only on that page.
		if ( $this->plugin_slug . '-ai-systems' === $current_page ) {
			wp_enqueue_script(
				$this->plugin_slug . '-ai-systems',
				EUAIACTREADY_PLUGIN_URL . 'build/admin/ai-tools.js',
				array( 'jquery' ),
				$this->version,
				true
			);
			wp_localize_script(
				$this->plugin_slug . '-ai-systems',
				'euaiactreadyAiTools',
				array(
					'i18n' => array(
						'errorOccurred' => __( 'An error occurred. Please try again.', 'eu-ai-act-ready' ),
						'saving'        => __( 'Saving...', 'eu-ai-act-ready' ),
						'markAsAi'      => __( 'Mark as AI', 'eu-ai-act-ready' ),
						'confirmRemove' => __( 'Remove this manual AI tool declaration?', 'eu-ai-act-ready' ),
					),
				)
			);
		}

		wp_localize_script(
			$this->plugin_slug,
			'euaiactreadyAjax',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'euaiactready_nonce' ),
				'pluginSlug'          => $this->plugin_slug,
				'dashboardUrl'        => admin_url( 'admin.php?page=' . $this->plugin_slug ),
				'bulkScanBufferCount' => $euaiactready_buffer_count,
				'i18n'                => array(
					'confirmUnmark'     => __( 'Are you sure you want to unmark this content as AI-generated?', 'eu-ai-act-ready' ),
					'unmarking'         => __( 'Unmarking...', 'eu-ai-act-ready' ),
					'unmark'            => __( 'Unmark', 'eu-ai-act-ready' ),
					'unmarkFailed'      => __( 'Failed to unmark content.', 'eu-ai-act-ready' ),
					'errorOccurred'     => __( 'An error occurred. Please try again.', 'eu-ai-act-ready' ),
					'scanResumeTitle'   => __( 'Scan Interrupted', 'eu-ai-act-ready' ),
					'scanResumeBody'    => __( 'We found a partially completed media scan. What would you like to do?', 'eu-ai-act-ready' ),
					'scanResumeSave'    => __( 'Save Results', 'eu-ai-act-ready' ),
					'scanResumeRestart' => __( 'Start New Scan', 'eu-ai-act-ready' ),
					'scanResumeDismiss' => __( 'Do Nothing', 'eu-ai-act-ready' ),
					'scanSaving'        => __( 'Saving results...', 'eu-ai-act-ready' ),
					'scanDiscarding'    => __( 'Discarding results...', 'eu-ai-act-ready' ),
					'scanSaved'         => __( 'Saved scan results.', 'eu-ai-act-ready' ),
					'scanCleared'       => __( 'Previous scan results cleared.', 'eu-ai-act-ready' ),
				),
			)
		);
	}
	/**
	 * Add menu item to WordPress admin.
	 */
	public function euaiactready_add_plugin_admin_menu() {
		add_menu_page(
			__( 'EU AI Act Ready', 'eu-ai-act-ready' ),
			__( 'EU AI Act Ready', 'eu-ai-act-ready' ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'euaiactready_display_plugin_admin_page' ),
			'dashicons-superhero',
			80
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'Dashboard', 'eu-ai-act-ready' ),
			__( 'Dashboard', 'eu-ai-act-ready' ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'euaiactready_display_plugin_admin_page' )
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'Assessment', 'eu-ai-act-ready' ),
			__( 'Assessment', 'eu-ai-act-ready' ),
			'manage_options',
			$this->plugin_slug . '-assessment',
			array( $this, 'euaiactready_display_assessment_page' )
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'AI Systems', 'eu-ai-act-ready' ),
			__( 'AI Systems', 'eu-ai-act-ready' ),
			'manage_options',
			$this->plugin_slug . '-ai-systems',
			array( $this, 'euaiactready_display_ai_tools_page' )
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'AI Content', 'eu-ai-act-ready' ),
			__( 'AI Content', 'eu-ai-act-ready' ),
			'manage_options',
			$this->plugin_slug . '-content',
			array( $this, 'euaiactready_display_ai_content_page' )
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'AI Images', 'eu-ai-act-ready' ),
			__( 'AI Images', 'eu-ai-act-ready' ),
			'manage_options',
			$this->plugin_slug . '-images',
			array( $this, 'euaiactready_display_ai_images_page' )
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'AI Literacy', 'eu-ai-act-ready' ),
			__( 'AI Literacy', 'eu-ai-act-ready' ),
			'manage_options',
			$this->plugin_slug . '-literacy',
			array( $this, 'euaiactready_display_literacy_page' )
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'Settings', 'eu-ai-act-ready' ),
			__( 'Settings', 'eu-ai-act-ready' ),
			'manage_options',
			$this->plugin_slug . '-settings',
			array( $this, 'euaiactready_display_settings_page' )
		);

	}

	/**
	 * Add settings link to plugin action links.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function euaiactready_add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-settings' ) ) . '">' . esc_html__( 'Settings', 'eu-ai-act-ready' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render the admin page.
	 */
	public function euaiactready_display_plugin_admin_page() {
		include_once EUAIACTREADY_PLUGIN_DIR . 'admin/partials/dashboard.php';
	}

	/**
	 * Render the ai content page.
	 */
	public function euaiactready_display_ai_content_page() {
		include_once EUAIACTREADY_PLUGIN_DIR . 'admin/partials/ai-content.php';
	}

	/**
	 * Render the ai images page.
	 */
	public function euaiactready_display_ai_images_page() {
		include_once EUAIACTREADY_PLUGIN_DIR . 'admin/partials/ai-images.php';
	}

	/**
	 * Render the AI Tools Registry page.
	 */
	public function euaiactready_display_ai_tools_page() {
		include_once EUAIACTREADY_PLUGIN_DIR . 'admin/partials/euaiactready-admin-ai-tools.php';
	}

	/**
	 * Render the settings page.
	 */
	public function euaiactready_display_settings_page() {
		include_once EUAIACTREADY_PLUGIN_DIR . 'admin/partials/settings.php';
	}

	/**
	 * Render the assessment wizard page.
	 */
	public function euaiactready_display_assessment_page() {
		include_once EUAIACTREADY_PLUGIN_DIR . 'admin/partials/assessment.php';
	}

	/**
	 * Render the AI literacy page.
	 */
	public function euaiactready_display_literacy_page() {
		include_once EUAIACTREADY_PLUGIN_DIR . 'admin/partials/ai-literacy.php';
	}

	/**
	 * Handle the print report request (admin_post action).
	 * Outputs a self-contained print-friendly HTML page and exits.
	 */
	public function euaiactready_handle_print_report() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'eu-ai-act-ready' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'euaiactready_print_report' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'eu-ai-act-ready' ) );
		}

		include EUAIACTREADY_PLUGIN_DIR . 'admin/partials/report.php';
		exit;
	}

	/**
	 * Register the WP Dashboard widget.
	 */
	public function euaiactready_register_dashboard_widget() {
		wp_add_dashboard_widget(
			'euaiactready_dashboard_widget',
			__( 'EU AI Act Readiness', 'eu-ai-act-ready' ),
			array( $this, 'euaiactready_render_dashboard_widget' )
		);
	}

	/**
	 * Render the WP Dashboard widget content.
	 */
	public function euaiactready_render_dashboard_widget() {
		$readiness     = new Euaiactready_Readiness();
		$score         = $readiness->calculate();
		$traffic_light = Euaiactready_Readiness::get_traffic_light( $score );
		$light_label   = Euaiactready_Readiness::get_traffic_light_label( $traffic_light );

		$detector    = new Euaiactready_AI_Tools_Detector( new Euaiactready_AI_Tools_Registry() );
		$tool_count  = count( $detector->get_detected() );

		$content_count = 0;
		$content_query = new WP_Query( array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_euaiactready_ai_content',
					'value'   => array( '1', 'assisted', 'generated', 'generated_reviewed' ),
					'compare' => 'IN',
				),
			),
		) );
		$content_count = $content_query->found_posts;
		?>
		<div class="euaiactready-widget">
			<div class="euaiactready-widget-score">
				<div class="euaiactready-widget-circle euaiactready-traffic-<?php echo esc_attr( $traffic_light ); ?>">
					<span class="euaiactready-widget-number"><?php echo esc_html( $score ); ?></span>
					<span class="euaiactready-widget-denom">/100</span>
				</div>
				<div class="euaiactready-widget-info">
					<strong><?php echo esc_html( $light_label ); ?></strong><br>
					<span><?php esc_html_e( 'Compliance Readiness', 'eu-ai-act-ready' ); ?></span>
				</div>
			</div>
			<div class="euaiactready-widget-stats">
				<div class="euaiactready-widget-stat">
					<span class="euaiactready-widget-stat-num"><?php echo esc_html( $tool_count ); ?></span>
					<span class="euaiactready-widget-stat-lbl"><?php esc_html_e( 'AI Systems', 'eu-ai-act-ready' ); ?></span>
				</div>
				<div class="euaiactready-widget-stat">
					<span class="euaiactready-widget-stat-num"><?php echo esc_html( $content_count ); ?></span>
					<span class="euaiactready-widget-stat-lbl"><?php esc_html_e( 'AI Content', 'eu-ai-act-ready' ); ?></span>
				</div>
			</div>
			<div class="euaiactready-widget-links">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready' ) ); ?>"><?php esc_html_e( 'Dashboard', 'eu-ai-act-ready' ); ?></a>
				&middot;
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-ai-systems' ) ); ?>"><?php esc_html_e( 'AI Systems', 'eu-ai-act-ready' ); ?></a>
				&middot;
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=eu-ai-act-ready-assessment' ) ); ?>"><?php esc_html_e( 'Assessment', 'eu-ai-act-ready' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler to check buffered bulk scan count.
	 */
	public function euaiactready_ajax_check_bulk_scan_buffer() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$count = EUAIACTREADY_Data_Store::get_bulk_scan_buffer_count();
		wp_send_json_success(
			array(
				'count' => (int) $count,
			)
		);
	}

	/**
	 * AJAX handler to flush buffered bulk scan results.
	 */
	public function euaiactready_ajax_flush_bulk_scan_buffer() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		// Flush the transient buffer.
		$flushed_count = EUAIACTREADY_Data_Store::flush_bulk_media_scan_buffer();

		// Also perform a full sync to catch any orphans (items with meta but no DB record).
		$synced_count = EUAIACTREADY_Data_Store::sync_all_ai_assets();

		wp_send_json_success(
			array(
				'count'  => (int) $flushed_count,
				'synced' => (int) $synced_count,
			)
		);
	}

	/**
	 * AJAX handler to clear buffered bulk scan results.
	 */
	public function euaiactready_ajax_clear_bulk_scan_buffer() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		EUAIACTREADY_Data_Store::clear_bulk_scan_buffer();
		wp_send_json_success(
			array(
				'cleared' => true,
			)
		);
	}

	/**
	 * AJAX handler for chunked scanning with progress updates.
	 * Scans images that don't have the _euaiactready_ai_generated meta set.
	 */
	public function euaiactready_ajax_chunk_scan() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$chunk = 0;
		if ( isset( $_POST['chunk'] ) ) {
			$chunk = max( 0, absint( wp_unslash( $_POST['chunk'] ) ) );
		}

		$chunk_size = EUAIACTREADY_MEDIA_BULK_SCAN_BATCH_SIZE;

		try {
			EUAIACTREADY_Data_Store::begin_bulk_scan();
			$media_transparency = new EUAIACTREADY_Media_Transparency();

			// On first chunk, get ALL unscanned image IDs and store them.
			if ( 0 === $chunk ) {
				// Query for all images without _euaiactready_ai_generated meta AND not manually unmarked.
				$args = array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'post_status'    => 'inherit',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary to exclude images already processed or manually unmarked.
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => '_euaiactready_ai_generated',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_euaiactready_ai_manually_unmarked',
							'compare' => 'NOT EXISTS',
						),
					),
				);

				$query         = new WP_Query( $args );
				$all_image_ids = $query->posts;
				wp_reset_postdata();

				$total_items = count( $all_image_ids );

				// Apply scan limit if set (not -1).
				if ( $this->scan_limit > 0 && $total_items > $this->scan_limit ) {
					$all_image_ids = array_slice( $all_image_ids, 0, $this->scan_limit );
					$total_items   = $this->scan_limit;
				}

				// Store the list of IDs and total for subsequent chunks (5 hours expiration).
				set_transient( 'euaiactready_scan_ids', $all_image_ids, 5 * HOUR_IN_SECONDS );
				set_transient( 'euaiactready_scan_total', $total_items, 5 * HOUR_IN_SECONDS );
				set_transient( 'euaiactready_scan_found_count', 0, 5 * HOUR_IN_SECONDS );
			} else {
				// Get stored IDs and total from transient.
				$all_image_ids = get_transient( 'euaiactready_scan_ids' );
				$total_items   = get_transient( 'euaiactready_scan_total' );

				if ( ! $all_image_ids || ! $total_items ) {
					wp_send_json_error(
						array(
							'message' => esc_html__( 'Scan session expired. Please start again.', 'eu-ai-act-ready' ),
						)
					);
					return;
				}
			}

			// Calculate offset.
			$offset = $chunk * $chunk_size;

			// Check if we're done.
			if ( $offset >= $total_items || 0 === $total_items ) {
				EUAIACTREADY_Data_Store::end_bulk_scan();
				EUAIACTREADY_Data_Store::flush_bulk_media_scan_buffer();
				// Get the count found in THIS scan session.
				$found_in_this_scan = get_transient( 'euaiactready_scan_found_count' );
				if ( false === $found_in_this_scan ) {
					$found_in_this_scan = 0;
				}

				delete_transient( 'euaiactready_scan_total' );
				delete_transient( 'euaiactready_scan_found_count' );
				delete_transient( 'euaiactready_scan_ids' );

				wp_send_json_success(
					array(
						'done'          => true,
						'message'       => esc_html__( 'Scan complete!', 'eu-ai-act-ready' ),
						'total_found'   => $found_in_this_scan,
						'images'        => $found_in_this_scan,
						'total_scanned' => $total_items,
						'progress'      => 100,
						'processed'     => $total_items,
					)
				);
				return;
			}

			// Get the IDs for this chunk.
			$chunk_ids      = array_slice( $all_image_ids, $offset, $chunk_size );
			$chunk_ai_count = 0;

			// Process each image in this chunk.
			foreach ( $chunk_ids as $attachment_id ) {
				// Run AI detection on this image.
				$detection = $media_transparency->euaiactready_get_ai_detection_info( $attachment_id, true, 'bulk_scan' );

				// The euaiactready_get_ai_detection_info method will automatically set the _euaiactready_ai_generated meta.
				// Check if it was marked as AI.
				if ( $detection && isset( $detection['is_ai'] ) && $detection['is_ai'] ) {
					++$chunk_ai_count;
				}
			}

			// Update the running count for this scan session.
			$current_found = get_transient( 'euaiactready_scan_found_count' );
			if ( false === $current_found ) {
				$current_found = 0;
			}

			$current_found += $chunk_ai_count;
			set_transient( 'euaiactready_scan_found_count', $current_found, 5 * HOUR_IN_SECONDS );

			// Calculate progress.
			$processed = min( $offset + $chunk_size, $total_items );
			$progress  = ( 0 === $total_items ) ? 0 : round( ( $processed / $total_items ) * 100 );

			wp_send_json_success(
				array(
					'done'           => false,
					'progress'       => $progress,
					'processed'      => $processed,
					'total'          => $total_items,
					'total_scanned'  => $total_items,
					'chunk'          => $chunk + 1,
					'found_in_chunk' => $chunk_ai_count,
					'total_found'    => $current_found,
					'images'         => $current_found,
					'message'        => sprintf(
						/* translators: 1: Number of processed images, 2: Total images, 3: Percent complete. */
						esc_html__( 'Processing... %1$d/%2$d images (%3$d%%)', 'eu-ai-act-ready' ),
						$processed,
						$total_items,
						$progress
					),
				)
			);
			EUAIACTREADY_Data_Store::end_bulk_scan();

		} catch ( Exception $e ) {
			EUAIACTREADY_Data_Store::end_bulk_scan();
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s is the scan error message. */
						esc_html__( 'Scan error: %s', 'eu-ai-act-ready' ),
						wp_strip_all_tags( $e->getMessage() )
					),
				)
			);
		}
	}


	/**
	 * AJAX handler to unmark content as AI-generated.
	 */
	public function euaiactready_ajax_unmark_content() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( 0 === $post_id ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid post ID', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		// Verify user can edit this specific post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to edit this post', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		// Update the meta to unmark it (use canonical 'none', not legacy '0').
		$result = update_post_meta( $post_id, '_euaiactready_ai_content', 'none' );

		if ( false !== $result ) {
			wp_send_json_success(
				array(
					'message' => esc_html__( 'Content unmarked successfully', 'eu-ai-act-ready' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to unmark content', 'eu-ai-act-ready' ),
				)
			);
		}
	}

	/**
	 * AJAX handler to unmark image as AI-generated.
	 */
	public function euaiactready_ajax_unmark_image() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;

		if ( 0 === $attachment_id ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid attachment ID', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		// Verify user can edit this specific attachment.
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to edit this attachment', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		// Update the meta to unmark it.
		update_post_meta( $attachment_id, '_euaiactready_ai_generated', '0' );
		update_post_meta( $attachment_id, '_euaiactready_ai_manually_unmarked', '1' );
		update_post_meta( $attachment_id, '_euaiactready_ai_marked_method', 'none' );
		delete_post_meta( $attachment_id, '_euaiactready_ai_detection_source' );

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Image unmarked successfully', 'eu-ai-act-ready' ),
			)
		);
	}

	/**
	 * AJAX handler to restore image for scanning (remove manually unmarked status).
	 */
	public function euaiactready_ajax_restore_image() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;

		if ( 0 === $attachment_id ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid attachment ID', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to edit this attachment', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		// Delete the manually unmarked meta so it can be scanned again.
		delete_post_meta( $attachment_id, '_euaiactready_ai_manually_unmarked' );
		// Also ensure _euaiactready_ai_generated is removed so search picks it up.
		delete_post_meta( $attachment_id, '_euaiactready_ai_generated' );

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Image restored to scan queue successfully', 'eu-ai-act-ready' ),
			)
		);
	}

	/**
	 * AJAX handler to manually mark image as AI-generated.
	 */
	public function euaiactready_ajax_mark_image_as_ai() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;

		if ( 0 === $attachment_id ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid attachment ID', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to edit this attachment', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		// Update meta to mark as AI.
		update_post_meta( $attachment_id, '_euaiactready_ai_generated', '1' );
		update_post_meta( $attachment_id, '_euaiactready_ai_marked_method', 'manual' );
		// Remove manual unmark flag if present.
		delete_post_meta( $attachment_id, '_euaiactready_ai_manually_unmarked' );

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Image marked as AI successfully', 'eu-ai-act-ready' ),
			)
		);
	}

	/**
	 * AJAX handler for bulk actions.
	 */
	public function euaiactready_ajax_bulk_action() {
		check_ajax_referer( 'euaiactready_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Unauthorized', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$action    = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : '';
		$ids       = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();

		if ( empty( $ids ) || empty( $action ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid request', 'eu-ai-act-ready' ),
				)
			);
			return;
		}

		$count = 0;

		foreach ( $ids as $id ) {
			if ( 'content' === $item_type ) {
				if ( ! current_user_can( 'edit_post', $id ) ) {
					continue;
				}

				if ( 'mark_ai' === $action ) {
					// Use canonical 'generated' (not legacy '1') for new marks from the dashboard.
					update_post_meta( $id, '_euaiactready_ai_content', 'generated' );
					update_post_meta( $id, '_euaiactready_ai_content_marked_date', current_datetime()->getTimestamp() );
					++$count;
				} elseif ( 'unmark_ai' === $action ) {
					// Use canonical 'none' (not legacy '0') and keep the date for audit trail.
					update_post_meta( $id, '_euaiactready_ai_content', 'none' );
					++$count;
				}
			} elseif ( 'image' === $item_type ) {
				if ( ! current_user_can( 'edit_post', $id ) ) {
					continue;
				}

				if ( 'mark_ai' === $action ) {
					update_post_meta( $id, '_euaiactready_ai_generated', '1' );
					update_post_meta( $id, '_euaiactready_ai_marked_method', 'manual' );
					// Remove manual unmark flag if present.
					delete_post_meta( $id, '_euaiactready_ai_manually_unmarked' );
					++$count;
				} elseif ( 'unmark_ai' === $action ) {
					// Start option 3 unmark logic.
					update_post_meta( $id, '_euaiactready_ai_manually_unmarked', '1' );
					update_post_meta( $id, '_euaiactready_ai_generated', '0' ); // Set to 0 so it doesn't show in detector.
					update_post_meta( $id, '_euaiactready_ai_marked_method', 'none' );
					delete_post_meta( $id, '_euaiactready_ai_detection_source' );
					++$count;
				}
			} elseif ( 'unmarked_image' === $item_type ) {
				if ( ! current_user_can( 'edit_post', $id ) ) {
					continue;
				}

				if ( 'restore_scan' === $action ) {
					delete_post_meta( $id, '_euaiactready_ai_manually_unmarked' );
					delete_post_meta( $id, '_euaiactready_ai_generated' );
					++$count;
				} elseif ( 'mark_ai' === $action ) {
					update_post_meta( $id, '_euaiactready_ai_generated', '1' );
					update_post_meta( $id, '_euaiactready_ai_marked_method', 'manual' );
					delete_post_meta( $id, '_euaiactready_ai_manually_unmarked' );
					++$count;
				}
			}
		}

		wp_send_json_success(
			array(
				/* translators: %d is the number of processed items. */
				'message' => sprintf( esc_html__( '%d items processed successfully.', 'eu-ai-act-ready' ), (int) $count ),
			)
		);
	}
}
