<div class="wrap">
	<h2><?php _e( 'Content Sync: Queue', 'bea-content-sync-fusion' ); ?></h2>

	<?php
	global $wpdb;
	$nbqueue = BEA_CSF_Async::get_counter();

	echo '<p>' . sprintf( __( 'Cron process %1$d items by %2$d items', 'bea-content-sync-fusion' ), BEA_CSF_CRON_QTY, BEA_CSF_CRON_QTY ) . '</p>';

	echo __( 'Number of items in the queue : ', 'bea-content-sync-fusion' ) . $nbqueue;

	$lock_file = sys_get_temp_dir() . '/bea-content-sync-fusion.lock';
	if ( file_exists( $lock_file ) ) {
		echo '<p>' . sprintf( __( 'The file %s has been modified : ', 'bea-content-sync-fusion' ), $lock_file ) . date( 'd F Y H:i:s.', filemtime( $lock_file ) ) . '</p>';
		?>
		<form action="" method="post">
			<p class="submit">
				<?php wp_nonce_field( 'delete-bea-csf-file-lock' ); ?>
				<input type="submit" class="button-primary" name="delete-bea-csf-file-lock"
					   value="<?php _e( 'Delete file lock', 'bea-content-sync-fusion' ); ?>"/>
			</p>
		</form>
		<?php
	}
	if ( isset( $_GET['message'] ) && $_GET['message'] == 'deleted' ) {
		echo '<p>' . sprintf( __( 'The file %s has been deleted', 'bea-content-sync-fusion' ), $lock_file ) . '</p>';
	}

	// Maintenance
	$nb_queue_maintenance = $wpdb->get_var( 'SELECT COUNT(id) as nbqueue FROM ' . $GLOBALS['wpdb']->bea_csf_queue_maintenance );
	if ( '0' != $nb_queue_maintenance ) {
		echo '<p>' . __( 'Number of items in the queue of maintenance : ', 'bea-content-sync-fusion' ) . $nb_queue_maintenance . '</p>';
	}

	?>

	<h3><?php _e( 'For debug only (30 items only)', 'bea-content-sync-fusion' ); ?></h3>
	<p><?php _e( 'You can now debug the queue of each site one by one from their dashboard or the list of sites view.', 'bea-content-sync-fusion' ); ?></p>
</div>
