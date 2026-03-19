<div class="wrap">
	<?php if ( true === $edit ) : ?>
		<h2><?php esc_html_e( 'Content Sync: Edit', 'bea-content-sync-fusion' ); ?></h2>
	<?php else : ?>
		<h2><?php esc_html_e( 'Content Sync: Add', 'bea-content-sync-fusion' ); ?></h2>
	<?php endif; ?>


	<form action="" method="post">
		<p>
			<label><?php esc_html_e( 'Label', 'bea-content-sync-fusion' ); ?></label>
			<input type="text" name="sync[label]" class="widefat"
				   value="<?php echo esc_attr( $current_sync->get_field( 'label' ) ); ?>"/>
			<span
					class="description"><?php esc_html_e( 'The label of the synchronization is mainly used for the administration console.', 'bea-content-sync-fusion' ); ?></span>
		</p>

		<p>
			<label><?php esc_html_e( 'Post type', 'bea-content-sync-fusion' ); ?></label>
			<select class="widefat" name="sync[post_type]">
				<option value=""><?php esc_html_e( 'No post type, sync only taxo !', 'bea-content-sync-fusion' ); ?></option>
				<?php foreach ( get_post_types( [], 'objects' ) as $bea_csf_post_type ) : ?>
					<option
							value="<?php echo esc_attr( $bea_csf_post_type->name ); ?>" <?php selected( $bea_csf_post_type->name, $current_sync->get_field( 'post_type' ) ); ?>><?php echo esc_html( $bea_csf_post_type->labels->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
					class="description"><?php esc_html_e( 'You must select the type of content that you want to sync', 'bea-content-sync-fusion' ); ?></span>
		</p>

		<p id="bea-csf-taxonomies-block">
			<label><?php esc_html_e( 'Taxonomies', 'bea-content-sync-fusion' ); ?></label>
			<select multiple="multiple" class="widefat multiple-helper" name="sync[taxonomies][]">
				<?php foreach ( get_taxonomies( [], 'objects' ) as $bea_csf_taxonomy ) : ?>
					<option
							value="<?php echo esc_attr( $bea_csf_taxonomy->name ); ?>" <?php selected( true, in_array( $bea_csf_taxonomy->name, (array) $current_sync->get_field( 'taxonomies' ), true ) ); ?>><?php echo esc_html( $bea_csf_taxonomy->labels->name . ' (' . ( isset( $bea_csf_taxonomy->rewrite['slug'] ) ? $bea_csf_taxonomy->rewrite['slug'] : '' ) . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
					class="description"><?php esc_html_e( 'You must select taxonomies related to content that you want to sync', 'bea-content-sync-fusion' ); ?></span>
		</p>

		<?php if ( class_exists( 'P2P_Connection_Type_Factory' ) ) : ?>
			<p id="bea-csf-p2p-block">
				<label><?php esc_html_e( 'P2P connections', 'bea-content-sync-fusion' ); ?></label>
				<select multiple="multiple" class="widefat multiple-helper" name="sync[p2p_connections][]">
					<?php foreach ( $p2p_registered_connections as $p2p_name => $p2p_obj ) : ?>
						<option
								value="<?php echo esc_attr( $p2p_name ); ?>" <?php selected( true, in_array( $p2p_name, (array) $current_sync->get_field( 'p2p_connections' ), true ) ); ?>><?php echo esc_html( $p2p_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<span
						class="description"><?php esc_html_e( 'You must select P2P connection that you want to sync', 'bea-content-sync-fusion' ); ?></span>
			</p>
		<?php endif; ?>

		<p>
			<label><?php esc_html_e( 'Mode', 'bea-content-sync-fusion' ); ?></label>
			<select class="widefat" name="sync[mode]">
				<?php
				foreach (
					[
						'manual'            => __( 'Manual', 'bea-content-sync-fusion' ),
						'auto'              => __( 'Automatic', 'bea-content-sync-fusion' ),
						'exclude_default'   => __( 'Exclude default', 'bea-content-sync-fusion' ),
					] as $value => $label
				) :
					?>
					<option
							value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_sync->get_field( 'mode' ) ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
					class="description"><?php esc_html_e( 'Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', 'bea-content-sync-fusion' ); ?></span>
		</p>

		<p>
			<label><?php esc_html_e( 'Default status', 'bea-content-sync-fusion' ); ?></label>
			<select class="widefat" name="sync[status]">
				<?php
				foreach (
					[
						'publish' => __( 'Publish', 'bea-content-sync-fusion' ),
						'pending' => __( 'Pending', 'bea-content-sync-fusion' ),
						'user_selection'  => __( 'User selection\'s, only for manual mode', 'bea-content-sync-fusion' ),
					] as $value => $label
				) :
					?>
					<option
							value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $current_sync->get_field( 'status' ) ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
					class="description"><?php esc_html_e( 'When content is published and it is sent to other sites, it is automatically set to the status "published", you can also set the "pending" status and provide an opportunity for each admin to validate or not the content. (only for post type)', 'bea-content-sync-fusion' ); ?></span>
		</p>

		<p>
			<label><?php esc_html_e( 'Emitters', 'bea-content-sync-fusion' ); ?></label>
			<select class="widefat multiple-helper" name="sync[emitters][]" multiple="true">
				<option
						value="all" <?php selected( in_array( 'all', $current_sync->get_field( 'emitters', true ), true ), true ); ?>><?php esc_html_e( 'All', 'bea-content-sync-fusion' ); ?></option>
				<?php foreach ( BEA_CSF_Synchronizations::get_sites_from_network() as $site ) : ?>
					<option
							value="<?php echo esc_attr( $site['blog_id'] ); ?>" <?php selected( in_array( $site['blog_id'], $current_sync->get_field( 'emitters', true ), true ), true ); ?>><?php echo esc_html( $site['blogname'] . ' (' . $site['domain'] . $site['path'] . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
					class="description"><?php esc_html_e( 'Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', 'bea-content-sync-fusion' ); ?></span>
		</p>

		<p>
			<label><?php esc_html_e( 'Receivers', 'bea-content-sync-fusion' ); ?></label>
			<select class="widefat multiple-helper" name="sync[receivers][]" multiple="true">
				<option
						value="all" <?php selected( in_array( 'all', $current_sync->get_field( 'receivers', true ), true ), true ); ?>><?php esc_html_e( 'All, except emitters', 'bea-content-sync-fusion' ); ?></option>
				<?php foreach ( BEA_CSF_Synchronizations::get_sites_from_network() as $site ) : ?>
					<option
							value="<?php echo esc_attr( $site['blog_id'] ); ?>" <?php selected( in_array( $site['blog_id'], $current_sync->get_field( 'receivers', true ), true ), true ); ?>><?php echo esc_html( $site['blogname'] . ' (' . $site['domain'] . $site['path'] . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
					class="description"><?php esc_html_e( 'Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', 'bea-content-sync-fusion' ); ?></span>
		</p>

		<p class="submit">
			<?php wp_nonce_field( 'update-bea-csf-settings' ); ?>

			<?php if ( true === $edit ) : ?>
				<input type="hidden" name="sync[id]"
					   value="<?php echo esc_attr( $current_sync->get_field( 'id' ) ); ?>"/>
				<input type="submit" class="button-primary" name="update-bea-csf-settings"
					   value="<?php esc_attr_e( 'Save', 'bea-content-sync-fusion' ); ?>"/>
			<?php else : ?>
				<input type="submit" class="button-primary" name="update-bea-csf-settings"
					   value="<?php esc_attr_e( 'Add', 'bea-content-sync-fusion' ); ?>"/>
			<?php endif; ?>
		</p>
	</form>
</div>
