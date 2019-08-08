<?php
// Bail if WP-CLI is not present
if ( ! defined( 'WP_CLI' ) ) {
	return false;
}

class BEA_CSF_Cli_Relation extends WP_CLI_Command {
	private $emitter_blog_id = 0;
	private $receiver_blog_id = 0;

	private $global_counter = 0;

	/**
	 * This command allow to populate relation table after duplicate a blog on WordPress
	 *
	 * ## OPTIONS
	 *
	 * <emitter_blog_id>
	 * : The emitter
	 *
	 * <receiver_blog_id>
	 * : The receiver
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-sync-fusion relation mirror 1 5
	 *
	 * @when after_wp_load
	 *
	 * @param $args
	 * @param $params
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function mirror( $args, $params ) {
		if ( count( $args ) !== 2 ) {
			WP_CLI::error( 'Missing one parameter for emitter/receiver blog id' );
		}

		$this->emitter_blog_id  = absint( $args[0] );
		$this->receiver_blog_id = absint( $args[1] );

		if ( 1 === $this->receiver_blog_id ) {
			WP_CLI::error( 'You can\'t mirror a site to id 1' );
		}

		switch_to_blog( $this->emitter_blog_id );

		$syncs = $this->get_emitter_syncs();
		if ( empty( $syncs ) ) {
			WP_CLI::error( 'No sync registered for this emitter blog' );
		}

		$attachments = false;
		$post_types  = [
			'auto'   => [],
			'manual' => [],
		];
		$taxonomies  = [];

		foreach ( $syncs as $sync ) {
			/** @var BEA_CSF_Synchronization $sync */

			$receiver_blogs = $sync->get_receivers();
			if ( ! in_array( $this->receiver_blog_id, $receiver_blogs, true ) ) {
				WP_CLI::debug( sprintf( 'Skip sync %s because blog is not receiver', $sync->label ) );
				continue;
			}

			if ( ! empty( $sync->post_type ) ) {
				if ( 'attachment' === $sync->post_type ) {
					$attachments = true;
				} else {
					$post_types[ $sync->mode ][] = $sync->post_type;
				}
			}

			if ( ! empty( $sync->taxonomies ) ) {
				$taxonomies = array_merge( array_values( $taxonomies ), array_values( $sync->taxonomies ) );
			}
		}

		if ( true === $attachments ) {
			$this->mirror_attachments();
		}

		if ( ! empty( $post_types['auto'] ) ) {
			$this->mirror_post_types( $post_types['auto'], false );
		}

		if ( ! empty( $post_types['manual'] ) ) {
			$this->mirror_post_types( $post_types['manual'], true );
		}

		if ( ! empty( $taxonomies ) ) {
			$this->mirror_taxonomies( $taxonomies );
		}

		WP_CLI::success( sprintf( 'Mirror success, %d relations added !', $this->global_counter ) );
	}

	/**
	 * Mirroring attachments
	 */
	private function mirror_attachments() {
		$results = BEA_CSF_Cli_Helper::get_attachments();

		// Loop on attachments
		foreach ( (array) $results as $result ) {
			if ( ! is_a( $result, 'WP_Post' ) ) {
				continue;
			}

			$this->global_counter ++;

			BEA_CSF_Relations::merge( 'attachment', $this->emitter_blog_id, $result->ID, $this->receiver_blog_id, $result->ID );
		}
	}

	/**
	 * Mirroring post types
	 *
	 * @param array $post_types
	 * @param bool $is_manual
	 */
	private function mirror_post_types( $post_types, $is_manual = false ) {
		$results = BEA_CSF_Cli_Helper::get_posts( [ 'post_type' => $post_types ] );

		// Loop on posts
		foreach ( (array) $results as $result ) {
			if ( ! is_a( $result, 'WP_Post' ) ) {
				continue;
			}

			$this->global_counter ++;

			BEA_CSF_Relations::merge( 'posttype', $this->emitter_blog_id, $result->ID, $this->receiver_blog_id, $result->ID );

			if ( true === $is_manual ) {
				$meta_key = '_b' . $this->emitter_blog_id . '_post_receivers';

				$_post_receivers   = (array) get_post_meta( $result->ID, $meta_key, true );
				$_post_receivers[] = $this->receiver_blog_id;
				$_post_receivers   = array_unique( $_post_receivers );
				update_post_meta( $result->ID, $meta_key, $_post_receivers );
			}
		}
	}

	/**
	 * Mirroring taxonomies
	 *
	 * @param $taxonomies array with taxo names
	 */
	private function mirror_taxonomies( $taxonomies ) {
		$results = BEA_CSF_Cli_Helper::get_terms( $taxonomies );

		// Loop on terms
		foreach ( (array) $results as $result ) {
			if ( ! isset( $result->term_id ) ) {
				continue;
			}

			$this->global_counter ++;

			BEA_CSF_Relations::merge( 'taxonomy', $this->emitter_blog_id, $result->term_id, $this->receiver_blog_id, $result->term_id );
		}
	}

	/**
	 * Get sync for emitter
	 *
	 * @return array
	 */
	private function get_emitter_syncs() {
		$has_syncs = BEA_CSF_Synchronizations::get(
			[ 'emitters' => $this->emitter_blog_id ],
			'AND',
			false,
			true
		);

		return $has_syncs;
	}
}

WP_CLI::add_command(
	'content-sync-fusion relation',
	'BEA_CSF_Cli_Relation',
	array(
		'shortdesc' => __( 'All commands related "relation features" to the BEA Content Sync Fusion plugin', 'bea-content-sync-fusion' ),
	)
);
