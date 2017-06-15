<tr class="form-field">
	<th scope="row" valign="top"><label for="emitter-blog"><?php _e( 'Emitter', BEA_CSF_LOCALE ); ?></label></th>

	<td>
		<?php if ( self::is_valid_blog_id( $_origin_blog_id ) ) : ?>
			<?php _e( 'Blog:', BEA_CSF_LOCALE ); ?>
			<select id="emitter-blog" name="term_emitter[blog_id]">
				<?php foreach ( BEA_CSF_Admin_Synchronizations_Network::get_sites_from_network() as $site ) : ?>
					<option
						value="<?php echo esc_attr( $site['blog_id'] ); ?>" <?php selected( $site['blog_id'], $_origin_blog_id, true ); ?>><?php echo esc_html( $site['blogname'] . ' (' . $site['domain'] . $site['path'] . ')' ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php switch_to_blog( $_origin_blog_id ); ?>
			<br/>
			<?php _e( 'Term:', BEA_CSF_LOCALE ); ?>
			<select name="term_emitter[term_id]">
				<option value=""><?php _e( 'No term or invalid term', BEA_CSF_LOCALE ); ?></option>
				<?php foreach ( get_terms( $_origin_taxonomy, array( 'hide_empty' => false ) ) as $term ) : ?>
					<option
						value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $term->term_id, $_origin_term_id, true ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php restore_current_blog(); ?>
		<?php endif; ?>
	</td>
</tr>