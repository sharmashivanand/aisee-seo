<?php

function aisee_get_status() {
	return false;
}

function aisee_is_connected() {
	return aisee_get_setting( 'gsc' );
}

function aisee_get_setting( $setting ) {
	$defaults = aisee_defaults();
	$settings = wp_parse_args( get_option( 'aiseeseo', $defaults ), $defaults );
	return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
}

function aisee_defaults() {
	// $defaults = array(
	// 'connection' => '',
	// );
	$defaults = array(
		'connection' => array(),
		'gsc_filter' => array(
			'ctr'      => array(
				'min' => 1,
				'max' => 100,
			),
			'position' => array(
				'min' => 1,
				'max' => 100,
			),
		),
	);
	return $defaults;
}

function aisee_update_setting( $setting, $value ) {
	$settings = get_option( 'aiseeseo' );
	if ( ! $settings ) {
		$settings = array();
	}
	$settings[ $setting ] = $value;
	return update_option( 'aiseeseo', $settings );
}

function aisee_delete_setting( $setting ) {
	$settings = get_option( 'aiseeseo' );
	if ( ! $settings ) {
		$settings = array();
	}
	unset( $settings[ $setting ] );
	update_option( 'aiseeseo', $settings );
}

function aisee_sanitize( $settings ) {
	return $settings;
}

function aisee_encode( $data ) {
	$data = strtr( base64_encode( json_encode( $data ) ), '+/=', '-_,' );
	return $data;
}

function aisee_decode( $data ) {
	return json_decode( base64_decode( strtr( $data, '-_,', '+/=' ) ), true );
}

function aisee_llog( $str ) {
	echo '<pre>';
	print_r( $str );
	echo '</pre>';
}

function aisee_flog( $str ) {
	$file = trailingslashit( dirname( AISEEFILE ) ) . 'log.log';
	// file_put_contents( $file, PHP_EOL . '===START===' , FILE_APPEND | LOCK_EX );
	// file_put_contents( $file, PHP_EOL . debug_backtrace()[1]['function'] . PHP_EOL, FILE_APPEND | LOCK_EX );
	file_put_contents( $file, print_r( $str, 1 ) . PHP_EOL, FILE_APPEND | LOCK_EX );
	// file_put_contents( $file, '====END====' . PHP_EOL, FILE_APPEND | LOCK_EX );
}

add_action( 'admin_footer', 'aisee_debug' );

function aisee_debug(){
	
}
