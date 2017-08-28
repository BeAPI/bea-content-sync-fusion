<div class="wrap">
	<?php screen_icon( 'options-general' ); ?>
	<?php if ( $edit == true ) : ?>
		<h2><?php _e( "Content Sync: Edit", BEA_CSF_LOCALE ); ?></h2>
	<?php else: ?>
		<h2><?php _e( "Content Sync: Add", BEA_CSF_LOCALE ); ?></h2>
	<?php endif; ?>


	<form action="" method="post">
		<p>
			<label><?php _e( 'Label', BEA_CSF_LOCALE ); ?></label>
			<input type="text" name="sync[label]" class="widefat"
			       value="<?php echo esc_attr( $current_sync->get_field( 'label' ) ); ?>"/>
			<span
				class="description"><?php _e( 'The label of the synchronization is mainly used for the administration console.', BEA_CSF_LOCALE ); ?></span>
		</p>

		<p>
			<label><?php _e( 'Post type', BEA_CSF_LOCALE ); ?></label>
			<select class="widefat" name="sync[post_type]">
				<option value=""><?php _e( 'No post type, sync only taxo !', BEA_CSF_LOCALE ); ?></option>
				<?php foreach ( get_post_types( array(), 'objects' ) as $post_type ) : ?>
					<option
						value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $post_type->name, $current_sync->get_field( 'post_type' ) ); ?>><?php echo esc_html( $post_type->labels->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
				class="description"><?php _e( 'You must select the type of content that you want to sync', BEA_CSF_LOCALE ); ?></span>
		</p>

		<p id="bea-csf-taxonomies-block">
			<label><?php _e( 'Taxonomies', BEA_CSF_LOCALE ); ?></label>
			<select multiple="multiple" class="widefat multiple-helper" name="sync[taxonomies][]">
				<?php foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) : ?>
					<option
						value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( true, in_array( $taxonomy->name, (array) $current_sync->get_field( 'taxonomies' ) ) ); ?>><?php echo esc_html( $taxonomy->labels->name . ' (' . $taxonomy->rewrite['slug'] . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
				class="description"><?php _e( 'You must select taxonomies related to content that you want to sync', BEA_CSF_LOCALE ); ?></span>
		</p>

		<?php if ( class_exists( 'P2P_Connection_Type_Factory' ) ) : ?>
			<p id="bea-csf-p2p-block">
				<label><?php _e( 'P2P connections', BEA_CSF_LOCALE ); ?></label>
				<select multiple="multiple" class="widefat multiple-helper" name="sync[p2p_connections][]">
					<?php foreach ( $p2p_registered_connections as $p2p_name => $p2p_obj ) : ?>
						<option
							value="<?php echo esc_attr( $p2p_name ); ?>" <?php selected( true, in_array( $p2p_name, (array) $current_sync->get_field( 'p2p_connections' ) ) ); ?>><?php echo esc_html( $p2p_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<span
					class="description"><?php _e( 'You must select P2P connection that you want to sync', BEA_CSF_LOCALE ); ?></span>
			</p>
		<?php endif; ?>

		<p>
			<label><?php _e( 'Mode', BEA_CSF_LOCALE ); ?></label>
			<select class="widefat" name="sync[mode]">
				<?php foreach (
					array(
						'manual' => __( 'Manual', BEA_CSF_LOCALE ),
						'auto'   => __( 'Automatic', BEA_CSF_LOCALE )
					) as $value => $label
				) : ?>
					<option
						value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_sync->get_field( 'mode' ) ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
				class="description"><?php _e( 'Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE ); ?></span>
		</p>

		<p>
			<label><?php _e( 'Default status', BEA_CSF_LOCALE ); ?></label>
			<select class="widefat" name="sync[status]">
				<?php foreach (
					array(
						'publish' => __( 'Publish', BEA_CSF_LOCALE ),
						'pending' => __( 'Pending', BEA_CSF_LOCALE )
					) as $value => $label
				) : ?>
					<option
						value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_sync->get_field( 'status' ) ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
				class="description"><?php _e( 'When content is published and it is sent to other sites, it is automatically set to the status "published", you can also set the "pending" status and provide an opportunity for each admin to validate or not the content. (only for post type)', BEA_CSF_LOCALE ); ?></span>
		</p>

		<p>
			<label><?php _e( 'Emitters', BEA_CSF_LOCALE ); ?></label>
			<select class="widefat multiple-helper" name="sync[emitters][]" multiple="true">
				<?php foreach ( self::get_sites_from_network() as $site ) : ?>
					<option
						value="<?php echo esc_attr( $site['blog_id'] ); ?>" <?php selected( in_array( $site['blog_id'], $current_sync->get_field( 'emitters' ) ), true ); ?>><?php echo esc_html( $site['blogname'] . ' (' . $site['domain'] . $site['path'] . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
				class="description"><?php _e( 'Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE ); ?></span>
		</p>

		<p>
			<label><?php _e( 'Receivers', BEA_CSF_LOCALE ); ?></label>
			<select class="widefat multiple-helper" name="sync[receivers][]" multiple="true">
				<option
					value="all" <?php selected( in_array( 'all', $current_sync->get_field( 'receivers' ) ), true ); ?>><?php echo esc_html( 'All, except emitters' ); ?></option>
				<?php foreach ( self::get_sites_from_network() as $site ) : ?>
					<option
						value="<?php echo esc_attr( $site['blog_id'] ); ?>" <?php selected( in_array( $site['blog_id'], $current_sync->get_field( 'receivers' ) ), true ); ?>><?php echo esc_html( $site['blogname'] . ' (' . $site['domain'] . $site['path'] . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
				class="description"><?php _e( 'Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE ); ?></span>
		</p>

		<p class="submit">
			<?php wp_nonce_field( 'update-bea-csf-settings' ); ?>

			<?php if ( $edit == true ) : ?>
				<input type="hidden" name="sync[id]"
				       value="<?php echo esc_attr( $current_sync->get_field( 'id' ) ); ?>"/>
				<input type="submit" class="button-primary" name="update-bea-csf-settings"
				       value="<?php esc_attr_e( 'Save', BEA_CSF_LOCALE ); ?>"/>
			<?php else: ?>
				<input type="submit" class="button-primary" name="update-bea-csf-settings"
				       value="<?php esc_attr_e( 'Add', BEA_CSF_LOCALE ); ?>"/>
			<?php endif; ?>
		</p>
	</form>
</div>