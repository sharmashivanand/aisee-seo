<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	class AiSeeSEO {

		function __construct() {
            
		}

        function regenerate() {
            $gsc = new AISee_GSC();
            $gsc->batch_generate_tax();
        }

		function llog( $str ) {
            //print_r( date("c"));
			print_r( "\n" );
			print_r( $str );
		}

	}
	WP_CLI::add_command( 'aisee', 'AiSeeSEO' );
}