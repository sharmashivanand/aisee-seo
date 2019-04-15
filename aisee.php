<?php
/**
 * Plugin Name: AISee SEO
 * Plugin URI:  https://converticacommerce.com
 * Description: Keyword visualization, tag cloud generator and LSI keyword helper.
 * Version:     1.0.2
 * Author:      Shivanand Sharma
 * Author URI:  https://www.converticacommerce.com
 * Text Domain: aisee
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

define( 'AISEEFILE', __FILE__ );
define( 'AISEEAPIEPSL', 'https://aiseeseo.com/?p=9' );



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

        add_action( 'admin_init', array($this, 'save_gsc_profile' ));

        
        add_action( 'wp_ajax_aisee_register', array( $this, 'aisee_register' )); // respond to ajax
        add_action( 'wp_ajax_nopriv_aisee_register', '__return_false' ); // do not respont to ajax
        
        add_action( 'wp_ajax_aisee_get_connect_link', array( $this, 'get_connect_link' )); // respond to ajax
        add_action( 'wp_ajax_nopriv_get_connect_link', '__return_false' ); // do not respont to ajax
    }

    function save_gsc_profile(){
        if ( isset( $_REQUEST['aisee-action'] ) && $_REQUEST['aisee-action'] == 'oauth' ) {
            wp_verify_nonce( $_REQUEST['origin_nonce'], 'aisee_gscapi' );
            if( current_user_can('activate_plugins') &&
                !empty($_REQUEST['success']) &&
                $_REQUEST['success'] == 1
            ){
                //$this->llog($_REQUEST);
                $aisee_reg = get_option('aiseeseo');
                if( $aisee_reg ) {
                    $aisee_reg['gsc'] = true;
                    update_option( 'aiseeseo', $aisee_reg );
                    //$this->llog(get_option('aiseeseo'));
                }
            }
            else {  
            }
            //$this->llog($_REQUEST);
            //$this->llog(html_entity_decode( get_edit_post_link( sanitize_text_field( $_REQUEST['post'] ) ) ));
            //die();
            wp_redirect( html_entity_decode( get_edit_post_link( sanitize_text_field( $_REQUEST['post'] ) ) ), 302 );
            exit;
            //wp_redirect(  get_edit_post_link( sanitize_text_field( $_REQUEST['post'] ) ), 302 );
        }
        
        if ( isset( $_REQUEST['aisee-action'] ) && $_REQUEST['aisee-action'] == 'revoke' && isset( $_REQUEST['success'] ) && $_REQUEST['success'] == '1' ) {
            $aisee_reg = get_option('aiseeseo');
            if($aisee_reg && !empty($aisee_reg['gsc'])) {
                unset($aisee_reg['gsc']);
                update_option('aiseeseo', $aisee_reg);
            }
        }
    }

    function plugin_styles(){
        $screen = get_current_screen();
		if( in_array( $screen->post_type , get_post_types(array( 'public' => true) ) ) ) {
			wp_enqueue_style( 'aisee-stylesheet', $this->uri . 'assets/admin-styles.css' );
		}
    }
    
    function stop_words(){
        return apply_filters('aisee_stop_words', array('I','I\'d','I\'ll','I\'m','I\'ve','a','about','above','after','again','against','all','am','an','and','any','are','aren\'t','as','at','be','because','been','before','being','below','between','both','but','by','can','can\'t','cannot','com','could','couldn\'t','did','didn\'t','do','does','doesn\'t','doing','don\'t','down','during','each','few','for','from','further','had','hadn\'t','has','hasn\'t','have','haven\'t','having','he','he\'d','he\'ll','he\'s','her','here','here\'s','hers','herself','him','himself','his','how','how\'s','i','i\'d','i\'ll','i\'m','i\'ve','if','in','into','is','isn\'t','it','it\'s','its','itself','let\'s','me','more','most','mustn\'t','my','myself','net','no','nor','not','of','off','on','once','only','or','org','other','ought','our','ours','ourselves','out','over','own','same','shan\'t','she','she\'d','she\'ll','she\'s','should','shouldn\'t','so','some','such','than','that','that\'s','the','their','theirs','them','themselves','then','there','there\'s','these','they','they\'d','they\'ll','they\'re','they\'ve','this','those','through','to','too','use','add','jan','feb','mar','apr','jun','jul','aug','sep','oct','nov','dec','under','until','up','very','was','wasn\'t','we','we\'d','we\'ll','we\'re','we\'ve','were','weren\'t','what','what\'s','when','when\'s','where','where\'s','which','while','who','who\'s','whom','why','why\'s','with','will','won\'t','would','wouldn\'t','www','you','you\'d','you\'ll','you\'re','you\'ve','your','yours','yourself','yourselves','http','https','io','get'));
    }

    function add_meta_boxes(){
        foreach (get_post_types(array( 'public' => true)) as $post_type) {
            add_meta_box( 'aisee-gsc', __( 'AiSee Insights from Google&trade; Search Console', 'aisee' ), array($this, 'aisee_gsc_mb'), $post_type, 'normal', 'high');
            add_meta_box( 'aisee-tag', __( 'AiSee Tag Cloud', 'aisee' ), array($this, 'aisee_tag_cloud_mb'), $post_type, 'normal', 'high');
        }
    }

    function aisee_gsc_mb(){
        global $post;
        //delete_option( 'aiseeseo' );
        ?>
        <div class="aisee-updates">
            <?php
            if( ! $this->get_connectable_account()) {
                ?>
                <div id="is_unregistered">
                    <p><strong>Let's start setting up your AISee account to get search insights from Google&trade; Search Console.</strong></p><p><strong>Worry not, it's free and just takes a click!</strong></p>
                    <?php
                    $current_user = wp_get_current_user();
                    ?>
                    <div id="aisee_reg_form">
                        <label><strong>First name</strong> <input type="text" name="aisee_fn" id="aisee_fn" required value="<?php echo $current_user->user_firstname ?>" /></label>
                        <label><strong>Last name</strong> <input type="text" name="aisee_ln" id="aisee_ln" required value="<?php echo $current_user->user_lastname ?>" /></label>
                        <label><strong>Email</strong> <input type="email" name="aisee_eml" id="aisee_eml" required value="<?php echo $current_user->user_email ?>" /></label>
                        <label><strong>Site</strong> <input type="URL" readonly name="aisee_url" id="aisee_url" required value="<?php echo trailingslashit(site_url()); ?>" /></label>
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
                                //$('#is_unregistered').html('<p><strong>Your account is ready! Let\'s connect to Google&trade; Search Console.</strong></p>' + '<?php echo $button ?>');
                                get_connect_link = {
                                    get_connect_link_nonce: '<?php echo wp_create_nonce( 'get_connect_link' ); ?>',
                                    action: "aisee_get_connect_link",
                                    cachebust: Date.now(),
                                    post_id: <?php global $post; echo $post->ID ; ?>,
                                };
                                //console.log(get_connect_link);
                                
                                $.ajax({
                                    url: ajaxurl,
                                    method: 'POST',
                                    data: get_connect_link,
                                    
                                    complete: function(jqXHR, textStatus) {
                                        console.dir( jqXHR );
                                        if(jqXHR.hasOwnProperty('responseJSON') && jqXHR.responseJSON.hasOwnProperty('success') && jqXHR.responseJSON.success == true){
                                            response = jqXHR.responseJSON.data;
                                            $('#is_unregistered').html('<p><strong>Your account is ready! Let\'s connect to Google&trade; Search Console.</strong></p>' + response);
                                        }
                                        else{
                                            $('#reg_status').html('<p><strong>Error:</strong> ' + response + '</p>');
                                        }
                                    },
                                });
                                //location.reload(true);
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
        </div>
        <?php
        }
        else{
            if( ! $this->is_connected() ){
                echo '<p>';
                echo $this->connection_button();
                echo '</p>';
            }
            else { // we are set
                ?>
                <a href="" class="button primary button-primary">Fetch Data from Google&trade; Search Console</a>
                <?php
            }
        }
        $this->llog(get_option('aiseeseo'));
    }

    function is_connected(){
        return $this->get_setting('gsc');
    }

    function connection_button(){
        global $post;
        $id = get_edit_post_link($post->ID);
        $statevars = array(
            'site_url' => trailingslashit(get_site_url()),
            'return_url' => $id,
            'origin_nonce' => wp_create_nonce( 'aisee_gscapi' ),
            'origin_ajaxurl' => admin_url( 'admin-ajax.php' ),
        );
        $account = $this->get_connectable_account();
        if(!$account) {
           return;
        }

        $statevars = $this->encode(array_merge($account, $statevars));
        $auth = esc_url( add_query_arg( 'aisee_gsc_authenticate', $statevars, AISEEAPIEPSL ) );

        return '<a class="button-primary large" href="'.$auth.'">Connect with Google&trade; Search Console</a>';
    }

    function get_connect_link(){
        check_ajax_referer( 'get_connect_link', 'get_connect_link_nonce' );
        $id = !empty($_REQUEST['post_id']) ? get_edit_post_link( sanitize_text_field( $_REQUEST['post_id'] ) ) : false;
        if(!$id) {
            wp_send_json_error('Invalid post ID');
        }
        $statevars = array(
            'site_url' => trailingslashit(get_site_url()),
            'return_url' => $id,
            'origin_nonce' => wp_create_nonce( 'aisee_gscapi' ),
            'origin_ajaxurl' => admin_url( 'admin-ajax.php' ),
        );
        $account = $this->get_connectable_account();
        if(!$account) {
            wp_send_json_error('Account not setup');
        }

        $statevars = $this->encode(array_merge($account, $statevars));
        $auth = esc_url( add_query_arg( 'aisee_gsc_authenticate', $statevars, AISEEAPIEPSL ) );
        wp_send_json_success('<a class="button-primary large" href="'.$auth.'">Connect with Google&trade; Search Console</a>');
        //return $auth;
        //$revoke = esc_url( add_query_arg( 'aisee_gsc_revoke', $statevars, AISEEAPIEPSL ) );
    }

    function aisee_register(){
        //delete_option( 'aiseeseo' );
        check_ajax_referer( 'aisee_register', 'aisee_register_nonce' );
        if(empty($_REQUEST['user'])) {
            wp_send_json_error( 'Invalid details' );
        }
        $firstname = !empty($_REQUEST['user']['fn']) ? sanitize_text_field($_REQUEST['user']['fn']) : '' ;
        $lastname  = !empty($_REQUEST['user']['ln']) ? sanitize_text_field($_REQUEST['user']['ln']) : '' ;
        $useremail = !empty($_REQUEST['user']['email']) ? sanitize_text_field($_REQUEST['user']['email']) : '' ;
        if(empty($useremail)) {
            wp_send_json_error( 'Email missing' );
        }
        if ( ! filter_var($useremail, FILTER_VALIDATE_EMAIL)) {
            wp_send_json_error( 'Invalid email' );
        }
        $args = array(
            'user' => array(
                'fn' => $firstname,
                'ln' => $lastname,
                'email' => $useremail,
            ),
            'diag' => array (
                'site_url' => trailingslashit( site_url() ),
                'wp' => $wp_version,
                'plugin_version' => $this->plugin_data['Version'],
                'cachebust' => microtime()
            )
        );

        $args = $this->encode($args);
        $url = add_query_arg(
            'aisee_action',
            'aisee_register', 
            add_query_arg(
                'p',
                '9',
                add_query_arg('reg_details',$args, AISEEAPIEPSL)
                )
            );
        $response = wp_safe_remote_request(
            $url,
            array(
                'blocking' => true,
            )
        );
        if( is_wp_error($response) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        $response = wp_remote_retrieve_body($response);
        if(empty($response) || is_null($response)){
            wp_send_json_error( 'No response from AISee Server. Registration Failed.' );
        }
        $response = json_decode( $response, true );
        if(is_null($response)) {
            wp_send_json_error( 'Invalid server response.' );
        }
        if( isset( $response['success'] ) && $response['success'] == true) {
            if( !empty($response['data']['ID']) && !empty($response['data']['user_email']) ) {
                update_option( 'aiseeseo', $response['data'] ); // response['data] needs validation
                wp_send_json_success( 'Registration Succeeded.' );
            }
            else {
                wp_send_json_error( 'Invalid server response.' );
            }
        }
        if( isset( $data['success'] ) && $data['success'] != true) {
            if( isset($data['data']) ){
                wp_send_json_error( sanitize_text_field( $response['data'] ) );
            }
            else {
                wp_send_json_error( 'Unknown error occurred on the server.' );
            }
        }
    }

    function aisee_tag_cloud_mb(){
        ?>
        <div id="aisee-tag-cloud"></div>
        <p><label><strong>Drop words with density less than this percentage :</strong><br /><input type="number" id="aisee_drop_percentage" value="0.2" min="0" max="1" step=".1" /></label><br />Increase this to see a smaller tag cloud; decreasing results in a larger tag cloud</p>
        <p><label><strong>Ignore words containing less than these many characters:</strong><br /><input type="number" id="aisee_trim_length" value="2" min="0" max="5" /></label></p>
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
                        //console.dir(res);
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
    
    function aisee_tag_cloud(){

        check_ajax_referer( 'aisee_tag_cloud', 'aisee_tag_cloud_nonce' );
        
        // Don't get caught pants down... again
        $post_id         = !empty($_REQUEST['post_id']) ? sanitize_text_field( $_REQUEST['post_id'] ) : 0 ;
        $trimlen         = !empty($_REQUEST['trim']) ?  (int) sanitize_text_field( $_REQUEST['trim'] ) : 0;
        $drop_percentage = !empty($_REQUEST['drop_percentage'])? sanitize_text_field( $_REQUEST['drop_percentage'] ) : 0;
                    
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
        
        $keywords = sanitize_text_field($keywords);
        $stop     = $this->stop_words();

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
        $pattern  = '/(&[a-zA-Z]+);/';
        $keywords = preg_replace($pattern,' ', $keywords);
        $keywords = urldecode($keywords);
        $keywords = strtolower(strip_tags($keywords));
        $keywords = preg_replace('/[^a-z0-9]/',' ', $keywords);
        $keywords = preg_replace('/\s+/',' ', $keywords);
        $keywords = explode(' ',$keywords);
        $keywords = array_diff($keywords, $stop);
        $tags     = array_count_values($keywords);

        $tags = array_filter($tags, function($v,$k) use($trimlen) {
            $k = preg_replace('/\b\d+\b/','',$k);
            //if( $v == 1 ) { // remove words that occur only once; they have no weight
            //    return false;
            //}
            if(strlen(trim($k)) && strlen(trim($k)) < $trimlen ) {
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

        //arsort( $newtags );
        
        $newtags = implode( ' ', $newtags ); // Turn the array into a plain string. Only contains aplha-numeric chars.
        
        wp_send_json_success( wp_kses($newtags, array('span' => array('style'=>array(),'class'=>array()))) );
    }

    function get_setting( $setting ) {
        $defaults = $this->defaults();
        $settings = wp_parse_args( get_option( 'aiseeseo', $defaults ), $defaults );
        return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
    }

    function defaults() {
        //$defaults = array(
        //    'connection' => '',
        //);
        $defaults = array();
        return $defaults;
    }

    function sanitize( $settings ){
        return $settings;
    }

    function get_connectable_account(){
        return get_option('aiseeseo');
    }
    
    function encode($data){
        $data = strtr( base64_encode( json_encode( $data ) ), '+/=', '-_,' );
        return $data;
    }

    function decode($data){
        return json_decode( base64_decode( strtr($data, '-_,', '+/=' ) ), true);
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

