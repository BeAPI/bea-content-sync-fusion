<?php

class BEA_CSF_Synchronization {

	private $_fields = array(
		'id',
		'active',
		'label',
		'post_type',
		'taxonomies',
		'p2p_connections',
		'mode',
		'status',
		'emitters',
		'receivers',
	);
	private $_is_locked = true;
	private $_register_hooks = array();

	// Public fields for this synchronization
	public $id = 1;
	public $active = 1;
	public $label = '';
	public $post_type = '';
	public $taxonomies = array();
	public $p2p_connections = array();
	public $mode = '';
	public $status = '';
	public $emitters = array();
	public $receivers = array();

	// Private fields
	public $_hook_data = null;
	public $_current_filter = null;
	public $_current_object = null;
	public $_current_method = null;
	public $_current_blog_id = null;
	public $_data_to_transfer = null;
	public $_current_receiver_blog_id = null;

	/**
	 * Construct, allow set fields, all register actions
	 *
	 * @param array $fields
	 */
	public function __construct( array $fields = array() ) {
		$this->set_fields( $fields );
		$this->register_actions();
	}

	/**
	 * Register hooks depending synchronization configuration
	 *
	 * @return boolean
	 */
	public function register_actions() {
		global $connection_taxo_duplicate;

		if ( ! isset( $connection_taxo_duplicate ) ) {
			$connection_taxo_duplicate = array();
		}

		if ( 0 === $this->active ) {
			return false;
		}

		// Stars with the hooks deregister previously recorded especially in the case of a re-register!
		if ( ! empty( $this->_register_hooks ) ) {
			foreach ( $this->_register_hooks as $hook_name ) {
				remove_action( $hook_name, array( $this, 'send_to_receivers' ), 10, 4 );
			}
		}

		// Flush registered hooks
		$this->_register_hooks = array();

		// No emitters ? Go out !
		if ( empty( $this->get_emitters() ) ) {
			return false;
		}

		foreach ( $this->get_emitters() as $emitter_blog_id ) {
			// Register this hook only for post type attachment for evite doublon sync item
			if ( 'attachment' === $this->post_type ) { // Specific CPT : Attachments

				$this->_register_hooks[] = 'bea-csf' . '/' . 'Attachment' . '/' . 'delete' . '/attachment/' . $emitter_blog_id;
				$this->_register_hooks[] = 'bea-csf' . '/' . 'Attachment' . '/' . 'merge' . '/attachment/' . $emitter_blog_id;

			} else { // Classic CPT : Posts/Pages

				if ( ! empty( $this->post_type ) ) {
					$this->_register_hooks[] = 'bea-csf' . '/' . 'PostType' . '/' . 'merge' . '/' . $this->post_type . '/' . $emitter_blog_id;
					$this->_register_hooks[] = 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $this->post_type . '/' . $emitter_blog_id;
				}

			}

			// Terms for all kind of CPT
			if ( ! empty( $this->taxonomies ) ) {
				foreach ( $this->taxonomies as $taxonomy ) {
					// Skip register if taxo is already register on another synchro
					if ( isset( $connection_taxo_duplicate[ $taxonomy . '/' . $emitter_blog_id ] ) ) {
						//continue;
					}

					$this->_register_hooks[] = 'bea-csf' . '/' . 'Taxonomy' . '/' . 'delete' . '/' . $taxonomy . '/' . $emitter_blog_id;
					$this->_register_hooks[] = 'bea-csf' . '/' . 'Taxonomy' . '/' . 'merge' . '/' . $taxonomy . '/' . $emitter_blog_id;

					$connection_taxo_duplicate[ $taxonomy . '/' . $emitter_blog_id ] = true;
				}
			}

			// P2P
			if ( ! empty( $this->p2p_connections ) ) {
				foreach ( $this->p2p_connections as $p2p_connection ) {
					$this->_register_hooks[] = 'bea-csf' . '/' . 'P2P' . '/' . 'delete' . '/' . $p2p_connection . '/' . $emitter_blog_id;
					$this->_register_hooks[] = 'bea-csf' . '/' . 'P2P' . '/' . 'merge' . '/' . $p2p_connection . '/' . $emitter_blog_id;
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
	 *
	 * @return boolean
	 */
	public function set_fields( $fields = array() ) {
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
	 * Get all fields/value
	 * @return array
	 * @internal param array $fields
	 */
	public function get_fields() {
		$results = array();
		foreach ( $this->_fields as $field_name ) {
			$results[ $field_name ] = $this->{$field_name};
		}

		return $results;
	}

	/**
	 * Set value for a field
	 *
	 * @param string $field_name
	 * @param mixed $field_value
	 *
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
	 * @param bool $raw_value
	 *
	 * @return boolean|array
	 */
	public function get_field( $field_name, $raw_value = false ) {
		if ( 'receivers' == $field_name && false === $raw_value ) { // Add support "all except..." value context
			return $this->get_receivers();
		} elseif ( 'emitters' == $field_name && false === $raw_value ) { // Add support "all" value context
			return $this->get_emitters();
		} elseif ( in_array( $field_name, $this->_fields ) ) {
			return $this->{$field_name};
		} else {
			return false;
		}
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
	 * @return boolean
	 */
	public function is_locked() {
		return $this->_is_locked;
	}

	/**
	 * Translate "all" value by array with blogs_id
	 *
	 * @return array
	 */
	public function get_receivers() {
		$results = array();
		foreach ( $this->receivers as $key => $receiver_blog_id ) {
			if ( 'all' === $receiver_blog_id ) {
				$blogs    = BEA_CSF_Synchronizations::get_sites_from_network();
				$blog_ids = wp_list_pluck( $blogs, 'blog_id' );

				$results = array_merge( $results, $blog_ids );
			} else {
				$results[] = $receiver_blog_id;
			}
		}

		$results = array_map( 'intval', $results );
		$results = array_unique( $results );
		$results = array_filter( $results );

		return $results;
	}

	/**
	 * Translate "all" value by array with blogs_id
	 *
	 * @return array
	 */
	public function get_emitters() {
		$results = array();
		foreach ( $this->emitters as $key => $emitter_blog_id ) {
			if ( 'all' === $emitter_blog_id ) {
				$blogs    = BEA_CSF_Synchronizations::get_sites_from_network();
				$blog_ids = wp_list_pluck( $blogs, 'blog_id' );

				$results = array_merge( $results, $blog_ids );
			} else {
				$results[] = $emitter_blog_id;
			}
		}

		$results = array_map( 'intval', $results );
		$results = array_unique( $results );
		$results = array_filter( $results );

		return $results;
	}

	/**
	 * Generic method to get data from emitter and sent theses to receivers
	 *
	 * @param mixed $hook_data
	 * @param boolean $excluded_from_sync
	 * @param array|bool|false $receivers_inclusion
	 * @param boolean $ignore_mode
	 *
	 * @return bool
	 */
	public function send_to_receivers( $hook_data, $excluded_from_sync = false, $receivers_inclusion = false, $ignore_mode = false ) {
		global $_bea_origin_blog_id;

		// Set hook data into class var
		$this->_hook_data = $hook_data;
		unset( $hook_data );

		// Inclusion is not FALSE? But a empty array ? On manual mode or ignore mode ?
		if ( ( 'manual' === $this->mode || true === $ignore_mode ) && is_array( $receivers_inclusion ) && empty( $receivers_inclusion ) ) {
			return false;
		}

		// Emitter content is excluded from SYNC ?
		if ( ( 'auto' === $this->mode || true === $ignore_mode ) && true === $excluded_from_sync ) {
			return false;
		}

		// Get current filter
		$this->_current_filter = current_filter();

		// Explode filter for get object and method
		$current_filter_data = explode( '/', $this->_current_filter );

		// Set data into variable for improve lisibility
		$this->_current_object    = $current_filter_data[1];
		$this->_current_method    = $current_filter_data[2];
		$this->_current_blog_id   = $current_filter_data[4];
		$this->_current_object_id = $this->get_id_from_object();

		// Send data for each receivers
		foreach ( $this->get_receivers() as $receiver_blog_id ) {
			// Skip infinite sync when emetter and receiver are reciprocal
			if ( isset( $_bea_origin_blog_id ) && $_bea_origin_blog_id == $receiver_blog_id ) {
				continue;
			}

			// Skip sync when emetter and receiver are egal
			if ( get_current_blog_id() == $receiver_blog_id ) {
				continue;
			}

			// Set receiver blog id as var class
			$this->_current_receiver_blog_id = (int) $receiver_blog_id;

			// Allow data manipulation for each receiver
			$this->_current_receiver_blog_id = apply_filters( 'bea_csf.pre_pre_send_data', $this->_current_receiver_blog_id, $this );

			// If current_receiver_blog_id is empty, skip to next receiver
			if ( empty( $this->_current_receiver_blog_id ) ) {
				continue;
			}

			// Keep only ID on inclusion custom param
			if ( ( 'manual' === $this->mode || true === $ignore_mode ) && is_array( $receivers_inclusion ) && ! in_array( $receiver_blog_id, $receivers_inclusion ) ) {
				continue;
			}

			// Allow data manipulation for each receiver
			$this->_current_receiver_blog_id = apply_filters( 'bea_csf.pre_send_data', $this->_current_receiver_blog_id, $this );

			// If current_receiver_blog_id is empty, skip to next receiver
			if ( empty( $this->_current_receiver_blog_id ) ) {
				continue;
			}

			BEA_CSF_Async::insert( $this->_current_object, $this->_current_object_id, $this->_current_filter, $this->_current_receiver_blog_id, $this->get_fields() );
		}

		do_action( 'bea-csf-after-send_to_receivers', $this );

		return true;
	}

	public function get_id_from_object() {
		if ( 'PostType' === $this->_current_object || 'Attachment' === $this->_current_object ) {
			return $this->_hook_data->ID;
		} elseif ( 'Taxonomy' === $this->_current_object ) {
			return $this->_hook_data->taxonomy . '|||' . $this->_hook_data->term_id;
		} elseif ( 'P2P' === $this->_current_object ) {
			return $this->_hook_data;
		}

		return 0;
	}
}
