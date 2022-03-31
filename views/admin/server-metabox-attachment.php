<?php $input_id = sprintf('include-from-sync-%d', $post->ID); ?>
<p>
	<?php _e( 'This content will be transmitted to other network sites. You decide to include from synchronization by checking the box below.', 'bea-content-sync-fusion' ); ?>
</p>

<input type="checkbox" id="<?php echo esc_attr($input_id); ?>" name="include_from_sync"
	   value="1" <?php checked( $include_attachments, 1 ); ?> />
<input type="hidden" name="mode" value="exclude_default"/>
<label for="<?php echo esc_attr($input_id); ?>">
	<?php _e( 'Include this content from synchronization', 'bea-content-sync-fusion' ); ?>
</label>

<p>
	<?php printf( __( 'This content is concerned with these following synchronizations: <strong>%s</strong>', 'bea-content-sync-fusion' ), implode( ', ', $sync_names ) ); ?>
</p>
