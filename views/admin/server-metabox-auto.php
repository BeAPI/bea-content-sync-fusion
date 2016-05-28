<p>
	<?php _e( 'This content will be transmitted to other network sites. You decide to exclude from synchronization by checking the box below.', BEA_CSF_LOCALE ); ?>
</p>

<input type="checkbox" id="exclude_from_sync" name="exclude_from_sync"
       value="1" <?php checked( $current_value, 1 ); ?> />
<label for="exclude_from_sync">
	<?php _e( "Exclude this content from synchronization", BEA_CSF_LOCALE ); ?>
</label>

<p>
	<?php printf( __( 'This content is concerned with these following synchronizations: <strong>%s</strong>', BEA_CSF_LOCALE ), implode( ', ', $sync_names ) ); ?>
</p>
