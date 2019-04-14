<?php
/**
 * Plugin Name: AISee SEO
 * Plugin URI:  https://converticacommerce.com
 * Description: Keyword visualization, tag cloud generator and LSI keyword helper.
 * Version:     1.0.0
 * Author:      Shivanand Sharma
 * Author URI:  https://www.converticacommerce.com
 * Text Domain: aisee
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

define( 'AISEEFILE', __FILE__ );


class AISee {
    function __construct(){
    }

    static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup();
			$instance->hooks();
			$instance->includes();
		}
		return $instance;
    }

    function setup(){
        $this->dir = trailingslashit( plugin_dir_path( AISEEFILE ) );
        $this->uri  = trailingslashit( plugin_dir_url(  AISEEFILE ) );
    }

    function includes(){
    }

    function hooks(){
        add_action( 'add_meta_boxes', array( $this,'add_meta_boxes' ) ); // add metaboxes
        add_action( 'admin_enqueue_scripts', array( $this, 'plugin_styles' ) ); // enqueue plugin styles but only on the specific screen
        add_action( 'wp_ajax_aisee_tag_cloud', array( $this, 'aisee_tag_cloud' )); // respond to ajax
        add_action( 'wp_ajax_nopriv_aisee_tag_cloud', '__return_false' ); // do not respont to ajax
    }

    function plugin_styles(){
        $screen = get_current_screen();
		if( in_array( $screen->post_type , get_post_types(array( 'public' => true) ) ) ) {
			wp_enqueue_style( 'aisee-stylesheet', $this->uri . 'assets/admin-styles.css' );
		}
    }

    function add_meta_boxes(){
        foreach (get_post_types(array( 'public' => true)) as $post_type) {
            add_meta_box( 'aisee-tag', __( 'AiSee Tag Cloud', 'aisee' ), array($this, 'aisee_tag_cloud_mb'), $post_type, 'normal');
        }
    }

    function stop_words(){
        return apply_filters('aisee_stop_words', array('I','I\'d','I\'ll','I\'m','I\'ve','a','about','above','after','again','against','all','am','an','and','any','are','aren\'t','as','at','be','because','been','before','being','below','between','both','but','by','can','can\'t','cannot','com','could','couldn\'t','did','didn\'t','do','does','doesn\'t','doing','don\'t','down','during','each','few','for','from','further','had','hadn\'t','has','hasn\'t','have','haven\'t','having','he','he\'d','he\'ll','he\'s','her','here','here\'s','hers','herself','him','himself','his','how','how\'s','i','i\'d','i\'ll','i\'m','i\'ve','if','in','into','is','isn\'t','it','it\'s','its','itself','let\'s','me','more','most','mustn\'t','my','myself','net','no','nor','not','of','off','on','once','only','or','org','other','ought','our','ours','ourselves','out','over','own','same','shan\'t','she','she\'d','she\'ll','she\'s','should','shouldn\'t','so','some','such','than','that','that\'s','the','their','theirs','them','themselves','then','there','there\'s','these','they','they\'d','they\'ll','they\'re','they\'ve','this','those','through','to','too','use','add','jan','feb','mar','apr','jun','jul','aug','sep','oct','nov','dec','under','until','up','very','was','wasn\'t','we','we\'d','we\'ll','we\'re','we\'ve','were','weren\'t','what','what\'s','when','when\'s','where','where\'s','which','while','who','who\'s','whom','why','why\'s','with','will','won\'t','would','wouldn\'t','www','you','you\'d','you\'ll','you\'re','you\'ve','your','yours','yourself','yourselves','http','https','io','get'));
    }

    function aisee_tag_cloud(){

        check_ajax_referer( 'aisee_tag_cloud', 'aisee_tag_cloud_nonce' );
        
        // Don't get caught pants down... again
        $post_id         = !empty($_REQUEST['post_id']) ? sanitize_text_field($_REQUEST['post_id']) : 0 ;
        $trimlen         = !empty($_REQUEST['trim']) ?  (int) sanitize_text_field($_REQUEST['trim']) : 0;
        $drop_percentage = !empty($_REQUEST['drop_percentage'])? sanitize_text_field($_REQUEST['drop_percentage']) : 0;
                    
        $status = get_post_status($post_id);
        
        if(! $status ) {
            wp_send_json_error( 'Post does not exist.' );
        }

        $url = false;
        if( $status == 'publish' ) {
            $url = get_permalink($post_id);
        }
        else {
            wp_send_json_error( 'Please publish this post to see the tag cloud.' );
        }

        $response = wp_safe_remote_request(
            $url
        );

        if(is_wp_error($response)) {
            wp_send_json_error( $response->get_error_message() );
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );

        if( 200 != $status_code ) {
            wp_send_json_error( 'Failed to fetch post content: ' . $status_code );
        }
        
        $keywords = wp_remote_retrieve_body( $response );
        
        if(!$keywords) {
            wp_send_json_error('Encountered empty content.' );
        }
        
        $stop     = $this->stop_words();
        $keywords = sanitize_text_field($keywords);

        $dom = new DOMDocument();
        $dom->loadHTML($keywords);

        $_scripts=$dom->getElementsByTagName("script");
        $scripts = array();
        foreach ($_scripts as $script) {
            $scripts[]=$script;
        }

        foreach ($scripts as $script) {
            $script->parentNode->removeChild($script);
        }

        $_styles=$dom->getElementsByTagName("style");

        $styles = array();
        foreach ($_styles as $style) {
            $styles[]=$style;
        }

        foreach ($styles as $style) {
            $style->parentNode->removeChild($style);
        }
        
        $keywords = $dom->saveHTML();
        $pattern ='/(&[a-zA-Z]+);/';
        
        $keywords = preg_replace($pattern,' ', $keywords);
        $keywords = urldecode($keywords);
        $keywords = strtolower(strip_tags($keywords));
        $keywords = preg_replace('/[^a-z0-9]/',' ', $keywords);
        $keywords = preg_replace('/\s+/',' ', $keywords);
        $keywords = explode(' ',$keywords);
        $keywords = array_diff($keywords, $stop);
        $tags     = array_count_values($keywords);

        $tags = array_filter($tags, function($v,$k){
            $k = preg_replace('/\b\d+\b/','',$k);
            if( $v == 1 ) { // remove words that occur only once; they have no weight
                return false;
            }
            if(strlen(trim($k)) <= $_REQUEST['trim'] ) {
                return false;
            }
            return ! empty( trim( $k ) );
        }, ARRAY_FILTER_USE_BOTH);
        
        arsort( $tags );

        $newtags = array();
        $avg     = ( max( $tags ) + min( $tags ) ) / 2;
        $avg     = $avg / 1.618;                     // fine-tune the scaling here
        
        foreach($tags as $key => $value) {
            if( ( $drop_percentage ) > ( ( $value * 100 ) / count( $tags ) )  ) {
                continue;
            }
            $newtags[] = '<span class="aitag" style="font-size:'. ( 16.81 * ( ( $value + $avg ) / $avg ) ).'px">'.$key.'</span>';
        }

        for( $i=0; $i <= 49 ; $i++ ){
            shuffle( $newtags );
        }
        arsort( $newtags );
        $newtags = implode( ' ', $newtags );
        
        wp_send_json_success( $newtags );
    }

    function aisee_tag_cloud_mb(){
        ?>
        <div id="aisee-tag-cloud"></div>
        <p><label><strong>Drop words with density less than this percentage :</strong><br /><input type="number" id="aisee_drop_percentage" value="0.2" min="0" max="1" step=".1" /></label><br />Increase this to see a smaller tag cloud; decreasing results in a larger tag cloud</p>
        <p><label><strong>Ignore words containing less than these many characters:</strong><br /><input type="number" id="aisee_trim_length" value="1" min="0" max="5" /></label></p>
        <?php
        submit_button( 'Generate Tag Cloud', 'secondary', 'aisee-generate-tag-cloud');
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('#aisee-generate-tag-cloud').click(function(e) {
                e.preventDefault();
                aisee_tag_cloud = {
                    aisee_tag_cloud_nonce: '<?php echo wp_create_nonce( 'aisee_tag_cloud' ); ?>',
                    action: "aisee_tag_cloud",
                    drop_percentage : $('#aisee_drop_percentage').val(),
                    trim : $('#aisee_trim_length').val(),
                    cachebust: Date.now(),
                    post_id : '<?php global $post; echo $post->ID; ?>',
                };
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: aisee_tag_cloud,
                    success: function (res) {
                        $('#aisee-tag-cloud').html(res);
                        console.dir(res);
                        if(res.hasOwnProperty('success') && res.success == true && res.hasOwnProperty('data') && res.data.length) {
                            $('#aisee-tag-cloud').html(res.data);
                            console.dir(res.data);
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

    function llog($str){
        echo '<pre>';
        print_r($str);
        echo '</pre>';
    }

}

function aisee() {
	return AISee::get_instance();
}

// Let's roll!
aisee();

