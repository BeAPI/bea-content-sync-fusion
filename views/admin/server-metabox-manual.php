<p>
	<?php _e('You can choose which sites should receive this content. Select no site does not synchronize this content.', BEA_CSF_LOCALE); ?>
</p>

<div class="wp-tab-panel">
	<ul class="categorychecklist form-no-clear">
		<?php foreach( $blogs as $blog ) : ?>
			<li>
				<label class="selectit">
					<input type="checkbox" name="receivers[]" value="<?php echo $blog['blog_id']; ?>" <?php checked(in_array($blog['blog_id'], $post_receivers), true); ?> />&nbsp;
					<?php esc_html_e($blog['blogname']); ?>
				</label>
			</li>
		<?php endforeach; ?>
	</ul>
</div>