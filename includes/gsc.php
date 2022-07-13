<?php

class AISee_GSC {
	static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			$instance->hooks();
		}
		return $instance;
	}

	function hooks() {
		add_action( 'aisee_metaboxes', array( $this, 'add_meta_boxes' ) ); // add metaboxes

		add_action( 'admin_init', array( $this, 'save_gsc_profile' ) );

		add_action( 'wp_ajax_aisee_register', array( $this, 'aisee_register' ) ); // respond to ajax
		add_action( 'wp_ajax_nopriv_aisee_register', '__return_false' ); // do not respont to ajax

		add_action( 'wp_ajax_aisee_update_filter', array( $this, 'aisee_update_filter' ) ); // respond to ajax
		add_action( 'wp_ajax_nopriv_aisee_update_filter', '__return_false' ); // do not respont to ajax

		add_action( 'wp_ajax_aisee_populate_taxonomy', array( $this, 'aisee_populate_taxonomy' ) ); // respond to ajax
		add_action( 'wp_ajax_nopriv_aisee_populate_taxonomy', '__return_false' ); // do not respont to ajax

		add_action( 'wp_ajax_aisee_gsc_fetch', array( $this, 'aisee_gsc_fetch' ) ); // respond to ajax
		add_action( 'wp_ajax_nopriv_aisee_gsc_fetch', '__return_false' ); // do not respont to ajax

		add_action( 'wp_ajax_aisee_tag_action', array( $this, 'aisee_tag_action' ) ); // respond to ajax
		add_action( 'wp_ajax_nopriv_aisee_tag_action', '__return_false' ); // do not respont to ajax

		add_action( 'wp_ajax_aisee_get_connect_link', array( $this, 'get_connect_link' ) ); // respond to ajax
		add_action( 'wp_ajax_nopriv_get_connect_link', '__return_false' ); // do not respont to ajax

		add_action( 'pre_get_posts', array( $this, 'aisee_tags_support_query' ) );

		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget' ), 1 );

		$args = array( false );
		if ( ! wp_next_scheduled( 'aisee_weekly', $args ) ) {
			wp_schedule_event( time(), 'weekly', 'aisee_weekly', $args );
		}

		add_action( 'aisee_weekly', array( $this, 'aisee_weekly_batch' ) );

		// add_action('add_meta_boxes_post', array( $this, 'my_meta_box_order' ) );
	}

	function aisee_tag_action() {

		check_ajax_referer( 'aisee_tag_action', 'aisee_tag_action_nonce' );

		if ( empty( $_REQUEST['postid'] ) || empty( $_REQUEST['word'] ) || empty( $_REQUEST['action_type'] ) ) {
			wp_send_json_error( 'One of the required items is missing' );
		}
		$id = $_REQUEST['postid'];

		$word = sanitize_text_field( $_REQUEST['word'] );
		if ( $_REQUEST['action_type'] == 'remove' ) {
			$result = wp_remove_object_terms( $id, $word, 'aisee_term' );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}
			if ( empty( $result ) ) {
				wp_send_json_error( 'Empty result' );
			}
			wp_send_json_success( $result );
		}
		if ( $_REQUEST['action_type'] == 'add' ) {
			// wp_remove_object_terms( $id, $word, 'aisee_term' );
			$result = wp_set_post_terms( $id, $word, 'aisee_term', true );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}
			if ( empty( $result ) ) {
				wp_send_json_error( 'Empty result' );
			}
			wp_send_json_success( $result );
		}

	}

	function my_meta_box_order() {
		global $wp_meta_boxes;
		// aisee_flog( $wp_meta_boxes );
	}

	function dashboard_widget() {
		add_meta_box( 'aisee', 'AISee Terms', array( $this, 'aisee_dashboard_widget' ), 'dashboard', 'normal', 'high' );
	}

	function aisee_dashboard_widget() {
		aisee_global_cloud();
		// aisee_single_cloud();
	}

	function aisee_tags_support_query( $wp_query ) {
		if ( is_user_logged_in() && $wp_query->is_main_query() ) {
			$types = $this->get_supported_post_types();
			// if ( $wp_query->get( 'term' ) ) {
			// $wp_query->set( 'post_type', $types );
			// }
		}
	}

	function add_meta_boxes( $post_type ) {
		add_meta_box( 'aisee-gsc', __( 'AiSee Insights from Google&trade; Search Console', 'aisee' ), array( $this, 'aisee_gsc_mb' ), $post_type, 'normal', 'high' );
	}

	function aisee_gsc_mb() {
		// delete_option('aiseeseo');
		global $post;
		?>
		<div class="aisee-updates">
			<?php
			if ( ! $this->get_connectable_account() ) {
				?>
				<div id="is_unregistered">
					<p><strong>Let's start setting up your AISee account to get search insights from Google&trade; Search Console.</strong></p><p><strong>Worry not, it's free and just takes a click!</strong></p>
					<?php
					$current_user = wp_get_current_user();
					?>
					<div id="aisee_reg_form">
						<label><strong>First name</strong> <input type="text" name="aisee_fn" id="aisee_fn" required value="<?php echo $current_user->user_firstname; ?>" /></label>
						<label><strong>Last name</strong> <input type="text" name="aisee_ln" id="aisee_ln" required value="<?php echo $current_user->user_lastname; ?>" /></label>
						<label><strong>Email</strong> <input type="email" name="aisee_eml" id="aisee_eml" required value="<?php echo $current_user->user_email; ?>" /></label>
						<label><strong>Site</strong> <input type="URL" readonly name="aisee_url" id="aisee_url" required value="<?php echo trailingslashit( site_url() ); ?>" /></label>
					</div>
					<div id="reg_status"></div>
					<p><?php submit_button( 'Setup Account', 'primary large', 'aisee-register', false ); ?></p>
				</div>
				<script type="text/javascript">
				jQuery(document).ready(function ($) { //wrapper
					$("#aisee-register").click(function (e) {
						e.preventDefault();
						if( 
							! document.getElementById('aisee_fn').reportValidity() ||
							! document.getElementById('aisee_ln').reportValidity() || 
							! document.getElementById('aisee_eml').reportValidity() ||
							! document.getElementById('aisee_url').reportValidity()
						){
							return false;
						}
						aisee_register = {
							aisee_register_nonce: '<?php echo wp_create_nonce( 'aisee_register' ); ?>',
							action: "aisee_register",
							cachebust: Date.now(),
							postid: '<?php echo $post->ID; ?>',
							user: {
								fn: $('#aisee_fn').val(),
								ln: $('#aisee_ln').val(),
								email: $('#aisee_eml').val(),
							}
						};
						
						$.ajax({
							url: ajaxurl,
							method: 'POST',
							data: aisee_register,
							complete: function(jqXHR, textStatus){
								console.dir( jqXHR );
								if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('success') && jqXHR.responseJSON.success == true){
									response = jqXHR.responseJSON.data;
									$('#is_unregistered').html('<p><strong>Your account is ready! Let\'s connect to Google&trade; Search Console.</strong></p>' + '<a class="button-primary large" data-href="' + response + '" onclick="window.top.location.href = this.getAttribute(\'data-href\')" >Connect with Google&trade; Search Console</a>');
								}
								else {
									if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('data')) {
										$('#reg_status').html('<p><strong>' + jqXHR.responseJSON.data + '. Please email support@aiseeseo.com with this exact error.</strong></p>');
									}
									else {
										$('#reg_status').html('<p><strong>' + jqXHR.responseJSON.data + '. Please email support@aiseeseo.com.</strong></p>');
									}
								}
							},
							success: function (response) {
							} // initialize
						}); // ajax post
						return false;
					});
				});
				</script>
				<?php
			} else {
				if ( ! aisee_is_connected() ) {
					echo '<p>';
					echo '<a class="button-primary large aisee-btn" id="aisee_gsc_authenticate" onclick="window.top.location.href = this.getAttribute(\'data-href\')" data-href="' . $this->get_oauth_link( $post->ID, 'aisee_gsc_authenticate' ) . '">Connect with Google&trade; Search Console</a>';
					echo '</p>';
				} else { // we are set
					$meta = get_post_meta( $post->ID, '_aisee_keywords', true );
					$n_kw = $this->aisee_gsc_fetch( array( 'postid' => $post->ID ) );
					$html = '';
					if ( $meta ) {
						$html = $this->generate_html( $post->ID, $meta );
					}
					echo '<table id="aisee_terms_ui"><tr><td><div id="aisee_gsc_keywords">' . $html . '</div></td><td></td></tr><tr><td><p>';
					echo '<a class="button-primary large aisee-btn" id="aisee_gsc_fetch" href="#">Fetch Data from Google&trade; Search Console</a>';
					echo '</p></td>';
					echo '<td><p>';
					echo '<a class="button-primary large aisee-btn" id="aisee_gsc_revoke" onclick="window.top.location.href = this.getAttribute(\'data-href\')" data-href="' . $this->get_oauth_link( $post->ID, 'aisee_gsc_revoke' ) . '">Disconnect from AiSee SEO</a>';
					echo '</p></td></tr>';
					?>
					<tr><td><div id="aiseeseo_gsc_settings"><h3 style="font-weight:500">Keyword Filter</h3>
					<p><strong>Narrow down to keywords that match the following criteria:</strong></p>
					<!--<p>Clicks between</p> <div id="aiseeseo_clicks" class="aiseeseo_slider"></div> 
					<p>Impressions between</p> <div id="aiseeseo_impressions" class="aiseeseo_slider"></div>-->
					<p>Clicks between <span id="aiseeseo_clicks_min"></span> and <span id="aiseeseo_clicks_max"></p> <div id="aiseeseo_clicks" class="aiseeseo_slider"></div>

					<p>Impressions between <span id="aiseeseo_impressions_min"></span> and <span id="aiseeseo_impressions_max"></p> <div id="aiseeseo_impressions" class="aiseeseo_slider"></div>
					
					<p>CTR between <span id="aiseeseo_ctr_min"></span> and <span id="aiseeseo_ctr_max"></span></p> <div id="aiseeseo_ctr" class="aiseeseo_slider"></div>
					
					<p>Average position between <span id="aiseeseo_position_min"></span> and <span id="aiseeseo_position_max"></p> <div id="aiseeseo_position" class="aiseeseo_slider"></div>
					
					<div id="aiseeseo_ajax_status"></div>
					<p><?php submit_button( 'Populate Taxonomy &rarr;', 'primary', 'aisee_populate_taxonomy', false ); ?></p>
					</div></td>
					<td>
					<input type="button" value="Reset Filter to Defaults" id="aiseeseo_gsc_settings_reset" />
					
					</td></tr></table>
					<script type="text/javascript">
					jQuery(document).ready(function ($) { //wrapper
					
						try{
							$('#aisee_gsc_keywords_tbl tbody').sortable();
							if(1){

							var table = $('#aisee_gsc_keywords_tbl');

							$('th.sortable').click(function(){
								var table = $(this).parents('table').eq(0);
								var ths = table.find('tr:gt(0)').toArray().sort(compare($(this).index()));
								this.asc = !this.asc;
								if (!this.asc)
								ths = ths.reverse();
								for (var i = 0; i < ths.length; i++)
								table.append(ths[i]);
								console.log('sorted!');
							});
							}
						}
						catch(e) {
							console.dir(e);
						}

						$('#aisee_gsc_keywords').on('click', '.aisee-action', function(e){
							console.dir($(this).attr('class'));
							console.dir($(this).closest('tr').children('td:first').text());
							word = $(this).closest('tr').children('td:first').text();
							if($(this).hasClass('aisee-action-add')) {
								action_type = 'add';
							}
							if($(this).hasClass('aisee-action-remove')) {
								action_type = 'remove';
								action_label = '-';
							}
							aisee_tag_action = {
								aisee_tag_action_nonce: '<?php echo wp_create_nonce( 'aisee_tag_action' ); ?>',
								action: "aisee_tag_action",
								postid: '<?php echo $post->ID; ?>',
								word: word,
								action_type: action_type,
							}

							$.ajax({
								url: ajaxurl,
								method: 'POST',
								data: aisee_tag_action,
								context: this,
								complete: function(jqXHR, textStatus){
									
									if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('success')){ // success
										if(jqXHR.responseJSON.success) {
											$(this).removeClass($(this).attr('class'));
											if(action_type == 'remove') {
												$(this).addClass('aisee-action aisee-action-add');
												$(this).attr('title', 'Add Keyword');
												$(this).html('+');
											}
											if(action_type == 'add') {
												$(this).addClass('aisee-action aisee-action-remove');
												$(this).attr('title', 'Remove Keyword');
												$(this).html('-');
											}
										}
										else {
											// $(this).css('border','5px solid purple');
										}
									}
									else { // no response json
										$(this).css('border','5px solid purple');
										// $('#aiseeseo_ajax_status').html('<div class="aiseeseo_status_error aiseeseo_status">Failed to get a valid response!</div>').fadeOut(10000);
									}
								},
								success: function (response) {
								} // initialize
							}); // ajax post

						});

						$('#aisee_populate_taxonomy').click(function(e){
							aisee_populate_taxonomy = {
								aisee_generate_tags_nonce: '<?php echo wp_create_nonce( 'aisee_populate_taxonomy' ); ?>',
								action: "aisee_populate_taxonomy",
								postid: '<?php echo $post->ID; ?>',
							};
							
							$.ajax({
								url: ajaxurl,
								method: 'POST',
								data: aisee_populate_taxonomy,
								complete: function(jqXHR, textStatus){
									// console.dir(jqXHR);
									// console.dir(typeof jqXHR.responseJSON.success);
									if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('success')){ // success
										// success / fadeout
										if(jqXHR.responseJSON.success) {
											$('#aiseeseo_ajax_status').html('<div class="aiseeseo_status_success aiseeseo_status">Settings Updated</div>').fadeOut(10000);
										}
										else {
											$('#aiseeseo_ajax_status').html('<div class="aiseeseo_status_error aiseeseo_status">Couldn\'t save settings!</div>').fadeOut(10000);
										}
									}
									else { // no response json
										$('#aiseeseo_ajax_status').html('<div class="aiseeseo_status_error aiseeseo_status">Failed to get a valid response!</div>').fadeOut(10000);
									}
								},
								success: function (response) {
								} // initialize
							}); // ajax post
						});

						<?php
						$defaults   = aisee_defaults();
						$defaults   = $defaults['gsc_filter'];
						$gsc_filter = aisee_get_setting( 'gsc_filter' );
						$gsc_filter = array_replace_recursive( $defaults, $gsc_filter );
						$defaults   = json_encode( $defaults );

						$gsc_filter = json_encode( $gsc_filter );

						?>
						aisee_defaults = <?php echo $defaults; ?>;
						//console.dir(aisee_defaults);
						gsc_filter = <?php echo $gsc_filter; ?>;
						console.dir(gsc_filter);
						$( "#aiseeseo_clicks" ).slider({
							range: true,
							min: 0,
							max: 10000,
							step: 1,
							values: [gsc_filter.clicks.min,gsc_filter.clicks.max],
							change: aisee_save_filter_values,
							slide: aisee_sync_from_sliders
						});
						$( "#aiseeseo_impressions" ).slider({
							range: true,
							min: 0,
							max: 10000,
							step: 1,
							values: [gsc_filter.impressions.min,gsc_filter.impressions.max],
							change: aisee_save_filter_values,
							slide: aisee_sync_from_sliders
						});
						$( "#aiseeseo_ctr" ).slider({
							range: true,
							min: 0,
							max: 100,
							step: 0.1,
							values: [gsc_filter.ctr.min,gsc_filter.ctr.max],
							change: aisee_save_filter_values,
							slide: aisee_sync_from_sliders
						});
						$( "#aiseeseo_position" ).slider({
							range: true,
							min: 0,
							max: 100,
							step: 1,
							values: [gsc_filter.position.min,gsc_filter.position.max],
							change: aisee_save_filter_values,
							slide: aisee_sync_from_sliders
						});
						
						$('#aiseeseo_clicks_min').html(gsc_filter.clicks.min);
						$('#aiseeseo_clicks_max').html(gsc_filter.clicks.max);

						$('#aiseeseo_impressions_min').html(gsc_filter.impressions.min);
						$('#aiseeseo_impressions_max').html(gsc_filter.impressions.max);
						
						$('#aiseeseo_ctr_min').html(gsc_filter.ctr.min);
						$('#aiseeseo_ctr_max').html(gsc_filter.ctr.max);

						$('#aiseeseo_position_min').html(gsc_filter.position.min);
						$('#aiseeseo_position_max').html(gsc_filter.position.max);


						function aisee_sync_from_sliders(occurance, ui){

							if(ui.handleIndex == 0) {
								$('#'+$(this).attr('id') + '_min').html(ui.value);
								$str = $(this).attr('id') + '_min';
								$str = $str.split('_');
								//console.log($str);
								gsc_filter[$str[1]][$str[2]] = ui.value
								
							}
							if(ui.handleIndex == 1) {
								$('#'+$(this).attr('id') + '_max').html(ui.value);
								$str = $(this).attr('id') + '_max';
								$str = $str.split('_');
								//console.log($str);
								gsc_filter[$str[1]][$str[2]] = ui.value
							}
							//console.dir();
						}

						$('#aiseeseo_gsc_settings_reset').click(function(e){
							e.preventDefault();
							//console.dir(aisee_defaults);
							console.dir(aisee_defaults.clicks.min);
							console.dir(aisee_defaults.clicks.max);
							
							console.dir(aisee_defaults.impressions.min);
							console.dir(aisee_defaults.impressions.max);
							
							console.dir(aisee_defaults.ctr.min);
							console.dir(aisee_defaults.ctr.max);

							console.dir(aisee_defaults.position.min);
							console.dir(aisee_defaults.position.max);


							// need these first because change eventhandler will fetch from these values when the event fires

							gsc_filter.clicks.min = aisee_defaults.clicks.min;
							gsc_filter.clicks.max = aisee_defaults.clicks.max;
							
							gsc_filter.impressions.min = aisee_defaults.impressions.min;
							gsc_filter.impressions.max = aisee_defaults.impressions.max;

							gsc_filter.ctr.min = aisee_defaults.ctr.min;
							gsc_filter.ctr.max = aisee_defaults.ctr.max;

							gsc_filter.position.min = aisee_defaults.position.min;
							gsc_filter.position.max = aisee_defaults.position.max;

							$( "#aiseeseo_clicks" ).slider( "values", 0, aisee_defaults.clicks.min );
							$( "#aiseeseo_clicks" ).slider( "values", 1, aisee_defaults.clicks.max );
							$( "#aiseeseo_impressions" ).slider( "values", 0, aisee_defaults.impressions.min );
							$( "#aiseeseo_impressions" ).slider( "values", 1, aisee_defaults.impressions.max );
							$( "#aiseeseo_ctr" ).slider( "values", 0, aisee_defaults.ctr.min );
							$( "#aiseeseo_ctr" ).slider( "values", 1, aisee_defaults.ctr.max );
							$( "#aiseeseo_position" ).slider( "values", 0, aisee_defaults.position.min );
							$( "#aiseeseo_position" ).slider( "values", 1, aisee_defaults.position.max );

							$( "#aiseeseo_clicks_min" ).html( aisee_defaults.clicks.min );
							$( "#aiseeseo_clicks_max" ).html( aisee_defaults.clicks.max );
							$( "#aiseeseo_impressions_min" ).html( aisee_defaults.impressions.min );
							$( "#aiseeseo_impressions_max" ).html( aisee_defaults.impressions.max );
							$( "#aiseeseo_ctr_min" ).html( aisee_defaults.ctr.min );
							$( "#aiseeseo_ctr_max" ).html( aisee_defaults.ctr.max );
							$( "#aiseeseo_position_min" ).html( aisee_defaults.position.min );
							$( "#aiseeseo_position_max" ).html( aisee_defaults.position.max );
							
							
							console.dir('aiseeseo_gsc_settings_reset');
							console.dir(gsc_filter);
						});

						function aisee_save_filter_values(occurance, ui) {
							console.dir('fired aisee_save_filter_values');
							// console.dir($(this).attr('id'));
							// console.dir(occurance);
							// console.dir(ui);
							//if(ui.handleIndex == 0) {
							//	$('#'+$(this).attr('id') + '_min').html(ui.value);
							//	console.log('targeting:' + '#'+$(this).attr('id') + '_min');
							//	
							//}
							//if(ui.handleIndex == 1) {
							//	$('#'+$(this).attr('id') + '_max').html(ui.value);
							//	console.log('targeting:' + '#'+$(this).attr('id') + '_max');
							//}
							// console.dir('<?php get_option( 'aiseeseo' ); ?>')
							console.log(gsc_filter);
							aisee_update_filter = {
								aisee_update_filter_nonce: '<?php echo wp_create_nonce( 'aisee_update_filter' ); ?>',
								action: "aisee_update_filter",
								gsc_filter: gsc_filter
							};
							
							$.ajax({
								url: ajaxurl,
								method: 'POST',
								data: aisee_update_filter,
								complete: function(jqXHR, textStatus){
									// console.dir(jqXHR);
									// console.dir(typeof jqXHR.responseJSON.success);
									if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('success')){ // success
										// success / fadeout
										if(jqXHR.responseJSON.success) {
											$('#aiseeseo_ajax_status').html('<div class="aiseeseo_status_success aiseeseo_status">Settings Updated</div>').fadeOut(10000);
										}
										else {
											$('#aiseeseo_ajax_status').html('<div class="aiseeseo_status_error aiseeseo_status">Couldn\'t save settings!</div>').fadeOut(10000);
										}
									}
									else { // no response json
										$('#aiseeseo_ajax_status').html('<div class="aiseeseo_status_error aiseeseo_status">Failed to get a valid response!</div>').fadeOut(10000);
									}
								},
								success: function (response) {
								} // initialize
							}); // ajax post
						}

						
						function compare(idx) {
							return function(a, b) {
							var A = tableCell(a, idx), B = tableCell(b, idx)
							if( A.match(/\d+%/) && B.match(/\d+%/) ) { // Help sort by CTR percentage.
								A = A.replace('%','');
								B = B.replace('%','');
							}
							return $.isNumeric(A) && $.isNumeric(B) ? 
								A - B : A.toString().localeCompare(B)
							}
						}

						function tableCell(tr, index){ 
							return $(tr).children('td').eq(index).text() 
						}
					
						$("#aisee_gsc_fetch").click(function (e) {
							e.preventDefault();
							$(this).addClass('aisee-btn-loading');
							aisee_gsc_fetch = {
								aisee_gsc_fetch_nonce: '<?php echo wp_create_nonce( 'aisee_gsc_fetch' ); ?>',
								action: "aisee_gsc_fetch",
								postid: '<?php echo $post->ID; ?>',
							};
							$.ajax({
								url: ajaxurl,
								method: 'POST',
								data: aisee_gsc_fetch,
								complete: function(jqXHR, textStatus){
									console.dir(jqXHR);
									$('#aisee_gsc_fetch').removeClass('aisee-btn-loading');
									if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('success') && jqXHR.responseJSON.success == true){
										response = jqXHR.responseJSON.data;
										$('#aisee_gsc_keywords').html(response);
									}
									else {
										if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('data')) {
											$('#aisee_gsc_keywords').html('<p><strong>' + jqXHR.responseJSON.data + ' Please email <a href="mailto:support@aiseeseo.com">support@aiseeseo.com</a> with this exact error.</strong></p>');
										}
										else { // not json or no data
											$('#reg_status').html('<p><strong>Plugin failed to parse data. Please email <a href="mailto:support@aiseeseo.com">support@aiseeseo.com</a>.</strong></p>');
										}
									}
								},
								success: function (response) {
								} // initialize
							}); // ajax post
						});
					});
					</script>
					<?php
					// $terms =  wp_get_post_terms( $post->ID, 'aisee_term', array() );
					// foreach($terms as $term) {
					// echo $term->name . '<br />';
					// }
				}
			}
			?>
			</div>
			<?php
	}

	function aisee_update_filter() {
		check_ajax_referer( 'aisee_update_filter', 'aisee_update_filter_nonce' );
		aisee_update_setting( 'gsc_filter', $_REQUEST['gsc_filter'] );
		aisee_update_setting( 'gsc_time_updated', time() );
		wp_send_json_success( $_REQUEST );
	}

	function aisee_weekly_batch() {
		$this->batch_generate_tax();
	}

	function get_supported_post_types() {
		global $wp_taxonomies;
		return ( isset( $wp_taxonomies['aisee_term'] ) ) ? $wp_taxonomies['aisee_term']->object_type : array();
	}

	function batch_generate_tax() {
		$args = array(
			'post_type'      => $this->get_supported_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			// 'tax_query'      => array( // only add post meta to those that do not have keywords
			// array(
			// 'taxonomy' => 'aisee_term',
			// 'field'    => 'slug',
			// 'operator' => 'NOT IN',
			// 'terms'    => array( '' ),
			// ),
			// ),
		);
		$query   = new WP_Query( $args );
		$posts   = $query->get_posts();
		$timeout = ini_get( 'max_execution_time' );
		if ( empty( $timeout ) ) {
			$timeout = 29;
		} else {
			$timeout = $timeout - 1;
		}
		// aisee_flog( $posts );
		foreach ( $posts as $post ) {
			set_time_limit( $timeout );
			aisee_flog( 'Generating Tags for: ' . $post->ID . "\t" . $post->post_title );
			$this->aisee_populate_taxonomy( array( 'postid' => $post->ID ) );
			// aisee_flog( $post->post_title );
		}
		// wp_send_json_success( $query );
	}

	/**
	 * Filter and adds terms to post
	 * Can be called via cron or via wp-ajax on individual post by clicking Populate Taxonomy
	 *
	 * @param array $request
	 * @return void
	 */
	function aisee_populate_taxonomy( $request = array() ) {
		// wp_send_json_success( $this->batch_generate_tax() );
		// print_r( get_post_types( array( 'public' => true ) ) ); return;
		// wp_send_json_success( get_taxonomies() );
		if ( wp_doing_ajax() ) {
			check_ajax_referer( 'aisee_populate_taxonomy', 'aisee_generate_tags_nonce' );
			$request = $_REQUEST;
		}

		$meta = get_post_meta( $request['postid'], '_aisee_keywords', true );

		if ( // attempt to fetch fresh and add them to the post_meta
			empty( $meta ) ||
			empty( $meta['keywords'] ) ||
			! is_array( $meta['keywords'] )
		) {
			$this->aisee_gsc_fetch( array( 'postid' => $request['postid'] ) );
			$meta = get_post_meta( $request['postid'], '_aisee_keywords', true );
		}

		if (
			empty( $meta ) ||
			empty( $meta['keywords'] ) ||
			! is_array( $meta['keywords'] )
		) { // no keywords even after a fresh fetch
			if ( wp_doing_ajax() ) {
				wp_send_json_error( $request );
			}
		} else {
			// aisee_flog( "\taisee_generate_tags processing Post ID " . $request['postid'] . ' MAY get Tags: ' . var_export( $meta, 1 ) );
			$kw = $meta['keywords'];
			uasort( $kw, fn( $a, $b ) => $a['impressions'] <=> $b['impressions'] );
			$kw         = array_reverse( $kw );
			$gsc_filter = aisee_get_setting( 'gsc_filter' );
			// aisee_flog( $gsc_filter );
			$valid_terms = array();
			$count       = 0;
			$limit       = apply_filters( 'aisee_term_limit', 10 );
			foreach ( $kw as $index => $stats ) {
				$stats['ctr'] = $stats['ctr'] * 100;
				// aisee_flog( $gsc_filter );
				// aisee_flog( $stats );
				if (
					$stats['ctr'] >= $gsc_filter['ctr']['min'] &&
					$stats['ctr'] <= $gsc_filter['ctr']['max'] &&
					$stats['position'] >= $gsc_filter['position']['min'] &&
					$stats['position'] <= $gsc_filter['position']['max'] &&

					$stats['clicks'] >= $gsc_filter['clicks']['min'] &&
					$stats['clicks'] <= $gsc_filter['clicks']['max'] &&
					$stats['impressions'] >= $gsc_filter['impressions']['min'] &&
					$stats['impressions'] <= $gsc_filter['impressions']['max']
				) {
					$valid_terms[] = $stats['keys'];
					// aisee_flog( "\tPost ID " . $request['postid'] . ' will get Tags: ' . $stats['keys'] );
					if ( $count < $limit ) {
						wp_insert_term(
							$stats['keys'], // the term
							'aisee_term', // the taxonomy
						);
					}

					$count++;
					// aisee_flog( $stats['keys'] . ' will be added as a tag.' );
				} else {
					// aisee_flog( "\tPost ID " . $request['postid'] . ' will NOT GET Tags: ' . $stats['keys'] );
					// aisee_flog( $stats );
				}
			}
			// wp_set_post_tags( $request['postid'], implode( ',', $valid_terms ), false );
			wp_set_post_terms( $request['postid'], implode( ',', $valid_terms ), 'aisee_term', false );
			// wp_set_post_terms( $request['postid'], implode( ',', $valid_terms ), 'aisee_term', ! wp_doing_ajax() );
		}

		if ( wp_doing_ajax() ) {
			wp_send_json_success( $request );
		}
	}

	function aisee_gsc_fetch( $request = array() ) {
		if ( wp_doing_ajax() ) {
			check_ajax_referer( 'aisee_gsc_fetch', 'aisee_gsc_fetch_nonce' );
			$request = $_REQUEST;
		}
		$id  = sanitize_text_field( $request['postid'] );
		$url = $this->get_oauth_link( $id, 'aisee_gsc_fetch' );
		$url = add_query_arg( 'cb', time(), $url );
		$url = add_query_arg( 'status', aisee_get_status(), $url );
		if ( get_post_status( $id ) != 'publish' ) {
			if ( wp_doing_ajax() ) {
				wp_send_json_success( 'Post is not published or is not public.' );
			} else {
				return '';
			}
		}
		$meta = get_post_meta( $id, '_aisee_keywords', true );

		if ( $meta ) {
			$t = time();
			if ( empty( $meta['time'] ) ||
				( $t - $meta['time'] ) >= ( 86400 * 7 ) || // If the difference is greater than 7 days then fetch fresh
				( aisee_get_setting( 'gsc_time_updated' ) > $meta['time'] )  // If filter settings were updated after fetching the keywords of this post
			) {
				$meta = false;
			}
		} else {
		}

		if ( ! $meta ) {
			$args     = array(
				'httpversion' => '1.1',
				'compress'    => true,
				'headers'     => array(
					'aisee-gsc-fetch' => true,
				),
			);
			$response = wp_safe_remote_request(
				$url,
				$args
			);
			if ( is_wp_error( $response ) ) {
				if ( wp_doing_ajax() ) {
					wp_send_json_error( $response->get_error_message() );
				} else {
					return '';
				}
			}
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 != $status_code ) {
				if ( wp_doing_ajax() ) {
					wp_send_json_error( 'Failed to fetch response from AISee service. Error Code: ' . $status_code );
				} else {
					return '';
				}
			}
			$response = wp_remote_retrieve_body( $response );
			if ( empty( $response ) || is_null( $response ) ) {
				if ( wp_doing_ajax() ) {
					wp_send_json_error( 'Empty server response.' );
				} else {
					return '';
				}
			}
			$response = json_decode( $response, true );
			if ( is_null( $response ) ) { // NULL if the json cannot be decoded / data is deeper than recursion limit. OR no data exists
				if ( wp_doing_ajax() ) {
					wp_send_json_error( 'Invalid server response.' );
				} else {
					return '';
				}
			}
			if ( isset( $response['success'] ) && $response['success'] == true ) {
				if ( ! empty( $response['data'] ) ) {
					$meta = array(
						'time'     => time(),
						'keywords' => $response['data'],
					);
					$ret  = update_post_meta( $id, '_aisee_keywords', $meta );
					$html = $this->generate_html( $id, $meta );
					if ( wp_doing_ajax() ) {
						wp_send_json_success( $html );
					} else {
						return $html;
					}
				} else {
					$ret = update_post_meta(
						$id,
						'_aisee_keywords',
						array(
							'time'     => time(),
							'keywords' => array(),
						)
					);
					if ( wp_doing_ajax() ) {
						wp_send_json_success( 'No keywords yet.' );
					} else {
						return '';
					}
				}
			}
		} else {
			$html = $this->generate_html( $id, $meta );
			if ( wp_doing_ajax() ) {
				wp_send_json_success( $html );
			} else {
				return $html;
			}
		}
	}

	function generate_html( $id, $meta ) {
		$html     = '';
		$keywords = ! empty( $meta['keywords'] ) ? $meta['keywords'] : false;
		// aisee_flog( $meta );
		if ( ! $keywords ) {
			// aisee_flog( '! $keywords' );
			return;
		}
		$tagged_terms = wp_get_post_terms( $id, 'aisee_term', array() );
		$aisee_tags   = array();
		foreach ( $tagged_terms as $term ) {
			$aisee_tags[] = $term->name;
		}

		// aisee_flog( '$aisee_tags' );
		// aisee_flog( $aisee_tags );
		if ( count( $keywords ) ) {
			uasort( $keywords, fn( $a, $b) => $a['impressions'] <=> $b['impressions'] );
			$keywords = array_reverse( $keywords );
			foreach ( $keywords as $key => $value ) {
				$action       = 'add';
				$action_label = '+';

				$kw = $value['keys'];

				if ( in_array( $kw, $aisee_tags ) ) {

					$action       = 'remove';
					$action_label = '-';
					// unset( $aisee_tags[ $kw ] );
					unset( $aisee_tags[ array_search( $kw, $aisee_tags ) ] );
				}
				$html .= '<tr><td>' . $value['keys'] . '</td><td>' . $value['clicks'] . '</td><td>' . round( ( 100 * $value['ctr'] ), 2 ) . '%</td><td>' . $value['impressions'] . '</td><td>' . round( $value['position'], 2 ) . '</td><td><span title="' . ucwords( $action ) . ' Keyword" class="aisee-action aisee-action-' . $action . '">' . $action_label . '</span></td></tr>';
			}

			// $html = '<table id="aisee_gsc_keywords_tbl"><thead><tr><th class="sortable">Keyword Phrase</th><th class="sortable">Clicks</th><th class="sortable">CTR</th><th class="sortable">Impressions</th><th class="sortable">Position</th><th></th></tr></thead>' . $html . '</table>';
		} else {
			// $html = '';
		}
		$tags_html = '';
		foreach ( $aisee_tags as $aisee_tag ) {
			$tags_html .= '<tr><td>' . $aisee_tag . '</td><td> &mdash; </td><td> &mdash; </td><td> &mdash; </td><td> &mdash; </td><td><span title="Remove Keyword" class="aisee-action aisee-action-remove">-</span></td></tr>';
		}

		$html = $html . $tags_html;
		if ( $html ) {
			$html = '<table id="aisee_gsc_keywords_tbl"><thead><tr><th class="sortable">Keyword Phrase</th><th class="sortable">Clicks</th><th class="sortable">CTR</th><th class="sortable">Impressions</th><th class="sortable">Position</th><th class="sortable">Add / Remove</th></tr></thead>' . $html . '</table>';
		} else {
			$html = '<table id="aisee_gsc_keywords_tbl"><thead><tr><th class="sortable">Keyword Phrase</th><th class="sortable">Clicks</th><th class="sortable">CTR</th><th class="sortable">Impressions</th><th class="sortable">Position</th></tr></thead><tr><td colspan="4">No keywords found</td></tr></table>';
		}

		if ( ! empty( $meta['time'] ) ) {
			$fetched = date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $meta['time'] );
			if ( $fetched ) {
				$html = '<p id="aisee_fetched_on">Fetched On ' . $fetched . '.</p>' . $html . '<p id="aisee_fetched_on_notice">The data is refreshed every 15 days.</p>';
			}
		}
		$html = wp_kses(
			$html,
			array(
				'span'  => array(
					'id'    => array(),
					'class' => array(),
					'title' => array(),
				),
				'p'     => array(
					'id'    => array(),
					'class' => array(),
				),
				'table' => array(
					'id'    => array(),
					'class' => array(),
				),
				'thead' => array(
					'id'    => array(),
					'class' => array(),
				),
				'tbody' => array(
					'id'    => array(),
					'class' => array(),
				),
				'tr'    => array(
					'id'    => array(),
					'class' => array(),
				),
				'th'    => array(
					'id'    => array(),
					'class' => array(),
				),
				'td'    => array(
					'id'    => array(),
					'class' => array(),
				),
				'tfoot' => array(
					'id'    => array(),
					'class' => array(),
				),
			)
		);
		return $html;
	}

	function save_gsc_profile() {
		if ( isset( $_REQUEST['aisee-action'] ) && $_REQUEST['aisee-action'] == 'oauth' ) {
			wp_verify_nonce( $_REQUEST['origin_nonce'], 'aisee_gscapi' );
			if ( current_user_can( 'activate_plugins' ) &&
				! empty( $_REQUEST['success'] ) &&
				$_REQUEST['success'] == 1
			) {
				$aisee_reg = get_option( 'aiseeseo' );
				if ( $aisee_reg ) {
					$aisee_reg['gsc'] = true;
					update_option( 'aiseeseo', $aisee_reg );
				}
			} else {
			}
			wp_redirect( html_entity_decode( get_edit_post_link( sanitize_text_field( $_REQUEST['post'] ) ) ), 302 );
			exit;
		}

		if ( isset( $_REQUEST['aisee-action'] ) && $_REQUEST['aisee-action'] == 'revoke' && isset( $_REQUEST['success'] ) && $_REQUEST['success'] == '1' ) {
			$aisee_reg = get_option( 'aiseeseo' );
			if ( $aisee_reg && ! empty( $aisee_reg['gsc'] ) ) {
				unset( $aisee_reg['gsc'] );
				update_option( 'aiseeseo', $aisee_reg );
			}
		}
	}

	function get_oauth_link( $id, $action = false ) {
		$statevars = array(
			'site_url'       => trailingslashit( get_site_url() ),
			'return_url'     => get_edit_post_link( $id ),
			'permalink'      => get_permalink( $id ),
			'origin_nonce'   => wp_create_nonce( 'aisee_gscapi' ),
			'origin_ajaxurl' => admin_url( 'admin-ajax.php' ),
		);
		$account   = $this->get_connectable_account();
		if ( ! $account ) {
			return;
		}
		$statevars = aisee_encode( array_merge( $account, $statevars ) );
		$auth      = add_query_arg( $action, $statevars, AISEEAPIEPSL );
		$auth      = add_query_arg( 'aisee_action', $action, $auth );
		// aisee_flog( $auth );
		return $auth;

	}

	function get_connect_link() {
		check_ajax_referer( 'get_connect_link', 'get_connect_link_nonce' );
		$id = ! empty( $_REQUEST['post_id'] ) ? sanitize_text_field( $_REQUEST['post_id'] ) : false;
		if ( ! $id ) {
			wp_send_json_error( 'Invalid post ID' );
		}
		$account = $this->get_connectable_account();
		if ( ! $account ) {
			wp_send_json_error( 'Account not setup' );
		}
		$auth = $this->get_oauth_link( $id, 'aisee_gsc_authenticate' );
		wp_send_json_success( '<a class="button-primary large" data-href="' . $auth . '" onclick="window.top.location.href = this.getAttribute(\'data-href\')" >Connect with Google&trade; Search Console</a>' );
	}

	function aisee_register() {
		check_ajax_referer( 'aisee_register', 'aisee_register_nonce' );
		global $wp_version;
		if ( empty( $_REQUEST['user'] ) ) {
			wp_send_json_error( 'Invalid details' );
		}
		$firstname = ! empty( $_REQUEST['user']['fn'] ) ? sanitize_text_field( $_REQUEST['user']['fn'] ) : '';
		$lastname  = ! empty( $_REQUEST['user']['ln'] ) ? sanitize_text_field( $_REQUEST['user']['ln'] ) : '';
		$useremail = ! empty( $_REQUEST['user']['email'] ) ? sanitize_text_field( $_REQUEST['user']['email'] ) : '';
		if ( empty( $useremail ) ) {
			wp_send_json_error( 'Email missing' );
		}
		if ( ! filter_var( $useremail, FILTER_VALIDATE_EMAIL ) ) {
			wp_send_json_error( 'Invalid email' );
		}
		$args     = array(
			'user' => array(
				'fn'    => $firstname,
				'ln'    => $lastname,
				'email' => $useremail,
			),
			'diag' => array(
				'site_url'       => trailingslashit( site_url() ),
				'wp'             => $wp_version,
				'plugin_version' => aisee()->plugin_data['Version'],
				'cachebust'      => microtime(),
			),
		);
		$args     = aisee_encode( $args );
		$url      = add_query_arg(
			'aisee_action',
			'aisee_register',
			add_query_arg(
				'p',
				'9',
				add_query_arg( 'reg_details', $args, AISEEAPIEPSL )
			)
		);
		$response = wp_safe_remote_request(
			$url
		);
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 != $status_code ) {
			wp_send_json_error( 'Failed to fetch response from AISee service. Error Code: ' . $status_code );
		}
		$response = wp_remote_retrieve_body( $response );
		if ( empty( $response ) || is_null( $response ) ) {
			wp_send_json_error( 'No response from AISee Server. Registration Failed.' );
		}
		$response = json_decode( $response, true );
		if ( is_null( $response ) ) {
			wp_send_json_error( 'Invalid server response.' );
		}
		if ( isset( $response['success'] ) && $response['success'] == true ) {
			if ( ! empty( $response['data']['ID'] ) && ! empty( $response['data']['user_email'] ) ) {
				update_option( 'aiseeseo', $response['data'] ); // response['data] needs validation
				wp_send_json_success( $this->get_oauth_link( sanitize_text_field( $_REQUEST['postid'] ), 'aisee_gsc_authenticate' ) );
			} else {
				wp_send_json_error( 'Invalid server response.' );
			}
		}

	}

	function get_connectable_account() {
		return get_option( 'aiseeseo' );
	}
}

