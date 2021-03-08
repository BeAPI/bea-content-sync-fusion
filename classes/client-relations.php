<?php

class BEA_CSF_Client_Relations {
	/**
	 * BEA_CSF_Relations constructor.
	 * Hook delete post, attachment, term, blog...
	 *
	 */
	public function __construct() {
		add_action( 'deleted_post', array( __CLASS__, 'deleted_post' ), 10 );
		add_action( 'delete_term', array( __CLASS__, 'delete_term' ), 10 );
		add_action( 'deleted_blog', array( __CLASS__, 'deleted_blog' ), 10 );
	}

	/**
	 * Delete data from relations table when post is deleted from DB
	 *
	 * @param integer $object_id
	 */
	public static function deleted_post( $object_id ) {
		BEA_CSF_Relations::delete_by_receiver( 'attachment', get_current_blog_id(), $object_id );
		BEA_CSF_Relations::delete_by_receiver( 'posttype', get_current_blog_id(), $object_id );
	}

	/**
	 * Delete data from relations table when term is deleted from DB
	 *
	 * @param integer $term_id
	 */
	public static function delete_term( $term_id ) {
		BEA_CSF_Relations::delete_by_receiver( 'taxonomy', get_current_blog_id(), $term_id );
	}

	/**
	 * Delete data from relations table when blog is deleted from DB
	 *
	 * @param integer $blog_id
	 */
	public static function deleted_blog( $blog_id ) {
		BEA_CSF_Relations::delete_by_blog_id( $blog_id );
	}
}
