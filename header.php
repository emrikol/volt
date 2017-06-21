<!DOCTYPE html>
<html amp <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
		<link rel="profile" href="http://gmpg.org/xfn/11">
		<?php if ( is_front_page() || is_author() || is_archive() ) : ?>
		<link rel="canonical" href="<?php echo esc_url( volt_get_current_url() ); ?>">
		<?php endif; ?>
		<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
		<?php wp_head(); ?>
	</head>

	<body <?php body_class(); ?>>
		<div id="overflow-container" class="overflow-container">
			<div id="theme-container" class="theme-container">
				<div id="max-width" class="max-width">
					<?php do_action( 'volt_before_header' ); ?>
					<header class="site-header" id="site-header" role="banner">
						<div id="title-container" class="title-container">
						<?php
							$logo = get_theme_mod( 'custom_logo' );

						if ( $logo ) {
							echo "<div id='site-title' class='site-title'>";
							if ( function_exists( 'the_custom_logo' ) ) {
								the_custom_logo();
							}
							echo '</div>';
						} else {
							echo "<div id='site-title' class='site-title'>";
							echo "<a href='" . esc_url( home_url() ) . "'>";
							echo esc_html( get_bloginfo( 'name' ) );
							echo '</a>';
							echo '</div>';
						}
						?>
						</div>
						<nav class="site-navbar">
							<input type="checkbox" id="toggle_menu" class="toggle-menu-checkbox" />
							<label for="toggle_menu" class="toggle-menu-label uppercase"></label>
							<input type="checkbox" id="toggle_overlay" class="toggle-overlay-checkbox" />
							<label for="toggle_menu" class="toggle-overlay-label uppercase"></label>
							<?php wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>
						</nav>
						<div id="menu-primary-container" class="menu-primary-container">
							<div class="max-width">
								<div id="scroll-container" class="scroll-container">
									<?php if ( get_bloginfo( 'description' ) ) : ?>
									<p class="tagline"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
									<?php endif; ?>



									<?php get_template_part( 'content/search-bar' ); ?>
									<?php // TODO: volt_social_icons_output(); ?>
								</div>
							</div>
						</div>
					</header>
					<?php do_action( 'volt_after_header' ); ?>
					<section id="main" class="main" role="main">
						<?php do_action( 'volt_main_top' );
						if ( function_exists( 'yoast_breadcrumb' ) ) {
							yoast_breadcrumb( '<p id="breadcrumbs">', '</p>' );
						}
