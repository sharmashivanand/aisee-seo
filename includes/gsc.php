<?php

class AISee_GSC{
    static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->hooks();
		}
		return $instance;
    }

    function hooks(){
        //add_action( 'aisee_metaboxes', array( $this,'add_meta_boxes' ) ); // add metaboxes
        
        add_action( 'admin_init', array($this, 'save_gsc_profile' ));

        add_action( 'wp_ajax_aisee_register', array( $this, 'aisee_register' )); // respond to ajax
        add_action( 'wp_ajax_nopriv_aisee_register', '__return_false' ); // do not respont to ajax

        add_action( 'wp_ajax_aisee_gsc_fetch', array( $this, 'aisee_gsc_fetch' )); // respond to ajax
        add_action( 'wp_ajax_nopriv_aisee_gsc_fetch', '__return_false' ); // do not respont to ajax
        
        add_action( 'wp_ajax_aisee_get_connect_link', array( $this, 'get_connect_link' )); // respond to ajax
        add_action( 'wp_ajax_nopriv_get_connect_link', '__return_false' ); // do not respont to ajax
    }

    function add_meta_boxes($post_type) {
        add_meta_box( 'aisee-gsc', __( 'AiSee Insights from Google&trade; Search Console', 'aisee' ), array($this, 'aisee_gsc_mb'), $post_type, 'normal', 'high');
    }

    function aisee_gsc_mb(){
        //delete_option('aiseeseo');
        global $post;
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
        </div>
        <?php
        }
        else{
            
            if( ! aisee_is_connected() ){
                echo '<p>';
                echo '<a class="button-primary large aisee-btn" id="aisee_gsc_authenticate" onclick="window.top.location.href = this.getAttribute(\'data-href\')" data-href="'.$this->get_oauth_link($post->ID, 'aisee_gsc_authenticate').'">Connect with Google&trade; Search Console</a>';
                echo '</p>';
            }
            else { // we are set
                $meta = get_post_meta($post->ID, '_aisee_keywords', true);
                $html = '';
                if($meta) {
                    $html = $this->generate_html($meta);
                }
                echo '<div id="aisee_gsc_keywords">'.$html.'</div><p>';
                echo '<a class="button-primary large aisee-btn" id="aisee_gsc_fetch" href="#">Fetch Data from Google&trade; Search Console</a>';
                echo '</p>';
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function ($) { //wrapper
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
            }
        }
    }

    function aisee_gsc_fetch(){
        check_ajax_referer( 'aisee_gsc_fetch', 'aisee_gsc_fetch_nonce' );
        $id = sanitize_text_field($_REQUEST['postid']);
        $url = $this->get_oauth_link($id, 'aisee_gsc_fetch');
        $url = add_query_arg( 'cb', time() , $url );
        $url = add_query_arg( 'status', aisee_get_status(), $url );
        if(get_post_status($id) != 'publish'){
            wp_send_json_success('Post is not published or is not public.');
        }
        $meta = get_post_meta($id, '_aisee_keywords', true);
        if($meta) {
            if( (time() - strtotime($meta['time'])) >= (86400 * 15) ) {
                $meta = false;
            }
        }
        if( ! $meta ){
            $args = array(
                'httpversion' => '1.1',
                'compress' => true,
                'headers' => array(
                    'aisee-gsc-fetch' => true
                ),
            );
            $response = wp_safe_remote_request(
                $url,
                $args
            );
            if( is_wp_error($response) ) {
                wp_send_json_error( $response->get_error_message() );
            }
            $status_code = wp_remote_retrieve_response_code( $response );
            if( 200 != $status_code ) {
                wp_send_json_error( 'Failed to fetch response from AISee service. Error Code: ' . $status_code );
            }
            $response = wp_remote_retrieve_body($response);
            if(empty($response) || is_null($response)){
                wp_send_json_error( 'Empty server respose.' );
            }
            $response = json_decode( $response, true );
            if(is_null($response)) { // NULL if the json cannot be decoded / data is deeper than recursion limit. OR no data exists
                wp_send_json_error( 'Invalid server response.' );
            }
            if( isset( $response['success'] ) && $response['success'] == true) {
                if( ! empty( $response['data'] ) ) {
                    $meta = array('time' => time(), 'keywords' => $response['data']);
                    update_post_meta($id, '_aisee_keywords', $meta);
                    $html = $this->generate_html($meta);
                    wp_send_json_success( $html );
                }
                else {
                    wp_send_json_success( 'No keywords yet.' );
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
        else {
            $html = $this->generate_html($meta);
            wp_send_json_success( $html );
        }
    }

    function generate_html($meta){
        $html = '';
        $keywords = !empty($meta['keywords'])? $meta['keywords']: false;
        if(!$keywords) {
            return;
        }
        if(count($keywords)) {
            foreach($keywords as $key => $value) {
                $html .= '<tr><td>'.$value['keys'].'</td><td>'.$value['clicks'].'</td><td>'.round( (100 * $value['ctr']), 2 ).'%</td><td>'.$value['impressions'].'</td><td>'.round($value['position'], 2).'</td></tr>';
            }
            $html = '<table id="aisee_gsc_keywords_tbl"><thead><tr><th>Keyword Phrase</th><th>Clicks</th><th>CTR</th><th>Impressions</th><th>Position</th></tr></thead>' . $html . '</table>';
        }
        else {
            $html = '<table id="aisee_gsc_keywords_tbl"><thead><tr><th>Keyword Phrase</th><th>Clicks</th><th>CTR</th><th>Impressions</th><th>Position</th></tr></thead><tr><td colspan="4">No keywords found</td></tr></table>';
        }
        if(!empty($meta['time'])) {
            $fetched = date( get_option('date_format') .' '. get_option('time_format') , $meta['time']) ;
            if($fetched) {
                $html = '<p id="aisee_fetched_on">Fetched On ' . $fetched . '.</p>' . $html . '<p id="aisee_fetched_on_notice">The data is refreshed every 15 days.</p>';
            }
        }
        $html = wp_kses( $html, 
            array(
                'p' => array(
                    'id'=>array(),
                    'class'=>array()
                ),
                'table' => array(
                    'id'=>array(),
                    'class'=>array(),
                ),
                'thead' => array(
                    'id'=>array(),
                    'class'=>array(),
                ),
                'tbody' => array(
                    'id'=>array(),
                    'class'=>array(),
                ),
                'tr' => array(
                    'id'=>array(),
                    'class'=>array(),
                ),
                'th' => array(
                    'id'=>array(),
                    'class'=>array(),
                ),
                'td' => array(
                    'id'=>array(),
                    'class'=>array(),
                ),
                'tfoot' => array(
                    'id'=>array(),
                    'class'=>array(),
                ),
            )
        );
        return $html;
    }

    function save_gsc_profile(){
        if ( isset( $_REQUEST['aisee-action'] ) && $_REQUEST['aisee-action'] == 'oauth' ) {
            wp_verify_nonce( $_REQUEST['origin_nonce'], 'aisee_gscapi' );
            if( current_user_can('activate_plugins') &&
                !empty($_REQUEST['success']) &&
                $_REQUEST['success'] == 1
            ){
                $aisee_reg = get_option('aiseeseo');
                if( $aisee_reg ) {
                    $aisee_reg['gsc'] = true;
                    update_option( 'aiseeseo', $aisee_reg );
                }
            }
            else {  
            }
            wp_redirect( html_entity_decode( get_edit_post_link( sanitize_text_field( $_REQUEST['post'] ) ) ), 302 );
            exit;
        }
        
        if ( isset( $_REQUEST['aisee-action'] ) && $_REQUEST['aisee-action'] == 'revoke' && isset( $_REQUEST['success'] ) && $_REQUEST['success'] == '1' ) {
            $aisee_reg = get_option('aiseeseo');
            if($aisee_reg && !empty($aisee_reg['gsc'])) {
                unset($aisee_reg['gsc']);
                update_option('aiseeseo', $aisee_reg);
            }
        }
    }

    function get_oauth_link($id, $action = false){
        $statevars = array(
            'site_url' => trailingslashit(get_site_url()),
            'return_url' => get_edit_post_link($id),
            'permalink' => get_permalink($id),
            'origin_nonce' => wp_create_nonce( 'aisee_gscapi' ),
            'origin_ajaxurl' => admin_url( 'admin-ajax.php' ),
        );
        $account = $this->get_connectable_account();
        if(!$account) {
           return;
        }
        $statevars = aisee_encode(array_merge($account, $statevars));
        $auth = add_query_arg( $action, $statevars, AISEEAPIEPSL );
        $auth = add_query_arg( 'aisee_action', $action, $auth );
        return $auth;
        switch($action){
            case 'aisee_gsc_authenticate':
                return '<a class="button-primary large aisee-btn" id="'.$action.'" href="'.$auth.'">Connect with Google&trade; Search Console</a>';
            case 'aisee_gsc_fetch':
                return '<a class="button-primary large aisee-btn" id="'.$action.'" data-href="'.$auth.'">Fetch Data From Google&trade; Search Console</a>';
        }
    }

    function get_connect_link(){
        check_ajax_referer( 'get_connect_link', 'get_connect_link_nonce' );
        $id = !empty($_REQUEST['post_id']) ? sanitize_text_field( $_REQUEST['post_id'] ) : false;
        if(!$id) {
            wp_send_json_error('Invalid post ID');
        }
        $account = $this->get_connectable_account();
        if(!$account) {
            wp_send_json_error('Account not setup');
        }
        $auth = $this->get_oauth_link($id, 'aisee_gsc_authenticate');
        wp_send_json_success('<a class="button-primary large" data-href="'.$auth.'" onclick="window.top.location.href = this.getAttribute(\'data-href\')" >Connect with Google&trade; Search Console</a>');
    }

    function aisee_register(){
        check_ajax_referer( 'aisee_register', 'aisee_register_nonce' );
        global $wp_version;
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
                'plugin_version' => aisee()->plugin_data['Version'],
                'cachebust' => microtime()
            )
        );
        $args = aisee_encode($args);
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
            $url
        );
        if( is_wp_error($response) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        $status_code = wp_remote_retrieve_response_code( $response );
        if( 200 != $status_code ) {
            wp_send_json_error( 'Failed to fetch response from AISee service. Error Code: ' . $status_code );
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
                wp_send_json_success( $this->get_oauth_link(sanitize_text_field($_REQUEST['postid']), 'aisee_gsc_authenticate') );
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

    function get_connectable_account(){
        return get_option('aiseeseo');
    }
}

function aisee_gsc(){
    return AISee_GSC::get_instance();
}

aisee_gsc();
