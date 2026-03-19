<div class="main">
	<p>
		<?php
		/* translators: %d: Number of items waiting in the sync queue. */
		printf( esc_html__( 'There are still %d item(s) to synchronize.', 'bea-content-sync-fusion' ), (int) $counter );
		?>
	</p>

	<?php if ( $counter > 0 ) : ?>
		<form method="post">
			<p class="submit">
				<?php wp_nonce_field( 'bea-csf-force-refresh' ); ?>
				<input type="submit" class="button-primary" name="bea_csf_force_blog_refresh" value="<?php esc_attr_e( 'Force sync 30 first items', 'bea-content-sync-fusion' ); ?>">
			</p>
		</form>
	<?php endif; ?>
</div>
