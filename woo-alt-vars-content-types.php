<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class AltVarsContentTypesClass {

	public function __construct() {

		

		// Register content types
		add_action( 'init', array( $this, 'addAltVariationContentType' ) );

		add_action(
			'admin_init',
			function() {
				if ( current_user_can( 'manage_options' ) && get_transient( 'alt_vars_flush_rewrite_rules', false ) ) {
					flush_rewrite_rules();
					delete_transient( 'alt_vars_flush_rewrite_rules' );
				}
			},
			99
		);

		register_activation_hook(
			plugin_basename( __FILE__ ),
			function() {
				$this->addAltVariationContentType();
				flush_rewrite_rules();
			}
		);

	}

	// Register altvariation content type
	public function addAltVariationContentType() {
		register_taxonomy(
			'var_product_visibility',
			array( 'product'),
			array(
					'hierarchical'      => false,
					'show_ui'           => false,
					'show_in_nav_menus' => false,
					'query_var'         => true,
					'rewrite'           => false,
					'public'            => false,
					'label'             => __( 'Видимость в каталоге', 'woo-alt-variations' ),
				)

		);
	}
}

// Finally initialize code
new AltVarsContentTypesClass();