function aisee_gsc() {
	return AISee_GSC::get_instance();
}

aisee_gsc();



function aisee_global_cloud( $echo = true ) {

	$terms = get_terms(
		array(
			'taxonomy'   => 'aisee_term',
			'hide_empty' => false,
		)
	);

	$words = array();
	foreach ( $terms as $key => $value ) {
		// $value = explode(' ', $value);
		// aisee_llog( $value['name'] );

		$value = explode( ' ', $value->name );
		foreach ( $value as $v ) {
			$words[] = $v;
		}
	}
	// aisee_llog( $terms );
	$words = array_count_values( $words );
	// var_dump( $words );
	arsort( $words );
	// aisee_llog( $words );

	$avg             = ( max( $words ) + min( $words ) ) / 2;
	$avg             = $avg / 1.618;
	$percentage      = array();
	$drop_percentage = 0;
	foreach ( $words as $key => $value ) {
		$percentage[ $key ]['current'] = ( ( $value / count( $words ) ) * 100 ) . '%';
		$percentage[ $key ]['cutoff']  = $drop_percentage;
		// echo 'key:'.$key .'value:'.  $value  . ': out of:' . count($keywords). ':' . ( $value / count($keywords)   / 100).'%';
		// echo $key .':'. ( ($value / count($keywords) ) * ( $drop_percentage / 100 ) );
		// $percentage[$key] = ( $value * 100 / count($keywords)  );
		// echo 'cutoff:'.$drop_percentage .': current:' . ( ( $value * 100  * 1000) / count($keywords) )
		if ( ( $drop_percentage ) > ( ( $value * 100 ) / count( $words ) ) ) {
			continue;
		}
		// $newtags[$key] = ($value + $avg) / $avg;
		// $newtags[] = '<span style="font-family:impact,sans-serif;font-size:'. (16.81 * (($value + $avg) / $avg) ).'px">'.$key.'</span>';
		$newtags[] = '<span class="aitag" style="font-size:' . ( 16.18 * ( ( $value + $avg ) / $avg ) ) . 'px">' . $key . '</span>';
	}

	echo implode( ' ', $newtags );
	
	echo '<hr />';

	$cloud = wp_tag_cloud(
		array(
			'taxonomy' => 'aisee_term',
			'number'   => 0,
			'echo'     => false,
		)
	);
	if ( ! is_wp_error( $cloud ) && ! empty( $cloud ) ) {
		$cloud = '<div class="aisee_global_cloud">' . $cloud . '</div>';
		if ( $echo ) {
			echo $cloud;
		} else {
			return $cloud;
		}
	}
}



function aisee_single_cloud( $id = false, $echo = true ) {
	$aic = AISee_GSC::get_instance();
	if ( $id ) {
		$terms = wp_get_post_terms( $id, 'aisee_term', array() );
	} elseif ( is_singular( $aic->get_supported_post_types() ) ) {
		global $post;
		$terms = wp_get_post_terms( $post->ID, 'aisee_term', array() );
	} else {
		// id not provided and post is not singular / get_supported_post_types
		return;
	}

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		foreach ( $terms as $key => $tag ) {
			$link = get_term_link( (int) $tag->term_id, $tag->taxonomy );
			if ( is_wp_error( $link ) ) {
				return;
			}
			$terms[ $key ]->link = $link;
			$terms[ $key ]->id   = $tag->term_id;
		}

		if ( is_singular( $aic->get_supported_post_types() ) ) {
			$output = '<div class="aisee_singular_cloud">' . wp_generate_tag_cloud(
				$terms,
				array(
					'smallest' => '0.8125',
					'largest'  => '0.8125',
					'unit'     => 'em',
				)
			) . '</div>';
		} else {
			$output = '<div class="aisee_singular_cloud">' . wp_generate_tag_cloud( $terms, array() ) . '</div>';
		}
		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}
}
