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
    }

    function settings_menu(){
        $hook_suffix = add_menu_page(
			'AISee SEO', 
			'AISee SEO', 
			'manage_options', 
			'aisee', 
            array( $this, 'settings_page' ),
            $this->uri . 'assets/brand-icon.svg'
        );
        add_submenu_page( 'wpmr', 'Run Scan', 'Run Scan', 'manage_options' , 'wpmr', array($this, 'settings_page'));
        //add_submenu_page( 'wpmr', 'Tools', 'Tools', 'manage_options' , 'wpmr-tools', array($this, 'wpmrtools'));
    }

    function settings_page(){
        ?>
        <div class="wrap">
        dhinchak!
        </div>
        <?php
    }

    function admin_style(){ ?>
        <style type="text/css">#toplevel_page_aisee .wp-menu-image img { width: 20px;height: auto;opacity: 1;padding: 9px 0 0 0;}</style>
        <?php
    }

}

function aisee() {
	return AISee::get_instance();
}

// Let's roll!
aisee();

