<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AISee_TagComplete {
	/**
	 * Get the singleton instance of the AISee_TagComplete class.
	 *
	 * Creates a new instance if one doesn't exist and initializes hooks.
	 *
	 * @since 2.3
	 * @return AISee_TagComplete The singleton instance of the AISee_TagComplete class.
	 */
	static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			$instance->hooks();
		}
		return $instance;
	}

	/**
	 * Initialize WordPress hooks for tag completion functionality.
	 *
	 * Sets up metaboxes for Google search suggestions integration.
	 *
	 * @since 2.3
	 */
	function hooks() {
		add_action( 'aisee_metaboxes', array( $this, 'add_meta_boxes' ) ); // add metaboxes
		// add_action( 'admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'] );
	}

	/**
	 * Enqueue admin scripts for tag completion.
	 *
	 * Currently disabled but can be used to load jQuery UI components
	 * required for autocomplete functionality.
	 *
	 * @since 2.3
	 */
	function admin_enqueue_scripts() {
		// aisee()->plugin_data['Version'],
		// wp_enqueue_script( 'jquery-ui-autocomplete', false, array('jquery'));
		wp_dequeue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'https://code.jquery.com/ui/1.12.1/jquery-ui.js' );

		return;
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'jquery-ui-mouse' );
		wp_enqueue_script( 'jquery-ui-accordion' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'jquery-ui-slider' );
	}


	/**
	 * Add meta boxes for search suggestions functionality.
	 *
	 * Adds the AISee Search Suggestions meta box to post edit screens
	 * for Google search autocomplete integration.
	 *
	 * @since 2.3
	 * @param string $post_type The current post type.
	 */
	function add_meta_boxes( $post_type ) {
		add_meta_box( 'aisee-tagcomplete', __( 'AiSee Search Suggestions', 'aisee' ), array( $this, 'aisee_suggestions_mb' ), $post_type, 'normal', 'high' );
	}

	/**
	 * Display the search suggestions meta box content.
	 *
	 * Renders an autocomplete input field that connects to Google's
	 * suggestion API to provide search term recommendations.
	 *
	 * @since 2.3
	 */
	function aisee_suggestions_mb() {
		echo '<input type="text" id="aisee_suggestions" />';

		?>
		<script type="text/javascript">
		jQuery(document).ready(function ($) { //wrapper
			
			$('#aisee_suggestions').autocomplete({
				minChars: 1,
				source: function (term, suggest) {
					
					var promise = googleSuggest();
					returnSearch = function (term, choices) {
						suggest(choices);
					}

					jQuery.when(promise).then(function (data) {
						term = term.toString().toLowerCase();
						var result = [];
						jQuery.each(data[1], function (item, value) {
							var stripedValue = value[0].replace(/<[^>]+>/g, '');
							result.push(stripedValue);
						})
						returnSearch(term, result);
					})
				},
				classes: {
					"ui-autocomplete": "highlight"
				}
			}).autocomplete( "widget" ).addClass( "aisee_suggestions" ).removeClass( "ui-widget ui-front ui-menu ui-widget-content" );

			function googleSuggest(returnSearch) {
				var term = jQuery('#aisee_suggestions').val();
				var service = {
					youtube: { client: 'youtube', ds: 'yt' },
					books: { client: 'books', ds: 'bo' },
					products: { client: 'products-cc', ds: 'sh' },
					news: { client: 'news-cc', ds: 'n' },
					images: { client: 'img', ds: 'i' },
					web: { client: 'hp', ds: '' },
					recipes: { client: 'hp', ds: 'r' }
				};

				var promise = jQuery.ajax({
					url: 'https://clients1.google.com/complete/search',
					dataType: 'jsonp',
					data: {
						q: term,
						pws: '0',
						gl: '
						<?php
						if ( get_option( 'timezone_string' ) ) {
							$tz = timezone_location_get( new DateTimeZone( get_option( 'timezone_string' ) ) )['country_code'];
						} else {
							$tz = 'en_US';
						}echo $tz;
						?>
						',
						nolabels: 't',
						client: service.web.client,
						ds: service.web.ds
					}
				})

				return promise
			};
			
		});
		</script>
		<?php
	}
}

/**
 * Get the AISee_TagComplete singleton instance.
 *
 * Provides global access to the tag completion functionality.
 *
 * @since 2.3
 * @return AISee_TagComplete The AISee_TagComplete singleton instance.
 */
function aisee_tagcomplete() {
	return AISee_TagComplete::get_instance();
}

aisee_tagcomplete();
