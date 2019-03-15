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
    }

}

function aisee() {
	return AISee::get_instance();
}

// Let's roll!
aisee();
