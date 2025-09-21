<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * AISee SEO WP-CLI Command Handler.
	 *
	 * Provides command-line interface for AISee SEO operations
	 * including batch taxonomy regeneration.
	 *
	 * @since 2.3
	 */
	class AiSeeSEO {

		/**
		 * Constructor for AiSeeSEO CLI class.
		 *
		 * Currently empty but available for future initialization needs.
		 *
		 * @since 2.3
		 */
		function __construct() {
		}

		/**
		 * Regenerate taxonomies for all posts via CLI.
		 *
		 * Triggers batch generation of AISee taxonomies for all
		 * published posts. Useful for bulk operations and maintenance.
		 *
		 * @since 2.3
		 *
		 * ## EXAMPLES
		 *
		 *     wp aisee regenerate
		 */
		function regenerate() {
			$gsc = new AISee_GSC();
			$gsc->batch_generate_tax();
		}

		/**
		 * Log output for CLI debugging.
		 *
		 * Outputs data to the command line for debugging purposes
		 * with proper formatting and newlines.
		 *
		 * @since 2.3
		 * @param mixed $str The data to output.
		 */
		function llog( $str ) {
			// print_r( date("c"));
			print_r( "\n" );
			print_r( $str );
		}
	}
	WP_CLI::add_command( 'aisee', 'AiSeeSEO' );
}
