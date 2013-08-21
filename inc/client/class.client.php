<?php
class BEA_CSF_Client_Base {
	/**
	 * Integrity method return a MD5 based on terms name, post title
	 */
	public static function integrity() {
		global $wpdb;
		
		// Posts types
		$posts = $wpdb->get_col( "SELECT pm.meta_value FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id WHERE meta_key = 'master_id'" );
		
		// Terms
		$terms = $wpdb->get_col( "
			SELECT ttm.meta_value
			FROM $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id 
			INNER JOIN $wpdb->term_taxometa AS ttm ON tt.term_taxonomy_id = ttm.term_taxo_id 
			WHERE meta_key = 'master_id'
		" );
		
		return md5( implode('', $posts) . implode('', $terms) );
	}
	
	/**
	 * Flush method remove all syndicated content
	 */
	public static function flush() {
		global $wpdb;
		
		$flag = false;
		
		// Remove posts
		$objects = $wpdb->get_col( "SELECT p.ID FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id WHERE meta_key = 'master_id'" );
		if ( $objects != false &&  is_array($objects) ) { 
			foreach( $objects as $object_id ) {
				$attachment_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'attachment'", $object_id) );
				foreach ( $attachment_ids as $attachment_id ) {
					wp_delete_attachment($attachment_id, true);
				}
				
				wp_delete_post( $object_id, true );
			}
			$flag = true;
		}
		
		// Remove all terms
		$terms = $wpdb->get_results( "
			SELECT tt.*
			FROM $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id 
			INNER JOIN $wpdb->term_taxometa AS ttm ON tt.term_taxonomy_id = ttm.term_taxo_id 
			WHERE meta_key = 'master_id'
		" );
		if ( $terms != false &&  is_array($terms) ) { 
			foreach( $terms as $term ) {
				wp_delete_term($term->term_id, $term->taxonomy);
			}
			$flag = true;
		}
		
		return $flag;
	}
	
	/**
	 * Get post ID from post meta with meta_key and meta_value
	 */
	public static function get_post_id_from_meta( $key, $value ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $key, $value) );
	}
}