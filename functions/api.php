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
	if ( !isset($mem_upload_dir) ) {
		$mem_upload_dir = array();
	}

	$current_blog_id = get_current_blog_id();
	if ( isset($mem_upload_dir[$current_blog_id]) ) {
		return $mem_upload_dir[$current_blog_id];
	}

	$mem_upload_dir[$current_blog_id] = wp_upload_dir();

	return $mem_upload_dir[$current_blog_id];
}