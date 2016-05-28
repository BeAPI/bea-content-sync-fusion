<?php
class MFT_Admin{

	/**
	 * MFT_Admin constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function admin_notices() {
		if( MFT_Migration::is_finished() ) {
			return;
		}

		$class = 'notice';
		$message = sprintf( 'Meta for taxonomies : The migration to 4.4 native term meta is running. %s Todo', mft_get_terms_to_do() );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}
}
