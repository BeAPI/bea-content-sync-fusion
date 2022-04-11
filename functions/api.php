<?php
function register_synchronization( $args ) {
	BEA_CSF_Synchronizations::register( $args );
}

/**
 * Clone of wp_upload_dir with memory cache
 * Not allow user param to original function
 *
 */
function bea_csf_upload_dir() {
	global $mem_upload_dir;

	// First init, create array
	if ( ! isset( $mem_upload_dir ) ) {
		$mem_upload_dir = array();
	}

	$current_blog_id = get_current_blog_id();
	if ( isset( $mem_upload_dir[ $current_blog_id ] ) ) {
		return $mem_upload_dir[ $current_blog_id ];
	}

	$mem_upload_dir[ $current_blog_id ] = wp_upload_dir();

	return $mem_upload_dir[ $current_blog_id ];
}

function get_term_id_from_meta( $meta_key = '', $meta_value = '', $taxonomy = false ) {
	global $wpdb;

	//$key = md5( $meta_key . $meta_value . $taxonomy );
	//$result = wp_cache_get( $key, 'term_meta' );
	//if ( false === $result ) {
	if ( $taxonomy !== false ) {
		$result = (int) $wpdb->get_var( $wpdb->prepare( "SELECT tm.term_id FROM $wpdb->termmeta tm INNER JOIN $wpdb->term_taxonomy tt ON tm.term_id = tt.term_id WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy = %s", $meta_key, $meta_value, $taxonomy ) );
	} else {
		$result = (int) $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );
	}

		//wp_cache_set( $key, $result, 'term_meta' );
	//}

	return $result;
}
