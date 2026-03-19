<?php $input_id = sprintf( 'include-from-sync-%d', $post->ID ); ?>
<p>
	<?php esc_html_e( 'This content will be transmitted to other network sites. You decide to include from synchronization by checking the box below.', 'bea-content-sync-fusion' ); ?>
</p>

<input type="checkbox" id="<?php echo esc_attr( $input_id ); ?>" name="include_from_sync"
	   value="1" <?php checked( $include_attachments, 1 ); ?> />
<input type="hidden" name="mode" value="exclude_default"/>
<label for="<?php echo esc_attr( $input_id ); ?>">
	<?php esc_html_e( 'Include this content from synchronization', 'bea-content-sync-fusion' ); ?>
</label>

<p>
	<?php
	/* translators: %s: Comma-separated list of synchronization names. */
	printf( esc_html__( 'This content is concerned with these following synchronizations: <strong>%s</strong>', 'bea-content-sync-fusion' ), esc_html( implode( ', ', $sync_names ) ) );
	?>
</p>
