<p>
	<?php _e( 'You can choose which sites should receive this content. The publication status is taken into account only during the first synchronization on the concerned site.', 'bea-content-sync-fusion' ); ?>
</p>

<div class="wp-tab-panel">
	<ul class="categorychecklist form-no-clear">
		<?php
        foreach ( $sync_receivers as $blog ) :
            if ( !isset($current_post_receivers_status[$blog['blog_id']]) ) {
	            $current_post_receivers_status[$blog['blog_id']] = 'publish';
            }
            ?>
			<li style="clear: both;">
				<label class="selectit">
					<input type="checkbox" name="post_receivers[]"
					       value="<?php echo $blog['blog_id']; ?>" <?php checked( in_array( $blog['blog_id'], $current_post_receivers ), true ); ?> />&nbsp;
					<?php esc_html_e( $blog['blogname'] ); ?>
				</label>

                <?php if ( $show_blog_status == true ) : ?>

                    <select name="post_receivers_status[<?php echo $blog['blog_id']; ?>]" style="float:right;">
                        <option value="publish" <?php selected( $current_post_receivers_status[$blog['blog_id']], 'publish', true ); ?>><?php _e( 'Publish always', 'bea-content-sync-fusion' ); ?></option>
                        <option value="pending" <?php selected( $current_post_receivers_status[$blog['blog_id']], 'pending', true ); ?>><?php _e( 'Pending + Leave as', 'bea-content-sync-fusion' ); ?></option>

                        <?php if ( defined('REVISIONIZE_VERSION') ) : ?>
                            <option value="publish-draft" <?php selected( $current_post_receivers_status[$blog['blog_id']], 'publish-draft', true ); ?>><?php _e( 'Publish + Draft future', 'bea-content-sync-fusion' ); ?></option>
                            <option value="pending-draft" <?php selected( $current_post_receivers_status[$blog['blog_id']], 'pending-draft', true ); ?>><?php _e( 'Pending + Draft future', 'bea-content-sync-fusion' ); ?></option>
                        <?php endif; ?>
                    </select>

                <?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>

<p>
    <label for="post_receivers_note"><?php _e( 'An internal note for diffusion.', 'bea-content-sync-fusion' ); ?></label>
    <textarea class="widefat" id="post_receivers_note" name="post_receivers_note"><?php echo esc_textarea($current_receivers_note); ?></textarea>
</p>

<p>
	<?php printf( __( 'This content is concerned with these following synchronizations: <strong>%s</strong>', 'bea-content-sync-fusion' ), implode( ', ', $sync_names ) ); ?>
</p>
