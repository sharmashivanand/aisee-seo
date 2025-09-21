<?php

trait Aisee_Helpers {


	/**
	 * Get the current status of the plugin.
	 *
	 * Currently returns false as a placeholder.
	 * This method can be extended to provide plugin status information.
	 *
	 * @since 2.3
	 * @return bool Always returns false currently.
	 */
	function get_status() {
		return false;
	}

	/**
	 * Check if Google Search Console is connected.
	 *
	 * Determines whether the plugin has been connected to Google Search Console
	 * by checking the 'gsc' setting value.
	 *
	 * @since 2.3
	 * @return mixed The GSC setting value, or false if not connected.
	 */
	function is_gsc_connected() {
		return $this->get_setting( 'gsc' );
	}

	/**
	 * Get a specific plugin setting value.
	 *
	 * Retrieves a setting from the plugin's options, merging with defaults
	 * to ensure all expected values are available.
	 *
	 * @since 2.3
	 * @param string $setting The setting key to retrieve.
	 * @return mixed The setting value, or false if not found.
	 */
	function get_setting( $setting ) {
		$defaults = $this->get_defaults();
		$settings = wp_parse_args( get_option( 'aiseeseo', $defaults ), $defaults );
		return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
	}

	/**
	 * Get default plugin settings.
	 *
	 * Returns the default configuration values for all plugin settings,
	 * including connection details and Google Search Console filter parameters.
	 *
	 * @since 2.3
	 * @return array Array of default setting values.
	 */
	function get_defaults() {
		// $defaults = array(
		// 'connection' => '',
		// );
		$defaults = array(
			'connection' => array(),
			'gsc_filter' => array(
				'ctr'         => array(
					'min' => 0,
					'max' => 100,
				),
				'position'    => array(
					'min' => 1,
					'max' => 100,
				),
				'impressions' => array(
					'min' => 180,
					'max' => 1000,
				),
				'clicks'      => array(
					'min' => 0,
					'max' => 10000,
				),
			),
		);
		return $defaults;
	}

	/**
	 * Update a specific plugin setting.
	 *
	 * Updates or adds a setting value in the plugin's options.
	 * Creates the options array if it doesn't exist.
	 *
	 * @since 2.3
	 * @param string $setting The setting key to update.
	 * @param mixed  $value The new value for the setting.
	 * @return bool True if the option was updated successfully, false otherwise.
	 */
	function update_setting( $setting, $value ) {
		$settings = get_option( 'aiseeseo' );
		if ( ! $settings ) {
			$settings = array();
		}
		$settings[ $setting ] = $value;
		return update_option( 'aiseeseo', $settings );
	}

	/**
	 * Delete a specific plugin setting.
	 *
	 * Removes a setting from the plugin's options array.
	 * Creates the options array if it doesn't exist.
	 *
	 * @since 2.3
	 * @param string $setting The setting key to delete.
	 */
	function delete_setting( $setting ) {
		$settings = get_option( 'aiseeseo' );
		if ( ! $settings ) {
			$settings = array();
		}
		unset( $settings[ $setting ] );
		update_option( 'aiseeseo', $settings );
	}

	/**
	 * Sanitize plugin settings.
	 *
	 * Currently returns settings as-is. This method can be extended
	 * to implement comprehensive sanitization of user input.
	 *
	 * @since 2.3
	 * @param array $settings The settings array to sanitize.
	 * @return array The sanitized settings array.
	 */
	function sanitize( $settings ) {
		return $settings;
	}

	/**
	 * Encode data for secure transmission.
	 *
	 * Converts data to JSON, then base64 encodes it with URL-safe characters
	 * to ensure safe transmission in URLs and API calls.
	 *
	 * @since 2.3
	 * @param mixed $data The data to encode.
	 * @return string The encoded data string.
	 */
	function encode( $data ) {
		$data = strtr( base64_encode( json_encode( $data ) ), '+/=', '-_,' );
		return $data;
	}

	/**
	 * Decode previously encoded data.
	 *
	 * Reverses the encoding process by converting URL-safe base64 back
	 * to standard base64, decoding it, and parsing the JSON.
	 *
	 * @since 2.3
	 * @param string $data The encoded data string to decode.
	 * @return mixed The decoded data, or null if decoding fails.
	 */
	function decode( $data ) {
		return json_decode( base64_decode( strtr( $data, '-_,', '+/=' ) ), true );
	}

	/**
	 * Log data to screen output for debugging.
	 *
	 * Outputs data wrapped in HTML pre tags for readable debugging.
	 * Should only be used during development.
	 *
	 * @since 2.3
	 * @param mixed $str The data to display.
	 */
	function llog( $str ) {
		echo '<pre>';
		print_r( $str );
		echo '</pre>';
	}

	/**
	 * Capture var_dump output as string.
	 *
	 * Uses output buffering to capture var_dump output and return it
	 * as a string instead of outputting directly.
	 *
	 * @since 2.3
	 * @param mixed $vars The variables to dump.
	 * @return string The var_dump output as a string.
	 */
	function dlog( $vars ) {
		ob_start();
		var_dump( $vars );
		return ob_get_clean();
	}

	/**
	 * Log data to a file for debugging.
	 *
	 * Appends debugging information to a log.log file in the plugin directory.
	 * Useful for debugging when screen output is not practical.
	 *
	 * @since 2.3
	 * @param mixed $str The data to log to file.
	 */
	function flog( $str ) {
		$file = trailingslashit( dirname( AISEEFILE ) ) . 'log.log';
		// file_put_contents( $file, PHP_EOL . '===START===' , FILE_APPEND | LOCK_EX );
		// file_put_contents( $file, PHP_EOL . debug_backtrace()[1]['function'] . PHP_EOL, FILE_APPEND | LOCK_EX );
		// file_put_contents( $file, date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND | LOCK_EX );
		file_put_contents( $file, print_r( $str, 1 ) . PHP_EOL, FILE_APPEND | LOCK_EX );
		// file_put_contents( $file, '====END====' . PHP_EOL, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Timestamp placeholder method.
	 *
	 * Currently empty but can be used for timestamp-related functionality
	 * when needed.
	 *
	 * @since 2.3
	 */
	function stamp() {
	}
}
