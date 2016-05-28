<div class="wrap">
	<h2><?php _e( "Content Sync: Notifications", 'bea-content-sync-fusion' ); ?></h2>

	<div id="col-container">
		<p><?php _e( "This page allows you to choose what user must be notified when adding new content with sync feature. If no user is selected, no notification will be sent.", 'bea-content-sync-fusion' ); ?></p>

		<form action="" method="post">
			<?php foreach ( $syncs as $sync_obj ) :
				if ( ! isset( $current_values[ $sync_obj->get_field( 'id' ) ] ) ) {
					$current_values[ $sync_obj->get_field( 'id' ) ] = array();
				}
				?>

				<h3><?php echo $sync_obj->get_field( 'label' ); ?></h3>
				<select class="widefat multiple-helper"
				        name="sync_notifications[<?php echo $sync_obj->get_field( 'id' ); ?>][]" multiple="true">
					<?php foreach ( $users as $user ) : ?>
						<option
							value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( true, in_array( $user->ID, $current_values[ $sync_obj->get_field( 'id' ) ] ) ); ?>><?php echo esc_html( $user->user_login ); ?></option>
					<?php endforeach; ?>
				</select>

			<?php endforeach; ?>

			<p class="submit">
				<?php wp_nonce_field( 'update-bea-csf-notifications' ); ?>
				<input type="submit" class="button-primary" name="update-bea-csf-notifications"
				       value="<?php _e( 'Save settings', 'bea-content-sync-fusion' ); ?>"/>
			</p>
		</form>
	</div>
	<!-- /col-container -->
</div>