<?php
class BEA_CSF_Synchronization {

	private $_fields = array( 'active', 'label', 'post_type', 'mode', 'status', 'notifications', 'emitters', 'receivers' );
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

	public function __construct( $fields = array( ) ) {
		$this->set_fields( $fields );
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

}