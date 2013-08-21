<?php
class BEA_CSF_Server_Taxonomy {
	/**
	 * Constructor, register hooks 
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'create_term', array(__CLASS__, 'merge_term'), 10, 3 );
		add_action( 'edit_term',   array(__CLASS__, 'merge_term'), 10, 3 );
		add_action( 'delete_term', array(__CLASS__, 'delete_term'), 10, 3 );
	}
	
	/**
	 * Check for term deletion and send it to client
	 */
	public static function delete_term( $term_id, $tt_id, $taxonomy, $blog_id = 0 ) {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		if ( !isset($current_options['master']) || $wpdb->blogid != $current_options['master'] ) {
			return false;
		}
		
		// Encode datas
		$term = array('term_taxonomy_id' => $tt_id, 'term_id' => $term_id, 'taxonomy' => $taxonomy );
		
		return BEA_CSF_Server_Client::send_to_clients( 'BEA_CSF_Client_Taxonomy', 'remove_term', $term, $blog_id );
	}
	
	/**
	 * Check for new term and send it to client
	 */
	public static function merge_term( $term_id, $tt_id, $taxonomy, $blog_id = 0 ) {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		if ( !isset($current_options['master']) || $wpdb->blogid != $current_options['master'] ) {
			return false;
		}
		
		// Get term
		$term = get_term( $term_id, $taxonomy );
		if ( $term == false || is_wp_error($term) ) {
			return false;
		}
		
		// Get parent TT_ID
		if( $term->parent > 0 ) {
			$parent_term = get_term( $term->parent, $taxonomy );
			if ( $term != false && !is_wp_error($term) ) {
				$term->parent_tt_id = $parent_term->term_taxonomy_id;
			}
		} else {
			$term->parent_tt_id = 0;
		}
		
		// Get images
		$term->image_id = (int) self::get_term_media_id( $term->term_taxonomy_id );
		
		// Remove unused fields
		unset($term->term_group, $term->count);
		
		// Send image to client if exist
		if ( $term->image_id != 0 ) {
			$term->image = get_post($term->image_id, ARRAY_A);
			$term->image['meta'] = get_post_custom($term->image_id);
		}
		
		// Add Server URL
		$term->server_url = home_url('/');
		$uploads = wp_upload_dir();
		$term->upload_url =  $uploads['baseurl'];
		
		return BEA_CSF_Server_Client::send_to_clients( 'BEA_CSF_Client_Taxonomy', 'new_term', (array) $term, $blog_id );
	}
	
	/**
	 * Get the term image with the taxonomy image plugin
	 *
	 * @param int $tt_id Term Taxonomy Id, required
	 * @return integer|bool False on failure. The MediaID.
	 * 
	 * @author Amaury Balmer
	 */
	public static function get_term_media_id( $tt_id = 0 ) {
		if ( (int) $tt_id == 0 || !function_exists('taxonomy_image_plugin_get_associations') ) {
			return '';
		}
			
		// Get associations
		$assocs = taxonomy_image_plugin_get_associations();
		
		// Check if the term is associated yet
		if ( isset($assocs[$tt_id]) ) {
			return $assocs[$tt_id];
		}
		
		return '';
	}
}