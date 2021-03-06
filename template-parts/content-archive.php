<div <?php post_class(); ?>>
	<?php do_action( 'volt_archive_post_before' ); ?>
	<article>
		<?php volt_featured_image(); ?>
		<div class="post-container">
			<div class='post-header'>
				<?php do_action( 'volt_sticky_post_status' ); ?>
				<h2 class='post-title'>
					<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
				</h2>
				<?php get_template_part( 'template-parts/post-byline' ); ?>
			</div>
			<div class="post-content">
				<?php volt_excerpt(); ?>
			</div>
		</div>
	</article>
	<?php do_action( 'volt_archive_post_after' ); ?>
</div>
