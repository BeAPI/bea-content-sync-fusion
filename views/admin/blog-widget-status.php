<div class="main">
	<p>
		<?php printf( esc_html__( 'There are still %d item(s) to synchronize.', 'bea-content-sync-fusion' ), BEA_CSF_Async::get_counter( get_current_blog_id() ) ); ?>
	</p>

	<?php if ( BEA_CSF_Async::get_counter( get_current_blog_id() ) > 0 ) : ?>
		<form method="post">
			<p class="submit">
				<?php wp_nonce_field( 'bea-csf-force-refresh' ); ?>
				<input type="submit" class="button-primary" name="bea_csf_force_blog_refresh" value="<?php _e( 'Force sync 30 first items', 'bea-content-sync-fusion' ); ?>">
			</p>
		</form>
	<?php endif; ?>
</div>
