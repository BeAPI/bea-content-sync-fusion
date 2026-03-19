<tr class="form-field">
	<th scope="row" valign="top"><label for="emitter-blog"><?php esc_html_e( 'Emitter', 'bea-content-sync-fusion' ); ?></label></th>

	<td>
		<?php if ( self::is_valid_blog_id( $_origin_blog_id ) ) : ?>
			<?php esc_html_e( 'Blog:', 'bea-content-sync-fusion' ); ?>
			<select id="emitter-blog" name="term_emitter[blog_id]">
				<?php foreach ( BEA_CSF_Synchronizations::get_sites_from_network() as $site ) : ?>
					<option
						value="<?php echo esc_attr( $site['blog_id'] ); ?>" <?php selected( $site['blog_id'], $_origin_blog_id, true ); ?>><?php echo esc_html( $site['blogname'] . ' (' . $site['domain'] . $site['path'] . ')' ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php switch_to_blog( $_origin_blog_id ); ?>
			<br/>
			<?php esc_html_e( 'Term:', 'bea-content-sync-fusion' ); ?>
			<select name="term_emitter[term_id]">
				<option value=""><?php esc_html_e( 'No term or invalid term', 'bea-content-sync-fusion' ); ?></option>
				<?php
				foreach ( get_terms(
					[
						'taxonomy' => $_origin_taxonomy,
						'hide_empty' => false,
					]
				) as $_term ) :
					?>
					<option
						value="<?php echo esc_attr( $_term->term_id ); ?>" <?php selected( $_term->term_id, $_origin_term_id, true ); ?>><?php echo esc_html( $_term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php restore_current_blog(); ?>
		<?php endif; ?>
	</td>
</tr>
