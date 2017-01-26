<div class="post-byline">
	<span class="post-author">
		<span><?php esc_html_e( 'By', 'volt' ); ?></span>
		<?php the_author(); ?>
	</span>
	<span class="post-date">
		<span>
			<?php
			if ( 'hide' !== $author_display ) {
				esc_html_e( 'on', 'volt' );
			}
			?>
		</span>
		<?php
		$date = date_i18n( get_option( 'date_format' ), strtotime( get_the_date( 'r' ) ) );
		echo esc_html( $date );
		?>
	</span>
</div>
