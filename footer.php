<?php // footer.php ?>
					<?php do_action( 'volt_main_bottom' ); ?>
					</section> <!-- .main -->

					<?php do_action( 'volt_after_main' ); ?>

					<footer id="site-footer" class="site-footer" role="contentinfo">
						<?php do_action( 'volt_footer_top' ); ?>
						<div class="design-credit">
							<span>
								<?php
								$footer_text = '<a href="' . esc_url( __( 'https://wordpress.org/', 'volt' ) ) . '">' . sprintf( esc_html__( 'Proudly powered by %s', 'volt' ), 'WordPress' ) . '</a><span class="sep"> | </span>';
								$footer_text .= sprintf( esc_html__( 'Theme: %1$s by %2$s.', 'volt' ), 'Volt', '<a href="https://emrikol.com/" rel="designer">Derrick Tennant</a>' );
								$footer_text = apply_filters( 'volt_footer_text', $footer_text );
								echo wp_kses_post( $footer_text );
								?>
							</span>
						</div>
					</footer>
				</div><!-- .max-width -->
			</div><!-- .theme-container -->
		</div><!-- .overflow-container -->

		<?php do_action( 'volt_body_bottom' ); ?>

		<?php wp_footer(); ?>

	</body>
</html>
