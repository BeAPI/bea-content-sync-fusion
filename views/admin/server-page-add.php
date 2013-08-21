<div class="wrap">
	<?php screen_icon('options-general'); ?>
	<h2><?php _e("Content Sync: Add", BEA_CSF_LOCALE); ?></h2>

	<form action="" method="post">
		<p>
			<label><?php _e('Name', BEA_CSF_LOCALE); ?></label>
			<input type="text" name="" class="widefat" value="" />
			<span class="description"><?php _e('The name of the synchronization is mainly used for the administration console.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Custom post type', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="">
				<?php foreach( get_post_types(array('public' => true), 'objects') as $post_type ) : ?>
					<option value="<?php echo esc_attr($post_type->name); ?>"><?php echo esc_html($post_type->labels->name); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('You must select the type of content that you want to sync', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Mode', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="">
				<?php foreach( array('manual' => __('Manual', BEA_CSF_LOCALE), 'auto' => __('Automatic', BEA_CSF_LOCALE) ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Default status', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="">
				<?php foreach( array('publish' => __('Publish', BEA_CSF_LOCALE), 'pending' => __('Pending', BEA_CSF_LOCALE)) as $value => $label ) : ?>
					<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('When content is published and it is sent to other sites, it is automatically set to the status "published", you can also set the "pending" status and provide an opportunity for each admin to validate or not the content.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Enable notification settings on local admin ?', BEA_CSF_LOCALE); ?></label>
			<select class="widefat" name="">
				<?php foreach( array('yes' => __('Yes', BEA_CSF_LOCALE), 'no' => __('No', BEA_CSF_LOCALE) ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('Enabling this feature will add a page in the administrative console each site in order to choose the people notified when creating new content via the sync feature.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Emitters', BEA_CSF_LOCALE); ?></label>
			<select class="widefat multiple-helper" name="" multiple="true">
				<?php foreach( self::get_blogs() as $blog ) : ?>
					<option value="<?php echo esc_attr($blog['blog_id']); ?>"><?php echo esc_html($blog['domain'].$blog['path']); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p>
			<label><?php _e('Receivers', BEA_CSF_LOCALE); ?></label>
			<select class="widefat multiple-helper" name="" multiple="true">
				<?php foreach( self::get_blogs() as $blog ) : ?>
					<option value="<?php echo esc_attr($blog['blog_id']); ?>"><?php echo esc_html($blog['domain'].$blog['path']); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php _e('Mode automatically is completely transparent to the user, whereas the manual mode adds a meta box in the writing page.', BEA_CSF_LOCALE); ?></span>
		</p>
		
		<p class="submit">
			<input type="submit" class="button-primary" name="" value="<?php esc_attr_e('Add', BEA_CSF_LOCALE); ?>" />
		</p>
	</form>
</div>