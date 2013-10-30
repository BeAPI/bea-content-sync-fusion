<div class="wrap">
	<?php screen_icon('options-general'); ?>
	<?php if ( $edit == true ) : ?>
		<h2><?php _e("Content Sync: Edit", BEA_CSF_LOCALE); ?></h2>
	<?php else: ?>
		<h2><?php _e("Content Sync: Add", BEA_CSF_LOCALE); ?></h2>
	<?php endif; ?>
	

	<form action="" method="post">
		<p>
			<label><?php _e('Label', BEA_CSF_LOCALE); ?></label>
			<input type="text" name="sync[label]" class="widefat" value="<?php echo esc_attr($current_sync->get_field('label')); ?>" />
			<span class="description"><?php _e('The label of the synchronization is mainly used for the administration console.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Enable synchronization ?', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="sync[active]">
				<?php foreach( array('1' => __('Yes', BEA_CSF_LOCALE), '0' => __('No', BEA_CSF_LOCALE) ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr($value); ?>" <?php selected($value, $current_sync->get_field('active')); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('You may decide to temporarily disable a synchronization rule for logistical reasons!', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Custom post type', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="sync[post_type]">
				<?php foreach( get_post_types(array('public' => true), 'objects') as $post_type ) : ?>
					<option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($post_type->name, $current_sync->get_field('post_type')); ?>><?php echo esc_html($post_type->labels->name); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('You must select the type of content that you want to sync', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Mode', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="sync[mode]">
				<?php foreach( array('manual' => __('Manual', BEA_CSF_LOCALE), 'auto' => __('Automatic', BEA_CSF_LOCALE) ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr($value); ?>" <?php selected($value, $current_sync->get_field('mode')); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Default status', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="sync[status]">
				<?php foreach( array('publish' => __('Publish', BEA_CSF_LOCALE), 'pending' => __('Pending', BEA_CSF_LOCALE)) as $value => $label ) : ?>
					<option value="<?php echo esc_attr($value); ?>" <?php selected($value, $current_sync->get_field('status')); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('When content is published and it is sent to other sites, it is automatically set to the status "published", you can also set the "pending" status and provide an opportunity for each admin to validate or not the content.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Enable notification settings on local admin ?', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="sync[notifications]">
				<?php foreach( array('1' => __('Yes', BEA_CSF_LOCALE), '0' => __('No', BEA_CSF_LOCALE) ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr($value); ?>" <?php selected($value, $current_sync->get_field('notifications')); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('Enabling this feature will add a page in the administrative console each site in order to choose the people notified when creating new content via the sync feature.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Emitters', BEA_CSF_LOCALE); ?></label>
			<select class="widefat multiple-helper" name="sync[emitters][]" multiple="true">
				<?php foreach( self::get_blogs() as $blog ) : ?>
					<option value="<?php echo esc_attr($blog['blog_id']); ?>" <?php selected(in_array($blog['blog_id'], $current_sync->get_field('emitters')), true); ?>><?php echo esc_html($blog['blogname'] . ' ('.$blog['domain'].$blog['path'].')'); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Receivers', BEA_CSF_LOCALE); ?></label>
			<select class="widefat multiple-helper" name="sync[receivers][]" multiple="true">
				<?php foreach( self::get_blogs() as $blog ) : ?>
					<option value="<?php echo esc_attr($blog['blog_id']); ?>" <?php selected(in_array($blog['blog_id'], $current_sync->get_field('receivers')), true); ?>><?php echo esc_html($blog['blogname'] . ' ('.$blog['domain'].$blog['path'].')'); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p class="submit">
			<?php wp_nonce_field('update-bea-csf-settings'); ?>
			
			<?php if ( $edit == true ) : ?>
				<input type="hidden" name="sync[id]" value="<?php echo esc_attr($current_sync->get_field('id')); ?>" />
				<input type="submit" class="button-primary" name="update-bea-csf-settings" value="<?php esc_attr_e('Save', BEA_CSF_LOCALE); ?>" />
			<?php else: ?>
				<input type="submit" class="button-primary" name="update-bea-csf-settings" value="<?php esc_attr_e('Add', BEA_CSF_LOCALE); ?>" />
			<?php endif; ?>
		</p>
	</form>
</div>