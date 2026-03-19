<div class="wrap">
	<h2><?php esc_html_e( 'Content Sync: Queue', 'bea-content-sync-fusion' ); ?></h2>

	<?php
	$nbqueue = BEA_CSF_Async::get_counter();

	/* translators: 1,2: Number of items processed per cron batch (same value shown twice per original wording). */
	echo '<p>' . esc_html( sprintf( __( 'Cron process %1$d items by %2$d items', 'bea-content-sync-fusion' ), (int) BEA_CSF_CRON_QTY, (int) BEA_CSF_CRON_QTY ) ) . '</p>';

	echo '<p>' . esc_html( __( 'Number of items in the queue : ', 'bea-content-sync-fusion' ) ) . esc_html( (string) $nbqueue ) . '</p>';

	$lock_file = sys_get_temp_dir() . '/bea-content-sync-fusion.lock';
	if ( file_exists( $lock_file ) ) {
		/* translators: 1: Lock file path, 2: Last modified time (GMT). */
		$bea_csf_lock_modified_msg = __( 'The file %1$s has been modified : %2$s', 'bea-content-sync-fusion' );
		echo '<p>' . esc_html(
			sprintf(
				$bea_csf_lock_modified_msg,
				$lock_file,
				gmdate( 'd F Y H:i:s.', filemtime( $lock_file ) )
			)
		) . '</p>';
		?>
		<form action="" method="post">
			<p class="submit">
				<?php wp_nonce_field( 'delete-bea-csf-file-lock' ); ?>
				<input type="submit" class="button-primary" name="delete-bea-csf-file-lock"
					   value="<?php esc_attr_e( 'Delete file lock', 'bea-content-sync-fusion' ); ?>"/>
			</p>
		</form>
		<?php
	}
	if ( isset( $_GET['message'] ) && 'deleted' === $_GET['message'] ) {
		/* translators: %s: Path to the deleted lock file. */
		echo '<p>' . esc_html( sprintf( __( 'The file %s has been deleted', 'bea-content-sync-fusion' ), $lock_file ) ) . '</p>';
	}

	// Maintenance
	global $wpdb;
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is the registered wpdb->bea_csf_queue_maintenance identifier.
	$nb_queue_maintenance = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->bea_csf_queue_maintenance}" );
	if ( $nb_queue_maintenance > 0 ) {
		echo '<p>' . esc_html( __( 'Number of items in the queue of maintenance : ', 'bea-content-sync-fusion' ) ) . esc_html( (string) $nb_queue_maintenance ) . '</p>';
	}
	?>

	<h3><?php esc_html_e( 'For debug only (30 items only)', 'bea-content-sync-fusion' ); ?></h3>
	<p><?php esc_html_e( 'You can now debug the queue of each site one by one from their dashboard or the list of sites view.', 'bea-content-sync-fusion' ); ?></p>
</div>
