<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin functionality for AISee SEO plugin
 *
 * This file contains the admin trait that provides settings page functionality
 * with AJAX-based settings that auto-save without a save button.
 */

trait AISeeAdmin {

	/**
	 * Initialize admin functionality.
	 *
	 * Sets up admin menu, AJAX handlers, and scripts for the AISee SEO
	 * settings page with auto-save functionality.
	 *
	 * @since 2.3
	 */
	public function init_admin() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_ajax_aisee_save_setting', array( $this, 'ajax_save_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer_scripts' ) );
	}

	/**
	 * Output admin footer scripts for settings page.
	 *
	 * Adds JavaScript for auto-save functionality on the AISee SEO settings page.
	 * Only loads on the plugin's settings page to avoid unnecessary script loading.
	 * Handles debounced AJAX saving of form fields with visual feedback.
	 *
	 * @since 2.3
	 */
	function admin_footer_scripts() {
		if ( 'toplevel_page_aisee-seo-settings' !== get_current_screen()->id ) {
			return;
		}
		?>
		<script type="text/javascript">
            jQuery(document).ready(function ($) {
                'use strict';

                // Auto-save functionality for settings
                $('.aisee-auto-save').on('change input', function () {
                    var $field = $(this);
                    var fieldName = $field.attr('name');
                    var fieldValue = $field.val();
                    var $status = $('#aisee-save-status');

                    // Show saving status
                    $status.show().removeClass('saved error').addClass('saving');
                    $status.find('.saving').show();
                    $status.find('.saved, .error').hide();

                    // Debounce rapid changes
                    clearTimeout($field.data('timeout'));
                    $field.data('timeout', setTimeout(function () {
                        saveSettings(fieldName, fieldValue, $status);
                    }, 500));
                });

                /**
                * Save settings via AJAX
                */
                function saveSettings(fieldName, fieldValue, $status) {
                    $.ajax({
                        url: aiseeAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aisee_save_setting',
                            nonce: aiseeAdmin.nonce,
                            setting: fieldName,
                            value: fieldValue
                        },
                        success: function (response) {
                            if (response.success) {
                                $status.removeClass('saving').addClass('saved');
                                $status.find('.saving').hide();
                                $status.find('.saved').show();

                                setTimeout(function () {
                                    $status.fadeOut();
                                }, 2000);
                            } else {
                                showError($status, response.data.message || 'Unknown error occurred');
                            }
                        },
                        error: function (xhr, status, error) {
                            showError($status, 'Network error: ' + error);
                        }
                    });
                }

                /**
                * Show error message
                */
                function showError($status, message) {
                    $status.removeClass('saving').addClass('error');
                    $status.find('.saving').hide();
                    $status.find('.error').text(message).show();

                    setTimeout(function () {
                        $status.fadeOut();
                    }, 5000);
                }

                // Add visual feedback for input focus
                $('.aisee-auto-save').on('focus', function () {
                    $(this).addClass('aisee-focused');
                }).on('blur', function () {
                    $(this).removeClass('aisee-focused');
                });
            });
        </script>
		<?php
	}

	/**
	 * Add admin menu page for AISee SEO settings.
	 *
	 * Creates a top-level admin menu item for AISee SEO configuration.
	 * Uses the search dashicon and positions the menu at priority 30.
	 *
	 * @since 2.3
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'AISee SEO Settings', 'aisee' ),
			__( 'AISee SEO', 'aisee' ),
			'manage_options',
			'aisee-seo-settings',
			array( $this, 'admin_page' ),
			'dashicons-search',
			30
		);
	}

	/**
	 * Enqueue admin scripts and styles for settings page.
	 *
	 * Loads the plugin's admin stylesheet only on the AISee SEO settings page
	 * to ensure styles don't interfere with other admin pages.
	 *
	 * @since 2.3
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'toplevel_page_aisee-seo-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'aisee-admin',
			$this->uri . 'assets/admin-styles.css',
			array(),
			$this->plugin_data['Version']
		);
	}

	/**
	 * Display the admin settings page.
	 *
	 * Renders the AISee SEO settings form with Google Search Console
	 * connection settings and filtering options for impressions, position,
	 * CTR, and clicks. All fields use auto-save functionality.
	 *
	 * @since 2.3
	 */
	public function admin_page() {
		$settings = get_option( 'aiseeseo', $this->get_defaults() );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<form id="aisee-settings-form" method="post">
				<?php wp_nonce_field( 'aisee_admin_nonce', 'aisee_nonce' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="gsc_connection"><?php _e( 'Google Search Console Connection', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="text" 
								id="gsc_connection" 
								name="gsc" 
								value="<?php echo esc_attr( isset( $settings['gsc'] ) ? $settings['gsc'] : '' ); ?>" 
								class="regular-text aisee-auto-save"
								placeholder="<?php _e( 'Enter your GSC credentials', 'aisee' ); ?>"
							/>
							<p class="description">
								<?php _e( 'Configure your Google Search Console connection details.', 'aisee' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gsc_filter_impressions_min"><?php _e( 'Minimum Impressions', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="gsc_filter_impressions_min" 
								name="gsc_filter[impressions][min]" 
								value="<?php echo esc_attr( $settings['gsc_filter']['impressions']['min'] ); ?>" 
								class="small-text aisee-auto-save"
								min="0"
							/>
							<p class="description">
								<?php _e( 'Minimum number of impressions to include in results.', 'aisee' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gsc_filter_impressions_max"><?php _e( 'Maximum Impressions', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="gsc_filter_impressions_max" 
								name="gsc_filter[impressions][max]" 
								value="<?php echo esc_attr( $settings['gsc_filter']['impressions']['max'] ); ?>" 
								class="small-text aisee-auto-save"
								min="0"
							/>
							<p class="description">
								<?php _e( 'Maximum number of impressions to include in results.', 'aisee' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gsc_filter_position_min"><?php _e( 'Minimum Position', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="gsc_filter_position_min" 
								name="gsc_filter[position][min]" 
								value="<?php echo esc_attr( $settings['gsc_filter']['position']['min'] ); ?>" 
								class="small-text aisee-auto-save"
								min="1"
								max="100"
							/>
							<p class="description">
								<?php _e( 'Minimum search position to include in results.', 'aisee' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gsc_filter_position_max"><?php _e( 'Maximum Position', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="gsc_filter_position_max" 
								name="gsc_filter[position][max]" 
								value="<?php echo esc_attr( $settings['gsc_filter']['position']['max'] ); ?>" 
								class="small-text aisee-auto-save"
								min="1"
								max="100"
							/>
							<p class="description">
								<?php _e( 'Maximum search position to include in results.', 'aisee' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gsc_filter_ctr_min"><?php _e( 'Minimum CTR (%)', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="gsc_filter_ctr_min" 
								name="gsc_filter[ctr][min]" 
								value="<?php echo esc_attr( $settings['gsc_filter']['ctr']['min'] ); ?>" 
								class="small-text aisee-auto-save"
								min="0"
								max="100"
								step="0.1"
							/>
							<p class="description">
								<?php _e( 'Minimum click-through rate percentage.', 'aisee' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gsc_filter_ctr_max"><?php _e( 'Maximum CTR (%)', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="gsc_filter_ctr_max" 
								name="gsc_filter[ctr][max]" 
								value="<?php echo esc_attr( $settings['gsc_filter']['ctr']['max'] ); ?>" 
								class="small-text aisee-auto-save"
								min="0"
								max="100"
								step="0.1"
							/>
							<p class="description">
								<?php _e( 'Maximum click-through rate percentage.', 'aisee' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gsc_filter_clicks_min"><?php _e( 'Minimum Clicks', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="gsc_filter_clicks_min" 
								name="gsc_filter[clicks][min]" 
								value="<?php echo esc_attr( $settings['gsc_filter']['clicks']['min'] ); ?>" 
								class="small-text aisee-auto-save"
								min="0"
							/>
							<p class="description">
								<?php _e( 'Minimum number of clicks to include in results.', 'aisee' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="gsc_filter_clicks_max"><?php _e( 'Maximum Clicks', 'aisee' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="gsc_filter_clicks_max" 
								name="gsc_filter[clicks][max]" 
								value="<?php echo esc_attr( $settings['gsc_filter']['clicks']['max'] ); ?>" 
								class="small-text aisee-auto-save"
								min="0"
							/>
							<p class="description">
								<?php _e( 'Maximum number of clicks to include in results.', 'aisee' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<div class="aisee-save-status" id="aisee-save-status" style="display: none;">
					<span class="saving"><?php _e( 'Saving...', 'aisee' ); ?></span>
					<span class="saved"><?php _e( 'Settings saved!', 'aisee' ); ?></span>
					<span class="error"><?php _e( 'Error saving settings.', 'aisee' ); ?></span>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * AJAX handler for saving individual settings.
	 *
	 * Processes auto-save requests from the settings form. Handles both
	 * simple and nested settings (like gsc_filter arrays). Includes
	 * security checks and user permission validation.
	 *
	 * @since 2.3
	 */
	public function ajax_save_setting() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aisee_admin_nonce' ) ) {
			wp_die( __( 'Security check failed', 'aisee' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action', 'aisee' ) );
		}

		$setting = sanitize_text_field( $_POST['setting'] );
		$value   = sanitize_text_field( $_POST['value'] );

		// Get current settings
		$settings = get_option( 'aiseeseo', $this->get_defaults() );

		// Handle nested settings (like gsc_filter[impressions][min])
		if ( strpos( $setting, '[' ) !== false ) {
			// Parse nested setting name
			preg_match_all( '/\[([^\]]*)\]/', $setting, $matches );
			$keys     = $matches[1];
			$base_key = explode( '[', $setting )[0];

			// Build nested array
			$current = &$settings[ $base_key ];
			foreach ( $keys as $key ) {
				if ( ! isset( $current[ $key ] ) ) {
					$current[ $key ] = array();
				}
				if ( $key === end( $keys ) ) {
					$current[ $key ] = $value;
				} else {
					$current = &$current[ $key ];
				}
			}
		} else {
			// Handle simple settings
			$settings[ $setting ] = $value;
		}

		// Save settings
		$updated = update_option( 'aiseeseo', $settings );

		if ( $updated ) {
			wp_send_json_success( array( 'message' => __( 'Setting saved successfully', 'aisee' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save setting', 'aisee' ) ) );
		}
	}
}