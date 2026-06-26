<?php
/**
 * EU AI Act Ready - AI Tools Admin Page
 *
 * @package EUAIACTREADY
 */


if ( ! defined( 'WPINC' ) ) {
	die;
}

global $euaiactready_ai_tools_instance;

if ( ! $euaiactready_ai_tools_instance instanceof Euaiactready_AI_Tools ) {
	return;
}


$euaiactready_registry   = $euaiactready_ai_tools_instance->get_registry();
$euaiactready_detector   = $euaiactready_ai_tools_instance->get_detector();
$euaiactready_meta       = $euaiactready_registry->get_meta();
$euaiactready_detected   = $euaiactready_detector->get_detected();
$euaiactready_unknown    = $euaiactready_detector->get_unknown_plugins();
$euaiactready_manual     = $euaiactready_detector->get_manual_tools();
$euaiactready_visibility = get_option( Euaiactready_AI_Tools::OPTION_VISIBILITY, array() );
$euaiactready_notice_style = get_option( Euaiactready_AI_Tools::OPTION_NOTICE_STYLE, 'banner' );
$euaiactready_detected_at  = $euaiactready_detector->get_detected_at();

// Category labels.
$euaiactready_category_labels = array(
	'content'         => __( 'Content AI', 'eu-ai-act-ready' ),
	'seo'             => __( 'SEO AI', 'eu-ai-act-ready' ),
	'image'           => __( 'Image AI', 'eu-ai-act-ready' ),
	'chatbot'         => __( 'Chatbot', 'eu-ai-act-ready' ),
	'media'           => __( 'Video / Media AI', 'eu-ai-act-ready' ),
	'translation'     => __( 'Translation AI', 'eu-ai-act-ready' ),
	'search'          => __( 'AI Search', 'eu-ai-act-ready' ),
	'personalisation' => __( 'Personalisation AI', 'eu-ai-act-ready' ),
	'commerce'        => __( 'Commerce AI', 'eu-ai-act-ready' ),
	'security'        => __( 'Security AI', 'eu-ai-act-ready' ),
	'voice'           => __( 'Voice AI', 'eu-ai-act-ready' ),
	'other'           => __( 'Other', 'eu-ai-act-ready' ),
);

$euaiactready_notice_enabled = (bool) get_option( Euaiactready_AI_Tools::OPTION_ENABLED, true );

// Site Fetch: tools detected by scanning live page HTML (JS signature matching).
$euaiactready_site_fetcher        = new Euaiactready_AI_Tools_Site_Fetch();
$euaiactready_site_fetch_results  = $euaiactready_site_fetcher->get_cached();
$euaiactready_site_fetch_results  = is_array( $euaiactready_site_fetch_results ) ? $euaiactready_site_fetch_results : array();
$euaiactready_site_fetch_stored   = get_option( Euaiactready_AI_Tools_Site_Fetch::OPTION_KEY, array() );
$euaiactready_site_fetch_at       = ! empty( $euaiactready_site_fetch_stored['fetched_at'] ) ? (int) $euaiactready_site_fetch_stored['fetched_at'] : null;

// Merge site-fetch detections into the detected list (normalise field names).
$euaiactready_detected_ids = array_column( $euaiactready_detected, 'id' );
foreach ( $euaiactready_site_fetch_results as $euaiactready_sf_tool ) {
	if ( ! in_array( $euaiactready_sf_tool['id'], $euaiactready_detected_ids, true ) ) {
		// Normalise 'article' key to 'eu_ai_act_article' used by the rest of the page.
		if ( ! isset( $euaiactready_sf_tool['eu_ai_act_article'] ) && isset( $euaiactready_sf_tool['article'] ) ) {
			$euaiactready_sf_tool['eu_ai_act_article'] = $euaiactready_sf_tool['article'];
		}
		$euaiactready_detected[] = $euaiactready_sf_tool;
	}
}

// Possibly AI heuristic: active plugins that match AI keywords but aren't in the registry.
$euaiactready_possibly_ai       = $euaiactready_detector->get_possibly_ai_plugins();
$euaiactready_possibly_ai_slugs = array_flip( array_column( $euaiactready_possibly_ai, 'slug' ) );
?>

