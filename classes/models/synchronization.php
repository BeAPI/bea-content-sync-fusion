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
		'notifications',
		'emitters',
		'receivers'
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
	public $notifications = 1;
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

		// Register actions if sync is active and haven't a conflict emitters/receivers
		if ( $this->active == 0 ) {
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
		$emitters = $this->get_emitters();
		if ( empty( $emitters ) ) {
			return false;
		}

		foreach ( $emitters as $emitter_blog_id ) {
			// Register this hook only for post type attachment for evite doublon sync item
			if ( $this->post_type == 'attachment' ) { // Specific CPT : Attachments

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
						continue;
					}

					$this->_register_hooks[] = 'bea-csf' . '/' . 'Taxonomy' . '/' . 'delete' . '/' . $taxonomy . '/' . $emitter_blog_id;
					$this->_register_hooks[] = 'bea-csf' . '/' . 'Taxonomy' . '/' . 'merge' . '/' . $taxonomy . '/' . $emitter_blog_id;

					$connection_taxo_duplicate[ $taxonomy . '/' . $emitter_blog_id ] = true;
				}
			}

			// P2P
			/*
			if ( ! empty( $this->p2p_connections ) ) {
				foreach ( $this->p2p_connections as $p2p_connection ) {
					$this->_register_hooks[] = 'bea-csf' . '/' . 'P2P' . '/' . 'delete' . '/' . $p2p_connection . '/' . $emitter_blog_id;
					$this->_register_hooks[] = 'bea-csf' . '/' . 'P2P' . '/' . 'merge' . '/' . $p2p_connection . '/' . $emitter_blog_id;
				}
			}
			*/
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
	 *
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

		return ! empty( $result );
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
	 * @return array
	 */
	public function get_receivers() {
		global $wpdb;

		$results = array();
		foreach ( $this->receivers as $key => $receiver_blog_id ) {
			if ( $receiver_blog_id == 'all' ) {
				// Get all sites
				$blogs = self::get_sites_from_network( 0, false );
				foreach ( $blogs as $blog ) {
					// Exclude current emitter
					if ( $blog['blog_id'] != $wpdb->blogid ) {
						$results[] = $blog['blog_id'];
					}
				}
			} else {
				$results[] = $receiver_blog_id;
			}
		}

		return $results;
	}

	public function get_emitters() {
		$results = array();
		foreach ( $this->emitters as $key => $emitter_blog_id ) {
			if ( $emitter_blog_id == 'all' ) {
				// Get all sites
				$blogs = self::get_sites_from_network( 0, false );
				foreach ( $blogs as $blog ) {
					$results[] = $blog['blog_id'];
				}
			} else {
				$results[] = $emitter_blog_id;
			}
		}

		return $results;
	}

	/**
	 * Helper: Get sites list for a network ID
	 *
	 * @param int $network_id
	 * @param bool $get_blog_name
	 *
	 * @return array|bool
	 * @author Amaury Balmer
	 */
	public static function get_sites_from_network( $network_id = 0, $get_blog_name = true ) {
		global $wpdb;

		if ( $network_id == 0 ) {
			$network_id = $wpdb->siteid;
		}

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC", $network_id ), ARRAY_A );
		if ( empty( $results ) ) {
			return false;
		}

		$sites = array();
		foreach ( $results as $result ) {
			$sites[ $result['blog_id'] ] = $result;
			if ( $get_blog_name == true ) {
				$sites[ $result['blog_id'] ]['blogname'] = get_blog_option( $result['blog_id'], 'blogname' );
			}
		}

		return $sites;
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
		// Set hook data into class var
		$this->_hook_data = $hook_data;
		unset( $hook_data );

		// Inclusion is not FALSE? But a empty array ? On manual mode or ignore mode ?
		if ( ( $this->mode == 'manual' || $ignore_mode == true ) && is_array( $receivers_inclusion ) && empty( $receivers_inclusion ) ) {
			return false;
		}

		// Emitter content is excluded from SYNC ?
		if ( ( $this->mode == 'auto' || $ignore_mode == true ) && $excluded_from_sync === true ) {
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
			// Set receiver blog id as var class
			$this->_current_receiver_blog_id = (int) $receiver_blog_id;

			// Allow data manipulation for each receiver
			$this->_current_receiver_blog_id = apply_filters( 'bea_csf.pre_pre_send_data', $this->_current_receiver_blog_id, $this );

			// If current_receiver_blog_id is empty, skip to next receiver
			if ( empty( $this->_current_receiver_blog_id ) ) {
				continue;
			}

			// Keep only ID on inclusion custom param
			if ( ( $this->mode == 'manual' || $ignore_mode == true ) && is_array( $receivers_inclusion ) && ! in_array( $receiver_blog_id, $receivers_inclusion ) ) {
				continue;
			}

			// Allow data manipulation for each receiver
			$this->_current_receiver_blog_id = apply_filters( 'bea_csf.pre_send_data', $this->_current_receiver_blog_id, $this );

			// If current_receiver_blog_id is empty, skip to next receiver
			if ( empty( $this->_current_receiver_blog_id ) ) {
				continue;
			}

			BEA_CSF_Async::insert( $this->_current_object_id, $this->_current_filter, $this->_current_receiver_blog_id, $this->get_fields() );
		}

		do_action( 'bea-csf-after-send_to_receivers', $this );

		return true;
	}

	public function get_id_from_object() {
		if ( $this->_current_object == 'PostType' || $this->_current_object == 'Attachment' ) {
			return $this->_hook_data->ID;
		} elseif ( $this->_current_object == 'Taxonomy' ) {
			return $this->_hook_data->taxonomy . '|||' . $this->_hook_data->term_id;
		}

		return 0;
	}
}
