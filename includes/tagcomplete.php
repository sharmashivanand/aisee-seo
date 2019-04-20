<?php

class AISee_TagSuggest {
	static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			$instance->hooks();
		}
		return $instance;
	}

	function hooks() {
		// add_action( 'aisee_metaboxes', array( $this,'add_meta_boxes' ) ); // add metaboxes
		// add_action( 'admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'] );
	}

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


	function add_meta_boxes( $post_type ) {
		add_meta_box( 'aisee-tagcomplete', __( 'AiSee Search Suggestions', 'aisee' ), array( $this, 'aisee_suggestions_mb' ), $post_type, 'normal', 'high' );
	}

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
						gl: '<?php echo timezone_location_get( new DateTimeZone( get_option( 'timezone_string' ) ) )['country_code']; ?>',
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

function aisee_tagsuggest() {
	return AISee_TagSuggest::get_instance();
}

aisee_tagsuggest();
