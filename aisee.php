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
	function __construct() {
	}

	static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			$instance->setup();
			$instance->includes();
			$instance->hooks();
		}
		return $instance;
	}

	function setup() {
		$this->dir = trailingslashit( plugin_dir_path( AISEEFILE ) );
		$this->uri = trailingslashit( plugin_dir_url( AISEEFILE ) );
	}

	function includes() {
		require_once $this->dir . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
		require_once $this->dir . 'includes' . DIRECTORY_SEPARATOR . 'gsc.php';
		require_once $this->dir . 'includes' . DIRECTORY_SEPARATOR . 'tagcloud.php';
		require_once $this->dir . 'includes' . DIRECTORY_SEPARATOR . 'tagcomplete.php';
	}

	function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) ); // add metaboxes
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_styles' ) ); // enqueue plugin styles but only on the specific screen
		add_action( 'admin_init', array( $this, 'plugin_data' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ), 0 );
	}

	function register_taxonomies() {
		$labels = array(
			'name'                       => 'AISee Terms',
			'singular_name'              => 'AISee Term',
			'search_items'               => 'Search AISee Terms',
			'popular_items'              => 'Popular AISee Terms',
			'all_items'                  => 'All AISee Terms',
			'parent_item'                => '',
			'parent_item_colon'          => '',
			'edit_item'                  => 'Edit AISee Term',
			'view_item'                  => 'View AISee Term',
			'update_item'                => 'Update AISee Term',
			'add_new_item'               => 'Add New AISee Term',
			'new_item_name'              => 'New AISee Term Name',
			'separate_items_with_commas' => 'Separate AISee terms with commas',
			'add_or_remove_items'        => 'Add or remove AISee terms',
			'choose_from_most_used'      => 'Choose from the most used AISee terms',
			'not_found'                  => 'No AISee terms found.',
			'no_terms'                   => 'No AISee terms',
			'items_list_navigation'      => 'AISee Terms list navigation',
			'items_list'                 => 'AISee Terms list',
			'most_used'                  => 'Most Used',
			'back_to_items'              => '&larr; Back to AISee Terms',
			'menu_name'                  => 'AISee Terms',
			'name_admin_bar'             => 'aisee_term',
			'archives'                   => 'All AISee Terms',
		);
		register_taxonomy(
			'aisee_term',
			'post',
			array(
				'hierarchical'          => false,
				'query_var'             => 'term',
				'labels'                => $labels,
				'rewrite'               => array( 'slug' => 'term' ),
				'public'                => true,
				'show_ui'               => true,
				'show_admin_column'     => true,
				'_builtin'              => false,
				'show_in_rest'          => true,
				'rest_base'             => 'terms',
				'rest_controller_class' => 'WP_REST_Terms_Controller',
			)
		);
		// register_taxonomy_for_object_type( 'aisee_term', 'post' );
	}

	function plugin_data() {
		if ( is_admin() ) {
			$this->plugin_data = get_plugin_data( AISEEFILE, false, false );
		}
	}

	function plugin_styles() {
		$screen = get_current_screen();
		if ( in_array( $screen->post_type, get_post_types( array( 'public' => true ) ) ) ) {
			wp_enqueue_style(
				'aisee-stylesheet',
				$this->uri . 'assets/admin-styles.css',
				array(), // $deps
				( is_user_logged_in() ? time() : false ),
				'all' // $media
			);
		}
	}

	function add_meta_boxes() {
		foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
			do_action( 'aisee_metaboxes', $post_type );
		}
	}

}

function aisee() {
	return AISee::get_instance();
}

// Let's roll!
aisee();
