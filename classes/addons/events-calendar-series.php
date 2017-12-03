<?php
/**
 * Class BEA_CSF_Addon_Events_Calendar_Series
 *
 * Addon for Events Calendar, Events Calendar Pro, Events Tickets, Events Tickets Pro
 *
 * @author Amaury BALMER
 */

class BEA_CSF_Addon_Events_Calendar_Series {

	public function __construct() {
		if ( ! defined( 'EVENT_TICKETS_DIR' ) ) {
			return false;
		}

		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 1 );

		add_action( 'bea_csf.server.posttype.merge', array( __CLASS__, 'bea_csf_server_posttype_merge' ), 10, 2 );
		add_action( 'bea_csf.client.posttype.merge', array( __CLASS__, 'bea_csf_client_posttype_merge_tickets' ), 10, 3 );
		add_action( 'bea_csf.client.posttype.merge', array( __CLASS__, 'bea_csf_client_posttype_merge_venue' ), 10, 3 );
		add_action( 'bea_csf.client.posttype.merge', array( __CLASS__, 'bea_csf_client_posttype_merge_organizer' ), 10, 3 );

		return true;
	}

	/**
	 * Add event and all related tickets into queue when save tickets
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public static function save_post( $post_id ) {
		global $wpdb;

		$post_type = get_post_type( $post_id );
		if ( "tribe_rsvp_tickets" != $post_type ) {
			return false;
		}

		// Get emitter for current post
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( 'posttype', $wpdb->blogid, $post_id );
		if ( ! empty( $emitter_relation ) ) {
			return false;
		}

		$event_id = get_post_meta( $post_id, '_tribe_rsvp_for_event', true );
		if ( $event_id == false ) {
			return false;
		}

		$event = get_post( $event_id );
		if ( $event == false ) {
			return false;
		}

		do_action( 'transition_post_status', $event->post_status, $event->post_status, $event );

		return true;
	}

	/**
	 * From server part, get all tickets for current event
	 *
	 * @param array $data
	 * @param array $sync_fields
	 *
	 * @return array mixed
	 */
	public static function bea_csf_server_posttype_merge( $data, $sync_fields ) {
		if ( ! isset( $data['post_type'] ) || 'tribe_events' !== $data['post_type'] ) {
			return $data;
		}

		// Get tickets
		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $data['ID'] );
		if ( $tickets != false && ! empty( $tickets ) ) {
			$data['tickets'] = array();

			foreach ( $tickets as $ticket ) {
				$data['tickets'][] = BEA_CSF_Server_PostType::get_data( $ticket->ID, $sync_fields );
			}
		}

		// Get organizer
		$data['_EventOrganizerID'] = get_post_meta( $data['ID'], '_EventOrganizerID', true );
		if ( $data['_EventOrganizerID'] != false ) {
			$data['_EventOrganizer'] = BEA_CSF_Server_PostType::get_data( $data['_EventOrganizerID'], $sync_fields );
		}

		// Get venue
		$data['_EventVenueID'] = get_post_meta( $data['ID'], '_EventVenueID', true );
		if ( $data['_EventVenueID'] != false ) {
			$data['_EventVenue'] = BEA_CSF_Server_PostType::get_data( $data['_EventVenueID'], $sync_fields );
		}

		return $data;
	}

	/**
	 * Insert/update organizer on client part after event insertion
	 *
	 * @param array $data
	 * @param array $sync_fields
	 * @param WP_Post $new_post
	 *
	 * @return array mixed
	 */
	public static function bea_csf_client_posttype_merge_organizer( $data, $sync_fields, $new_post ) {
		if ( ! isset( $data['_EventOrganizer'] ) || empty( $data['_EventOrganizer'] ) ) {
			return $data;
		}

		// Get local event
		$local_event_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( false == $local_event_id ) {
			return $data;
		}

		// Create organizer on client if not exists
		$local_organizer_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['_EventOrganizerID'], $data['_EventOrganizerID'] );
		if ( false == $local_organizer_id ) {
			$data['_EventOrganizer']['blogid'] = $data['blogid'];
			BEA_CSF_Client_PostType::merge( $data['_EventOrganizer'], $sync_fields );

			// Renew mapping ID
			$local_organizer_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['_EventOrganizerID'], $data['_EventOrganizerID'] );
		}

		// Update local organizer
		update_post_meta( $local_event_id, '_EventOrganizerID', $local_organizer_id );

		return $data;
	}

	/**
	 * Insert/update venue on client part after event insertion
	 *
	 * @param array $data
	 * @param array $sync_fields
	 * @param WP_Post $new_post
	 *
	 * @return array mixed
	 */
	public static function bea_csf_client_posttype_merge_venue( $data, $sync_fields, $new_post ) {
		if ( ! isset( $data['_EventVenue'] ) || empty( $data['_EventVenue'] ) ) {
			return $data;
		}

		// Get local event
		$local_event_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( false == $local_event_id ) {
			return $data;
		}

		// Create venue on client if not exists
		$local_venue_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['_EventVenueID'], $data['_EventVenueID'] );
		if ( false == $local_venue_id ) {
			$data['_EventVenue']['blogid'] = $data['blogid'];
			BEA_CSF_Client_PostType::merge( $data['_EventVenue'], $sync_fields );

			// Renew mapping ID
			$local_venue_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['_EventVenueID'], $data['_EventVenueID'] );
		}

		// Update local venue
		update_post_meta( $local_event_id, '_EventVenueID', $local_venue_id );

		return $data;
	}

	/**
	 * Insert/update tickets on client part after event insertion
	 *
	 * @param array $data
	 * @param array $sync_fields
	 * @param WP_Post $new_post
	 *
	 * @return array mixed
	 */
	public static function bea_csf_client_posttype_merge_tickets( $data, $sync_fields, $new_post ) {
		// No tickets, no sync :)
		if ( ! isset( $data['tickets'] ) || empty( $data['tickets'] ) ) {
			return $data;
		}

		// Get local event
		$local_event_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( false == $local_event_id ) {
			return $data;
		}

		// Get locals tickets
		$local_tickets    = Tribe__Tickets__Tickets::get_event_tickets( $local_event_id );
		$local_tickets_id = wp_list_pluck( $local_tickets, 'ID' );

		// Loop on each tickets for insertion, and keep ID
		$remote_tickets_id = array();
		foreach ( $data['tickets'] as &$ticket ) {
			// Fix event ID with local value
			if ( isset( $ticket['meta_data']['_tribe_rsvp_for_event'] ) ) {
				$ticket['meta_data']['_tribe_rsvp_for_event'][0] = $local_event_id;
			}

			$ticket['blogid'] = $data['blogid'];
			BEA_CSF_Client_PostType::merge( $ticket, $sync_fields );

			// Translated remote tickets with current ID
			$local_ticket_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $ticket['ID'], $ticket['ID'] );
			if ( false != $local_ticket_id ) {
				$remote_tickets_id[] = (int) $local_ticket_id;
			}
		}

		// Calcul diff between local and remote for delete "old remote tickets deleted"
		$tickets_id_to_delete = array_diff( $local_tickets_id, $remote_tickets_id );
		if ( ! empty( $tickets_id_to_delete ) ) {
			foreach ( $tickets_id_to_delete as $ticket_id ) {
				wp_delete_post( $ticket_id, true );
			}
		}

		return $data;
	}
}