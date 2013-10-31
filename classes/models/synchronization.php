<?php
class BEA_CSF_Synchronization {

	private $_fields = array( 'id', 'active', 'label', 'post_type', 'mode', 'status', 'notifications', 'emitters', 'receivers' );
	private $_is_locked = true;
	// Public fields for this synchronization
	public $id = 1;
	public $active = 1;
	public $label = '';
	public $post_type = '';
	public $mode = '';
	public $status = '';
	public $notifications = 1;
	public $emitters = array( );
	public $receivers = array( );
	
	public $register_hooks = array( );

	public function __construct( $fields = array( ) ) {
		$this->set_fields( $fields );
		$this->register_actions();
	}

	public function register_actions() {
		// Stars with the hooks deregister previously recorded especially in the case of a re-register!
		if ( !empty($this->register_hooks) ) {
			foreach( $this->register_hooks as $hook_name ) {
				remove_action( $hook_name, array($this, 'send_to_receivers'), 10, 1 );
			}
		}
		
		$this->register_hooks = array();
		foreach ( $this->emitters as $emitter_blog_id ) {
			// Attachmentss
			$this->register_hooks[] = 'bea-csf' . '/' . 'Attachment' . '/' . 'delete' . '/attachment/' . $emitter_blog_id;
			$this->register_hooks[] = 'bea-csf' . '/' . 'Attachment' . '/' . 'merge' . '/attachment/' . $emitter_blog_id;
			
			// Posts
			$this->register_hooks[] = 'bea-csf' . '/' . 'PostType' . '/' . 'merge' . '/' . $this->post_type . '/' . $emitter_blog_id;
			$this->register_hooks[] = 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $this->post_type . '/' . $emitter_blog_id;
			
			// Terms
			// TODO : Manage an array
			$this->register_hooks[] = 'bea-csf' . '/' . 'Taxonomy' . '/' . 'delete' . '/' . 'category' . '/' . $emitter_blog_id;
			$this->register_hooks[] = 'bea-csf' . '/' . 'Taxonomy' . '/' . 'merge' . '/' . 'category' . '/' . $emitter_blog_id;
		}
		
		// Call the unique action hook !
		foreach( $this->register_hooks as $hook_name ) {
			add_action( $hook_name, array($this, 'send_to_receivers'), 10, 1 );
		}
	}

	public function set_fields( $fields = array( ) ) {
		foreach ( $fields as $field_name => $field_value ) {
			if ( in_array( $field_name, $this->_fields ) ) {
				$this->{$field_name} = maybe_unserialize( $field_value );
			}
		}
	}

	public function set_field( $field_name, $field_value ) {
		if ( in_array( $field_name, $this->_fields ) ) {
			$this->{$field_name} = $field_value;
			return true;
		}

		return false;
	}

	public function get_field( $field_name ) {
		if ( in_array( $field_name, $this->_fields ) ) {
			return $this->{$field_name};
		} else {
			return false;
		}
	}

	public function has_conflict() {
		$result = array_intersect( (array) $this->emitters, (array) $this->receivers );
		return !empty( $result );
	}

	public function lock() {
		$this->_is_locked = true;
	}

	public function unlock() {
		$this->_is_locked = false;
	}

	public function is_locked() {
		return $this->_is_locked;
	}

	public function send_to_receivers( $hook_data ) {
		// Get current filter
		$current_filter = current_filter();
		
		// Explode filter for get object and method
		$current_filter_data = explode('/', $current_filter);
		
		// Set data into variable for improve lisibility
		$object = $current_filter_data[1];
		$method = $current_filter_data[2];
		
		// Get data from SERVER class
		$data_to_send = call_user_func( array( 'BEA_CSF_Server_'.$object, $method ), $hook_data );
		if ( $data_to_send == false ) {
			// TODO: Log
			return false;
		}
		
		// Send data for each receivers
		foreach ( $this->receivers as $receiver_blog_id ) {
			switch_to_blog( $receiver_blog_id );
			$result = call_user_func( array( 'BEA_CSF_Client_'.$object, $method ), $data_to_send );
			// var_dump($result);
			restore_current_blog();
		}

		return true;
	}

}