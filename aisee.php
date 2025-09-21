<?php
/**
 * Plugin Name: AISee SEO
 * Plugin URI:  https://converticacommerce.com
 * Description: Keyword research and insights for SEOs. Get performance data from Google Search Console. Visalize content as a tag cloud.
 * Version:     2.3
 * Author:      Shivanand Sharma
 * Author URI:  https://www.converticacommerce.com
 * Text Domain: aisee
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

define( 'AISEEFILE', __FILE__ );
define( 'AISEEAPIEPSL', 'https://aiseeseo.com/?p=9' );

// Include admin trait before class definition
require_once plugin_dir_path( AISEEFILE ) . 'includes' . DIRECTORY_SEPARATOR . 'helpers.php';
require_once plugin_dir_path( AISEEFILE ) . 'includes' . DIRECTORY_SEPARATOR . 'admin.php';

class AISee {
	use Aisee_Helpers;
	use AISeeAdmin;

	/**
	 * Constructor for the AISee class.
	 *
	 * Initializes the plugin instance.
	 *
	 * @since 2.3
	 */
	function __construct() {
	}

	/**
	 * Get the singleton instance of the AISee class.
	 *
	 * Creates a new instance if one doesn't exist, sets up the plugin,
	 * includes necessary files, and initializes hooks.
	 *
	 * @since 2.3
	 * @return AISee The singleton instance of the AISee class.
	 */
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

	/**
	 * Set up plugin paths and URLs.
	 *
	 * Initializes the plugin directory path and URL properties
	 * that are used throughout the plugin.
	 *
	 * @since 2.3
	 */
	function setup() {
		$this->dir = trailingslashit( plugin_dir_path( AISEEFILE ) );
		$this->uri = trailingslashit( plugin_dir_url( AISEEFILE ) );
	}

	/**
	 * Include required plugin files.
	 *
	 * Loads all necessary plugin component files including GSC integration,
	 * tag cloud functionality, tag completion, and CLI commands.
	 *
	 * @since 2.3
	 */
	function includes() {
		require_once $this->dir . 'includes' . DIRECTORY_SEPARATOR . 'gsc.php';
		require_once $this->dir . 'includes' . DIRECTORY_SEPARATOR . 'tagcloud.php';
		require_once $this->dir . 'includes' . DIRECTORY_SEPARATOR . 'tagcomplete.php';
		require_once $this->dir . 'includes' . DIRECTORY_SEPARATOR . 'cli.php';
	}

	/**
	 * Initialize WordPress hooks and actions.
	 *
	 * Sets up all the necessary WordPress hooks, actions, and filters
	 * for the plugin functionality including admin initialization,
	 * metaboxes, taxonomies, and custom columns.
	 *
	 * @since 2.3
	 */
	function hooks() {
		// Initialize admin functionality
		if ( is_admin() ) {
			$this->init_admin();
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) ); // add metaboxes
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_styles' ) ); // enqueue plugin styles but only on the specific screen
		add_action( 'admin_init', array( $this, 'plugin_data' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ), 0 );

		add_filter( 'manage_post_posts_columns', array( $this, 'set_custom_edit_post_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'custom_post_column' ), 10, 2 );
		add_filter( 'manage_page_posts_columns', array( $this, 'set_custom_edit_post_columns' ) );
		add_action( 'manage_page_posts_custom_column', array( $this, 'custom_post_column' ), 10, 2 );

		add_action( 'admin_footer', array( $this, 'aisee_debug' ) );
	}

	/**
	 * Customize the columns displayed in the posts/pages admin list table.
	 *
	 * Removes default taxonomy columns and adds a custom 'Link Recommendations'
	 * column to show related content suggestions based on AISee keyword analysis.
	 *
	 * @since 2.3
	 * @param array $columns The existing columns array.
	 * @return array Modified columns array with custom columns added.
	 */
	function set_custom_edit_post_columns( $columns ) {
		// unset( $columns['author'] );
		// if ( ! empty( $columns['taxonomy-aisee_tag'] ) ) {
			unset( $columns['taxonomy-aisee_tag'] );
		// }
		// if ( ! empty( $columns['tags'] ) ) {
			unset( $columns['tags'] );
		// }
		$columns['link_recommendations'] = 'Link Recommendations';
		// $columns['publisher'] = __( 'Publisher', 'your_text_domain' );

		return $columns;
	}

	/**
	 * Display content for custom post columns.
	 *
	 * Handles the display of custom column content, specifically the
	 * 'link_recommendations' column which shows related posts based on
	 * keyword analysis and Levenshtein distance calculations.
	 *
	 * @since 2.3
	 * @param string $column The name of the column to display.
	 * @param int    $post_id The ID of the current post.
	 */
	function custom_post_column( $column, $post_id ) {
		switch ( $column ) {

			case 'link_recommendations':
				$phrases = get_post_meta( $post_id, '_aisee_keywords', true );
				// $this->llog( $phrases );
				if ( ( ! empty( $phrases ) ) && ( ! empty( $phrases['keywords'] ) ) && count( $phrases['keywords'] ) ) {
					// trigger_error( '$post_id:' . $post_id );
					// trigger_error( 'count($phrases[keywords]): ' . count( $phrases['keywords'] ) );
					// trigger_error( 'empty($phrases[keywords]): ' . empty( $phrases['keywords'] ) );
					$phrases = $phrases['keywords'];
					// echo '<pre>' . print_r( $phrases, 1 ) . '</pre>';
					$data = array();
					foreach ( $phrases as $k => $v ) {
						$data[ $v['keys'] ] = $v['impressions'];
					}
					arsort( $data );
					$tags = array();
					foreach ( $data as $k => $v ) {
						$tags = array_merge( $tags, explode( ' ', $k ) );
					}

					$tags = array_map( 'sanitize_text_field', $tags );
					$tags = array_map(
						function ( $a ) {
							return preg_replace( '/[\W_]+/u', ' ', $a );
						},
						$tags
					);
					$tags = array_filter( $tags );

					$tags = array_unique( $tags );
					$tags = preg_split( '/[\s,]+/', implode( ',', $tags ) );
					$tags = array_filter(
						$tags,
						function ( $v ) {
							return ( strlen( (string) $v ) > 1 );
						}
					);
					$tags = array_diff( $tags, $this->stop_words() );
					$tags = array_map( 'strtolower', $tags );
					// $this->llog($tags);
					$query = new WP_Query(
						array(
							'post__not_in' => array( $post_id ),
							'post_type'    => array(
								'post',
								'page',
							),
							'tax_query'    => array(
								array(
									'taxonomy' => 'aisee_tag',
									'field'    => 'slug',
									'terms'    => $tags,
								),
							),
						)
					);
					if ( $query->have_posts() ) {
						$recommendations = array();
						echo '<ul>';
						while ( $query->have_posts() ) {
							$query->the_post();
							$terms       = get_the_terms( get_the_ID(), 'aisee_tag' );
							$post_aitags = array();
							foreach ( $terms as $term ) {
								$post_aitags[] = $term->name;
							}
							$post_aitags = implode( ' ', $post_aitags );
							foreach ( $data as $k => $v ) {
								$levscore = levenshtein( $k, $post_aitags, 1, 1, 1 );
							}
							$recommendations[ '<li>[' . $levscore . '] <a title="Edit in New Tab" target="_blank" href="' . get_edit_post_link() . '">' . get_the_title() . '</a></li>' ] = $levscore;
						}
						asort( $recommendations );
						echo implode( '', array_keys( $recommendations ) );
						echo '</ul>';
						echo '<p>Queried: ' . implode( ', ', $tags ) . '</p>';
					} else {
						$this->llog( 'Nothing found for: ' . implode( ' ', $tags ) );
					}
					wp_reset_postdata();
					// echo '<pre>' . print_r( $tags, 1 ) . '</pre>';
					// echo '<pre>' . print_r( $q, 1 ) . '</pre>';
				}
				break;
		}
	}

	/**
	 * Get the list of stop words to filter out during keyword analysis.
	 *
	 * Returns an array of common English stop words that should be excluded
	 * from keyword analysis and tag generation. The list can be filtered
	 * using the 'aisee_stop_words' filter.
	 *
	 * @since 2.3
	 * @return array Array of stop words to be filtered out.
	 */
	function stop_words() {
		return apply_filters( 'aisee_stop_words', array( 'I', 'I\'d', 'I\'ll', 'I\'m', 'I\'ve', 'a', 'about', 'above', 'across', 'add', 'after', 'afterwards', 'again', 'against', 'all', 'almost', 'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'among', 'amongst', 'amoungst', 'amount', 'an', 'and', 'another', 'any', 'anyhow', 'anyone', 'anything', 'anyway', 'anywhere', 'apr', 'are', 'aren\'t', 'around', 'as', 'at', 'aug', 'back', 'be', 'became', 'because', 'become', 'becomes', 'becoming', 'been', 'before', 'beforehand', 'behind', 'being', 'below', 'beside', 'besides', 'between', 'beyond', 'bill', 'both', 'bottom', 'but', 'by', 'call', 'can', 'can\'t', 'cannot', 'cant', 'co', 'com', 'con', 'could', 'couldn\'t', 'couldnt', 'cry', 'de', 'dec', 'describe', 'detail', 'did', 'didn\'t', 'do', 'does', 'doesn\'t', 'doing', 'don\'t', 'done', 'down', 'due', 'during', 'each', 'eg', 'eight', 'either', 'eleven', 'else', 'elsewhere', 'empty', 'enough', 'etc', 'even', 'ever', 'every', 'everyone', 'everything', 'everywhere', 'except', 'feb', 'few', 'fifteen', 'fifty', 'fill', 'find', 'fire', 'first', 'five', 'for', 'former', 'formerly', 'forty', 'found', 'four', 'from', 'front', 'full', 'further', 'get', 'give', 'go', 'had', 'hadn\'t', 'has', 'hasn\'t', 'hasnt', 'have', 'haven\'t', 'having', 'he', 'he\'d', 'he\'ll', 'he\'s', 'hence', 'her', 'here', 'here\'s', 'hereafter', 'hereby', 'herein', 'hereupon', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'how\'s', 'however', 'http', 'https', 'hundred', 'i', 'i\'d', 'i\'ll', 'i\'m', 'i\'ve', 'ie', 'if', 'in', 'inc', 'indeed', 'interest', 'into', 'io', 'is', 'isn\'t', 'it', 'it\'s', 'its', 'itself', 'jan', 'jul', 'jun', 'keep', 'last', 'latter', 'latterly', 'least', 'less', 'let\'s', 'ltd', 'made', 'many', 'mar', 'may', 'me', 'meanwhile', 'might', 'mill', 'mine', 'more', 'moreover', 'most', 'mostly', 'move', 'much', 'must', 'mustn\'t', 'my', 'myself', 'name', 'namely', 'neither', 'net', 'never', 'nevertheless', 'next', 'nine', 'no', 'nobody', 'none', 'noone', 'nor', 'not', 'nothing', 'nov', 'now', 'nowhere', 'oct', 'of', 'off', 'often', 'on', 'once', 'one', 'only', 'onto', 'or', 'org', 'other', 'others', 'otherwise', 'ought', 'our', 'ours', 'ourselves', 'out', 'over', 'own', 'part', 'per', 'perhaps', 'please', 'put', 'rather', 're', 'same', 'see', 'seem', 'seemed', 'seeming', 'seems', 'sep', 'serious', 'several', 'shan\'t', 'she', 'she\'d', 'she\'ll', 'she\'s', 'should', 'shouldn\'t', 'show', 'side', 'since', 'sincere', 'six', 'sixty', 'so', 'some', 'somehow', 'someone', 'something', 'sometime', 'sometimes', 'somewhere', 'still', 'such', 'system', 'take', 'ten', 'than', 'that', 'that\'s', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'thence', 'there', 'there\'s', 'thereafter', 'thereby', 'therefore', 'therein', 'thereupon', 'these', 'they', 'they\'d', 'they\'ll', 'they\'re', 'they\'ve', 'thickv', 'thin', 'third', 'this', 'those', 'though', 'three', 'through', 'throughout', 'thru', 'thus', 'to', 'together', 'too', 'top', 'toward', 'towards', 'twelve', 'twenty', 'two', 'un', 'under', 'until', 'up', 'upon', 'us', 'use', 'very', 'via', 'was', 'wasn\'t', 'we', 'we\'d', 'we\'ll', 'we\'re', 'we\'ve', 'well', 'were', 'weren\'t', 'what', 'what\'s', 'whatever', 'when', 'when\'s', 'whence', 'whenever', 'where', 'where\'s', 'whereafter', 'whereas', 'whereby', 'wherein', 'whereupon', 'wherever', 'whether', 'which', 'while', 'whither', 'who', 'who\'s', 'whoever', 'whole', 'whom', 'whose', 'why', 'why\'s', 'will', 'with', 'within', 'without', 'won\'t', 'would', 'wouldn\'t', 'www', 'yet', 'you', 'you\'d', 'you\'ll', 'you\'re', 'you\'ve', 'your', 'yours', 'yourself', 'yourselves' ) );
	}

	/**
	 * Register custom taxonomies for AISee functionality.
	 *
	 * Registers two custom taxonomies:
	 * - 'aisee_term': For storing keyword phrases from Google Search Console
	 * - 'aisee_tag': For storing individual words/tags extracted from keywords
	 *
	 * Both taxonomies are non-hierarchical and support REST API integration.
	 *
	 * @since 2.3
	 */
	function register_taxonomies() {
		$term_labels = array(
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
				'labels'                => $term_labels,
				'rewrite'               => array(
					'slug'       => 'term',
					'with_front' => true,
				),
				'public'                => true,
				'show_ui'               => true,
				'show_admin_column'     => true,
				'_builtin'              => false,
				'show_in_rest'          => true,
				'rest_base'             => 'terms',
				'rest_controller_class' => 'WP_REST_Terms_Controller',
				'show_tagcloud'         => true,
			)
		);

		$tag_labels = array(
			'name'                       => 'AISee Tags',
			'singular_name'              => 'AISee Tag',
			'search_items'               => 'Search AISee Tags',
			'popular_items'              => 'Popular AISee Tags',
			'all_items'                  => 'All AISee Tags',
			'parent_item'                => '',
			'parent_item_colon'          => '',
			'edit_item'                  => 'Edit AISee Tag',
			'view_item'                  => 'View AISee Tag',
			'update_item'                => 'Update AISee Tag',
			'add_new_item'               => 'Add New AISee Tag',
			'new_item_name'              => 'New AISee Tag Name',
			'separate_items_with_commas' => 'Separate AISee tags with commas',
			'add_or_remove_items'        => 'Add or remove AISee tags',
			'choose_from_most_used'      => 'Choose from the most used AISee tags',
			'not_found'                  => 'No AISee tags found.',
			'no_tags'                    => 'No AISee tags',
			'items_list_navigation'      => 'AISee Tags list navigation',
			'items_list'                 => 'AISee Tags list',
			'most_used'                  => 'Most Used',
			'back_to_items'              => '&larr; Back to AISee Tags',
			'menu_name'                  => 'AISee Tags',
			'name_admin_bar'             => 'aisee_tag',
			'archives'                   => 'All AISee Tags',
		);

		register_taxonomy(
			'aisee_tag',
			'post',
			array(
				'hierarchical'          => false,
				'query_var'             => 'aitag',
				'labels'                => $tag_labels,
				'rewrite'               => array(
					'slug'       => 'aitag',
					'with_front' => true,
				),
				'public'                => true,
				'show_ui'               => true,
				'show_admin_column'     => true,
				'_builtin'              => false,
				'show_in_rest'          => true,
				'rest_base'             => 'aitags',
				'rest_controller_class' => 'WP_REST_Terms_Controller',
				'show_tagcloud'         => true,
			)
		);
		// register_taxonomy_for_object_type( 'aisee_term', 'post' );
	}

	/**
	 * Load and store plugin metadata.
	 *
	 * Retrieves plugin information from the main plugin file header
	 * and stores it for use throughout the plugin. Only runs in admin context.
	 *
	 * @since 2.3
	 */
	function plugin_data() {
		if ( is_admin() ) {
			$this->plugin_data = get_plugin_data( AISEEFILE, false, false );
		}
	}

	/**
	 * Enqueue plugin styles and scripts for admin pages.
	 *
	 * Loads the plugin's CSS stylesheet and required JavaScript libraries
	 * (jQuery UI Sortable) only on public post type edit screens to avoid
	 * unnecessary loading on other admin pages.
	 *
	 * @since 2.3
	 */
	function plugin_styles() {
		$screen = get_current_screen();
		if ( in_array( $screen->post_type, get_post_types( array( 'public' => true ) ) ) ) {
			wp_enqueue_style(
				'aisee-stylesheet',
				$this->uri . 'assets/admin-styles.css',
				array( 'dashicons' ), // $deps
				( is_user_logged_in() ? time() : false ),
				'all' // $media
			);
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
	}

	/**
	 * Add meta boxes to post edit screens.
	 *
	 * Triggers the 'aisee_metaboxes' action for each public post type,
	 * allowing other parts of the plugin to add their metaboxes.
	 *
	 * @since 2.3
	 */
	function add_meta_boxes() {
		foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
			do_action( 'aisee_metaboxes', $post_type );
		}
	}

	/**
	 * Debug function placeholder.
	 *
	 * Currently empty but can be used for debugging purposes
	 * when needed during development.
	 *
	 * @since 2.3
	 */
	function aisee_debug() {
	}
}

/**
 * Get the main AISee plugin instance.
 *
 * This function provides global access to the AISee plugin singleton instance.
 * It's the recommended way to access plugin functionality from outside the class.
 *
 * @since 2.3
 * @return AISee The main AISee plugin instance.
 */
function aisee() {
	return AISee::get_instance();
}

// Let's roll!
aisee();
