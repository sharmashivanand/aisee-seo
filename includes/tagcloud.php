<?php

class AISee_TagCloud {

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
		add_action( 'aisee_mb', array( $this, 'aisee_cmb_tag_cloud' ) ); // add metaboxes
		add_action( 'wp_ajax_aisee_tag_cloud', array( $this, 'aisee_tag_cloud' ) ); // respond to ajax
        add_action( 'wp_ajax_nopriv_aisee_tag_cloud', '__return_false' ); // do not respont to ajax
        
        add_action( 'aisee_tabs', [ $this, 'tag_cloud_tab' ] );
    }
    
    function tag_cloud_tab($tabs){
        
        $tabs[] = array(
			'details' => array(
				'id'    => 'keyword_cloud',
				'icon'  => 'dashicons-star-filled',
				'title' => 'Keyword Cloud',

			),
			'fields'  => array(
                array(
                    'id' => AISEEPREFIX . 'drop_percentage',
                    'type' => 'text',
                    'name' => 'Drop Threshold',
                    'desc' => 'Drop words with density less than this percentage',
                    'attributes' => array(
                        'type' => 'number',
                        'pattern' => '\d*',
                        'min' => '0',
                        'max' => '1',
                        'step' => '0.1'
                    ),
                ),
                array(
                    'id' => AISEEPREFIX . 'trim_length',
                    'type' => 'text',
                    'name' => 'Minimum Characters',
                    'desc' => 'Ignore words containing less than these many characters',
                    'default' => '2',
                    'attributes' => array(
                        'type' => 'number',
                        'pattern' => '\d*',
                        'min' => '0',
                        'max' => '5',
                        'step' => '1',
                    ),
                ),
				array(
					'id'   => AISEEPREFIX . 'aisee_tag_cloud',
					'type' => 'aisee_ajax_control',
                    'name' => 'Generate Tag Cloud',
                    'show_names' => false,
				),
			),
		);
        return $tabs;
    }

	function add_meta_boxes( $post_type ) {
		add_meta_box( 'aisee-tag', __( 'AiSee Tag Cloud', 'aisee' ), array( $this, 'aisee_tag_cloud_mb' ), $post_type, 'normal', 'high' );
	}

	function aisee_cmb_tag_cloud( $cmb ) {
		$prefix = '_aisee_';

		// Regular text field
		$cmb->add_field(
			array(
				'name' => __( 'Register', 'cmb2' ),
				'desc' => __( 'You haven\'t registered yet', 'cmb2' ),
				'id'   => $prefix . 'register',
				'type' => 'aisee_ajax_control',
			// 'show_names' => false,
			// 'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
			// 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
			// 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
			// 'on_front'        => false, // Optionally designate a field to wp-admin only
			// 'repeatable'      => true,
			)
		);
	}

	function aisee_tag_cloud_mb() {
		?>
		<div id="aisee-tag-cloud"></div>
		<p><label><strong>Drop words with density less than this percentage :</strong><br /><input type="number" id="aisee_drop_percentage" value="0.2" min="0" max="1" step=".1" /></label><br />Increase this to see a smaller tag cloud; decreasing results in a larger tag cloud</p>
		<p><label><strong>Ignore words containing less than these many characters:</strong><br /><input type="number" id="aisee_trim_length" value="2" min="0" max="5" /></label></p>
		<?php
		echo '<p>';
		echo '<a href="#" class="button-primary aisee-btn large" id="aisee-generate-tag-cloud">Generate Tag Cloud</a>';
		echo '</p>';
		?>
		<script type="text/javascript">
		jQuery(document).ready(function ($) {
			$('#aisee-generate-tag-cloud').click(function(e) {
				e.preventDefault();
				$(this).addClass('aisee-btn-loading');
				aisee_tag_cloud = {
					aisee_tag_cloud_nonce: '<?php echo wp_create_nonce( 'aisee_tag_cloud' ); ?>',
					action: "aisee_tag_cloud",
					drop_percentage : $('#aisee_drop_percentage').val(),
					trim : $('#aisee_trim_length').val(),
					cachebust: Date.now(),
					post_id : '
					<?php
					global $post;
					echo $post->ID;
					?>
					',
				};
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: aisee_tag_cloud,
					success: function (res) {
						console.dir(res);
						$('#aisee-tag-cloud').html(res);
						$('#aisee-generate-tag-cloud').removeClass('aisee-btn-loading');
						if(res.hasOwnProperty('success') && res.success == true && res.hasOwnProperty('data') && res.data.length) {
							$('#aisee-tag-cloud').html(res.data);
							console.log(res.data);
						}
					},
					error: function (jqXHR, textStatus, errorThrown) {
						$('#aisee-tag-cloud').html(errorThrown);
					},
				});
			});
		});
		</script>
		<?php
	}

	function aisee_tag_cloud() {
		check_ajax_referer( 'aisee_tag_cloud', 'aisee_tag_cloud_nonce' );
		$post_id         = ! empty( $_REQUEST['post_id'] ) ? sanitize_text_field( $_REQUEST['post_id'] ) : 0;
		$trimlen         = ! empty( $_REQUEST['trim'] ) ? (int) sanitize_text_field( $_REQUEST['trim'] ) : 0;
		$drop_percentage = ! empty( $_REQUEST['drop_percentage'] ) ? sanitize_text_field( $_REQUEST['drop_percentage'] ) : 0;
		$status          = get_post_status( $post_id );
		if ( ! $status ) {
			wp_send_json_error( 'Post does not exist.' );
		}
		$url = false;
		if ( $status == 'publish' ) {
			$url = get_permalink( $post_id );
		} else {
			wp_send_json_error( 'Please publish this post to see the tag cloud.' );
		}
		$response = wp_safe_remote_request(
			$url
		);
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 != $status_code ) {
			wp_send_json_error( 'Failed to fetch post content: ' . $status_code );
		}
		$keywords = wp_remote_retrieve_body( $response );
		if ( ! $keywords ) {
			wp_send_json_error( 'Encountered empty content.' );
		}
		$keywords = sanitize_text_field( $keywords );
		$stop     = $this->stop_words();
		$dom      = new DOMDocument();
		$dom->loadHTML( $keywords );
		$_scripts = $dom->getElementsByTagName( 'script' );
		$scripts  = array();
		foreach ( $_scripts as $script ) {
			$scripts[] = $script;
		}
		foreach ( $scripts as $script ) {
			$script->parentNode->removeChild( $script );
		}
		$_styles = $dom->getElementsByTagName( 'style' );
		$styles  = array();
		foreach ( $_styles as $style ) {
			$styles[] = $style;
		}
		foreach ( $styles as $style ) {
			$style->parentNode->removeChild( $style );
		}
		$keywords = $dom->saveHTML();
		$pattern  = '/(&[a-zA-Z]+);/';
		$keywords = preg_replace( $pattern, ' ', $keywords );
		$keywords = urldecode( $keywords );
		$keywords = strtolower( strip_tags( $keywords ) );
		$keywords = preg_replace( '/[^a-z0-9]/', ' ', $keywords );
		$keywords = preg_replace( '/\s+/', ' ', $keywords );
		$keywords = explode( ' ', $keywords );
		$keywords = array_diff( $keywords, $stop );
		$tags     = array_count_values( $keywords );
		$tags     = array_filter(
			$tags,
			function( $v, $k ) use ( $trimlen ) {
				$k = preg_replace( '/\b\d+\b/', '', $k );
				if ( $v == 1 ) { // remove words that occur only once; they have no weight
					return false;
				}
				if ( strlen( trim( $k ) ) && strlen( trim( $k ) ) < $trimlen ) {
					return false;
				}
				return ! empty( trim( $k ) );
			},
			ARRAY_FILTER_USE_BOTH
		);
		arsort( $tags );
		$newtags = array();
		$avg     = ( max( $tags ) + min( $tags ) ) / 2;
		$avg     = $avg / 1.618;                     // fine-tune the scaling here
		foreach ( $tags as $key => $value ) {
			if ( ( $drop_percentage ) > ( ( $value * 100 ) / count( $tags ) ) ) {
				continue;
			}
			$newtags[] = '<span class="aitag" style="font-size:' . ( 16.81 * ( ( $value + $avg ) / $avg ) ) . 'px">' . $key . '</span>';
		}
		for ( $i = 0; $i <= 49; $i++ ) {
			shuffle( $newtags );
		}
		// arsort( $newtags );
		$newtags = implode( ' ', $newtags ); // Turn the array into a plain string. Only contains aplha-numeric chars.
		wp_send_json_success(
			wp_kses(
				$newtags,
				array(
					'span' => array(
						'style' => array(),
						'class' => array(),
					),
				)
			)
		);
	}


	function stop_words() {
		return apply_filters( 'aisee_stop_words', array( 'I', 'I\'d', 'I\'ll', 'I\'m', 'I\'ve', 'a', 'about', 'above', 'across', 'add', 'after', 'afterwards', 'again', 'against', 'all', 'almost', 'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'among', 'amongst', 'amoungst', 'amount', 'an', 'and', 'another', 'any', 'anyhow', 'anyone', 'anything', 'anyway', 'anywhere', 'apr', 'are', 'aren\'t', 'around', 'as', 'at', 'aug', 'back', 'be', 'became', 'because', 'become', 'becomes', 'becoming', 'been', 'before', 'beforehand', 'behind', 'being', 'below', 'beside', 'besides', 'between', 'beyond', 'bill', 'both', 'bottom', 'but', 'by', 'call', 'can', 'can\'t', 'cannot', 'cant', 'co', 'com', 'con', 'could', 'couldn\'t', 'couldnt', 'cry', 'de', 'dec', 'describe', 'detail', 'did', 'didn\'t', 'do', 'does', 'doesn\'t', 'doing', 'don\'t', 'done', 'down', 'due', 'during', 'each', 'eg', 'eight', 'either', 'eleven', 'else', 'elsewhere', 'empty', 'enough', 'etc', 'even', 'ever', 'every', 'everyone', 'everything', 'everywhere', 'except', 'feb', 'few', 'fifteen', 'fifty', 'fill', 'find', 'fire', 'first', 'five', 'for', 'former', 'formerly', 'forty', 'found', 'four', 'from', 'front', 'full', 'further', 'get', 'give', 'go', 'had', 'hadn\'t', 'has', 'hasn\'t', 'hasnt', 'have', 'haven\'t', 'having', 'he', 'he\'d', 'he\'ll', 'he\'s', 'hence', 'her', 'here', 'here\'s', 'hereafter', 'hereby', 'herein', 'hereupon', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'how\'s', 'however', 'http', 'https', 'hundred', 'i', 'i\'d', 'i\'ll', 'i\'m', 'i\'ve', 'ie', 'if', 'in', 'inc', 'indeed', 'interest', 'into', 'io', 'is', 'isn\'t', 'it', 'it\'s', 'its', 'itself', 'jan', 'jul', 'jun', 'keep', 'last', 'latter', 'latterly', 'least', 'less', 'let\'s', 'ltd', 'made', 'many', 'mar', 'may', 'me', 'meanwhile', 'might', 'mill', 'mine', 'more', 'moreover', 'most', 'mostly', 'move', 'much', 'must', 'mustn\'t', 'my', 'myself', 'name', 'namely', 'neither', 'net', 'never', 'nevertheless', 'next', 'nine', 'no', 'nobody', 'none', 'noone', 'nor', 'not', 'nothing', 'nov', 'now', 'nowhere', 'oct', 'of', 'off', 'often', 'on', 'once', 'one', 'only', 'onto', 'or', 'org', 'other', 'others', 'otherwise', 'ought', 'our', 'ours', 'ourselves', 'out', 'over', 'own', 'part', 'per', 'perhaps', 'please', 'put', 'rather', 're', 'same', 'see', 'seem', 'seemed', 'seeming', 'seems', 'sep', 'serious', 'several', 'shan\'t', 'she', 'she\'d', 'she\'ll', 'she\'s', 'should', 'shouldn\'t', 'show', 'side', 'since', 'sincere', 'six', 'sixty', 'so', 'some', 'somehow', 'someone', 'something', 'sometime', 'sometimes', 'somewhere', 'still', 'such', 'system', 'take', 'ten', 'than', 'that', 'that\'s', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'thence', 'there', 'there\'s', 'thereafter', 'thereby', 'therefore', 'therein', 'thereupon', 'these', 'they', 'they\'d', 'they\'ll', 'they\'re', 'they\'ve', 'thickv', 'thin', 'third', 'this', 'those', 'though', 'three', 'through', 'throughout', 'thru', 'thus', 'to', 'together', 'too', 'top', 'toward', 'towards', 'twelve', 'twenty', 'two', 'un', 'under', 'until', 'up', 'upon', 'us', 'use', 'very', 'via', 'was', 'wasn\'t', 'we', 'we\'d', 'we\'ll', 'we\'re', 'we\'ve', 'well', 'were', 'weren\'t', 'what', 'what\'s', 'whatever', 'when', 'when\'s', 'whence', 'whenever', 'where', 'where\'s', 'whereafter', 'whereas', 'whereby', 'wherein', 'whereupon', 'wherever', 'whether', 'which', 'while', 'whither', 'who', 'who\'s', 'whoever', 'whole', 'whom', 'whose', 'why', 'why\'s', 'will', 'with', 'within', 'without', 'won\'t', 'would', 'wouldn\'t', 'www', 'yet', 'you', 'you\'d', 'you\'ll', 'you\'re', 'you\'ve', 'your', 'yours', 'yourself', 'yourselves' ) );
	}

}

function aisee_tagcloud() {
	return AISee_TagCloud::get_instance();
}

aisee_tagcloud();
