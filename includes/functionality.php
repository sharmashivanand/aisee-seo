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
		add_action( 'aisee_tabs', [ $this, 'general' ] );
		add_action( 'aisee_tabs', [ $this, 'social' ] );
		add_action( 'aisee_tabs', [ $this, 'schema' ] );
		add_action( 'aisee_tabs', [ $this, 'meta' ] );
	}

	function general( $tabs ) {

		$tabs[] = array(
			'details' => array(
				'id'    => 'general',
				'icon'  => 'dashicons-admin-generic',
				'title' => 'General',

			),
			'fields'  => array(
				array(
					'id'      => AISEEPREFIX . 'title',
					'name'    => 'Title',
					'type'    => 'text',
					
				),
				array(
					'id'   => AISEEPREFIX . 'description',
					'name' => 'Description',
					'type' => 'textarea_small',
				),
				array(
					'id'   => AISEEPREFIX . 'focuskw_suggestions',
					'name' => 'Focus Keywords',
                    'type' => 'text',
                    'options' => array(
                        'flour'  => 'Flour',
                        'salt'   => 'Salt',
                        'eggs'   => 'Eggs',
                        'milk'   => 'Milk',
                        'butter' => 'Butter',
                    ),
                    'attributes' => array(
                        'dropdownAutoWidth' => 'false'
                    )
				),
			),
		);

		return $tabs;
	}

	function schema( $tabs ) {

		$tabs[] = array(
			'details' => array(
				'id'    => 'schema',
				'icon'  => 'dashicons-star-filled',
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
				'icon'  => 'dashicons-admin-tools',
				'title' => 'Meta',

			),
			'fields'  => array(
				array(
					'id'                => AISEEPREFIX . 'robots',
					'type'              => 'multicheck',
					'select_all_button' => false,
					'name'              => 'Robots Meta',
					'options'           => array(
						'noindex'      => 'No Index',
						'nofollow'     => 'No Follow',
						'noimageindex' => 'No Image Index',
						'noarchive'    => 'No Archive',
						'nosnippet'    => 'No Snippet',
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
				'icon'  => 'dashicons-share',
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

}

function aisee_functionality() {
	return AISee_Functionality::get_instance();

}

aisee_functionality();
