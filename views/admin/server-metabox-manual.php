<p>
	<?php _e( 'You can choose which sites should receive this content. Select no site does not synchronize this content.', BEA_CSF_LOCALE ); ?>
</p>

<div class="wp-tab-panel">
	<ul class="categorychecklist form-no-clear">
		<?php foreach ( $sync_receivers as $blog ) : ?>
			<li>
				<label class="selectit">
					<input type="checkbox" name="post_receivers[]"
					       value="<?php echo $blog['blog_id']; ?>" <?php checked( in_array( $blog['blog_id'], $current_values ), true ); ?> />&nbsp;
					<?php esc_html_e( $blog['blogname'] ); ?>
				</label>
			</li>
		<?php endforeach; ?>
	</ul>
</div>

<p>
	<?php printf( __( 'This content is concerned with these following synchronizations: <strong>%s</strong>', BEA_CSF_LOCALE ), implode( ', ', $sync_names ) ); ?>
</p>
