<?php
/**
 * Plugin Name: AISee SEO
 * Plugin URI:  https://converticacommerce.com
 * Description: Keyword research and insights for SEOs. Get performance data from Google Search Console. Visalize content as a tag cloud.
 * Version:     2.2
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
			$instance->includes();
			$instance->hooks();
		}
		return $instance;
    }

    function setup(){
        $this->dir = trailingslashit( plugin_dir_path( AISEEFILE ) );
        $this->uri  = trailingslashit( plugin_dir_url(  AISEEFILE ) );
    }

    function includes(){
        require_once( $this->dir . 'includes' . DIRECTORY_SEPARATOR  . 'functions.php' );
        require_once( $this->dir . 'includes' . DIRECTORY_SEPARATOR  . 'gsc.php' );
        require_once( $this->dir . 'includes' . DIRECTORY_SEPARATOR  . 'tagcloud.php' );
        require_once( $this->dir . 'includes' . DIRECTORY_SEPARATOR  . 'tagcomplete.php' );
    }

    function hooks(){
        add_action( 'add_meta_boxes', array( $this,'add_meta_boxes' ) ); // add metaboxes
        add_action( 'admin_enqueue_scripts', array( $this, 'plugin_styles' ) ); // enqueue plugin styles but only on the specific screen
        add_action( 'admin_init', [$this, 'plugin_data']);
    }

    function plugin_data(){
        if(is_admin()){
            $this->plugin_data = get_plugin_data( AISEEFILE, false, false );
        }
    }

    function plugin_styles(){
        $screen = get_current_screen();
		if( in_array( $screen->post_type , get_post_types(array( 'public' => true) ) ) ) {
			wp_enqueue_style( 'aisee-stylesheet', $this->uri . 'assets/admin-styles.css' );
		}
    }
    
    function add_meta_boxes(){
        foreach (get_post_types(array( 'public' => true)) as $post_type) {
            do_action('aisee_metaboxes', $post_type);
        }
    }

}

function aisee() {
	return AISee::get_instance();
}

// Let's roll!
aisee();
