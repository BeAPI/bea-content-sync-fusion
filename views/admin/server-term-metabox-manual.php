<tr>
	<th scope="row"><label for="description">Synchronizations (manual)</label></th>
	<td>
		<p>
			<?php _e( 'You can choose which sites should receive this content. Select no site does not synchronize this content.', 'bea-content-sync-fusion' ); ?>
		</p>

		<div class="wp-tab-panel">
			<ul class="categorychecklist form-no-clear">
				<?php foreach ( $sync_receivers as $blog ) : ?>
					<li>
						<label class="selectit">
							<input type="checkbox" name="term_receivers[]"
							       value="<?php echo $blog['blog_id']; ?>" <?php checked( in_array( $blog['blog_id'], $current_values ), true ); ?> />&nbsp;
							<?php esc_html_e( $blog['blogname'] ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<p>
			<?php printf( __( 'This content is concerned with these following synchronizations: <strong>%s</strong>', 'bea-content-sync-fusion' ), implode( ', ', $sync_names ) ); ?>
		</p>
	</td>
</tr>