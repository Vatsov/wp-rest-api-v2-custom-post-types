<?php

/**
 * Plugin Name: WP REST API V2 Custom Post Fields
 * Description: Adds Advanced Custom Post Fields to WP REST API V2 JSON output.
 * Version: 0.2
 * Author: Deyan Vatsov
 * Plugin URI: https://github.com/Vatsov/wp-rest-api-v2-custom-post-fields/
 */

if ( !function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( is_plugin_active('rest-api/plugin.php') ) {
	new CustomPostDataPlugin();
}

class CustomPostDataPlugin {
	public function __construct() {
		// Add Meta Fields to Posts
		add_action('rest_api_init', 	array( $this, 'add_custom_data' ) );
		add_filter('rest_query_vars',	array( $this, 'allow_meta_query' ) );
		add_filter('rest_post_query',	array( $this, 'allowed_meta_keys' ) );
	}

	function add_custom_data() {
		// Register the type fields
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach($post_types as $post_type) {
			$post_type_name = $post_type->name;
			$show_in_rest		= ( isset( $post_type->show_in_rest ) && $post_type->show_in_rest ) ? true : false;

			if ( $show_in_rest ) {
				register_rest_field($post_type_name,
					'acf_fields', array(
						'get_callback' => array($this, 'get_custom_data'),
						'update_callback' => array($this, 'update_custom_data'),
						'schema' => null
					)
				);
			}
		}
	}

	/**
	 * Handler for getting custom data.
	 *
	 */
	function get_custom_data($object, $field_name, $request) {
		if ( function_exists('get_fields') ) {
			return get_fields($object['id']);
		}
	}

	 /**
	 * Handler for updating custom data.
	 */
	function update_custom_data($value, $post, $field_name) {
		if (!$value || !is_string($value)) {
			return;
		}

		return update_post_meta($post->ID, $field_name, strip_tags($value));
	}

	//Example: /wp-json/wp/v2/posts?filter[meta_key]=slideshow&filter[meta_value]=true
	function allow_meta_query($valid_vars) {
		$valid_vars = array_merge( $valid_vars, array( 'meta_key', 'meta_value' ) );
		return $valid_vars;
	}

	function allowed_meta_keys($args) {
		// ToDo: to be configurable from admin panel
		// set the allowed meta keys or asterisk for grant access
		$allowed_meta_keys = array( 'slideshow' );


		if ( isset($args['meta_value']) && $args['meta_value'] === "true" ) {
			$args['meta_value'] = true;
		}

		if ( isset($args['meta_value']) && $args['meta_value'] === "false" ) {
			$args['meta_value'] = false;
		}


		if ( $allowed_meta_keys[0] !== '*' && isset($args['meta_key']) && !in_array( $args['meta_key'], $allowed_meta_keys ) ) {
			unset( $args['meta_key'] );
			unset( $args['meta_value'] );
		}

		return $args;
	}
}
