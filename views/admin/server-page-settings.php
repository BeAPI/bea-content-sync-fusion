<div class="wrap">
	<?php screen_icon( 'options-general' ); ?>
	<h2><?php _e( "Content Sync: Settings", BEA_CSF_LOCALE ); ?></h2>

	<p><?php _e( 'This plugin gives you the ability to sync content published on a website network to one or more websites network of your choice.', BEA_CSF_LOCALE ); ?></p>

	<div id="col-container">
		<table class="widefat tag fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" class="manage-column "><?php _e( 'Name', BEA_CSF_LOCALE ); ?></th>
					<th scope="col" class="manage-column "><?php _e( 'Enable ?', BEA_CSF_LOCALE ); ?></th>
					<th scope="col" class="manage-column "><?php _e( 'Confict ?', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Post type', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Taxonomies', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Mode', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Default status', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Notifications ?', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Emitters', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Receivers', BEA_CSF_LOCALE ); ?></th>
				</tr>
			</thead>
			<tbody id="the-list" class="list:clients">
				<?php
				if ( $registered_syncs == false || empty( $registered_syncs ) ) :
					echo '<tr><td colspan="9">' . sprintf(__( 'No synchronization exists. Want to <a href="%s">create one</a>?', BEA_CSF_LOCALE ), network_admin_url('admin.php?page='. self::admin_slug . '-add')) . '</td></tr>';
				else :
					$class = 'alternate';
					$i = 0;
					foreach ( $registered_syncs as $sync ) :
						// Skip invalid data
						$label = $sync->get_field('label');
						if ( empty( $label ) ) {
							continue;
						}

						// Get post type label from cpt name
						$post_type_label = '';
						$post_type_object = get_post_type_object( $sync->get_field('post_type') );
						if ( $post_type_object != false ) {
							$post_type_label = $post_type_object->labels->name;
						}

						$i++;
						$class = ( $class == 'alternate' ) ? '' : 'alternate';
						?>
						<tr class="<?php echo $class; ?>">
							<td>
								<strong><?php echo esc_html( $sync->get_field('label') ); ?></strong>
								<div class="row-actions">
									<span class="edit">
										<?php if ( !$sync->is_locked() ) : ?>
											<a href="<?php echo wp_nonce_url( add_query_arg( array('action' => 'edit', 'sync_id' => $sync->get_field('id') ), network_admin_url( 'admin.php?page=' . self::admin_slug.'-add' ) ), '' ); ?>"><?php _e( 'Edit', BEA_CSF_LOCALE ); ?></a>
											|
											<a class="delete" href="<?php echo wp_nonce_url( add_query_arg( array('action' => 'delete', 'sync_id' => $sync->get_field('id') ), network_admin_url( 'admin.php?page=' . self::admin_slug.'-edit' ) ), '' ); ?>" onclick="return confirm('<?php echo esc_js( sprintf( __( "You are about to delete this sync '%s'\n  'Cancel' to stop, 'OK' to delete." ), $sync->get_field('label') ) ); ?>');"><?php _e( 'Delete', BEA_CSF_LOCALE ); ?></a>
											<!--<a class="delete" onclick="return confirm('<?php echo esc_js( sprintf( __( "You are about to fush clients for '%s'\n  'Cancel' to stop, 'OK' to delete." ), $sync->get_field('name') ) ); ?>');" href="<?php echo wp_nonce_url( network_admin_url( 'settings.php?page=' . self::admin_slug ) . '&amp;action=flush&amp;label=' . esc_attr( $sync->get_field('label') ), 'flush-client-' . $sync->get_field('label') ); ?>"><?php _e( 'Flush clients', BEA_CSF_LOCALE ); ?></a>-->
										<?php else : ?>
											<?php _e( 'This item is locked because registered since the developer API.', BEA_CSF_LOCALE ); ?>
										<?php endif; ?>
									</span>
								</div>
							</td>
							<td><?php echo esc_html( $i18n_true_false[$sync->get_field('active')] ); ?></td>
							<td><strong><?php echo esc_html( $i18n_true_false[$sync->has_conflict()] ); ?></strong></td>
							<td><?php echo esc_html( $post_type_label ); ?></td>
							<td><?php echo esc_html( 'tAXOS' ); ?></td>
							<td><?php echo esc_html( $sync->get_field('mode') ); ?></td>
							<td><?php echo esc_html( $sync->get_field('status') ); ?></td>
							<td><?php echo esc_html( $i18n_true_false[$sync->get_field('notifications')] ); ?></td>
							<td><?php echo implode( '<br />', self::get_sites($sync->get_field('emitters'), 'blogname') ); ?></td>
							<td><?php echo implode( '<br />', self::get_sites($sync->get_field('receivers'), 'blogname') ); ?></td>
						</tr>
						<?php
					endforeach;
				endif;
				?>
			</tbody>
		</table>
	</div><!-- /col-container -->
</div>