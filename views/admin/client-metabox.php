<p>
	<?php _e( 'This content comes from a synchronization. You can choose to stop receiving updates by checking the following box.', 'bea-content-sync-fusion' ); ?>
</p>

<input type="checkbox" id="exclude_from_futur_sync" name="exclude_from_futur_sync" value="1" <?php checked( $current_value, 1 ); ?> />
<label for="exclude_from_futur_sync">
	<?php _e( 'Exclude this content from future synchronization', 'bea-content-sync-fusion' ); ?>
</label>

<p>
	<h4><?php _e( 'An internal note for diffusion:', 'bea-content-sync-fusion' ); ?></h4>
	<textarea class="widefat" readonly="readonly"><?php echo esc_textarea( $current_receivers_note ); ?></textarea>
</p>

<p>
	<?php
	printf( __( 'This content comes from the site <strong>%1$s</strong>, and from the original article: <strong>%2$s</strong>', 'bea-content-sync-fusion' ), $emitter_data['blog_name'], $emitter_data['post_title'] );
	?>
</p>
