<div <?php post_class(); ?>>
	<?php do_action( 'volt_post_before' ); ?>
	<article>
		<?php volt_featured_image(); ?>
		<div class="post-container">
			<div class='post-header'>
				<h1 class='post-title'><?php the_title(); ?></h1>
				<?php get_template_part( 'template-parts/post-byline' ); ?>
			</div>
			<div class="post-content">
				<?php the_content(); ?>
				<?php wp_link_pages( array(
					'before' => '<p class="singular-pagination">' . esc_html__( 'Pages:', 'volt' ),
					'after'  => '</p>',
				) ); ?>
				<?php do_action( 'volt_post_after' ); ?>
			</div>
			<div class="post-meta">
				<?php get_template_part( 'template-parts/post-categories' ); ?>
				<?php get_template_part( 'template-parts/post-tags' ); ?>
				<?php get_template_part( 'template-parts/post-nav' ); ?>
			</div>
		</div>
	</article>
	<?php comments_template(); ?>
</div>
