<div class="main">
	<?php if ( $query_contents->have_posts() ) : ?>
		<table>
			<thead>
				<tr>
					<th style="text-align: left"><?php _e( 'Title', 'bea-content-sync-fusion' ); ?></th>
					<th><?php _e( 'Action', 'bea-content-sync-fusion' ); ?></th>
				</tr>
			</thead>
			<?php
			while ( $query_contents->have_posts() ) :
				$query_contents->the_post();
				?>
				<tr style="padding: 3px 0; border-bottom: 1px solid #ccc; margin-bottom: 3px;">
					<td><?php the_title(); ?> - <?php the_time( get_option( 'date_format' ) ); ?></td>
					<td><a href="<?php echo get_edit_post_link( get_the_ID() ); ?>"><?php _e( 'Edit', 'bea-content-sync-fusion' ); ?></a></td>
				</tr>
			<?php endwhile; ?>
		</table>
	<?php else : ?>
		<p><?php _e( 'No content to validate', 'bea-content-sync-fusion' ); ?></p>
	<?php endif; ?>
</div>
