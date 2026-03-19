<tr>
	<th scope="row"><label for="description"><?php esc_html_e( 'Synchronizations (manual)', 'bea-content-sync-fusion' ); ?></label></th>
	<td>
		<p>
			<?php esc_html_e( 'You can choose which sites should receive this content. Select no site does not synchronize this content.', 'bea-content-sync-fusion' ); ?>
		</p>

		<div class="wp-tab-panel">
			<ul class="categorychecklist form-no-clear">
				<?php foreach ( $sync_receivers as $blog ) : ?>
					<li>
						<label class="selectit">
							<input type="checkbox" name="term_receivers[]"
								   value="<?php echo esc_attr( $blog['blog_id'] ); ?>" <?php checked( in_array( $blog['blog_id'], $current_values, true ), true ); ?> />&nbsp;
							<?php echo esc_html( $blog['blogname'] ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<?php if ( ! empty( $sync_names ) ) : ?>
			<p>
				<?php
				/* translators: %s: Comma-separated list of synchronization names. */
				printf( esc_html__( 'This content is concerned with these following synchronizations: <strong>%s</strong>', 'bea-content-sync-fusion' ), esc_html( implode( ', ', $sync_names ) ) );
				?>
			</p>
		<?php endif; ?>
	</td>
</tr>
