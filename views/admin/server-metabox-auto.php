<p>
	<?php _e( 'This content will be transmitted to other network sites. You decide to exclude from synchronization by checking the box below.', 'bea-content-sync-fusion' ); ?>
</p>

<input type="checkbox" id="exclude_from_sync" name="exclude_from_sync"
       value="1" <?php checked( $current_value, 1 ); ?> />
<label for="exclude_from_sync">
	<?php _e( "Exclude this content from synchronization", 'bea-content-sync-fusion' ); ?>
</label>

<p>
    <label for="post_receivers_note"><?php _e( 'An internal note for diffusion.', 'bea-content-sync-fusion' ); ?></label>
    <textarea class="widefat" id="post_receivers_note" name="post_receivers_note"><?php echo esc_textarea($current_receivers_note); ?></textarea>
</p>

<p>
	<?php printf( __( 'This content is concerned with these following synchronizations: <strong>%s</strong>', 'bea-content-sync-fusion' ), implode( ', ', $sync_names ) ); ?>
</p>
