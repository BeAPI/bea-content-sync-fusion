<?php
class BEA_CSF_Synchronization {

	private $_fields = array( 'id', 'active', 'label', 'post_type', 'taxonomies', 'mode', 'status', 'notifications', 'emitters', 'receivers' );
	private $_is_locked = true;
	private $_register_hooks = array( );
	// Public fields for this synchronization
	public $id = 1;
	public $active = 1;
	public $label = '';
	public $post_type = '';
	public $taxonomies = array( );
	public $mode = '';
	public $status = '';
	public $notifications = 1;
	public $emitters = array( );
	public $receivers = array( );

	/**
	 * Construct, allow set fields, all register actions
	 * 
	 * @param type $fields
	 */
	public function __construct( $fields = array( ) ) {
		$this->set_fields( $fields );
		$this->register_actions();
	}

	/**
	 * Register hooks depending synchronization configuration
	 * 
	 * @return boolean
	 */
	public function register_actions() {
		// Register actions if sync is active and haven't a conflict emitters/receivers
		if ( $this->active == 0 || $this->has_conflict() ) {
			return false;
		}

		// Stars with the hooks deregister previously recorded especially in the case of a re-register!
		if ( !empty( $this->_register_hooks ) ) {
			foreach ( $this->_register_hooks as $hook_name ) {
				remove_action( $hook_name, array( $this, 'send_to_receivers' ), 10, 4 );
			}
		}

		// Flush registered hooks
		$this->_register_hooks = array( );

		// No emitters ? Go out !
		if ( empty( $this->emitters ) ) {
			return false;
		}

		foreach ( $this->emitters as $emitter_blog_id ) {
			// Attachments, consider this post type as classic content. Medias are only sync if linked to a content
			// $this->_register_hooks[] = 'bea-csf' . '/' . 'Attachment' . '/' . 'delete' . '/attachment/' . $emitter_blog_id;
			// $this->_register_hooks[] = 'bea-csf' . '/' . 'Attachment' . '/' . 'merge' . '/attachment/' . $emitter_blog_id;
			// Posts
			$this->_register_hooks[] = 'bea-csf' . '/' . 'PostType' . '/' . 'merge' . '/' . $this->post_type . '/' . $emitter_blog_id;
			$this->_register_hooks[] = 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $this->post_type . '/' . $emitter_blog_id;

			// Terms
			if ( !empty( $this->taxonomies ) ) {
				foreach ( $this->taxonomies as $taxonomy ) {
					$this->_register_hooks[] = 'bea-csf' . '/' . 'Taxonomy' . '/' . 'delete' . '/' . $taxonomy . '/' . $emitter_blog_id;
					$this->_register_hooks[] = 'bea-csf' . '/' . 'Taxonomy' . '/' . 'merge' . '/' . $taxonomy . '/' . $emitter_blog_id;
				}
			}
		}

		// Call the unique action hook !
		foreach ( $this->_register_hooks as $hook_name ) {
			add_action( $hook_name, array( $this, 'send_to_receivers' ), 10, 4 );
		}

		return true;
	}

	/**
	 * Set all fields/value, replace existing !
	 * 
	 * @param array $fields
	 * @return boolean
	 */
	public function set_fields( $fields = array( ) ) {
		if ( empty( $fields ) ) {
			return false;
		}

		foreach ( $fields as $field_name => $field_value ) {
			if ( in_array( $field_name, $this->_fields ) ) {
				$this->{$field_name} = maybe_unserialize( $field_value );
			}
		}

		return true;
	}

	/**
	 * Set value for a field
	 * 
	 * @param string $field_name
	 * @param mixed $field_value
	 * @return boolean
	 */
	public function set_field( $field_name, $field_value ) {
		if ( in_array( $field_name, $this->_fields ) ) {
			$this->{$field_name} = $field_value;
			return true;
		}

		return false;
	}

	/**
	 * Get value for a field
	 * 
	 * @param string $field_name
	 * @return boolean[mixed
	 */
	public function get_field( $field_name ) {
		if ( in_array( $field_name, $this->_fields ) ) {
			return $this->{$field_name};
		} else {
			return false;
		}
	}

	/**
	 * If a synchronization possess blogs as both transmitters and receivers as when there is a conflict!
	 * 
	 * @return boolean
	 */
	public function has_conflict() {
		$result = array_intersect( (array) $this->emitters, (array) $this->receivers );
		return !empty( $result );
	}

	/**
	 * Lock synchronization, do not allow edition on BO
	 */
	public function lock() {
		$this->_is_locked = true;
	}

	/**
	 * Lock synchronization, allow edition on BO
	 */
	public function unlock() {
		$this->_is_locked = false;
	}

	/**
	 * Test if synchronisation is allowed to edition on BO
	 * @return type
	 */
	public function is_locked() {
		return $this->_is_locked;
	}

	/**
	 * Generic method to get data from emitter and sent theses to receivers
	 * 
	 * @param mixed $hook_data
	 * @param boolean $excluded_from_sync
	 * @param false|array $receivers_inclusion
	 * @param boolean $ignore_mode
	 * @return boolean
	 */
	public function send_to_receivers( $hook_data, $excluded_from_sync = false, $receivers_inclusion = false, $ignore_mode = false ) {
		// Inclusion is not FALSE? But a empty array ? On manual mode or ignore mode ?
		if ( ( $this->mode == 'manual' || $ignore_mode == true ) && is_array($receivers_inclusion) && empty($receivers_inclusion) ) {
			return false;
		}

		// Emitter content is excluded from SYNC ?
		if ( ( $this->mode == 'auto' || $ignore_mode == true ) && $excluded_from_sync === true ) {
			return false;
		}

		// Get current filter
		$current_filter = current_filter();

		// Explode filter for get object and method
		$current_filter_data = explode( '/', $current_filter );

		// Set data into variable for improve lisibility
		$object = $current_filter_data[1];
		$method = $current_filter_data[2];
		$blogid = $current_filter_data[4];

		// Get data from SERVER class
		$data_to_transfer = call_user_func( array( 'BEA_CSF_Server_' . $object, $method ), $hook_data, $this );
		if ( $data_to_transfer == false ) {
			// TODO: Log
			return false;
		}
		
		// Append origin blog id to data to transfer
		$data_to_transfer['blogid'] = (int) $blogid;

		// Send data for each receivers
		foreach ( $this->receivers as $receiver_blog_id ) {
			// Keep only ID on inclusion custom param
			if ( ( $this->mode == 'manual' || $ignore_mode == true ) && is_array($receivers_inclusion) && !in_array($receiver_blog_id, $receivers_inclusion) ) {
				continue;
			}

			switch_to_blog( $receiver_blog_id );

			// Deactive hooks plugin
			BEA_CSF_Client::unregister_hooks();

			// Send data to CLIENT classes
			$result = call_user_func( array( 'BEA_CSF_Client_' . $object, $method ), $data_to_transfer, $this );

			// Reactive hooks plugin
			BEA_CSF_Client::register_hooks();

			// Allow users notifications
			if ( (int) $this->notifications == 1 ) {
				do_action( 'bea-csf-client-notifications', $result, $object, $method, $blogid, $this );
			}

			// var_dump($result);
			restore_current_blog();
		}

		return true;
	}

}