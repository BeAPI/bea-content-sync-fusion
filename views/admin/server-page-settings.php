<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e("Content Sync: Settings", BEA_CSF_LOCALE); ?></h2>

	<p></p>
	<div id="col-container">
		<form action="" method="post">
			<table class="widefat tag fixed" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-name"><?php _e('Website', BEA_CSF_LOCALE); ?></th>
						<th scope="col" style="width:5%;text-align: center;"><?php _e('Master', BEA_CSF_LOCALE); ?></th>
						<th scope="col" style="width:5%;text-align: center;"><?php _e('Client', BEA_CSF_LOCALE); ?></th>
						<th scope="col" style="width:5%;text-align: center;"><?php _e('State', BEA_CSF_LOCALE); ?></th>
					</tr>
				</thead>
				<tbody id="the-list" class="list:clients">
					<?php
					if ( $blogs == false || empty($blogs) ) :
						echo '<tr><td colspan="2">'.__('No blogs.', BEA_CSF_LOCALE).'</td></tr>';
					else :
						$class = 'alternate';
						$i = 0;
						foreach( $blogs as $blog ) :
							$i++;
							$class = ( $class == 'alternate' ) ? '' : 'alternate';
							?>
							<tr data-blog-id="<?php echo $blog['blog_id']; ?>" id="blog-<?php echo $blog['blog_id']; ?>" class="<?php echo $class; ?>">
								<td class="name column-name">
									<strong><?php echo esc_html($blog['domain'].$blog['path']); ?></strong>
									<br />
									<div class="row-actions">
										<?php if ( isset($current_options['clients']) && in_array($blog['blog_id'], $current_options['clients']) ) : ?>
										<span class="edit"><a class="cps-resync" href="<?php echo network_admin_url( 'settings.php?page='.self::admin_slug ); ?>&amp;action=resync&amp;blog_id=<?php echo esc_attr($blog['blog_id']); ?>">Resync</a> | </span>
										<span class="edit"><a class="cps-flush" href="<?php echo wp_nonce_url(network_admin_url( 'settings.php?page='.self::admin_slug ).'&amp;action=flush&amp;blog_id='. esc_attr($blog['blog_id']), 'flush-client-'.$blog['blog_id']); ?>">Flush</a></span>
										<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'resync-client-'.$blog['blog_id'] ); ?>" />
										<?php endif; ?>
									</div>
									<div class="progressbar" ></div>
								</td>
								<td style="text-align: center;"><input type="radio" name="master" value="<?php echo $blog['blog_id']; ?>" <?php checked( $blog['blog_id'], $current_options['master'] ); ?> /></td>
								<td style="text-align: center;"><input type="checkbox" name="client[]" value="<?php echo $blog['blog_id']; ?>" <?php checked(true, in_array($blog['blog_id'], $current_options['clients'])); ?> /></td>
								<td style="text-align: center;">
									<?php
									if ( isset($current_options['clients']) && in_array($blog['blog_id'], $current_options['clients']) ) {
										echo self::check_client_sum( $blog['blog_id'], $current_sum );
									}
									?>	
								</td>
							</tr>
						<?php
						endforeach;
					endif;
					?>
				</tbody>
			</table>

			<p class="submit">
				<?php wp_nonce_field('update-bea-csf-settings'); ?>
				<input type="submit" class="button-primary" name="update-bea-csf-settings" value="<?php _e('Save settings', BEA_CSF_LOCALE); ?>" />
				<input type="submit" class="button delete" name="flush-all-bea-csf-settings" value="<?php _e('Flush all blogs', BEA_CSF_LOCALE); ?>" onclick="if ( confirm('<?php echo esc_js('Are you sure ?', BEA_CSF_LOCALE); ?>') ) return true; return false;" />
			</p>
		</form>

		<div class="sync_messages"></div>
	</div><!-- /col-container -->
</div>