<div class="wrap euaiactready-wrap euaiactready-ai-tools-page">

	<h1 class="euaiactready-page-title">
		<?php echo wp_kses( EUAIACTREADY::euaiactready_get_ai_icon( 22, '#667eea' ), EUAIACTREADY::euaiactready_get_svg_allowed_html() ); ?>
		<?php esc_html_e( 'AI Systems', 'eu-ai-act-ready' ); ?>
	</h1>

	<!-- Registry status bar -->
	<div class="euaiactready-registry-status" style="flex-direction:column;align-items:flex-start;gap:10px">
		<div class="euaiactready-registry-status-info">
			<span class="euaiactready-registry-label">
				<?php esc_html_e( 'AI Systems Registry', 'eu-ai-act-ready' ); ?>
			</span>
			<span class="euaiactready-registry-sep" aria-hidden="true">&middot;</span>
			<span class="euaiactready-registry-count">
				<?php
				printf(
					/* translators: %d is the number of tools in the registry */
					esc_html__( '%d known AI systems', 'eu-ai-act-ready' ),
					(int) $euaiactready_meta['total']
				);
				?>
			</span>
			<span class="euaiactready-registry-sep" aria-hidden="true">&middot;</span>
			<span class="euaiactready-registry-status-chip euaiactready-status-<?php echo esc_attr( $euaiactready_meta['status'] ); ?>">
				<?php
				switch ( $euaiactready_meta['status'] ) {
					case 'ok':
						esc_html_e( 'Up to date', 'eu-ai-act-ready' );
						break;
					case 'error':
						esc_html_e( 'Sync failed', 'eu-ai-act-ready' );
						break;
					default:
						esc_html_e( 'Never synced', 'eu-ai-act-ready' );
				}
				?>
			</span>
			<?php if ( $euaiactready_meta['fetched_at'] ) : ?>
				<span class="euaiactready-registry-sep" aria-hidden="true">&middot;</span>
				<span class="euaiactready-registry-fetched-at">
					<?php
					printf(
						/* translators: %s is a human-readable time ago string */
						esc_html__( 'Last synced %s ago', 'eu-ai-act-ready' ),
						esc_html( human_time_diff( (int) $euaiactready_meta['fetched_at'], time() ) )
					);
					?>
				</span>
			<?php endif; ?>
		</div>
		<button id="euaiactready-refresh-registry" class="button button-secondary euaiactready-refresh-btn">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:middle;margin-right:5px;margin-top:-2px;flex-shrink:0;">
				<polyline points="23 4 23 10 17 10"></polyline>
				<path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
			</svg>
			<?php esc_html_e( 'Sync AI Systems', 'eu-ai-act-ready' ); ?>
		</button>
	</div>

	<div id="euaiactready-refresh-message" class="notice" style="display:none;" role="status"></div>

	<!-- Notice style setting -->
	<div class="euaiactready-card euaiactready-notice-settings-card">
		<h2><?php esc_html_e( 'Frontend Disclosure Notice', 'eu-ai-act-ready' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'When enabled, a disclosure notice listing your AI tools is added to the footer of every page on your site. Only tools with "Show in notice" toggled on below will appear in it.', 'eu-ai-act-ready' ); ?>
		</p>
		<form method="post" action="options.php" id="euaiactready-notice-style-form">
			<?php settings_fields( 'euaiactready_ai_tools_notice' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Enable notice', 'eu-ai-act-ready' ); ?>
					</th>
					<td>
						<label class="euaiactready-toggle" aria-label="<?php esc_attr_e( 'Show AI Tools disclosure notice on the frontend', 'eu-ai-act-ready' ); ?>">
							<input
								type="checkbox"
								name="euaiactready_ai_tools_notice_enabled"
								value="1"
								<?php checked( $euaiactready_notice_enabled ); ?>
							>
							<span class="euaiactready-toggle-slider"></span>
						</label>
						<p class="description"><?php esc_html_e( 'Show AI Tools disclosure notice on the frontend', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="euaiactready_ai_tools_notice_style"><?php esc_html_e( 'Notice style', 'eu-ai-act-ready' ); ?></label>
					</th>
					<td>
						<select name="euaiactready_ai_tools_notice_style" id="euaiactready_ai_tools_notice_style">
							<option value="floating" <?php selected( $euaiactready_notice_style, 'floating' ); ?>><?php esc_html_e( 'Floating badge — bottom-right corner (Recommended)', 'eu-ai-act-ready' ); ?></option>
							<option value="banner"   <?php selected( $euaiactready_notice_style, 'banner' ); ?>><?php esc_html_e( 'Banner — full-width bar at bottom', 'eu-ai-act-ready' ); ?></option>
							<option value="badge"    <?php selected( $euaiactready_notice_style, 'badge' ); ?>><?php esc_html_e( 'Badge — compact inline chip', 'eu-ai-act-ready' ); ?></option>
							<option value="inline"   <?php selected( $euaiactready_notice_style, 'inline' ); ?>><?php esc_html_e( 'Inline — text block in content flow', 'eu-ai-act-ready' ); ?></option>
							<option value="modal"    <?php selected( $euaiactready_notice_style, 'modal' ); ?>><?php esc_html_e( 'Modal — click a button to open', 'eu-ai-act-ready' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Matches the styles used by Content and Chatbot transparency notices.', 'eu-ai-act-ready' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Settings', 'eu-ai-act-ready' ), 'primary', 'submit', false ); ?>
		</form>
	</div>

	<!-- Detected tools -->
	<div class="euaiactready-card">
		<h2>
			<?php
			printf(
				/* translators: %d is the number of detected tools */
				esc_html__( 'Detected AI Tools (%d)', 'eu-ai-act-ready' ),
				count( $euaiactready_detected )
			);
			?>
			<?php if ( $euaiactready_detected_at ) : ?>
				<span class="euaiactready-scan-time">
					&mdash; <?php
					printf(
						/* translators: %s is a human-readable time ago string */
						esc_html__( 'last scanned %s ago', 'eu-ai-act-ready' ),
						esc_html( human_time_diff( $euaiactready_detected_at, time() ) )
					);
					?>
				</span>
			<?php endif; ?>
		</h2>
		<?php if ( $euaiactready_site_fetch_at ) : ?>
		<p class="description" style="margin-bottom:10px">
			<?php
			printf(
				/* translators: %s is a human-readable time ago string */
				esc_html__( 'Live page scan (JS signatures): last run %s ago. Tools detected this way are shown with a "Script" source tag.', 'eu-ai-act-ready' ),
				esc_html( human_time_diff( $euaiactready_site_fetch_at, time() ) )
			);
			?>
		</p>
		<?php endif; ?>

		<?php if ( empty( $euaiactready_detected ) ) : ?>
			<div class="euaiactready-empty-state">
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
				<p>
					<?php esc_html_e( 'No AI tools detected on this site yet.', 'eu-ai-act-ready' ); ?>
					<?php if ( 'never' === $euaiactready_meta['status'] ) : ?>
						<?php esc_html_e( 'Click "Refresh Registry" above to download the tool list and run the first scan.', 'eu-ai-act-ready' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'This means none of your installed plugins match the registry. You can manually declare tools below.', 'eu-ai-act-ready' ); ?>
					<?php endif; ?>
				</p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped euaiactready-tools-table" id="euaiactready-detected-tools-table">
				<thead>
					<tr>
						<th scope="col" class="column-name"><?php esc_html_e( 'Tool', 'eu-ai-act-ready' ); ?></th>
						<th scope="col" class="column-category"><?php esc_html_e( 'Category', 'eu-ai-act-ready' ); ?></th>
						<th scope="col" class="column-article"><?php esc_html_e( 'EU AI Act', 'eu-ai-act-ready' ); ?></th>
						<th scope="col" class="column-source"><?php esc_html_e( 'Source', 'eu-ai-act-ready' ); ?></th>
						<th scope="col" class="column-visibility"><?php esc_html_e( 'Show in notice', 'eu-ai-act-ready' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $euaiactready_detected as $euaiactready_tool ) : ?>
					<?php
					$euaiactready_tool_id    = $euaiactready_tool['id'];
					$euaiactready_is_visible = isset( $euaiactready_visibility[ $euaiactready_tool_id ] ) && $euaiactready_visibility[ $euaiactready_tool_id ];
					$euaiactready_is_manual  = ! empty( $euaiactready_tool['is_manual'] );
					$euaiactready_cat_label  = $euaiactready_category_labels[ $euaiactready_tool['category'] ] ?? ucfirst( $euaiactready_tool['category'] );
					$euaiactready_article    = $euaiactready_tool['eu_ai_act_article'] ?? '';
					?>
					<tr data-tool-id="<?php echo esc_attr( $euaiactready_tool_id ); ?>">
						<td class="column-name">
							<strong>
								<?php if ( ! empty( $euaiactready_tool['url'] ) ) : ?>
									<a href="<?php echo esc_url( $euaiactready_tool['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $euaiactready_tool['name'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $euaiactready_tool['name'] ); ?>
								<?php endif; ?>
							</strong>
							<?php if ( ! empty( $euaiactready_tool['description'] ) ) : ?>
								<span class="euaiactready-tool-description"><?php echo esc_html( $euaiactready_tool['description'] ); ?></span>
							<?php endif; ?>
						</td>
						<td class="column-category">
							<span class="euaiactready-category-badge euaiactready-cat-<?php echo esc_attr( $euaiactready_tool['category'] ); ?>">
								<?php echo esc_html( $euaiactready_cat_label ); ?>
							</span>
						</td>
						<td class="column-article">
							<?php if ( $euaiactready_article && 'None' !== $euaiactready_article ) : ?>
								<span class="euaiactready-article-badge"><?php echo esc_html( $euaiactready_article ); ?></span>
							<?php else : ?>
								<span class="euaiactready-no-obligation"><?php esc_html_e( 'No disclosure required', 'eu-ai-act-ready' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="column-source">
							<?php if ( $euaiactready_is_manual ) : ?>
								<span class="euaiactready-source-manual"><?php esc_html_e( 'Manual', 'eu-ai-act-ready' ); ?></span>
								<button class="button-link euaiactready-remove-manual" data-tool-id="<?php echo esc_attr( $euaiactready_tool_id ); ?>" aria-label="<?php esc_attr_e( 'Remove manual declaration', 'eu-ai-act-ready' ); ?>">
									<?php esc_html_e( 'Remove', 'eu-ai-act-ready' ); ?>
								</button>
							<?php elseif ( isset( $euaiactready_tool['source'] ) && 'script' === $euaiactready_tool['source'] ) : ?>
								<span class="euaiactready-source-script"><?php esc_html_e( 'Script', 'eu-ai-act-ready' ); ?></span>
							<?php else : ?>
								<span class="euaiactready-source-registry"><?php esc_html_e( 'Registry', 'eu-ai-act-ready' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="column-visibility">
							<?php
						/* translators: %s: AI tool name */
						$euaiactready_toggle_label = sprintf( __( 'Show %s in frontend notice', 'eu-ai-act-ready' ), $euaiactready_tool['name'] );
						?>
						<label class="euaiactready-toggle" aria-label="<?php echo esc_attr( $euaiactready_toggle_label ); ?>">
								<input
									type="checkbox"
									class="euaiactready-visibility-toggle"
									data-tool-id="<?php echo esc_attr( $euaiactready_tool_id ); ?>"
									<?php checked( $euaiactready_is_visible ); ?>
									<?php if ( $euaiactready_article && 'None' === $euaiactready_article ) : ?>
										title="<?php esc_attr_e( 'This tool has no Article 50 disclosure requirement but can still be disclosed voluntarily.', 'eu-ai-act-ready' ); ?>"
									<?php endif; ?>
								>
								<span class="euaiactready-toggle-slider"></span>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- Manual declarations -->
	<div class="euaiactready-card" id="euaiactready-manual-section">
		<h2><?php esc_html_e( 'Manually Declare an AI Tool', 'eu-ai-act-ready' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'The plugins listed below are active on your site but were not recognised by the registry. Most of them are not AI tools - they simply have not been reviewed yet. Do not mark a plugin unless you know it actually uses AI.', 'eu-ai-act-ready' ); ?>
		</p>
		<?php if ( ! empty( $euaiactready_possibly_ai ) ) : ?>
		<div class="notice notice-warning inline" style="margin:10px 0 16px;padding:10px 14px;">
			<p style="margin:0">
				<span class="dashicons dashicons-warning" style="color:#d63638;margin-right:4px" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Possible AI tools detected:', 'eu-ai-act-ready' ); ?></strong>
				<?php esc_html_e( 'Some plugins below have names or descriptions that suggest they may use AI (highlighted with a badge). Review them carefully and mark any that do.', 'eu-ai-act-ready' ); ?>
			</p>
		</div>
		<?php endif; ?>
		<div class="notice notice-info inline" style="margin:10px 0 16px;padding:10px 14px;">
			<p style="margin:0">
				<strong><?php esc_html_e( 'How to use this section:', 'eu-ai-act-ready' ); ?></strong>
				<?php esc_html_e( 'If you recognise a plugin that uses AI, choose the correct category from its dropdown, then click "Mark as AI". Leave the rest - they are just plugins not yet in our database.', 'eu-ai-act-ready' ); ?>
			</p>
		</div>

		<?php if ( empty( $euaiactready_unknown ) ) : ?>
			<p><em><?php esc_html_e( 'All active plugins are already in the registry. No unknown plugins to declare.', 'eu-ai-act-ready' ); ?></em></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped euaiactready-unknown-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Plugin', 'eu-ai-act-ready' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Category', 'eu-ai-act-ready' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Action', 'eu-ai-act-ready' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $euaiactready_unknown as $euaiactready_plugin ) : ?>
					<?php $euaiactready_is_possibly_ai = isset( $euaiactready_possibly_ai_slugs[ $euaiactready_plugin['slug'] ] ); ?>
					<tr data-slug="<?php echo esc_attr( $euaiactready_plugin['slug'] ); ?>"<?php echo $euaiactready_is_possibly_ai ? ' class="euaiactready-row-possibly-ai"' : ''; ?>>
						<td>
							<strong><?php echo esc_html( $euaiactready_plugin['name'] ); ?></strong>
							<?php if ( $euaiactready_is_possibly_ai ) : ?>
							<span class="euaiactready-possibly-ai-badge" title="<?php esc_attr_e( 'Name or description suggests this plugin may use AI', 'eu-ai-act-ready' ); ?>">
								<?php esc_html_e( 'Possibly AI', 'eu-ai-act-ready' ); ?>
							</span>
							<?php endif; ?>
						</td>
						<td>
							<?php $euaiactready_suggested = Euaiactready_Category_Suggester::suggest( $euaiactready_plugin['slug'], $euaiactready_plugin['name'] ); ?>
							<select class="euaiactready-manual-category" aria-label="<?php esc_attr_e( 'Category', 'eu-ai-act-ready' ); ?>">
								<?php foreach ( $euaiactready_category_labels as $euaiactready_val => $euaiactready_label ) : ?>
									<option value="<?php echo esc_attr( $euaiactready_val ); ?>" <?php selected( $euaiactready_val, $euaiactready_suggested ); ?>><?php echo esc_html( $euaiactready_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td>
							<button
								class="button button-secondary euaiactready-declare-ai"
								data-slug="<?php echo esc_attr( $euaiactready_plugin['slug'] ); ?>"
								data-name="<?php echo esc_attr( $euaiactready_plugin['name'] ); ?>"
							>
								<?php esc_html_e( 'Mark as AI', 'eu-ai-act-ready' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

</div><!-- .euaiactready-wrap -->
