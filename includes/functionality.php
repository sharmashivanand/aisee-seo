<?php

class AISee_Functionality {
	function __construct() {
	}

	static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			// $instance->setup();
			// $instance->includes();
			$instance->hooks();
		}
		return $instance;
	}

	function hooks() {
		// add_action( 'aisee_mb', [ $this, 'fields' ] );
		add_action( 'aisee_tabs', [ $this, 'general' ] );
		add_action( 'aisee_tabs', [ $this, 'schema' ] );
		add_action( 'aisee_tabs', [ $this, 'meta' ] );
		add_action( 'aisee_tabs', [ $this, 'social' ] );
		// add_action( 'aisee_tabs', [ $this, 'social' ] );
	}

	function general( $tabs ) {

		$tabs[] = array(
			'details' => array(
				'id'    => 'tab-1',
				'icon'  => '',
				'title' => 'General',

			),
			'fields'  => array(
				array(
					'id'   => AISEEPREFIX . '_field_1',
					'name' => 'Field 1',
					'type' => 'text',
				),
				array(
					'id'   => AISEEPREFIX . '_field_2',
					'name' => 'Field 2',
					'type' => 'text',
				),
			),
		);

		return $tabs;
	}

	function schema( $tabs ) {

		$tabs[] = array(
			'details' => array(
				'id'    => 'tab-2',
				'icon'  => '',
				'title' => 'Schema',

			),
			'fields'  => array(
				array(
					'id'   => AISEEPREFIX . '_field_3',
					'type' => 'text',
					'name' => 'Field 3',
				),
				array(
					'id'   => AISEEPREFIX . '_field_4',
					'type' => 'text',
					'name' => 'Field 4',
				),
			),
		);

		return $tabs;
	}

	function meta( $tabs ) {

		$tabs[] = array(
			'details' => array(
				'id'    => 'meta',
				'icon'  => '',
				'title' => 'Meta',

			),
			'fields'  => array(
				array(
					'id'   => AISEEPREFIX . 'robots',
					'type' => 'multicheck',
					'select_all_button' => false,
					'name' => 'Robots Meta',
					'options' => array(
						'noindex' => 'No Index',
						'nofollow' => 'No Follow',
						'noimageindex' => 'No Image Index',
						'noarchive' => 'No Archive',
						'nosnippet' => 'No Snippet',
					),
				),
				array(
					'id'   => AISEEPREFIX . 'canonical',
					'type' => 'text_url',
					'name' => 'Canonical URL',
				),
			),
		);

		return $tabs;
	}

	function social( $tabs ) {

		$tabs[] = array(
			'details' => array(
				'id'    => 'tab-2',
				'icon'  => '',
				'title' => 'Social',

			),
			'fields'  => array(
				array(
					'id'   => AISEEPREFIX . '_field_3',
					'type' => 'text',
					'name' => 'Field 3',
				),
				array(
					'id'   => AISEEPREFIX . '_field_4',
					'type' => 'text',
					'name' => 'Field 4',
				),
			),
		);

		return $tabs;
	}

	function fields( $cmb ) {
		$prefix = 'your_prefix_demo_';

		$cmb->tabs[] = array(
			array(
				'id'     => 'tab-1',
				'icon'   => 'dashicons-admin-site',
				'title'  => 'Tab 1',
				'fields' => array(
					$prefix . '_field_1',
					$prefix . '_field_2',
				),
			),
		);

		$cmb->add_field(
			array(
				'name' => __( 'Test field 2', 'cmb2' ),
				'id'   => $prefix . '_field_2',
				'type' => 'text',
			)
		);

	}

}

function aisee_functionality() {
	return AISee_Functionality::get_instance();

}

aisee_functionality();
