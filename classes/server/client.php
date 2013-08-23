<?php
class BEA_CSF_Server_Client {
	/**
	 * Post types to synchronized! 
	 */
	public static function get_post_types() {
		return array('post', 'page');
	}
	
	// Taxonomies to synchronized!
	public static function get_taxonomies() {
		return array('category', 'post_tag');
	}

	/**
	 * Build URL with action, make a POST request on each client
	 */
	public static function send_to_clients( $class = '', $method = '', $datas = null, $restrict_to_blog_id = null ) {
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		
		// No clients ?
		if ( empty($current_options['clients']) ) {
			return false;
		}
		
		$restrict_to_blog_id = (int) $restrict_to_blog_id;
		
		// Loop on each client
		foreach( $current_options['clients'] as $blog_id ) {
			$blog_id = (int) $blog_id;
			
			// Limit to one URL ?
			if ( $restrict_to_blog_id > 0 && $restrict_to_blog_id != $blog_id ) {
				continue;
			}
			
			switch_to_blog($blog_id);
			$result = call_user_func(array($class, $method), $datas);
			restore_current_blog();
			
			//var_dump($result, $class, $method, $datas);
			// Get datas &Return only if only URL isset
			if ( $restrict_to_blog_id > 0 && $restrict_to_blog_id == $blog_id ) {
				return $result;
			}
		}

		return true;
	}
}