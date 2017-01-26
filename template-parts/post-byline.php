<div class="post-byline">
	<span class="post-author">
		<span><?php esc_html_e( 'By', 'volt' ); ?></span>
		<?php the_author(); ?>
	</span>
	<span class="post-date">
		<span><?php esc_html_e( 'on', 'volt' ); ?></span>
		<a class="permalink" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
			<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( get_the_date( 'r' ) ) ) ); ?>
		</a>
	</span>
</div>
