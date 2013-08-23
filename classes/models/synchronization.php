<?php
class BEA_CSF_Synchronization {
	private $_id = 0;
	private $_fields = array('active', 'label', 'post_type', 'mode', 'status', 'notifications');
	
	public $active = 1;
	public $label = '';
	public $post_type = '';
	public $mode = '';
	public $status = '';
	public $notifications = 1;
	
	public function __construct( $id ) {
		
	}
	
	/**
	 * 
	 * @return type
	 */
	public function get_id() {
		return $this->_id;
	}
	

	public function commit() {
		global $wpdb;
		
		// prepare fields
		$datas = array();
		foreach( $this->_fields as $field_name ) {
			$datas[$field_name] = $this->{$field_name};
		}
		
		// Insert or update
		if ( $this->_id == 0 ) {
			$wpdb->insert( $wpdb->beac_synchronizations, $datas );
		} else {
			$wpdb->update( $wpdb->beac_synchronizations, $datas, array('id' => $this->_id) );
		}
		
		// Set relations
		
	}
}