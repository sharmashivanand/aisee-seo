<?php
/**
 * Plugin Name: AISee SEO
 * Plugin URI:  https://converticacommerce.com
 * Description: Keyword research and insights for SEOs. Get performance data from Google Search Console. Visalize content as a tag cloud.
 * Version:     2.1
 * Author:      Shivanand Sharma
 * Author URI:  https://www.converticacommerce.com
 * Text Domain: aisee
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

define( 'AISEEFILE', __FILE__ );
define( 'AISEEAPIEPSL', 'https://aiseeseo.com/?p=9' );

if(file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR. 'CMB2' . DIRECTORY_SEPARATOR . 'init.php' )) {
    require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR. 'CMB2' . DIRECTORY_SEPARATOR . 'init.php';
}
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
        add_action( 'admin_head', array( $this, 'admin_style' ) );
        add_action( 'admin_menu', array( $this, 'settings_menu' ) );

        add_action( 'cmb2_render_aisee_ajax_btn', [$this, 'aisee_ajax_btn'], 10, 5 );
        add_action( 'cmb2_admin_init', array( $this, 'metaboxes' ) );
    }

    function aisee_ajax_btn( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
        //echo $field_type_object->input( array( 'type' => 'email' ) );
        ?>
			<a class="button button-primary aisee_ajax_btn" style="user-select: none;" title="<?php esc_html_e( 'Insert Link', 'cmb' ); ?>">
	 			<span class="screen-reader-text"><?php esc_html_e( 'Choose Link', 'cmb' ); ?></span>Ola! How does it work?
	 		</a>
        <?php
    }

    function metaboxes($post_type = array()){
        $post_types = get_post_types( array( 'public' => true ) ) ;
        $prefix = '_yourprefix_';

        $cmb = new_cmb2_box( array(
            'id'            => 'aisee_mb',
            'title'         => 'AISee SEO',
            'object_types'  => array( $post_types ), // Post type
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true, // Show field names on the left
            // 'cmb_styles' => false, // false to disable the CMB stylesheet
            // 'closed'     => true, // Keep the metabox closed by default
        ) );
        do_action('aisee_mb', $cmb);
        //aisee_llog($cmb);
        //die();
    }

    function settings_menu(){
        add_menu_page('AISee', 'AISee', 'manage_options', 'aisee', array( $this, 'settings_page' ), $this->uri . 'assets/brand-icon.svg' );
        add_submenu_page( 'aisee',  'AISee Setup' ,  'AISee Setup' , 'manage_options', 'aisee-setup', array( $this, 'admin_page' ) );
    }

    function settings_page() {
        echo 'settings_page';
    }
    function admin_style(){ ?>
        <style type="text/css">
        .wp-has-submenu.toplevel_page_aisee .wp-menu-image img {
            width: 34px;
            height: auto;
            padding: 4px !important;
            animation: aihrot 10s ease-in-out infinite;
            opacity: 1 !important;
            box-sizing: border-box;
            filter: saturate(2);
        }

        @keyframes aihrot {
            0% {
                filter: hue-rotate(0deg);
            }
            10% {
                filter: hue-rotate(36deg);
            }
            20% {
                filter: hue-rotate(72deg);
            }
            30% {
                filter: hue-rotate(108deg);
            }
            40% {
                filter: hue-rotate(144deg);
            }
            50% {
                filter: hue-rotate(180deg);
            }
            60% {
                filter: hue-rotate(216deg);
            }
            70% {
                filter: hue-rotate(252deg);
            }
            80% {
                filter: hue-rotate(288deg);
            }
            90% {
                filter: hue-rotate(324deg);
            }
            100% {
                filter: hue-rotate(360deg);
            }
        }
        </style>
        <?php
    }
    
    function admin_page(){
        if ( ob_get_length() ) {
			ob_end_clean();
        }
        global $wp_version;
        ob_start();
        aisee_llog($this->plugin_data);
        $out = ob_get_clean();
        echo $out;
        exit;
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
