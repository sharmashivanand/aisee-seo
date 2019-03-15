<?php
/**
 * Plugin Name: AISee SEO
 * Plugin URI:  https://aiseeseo.com
 * Description: GSC Insights for content optimizers. Fetch SEO clicks, impressions, CTR and SERPS position of posts and pages from Google Search Console (Google Webmaster Tools)
 * Version:     1.0.0
 * Author:      Shivanand Sharma
 * Author URI:  https://www.converticacommerce.com
 * Text Domain: aisee
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

define( 'AISEEFILE', __FILE__ );
define( 'AISEEAPIEP', 'https://aiseeseo.com/?p=9' );

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
        add_action( 'admin_menu', array( $this, 'settings_menu' ) );
        add_action( 'admin_head', array( $this, 'admin_style' ) );
        add_action( 'admin_init', array($this, 'register_settings' ) );

    }

    function settings_menu(){
        $hook_suffix = add_menu_page(
			'AISee SEO', 
			'AISee SEO', 
			'manage_options', 
			'aiseeseo', 
            array( $this, 'settings_page' ),
            $this->uri . 'assets/brand-icon.svg'
        );
        add_submenu_page( 'wpmr', 'Run Scan', 'Run Scan', 'manage_options' , 'wpmr', array($this, 'settings_page'));
        //add_submenu_page( 'wpmr', 'Tools', 'Tools', 'manage_options' , 'wpmr-tools', array($this, 'wpmrtools'));
    }

    function defaults() {
        $defaults = array(
            'connection' => '',
        );
        return $defaults;
    }

    function register_settings(){
        register_setting( 'aiseeseo_group', 'aiseeseo', array( $this, 'sanitize' ));
        add_settings_section( 'aiseeseo_main', __( 'Main', 'aisee' ), array( $this,'main_section_text' ), 'aiseeseo' );
        add_settings_field( 'aiseeseo_connection', __('Connect', 'aisee' ), array( $this, 'connect' ), 'aiseeseo', 'aiseeseo_main' );
    }

    function main_section_text(){
        echo '<p>Please provide the following.</p>';
    }

    function connect(){
        $statevars = array(
            'origin_site_url' => get_site_url(),
            'origin_nonce' => wp_create_nonce( 'wprtsp_gaapi' ),
            'origin_ajaxurl' => admin_url( 'admin-ajax.php' ),
            'return_url' => admin_url('admin.php?page=aiseeseo')
        );
        $statevars = $this->encode($statevars);
        $auth = esc_url( add_query_arg( 'g_authenticate', $statevars, AISEEAPIEP ) );
        $revoke = esc_url( add_query_arg( 'g_revoke', $statevars, AISEEAPIEP ) );
        //$this->llog($statevars);
        //$this->llog($this->decode($statevars));

        ?>
        <input type="hidden" <?php echo $readonly ?> id="connect" name="aiseeseo['aiseeseo_connection']" value="<?php echo esc_attr( $this->get_setting('aiseeseo_connection')); ?>" />
        <?php
        if( ! $ga_profile) { 
            ?>
            <a class="button-primary" href="<?php echo $auth ?>">Connect with Google Services</a>
            <?php
        }
        else {
            ?>
            Profile Active: <?php echo $ga_profile; ?><br /><a href="<?php echo $revoke ?>" class="button-primary">Disconnect with Google Services</a>
            <?php
        }
    }

    function encode($data){
        $data = strtr( base64_encode( json_encode( $data ) ), '+/=', '-_,' );
        return $data;
    }

    function decode($data){
        return json_decode( base64_decode( strtr($data, '-_,', '+/=' ) ), true);
    }

    function get_setting( $setting ) {
        $defaults = $this->defaults();
        $settings = wp_parse_args( get_option( 'aiseeseo', $defaults ), $defaults );
        return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
    }

    function settings_page(){
        ?>
        <div class="wrap">
        <form method="post" action="options.php">
            <?php settings_fields( 'aiseeseo' ); ?>
            <?php do_settings_sections( 'aiseeseo' ); ?>
            <?php //submit_button(); ?>
        </form>
        </div>
        <?php
    }

    function sanitize( $settings ){
        return $settings;
    }

    function admin_style(){ ?>
        <style type="text/css">#toplevel_page_aiseeseo .wp-menu-image img { width: 20px; height: auto;opacity: 1;padding: 9px 0 0 0;}</style>
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

