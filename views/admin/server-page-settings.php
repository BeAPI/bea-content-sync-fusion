<div class="wrap">
	<?php screen_icon( 'options-general' ); ?>
	<h2><?php _e( "Content Sync: Settings", BEA_CSF_LOCALE ); ?></h2>

	<p><?php _e( 'This plugin gives you the ability to sync content published on a website network to one or more websites network of your choice.', BEA_CSF_LOCALE ); ?></p>

	<div id="col-container">
		<table class="widefat tag fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-name"><?php _e( 'Name', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Post type', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Mode', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Default status', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Notifications ?', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Emitters', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Receivers', BEA_CSF_LOCALE ); ?></th>
					<th scope="col"><?php _e( 'Actions', BEA_CSF_LOCALE ); ?></th>
				</tr>
			</thead>
			<tbody id="the-list" class="list:clients">
				<?php
				if ( $current_options == false || empty( $current_options ) ) :
					echo '<tr><td colspan="2">' . __( 'No synchronization exists. Want to create one?', BEA_CSF_LOCALE ) . '</td></tr>';
				else :
					$class = 'alternate';
					$i = 0;
					foreach ( $current_options as $sync ) :
						// Skip invalid data
						if ( !isset( $sync['label']) ) {
							continue;
						}
						
						// Get post type label from cpt name
						$post_type_label = '';
						$post_type_object = get_post_type_object($sync['post_type']);
						if ( $post_type_object != false ) {
							$post_type_label = $post_type_object->labels->name;
						}
						
						$i++;
						$class = ( $class == 'alternate' ) ? '' : 'alternate';
						?>
						<tr class="<?php echo $class; ?>">
							<td class="name column-name"><strong><?php echo esc_html( $sync['label'] ); ?></strong></td>
							<td class="name column-name"><?php echo esc_html( $post_type_label ); ?></td>
							<td class="name column-name"><?php echo esc_html( $sync['mode'] ); ?></td>
							<td class="name column-name"><?php echo esc_html( $sync['status'] ); ?></td>
							<td class="name column-name"><?php echo esc_html( $sync['notifications'] ); ?></td>
							<td class="name column-name"><?php echo print_r( $sync['emitters'] ); ?></td>
							<td class="name column-name"><?php echo print_r( $sync['receivers'] ); ?></td>
							<td>
								<a class="button" href="<?php echo network_admin_url( 'settings.php?page=' . self::admin_slug ); ?>&amp;action=resync&amp;blog_id=<?php echo esc_attr( $sync['name'] ); ?>"><?php _e( 'Edit', BEA_CSF_LOCALE ); ?></a>
								<a class="delete" onclick="return confirm('<?php echo esc_js( sprintf( __( "You are about to fush clients for '%s'\n  'Cancel' to stop, 'OK' to delete." ), $sync['name'] ) ); ?>');" href="<?php echo wp_nonce_url(network_admin_url( 'settings.php?page='.self::admin_slug ).'&amp;action=flush&amp;label='. esc_attr($sync['label']), 'flush-client-'.$sync['label']); ?>"><?php _e( 'Flush clients', BEA_CSF_LOCALE ); ?></a>
							</td>
						</tr>
						<?php
					endforeach;
				endif;
				?>
			</tbody>
		</table>
	</div><!-- /col-container -->
</div>