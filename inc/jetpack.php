<?php
/**
 * Jetpack Compatibility File.
 *
 * @link https://jetpack.com/
 *
 * @package Volt
 */

// Lots of this taken from https://github.com/Automattic/amp-wp/blob/e0050b70825b1ad583b6d9183e8b567c3a9b3ed8/jetpack-helper.php
add_action( 'init', 'volt_jetpack_mods' );

/**
 * Disable Jetpack features that are not compatible with AMP.
 **/
function volt_jetpack_mods() {
	if ( Jetpack::is_module_active( 'stats' ) ) {
		add_action( 'wp_footer', 'volt_add_stats_pixel' );
	}
}

function volt_disable_sharing() {
	add_filter( 'sharing_show', '__return_false', 100 );
}

function volt_remove_stats() {
	// Remove WP Stats Smiley removal CSS
	remove_action( 'wp_head', 'stats_hide_smile_css' );

	// Remove Stats JS
	remove_action( 'wp_head', 'stats_add_shutdown_action' );
	remove_action( 'wp_footer', 'stats_footer', 101 );
	remove_action( 'shutdown',  'stats_footer', 101 );
}
add_action( 'wp_enqueue_scripts', 'volt_remove_stats' );

// Remove Jetpack CSS from frontend
add_filter( 'jetpack_implode_frontend_css', '__return_false' );

function volt_jetpack_deregister_footer_scripts() {
	// Not AMP compatible.
	if ( ! is_admin() ) {
		wp_dequeue_script( 'devicepx' );
	}
}
add_action( 'wp_footer', 'volt_jetpack_deregister_footer_scripts' );

function volt_add_stats_pixel( $amp_template ) {
	?>
	<amp-pixel src="<?php echo esc_url( volt_build_stats_pixel_url() ); ?>"></amp-pixel>
	<?php
}

/**
 * Generate the stats pixel.
 *
 * Looks something like:
 *     https://pixel.wp.com/g.gif?v=ext&j=1%3A3.9.1&blog=1234&post=5678&tz=-4&srv=example.com&host=example.com&ref=&rand=0.4107963021218808
 */
function volt_build_stats_pixel_url() {
	global $wp_the_query;
	if ( function_exists( 'stats_build_view_data' ) ) { // added in https://github.com/Automattic/jetpack/pull/3445
		$data = stats_build_view_data();
	} else {
		$blog = Jetpack_Options::get_option( 'id' );
		$tz = get_option( 'gmt_offset' );
		$v = 'ext';
		$blog_url = wp_parse_url( site_url() );
		$srv = $blog_url['host'];
		$j = sprintf( '%s:%s', JETPACK__API_VERSION, JETPACK__VERSION );
		$post = $wp_the_query->get_queried_object_id();
		$data = compact( 'v', 'j', 'blog', 'post', 'tz', 'srv' );
	}

	$data['host'] = rawurlencode( $_SERVER['HTTP_HOST'] ); // @codingStandardsIgnoreLine.
	$data['rand'] = 'RANDOM'; // amp placeholder
	$data['ref'] = 'DOCUMENT_REFERRER'; // amp placeholder
	$data = array_map( 'rawurlencode' , $data );
	return add_query_arg( $data, 'https://pixel.wp.com/g.gif' );
}

//
// Sharing
//
function volt_jetpack_sharing_init() {
	add_filter( 'sharing_js', 'sharing_disable_js' ); // Disable JS
	remove_action( 'wp_head', 'sharing_add_header', 1 ); // Disable CSS
}
add_action( 'init', 'volt_jetpack_sharing_init' );

function volt_jetpack_sharing_ampify( $sharing_content ) {
	if ( ! class_exists( 'Sharing_Service' ) ) {
		return $sharing_content;
	}

	$sharer = new Sharing_Service();
	$global = $sharer->get_global_options();

	$enabled = apply_filters( 'sharing_enabled', $sharer->get_blog_services() );
	$sharing_content = '';

	$sharing_content .= '<div class="sharedaddy sd-sharing-enabled"><div class="robots-nocontent sd-block sd-social sd-social-' . $global['button_style'] . ' sd-sharing">';
	if ( '' !== $global['sharing_label'] ) {
		$sharing_content .= sprintf(
			/**
			 * Filter the sharing buttons' headline structure.
			 *
			 * @module sharing
			 *
			 * @since 4.4.0
			 *
			 * @param string $sharing_headline Sharing headline structure.
			 * @param string $global['sharing_label'] Sharing title.
			 * @param string $sharing Module name.
			 */
			apply_filters( 'jetpack_sharing_headline_html', '<h3 class="sd-title">%s</h3>', $global['sharing_label'], 'sharing' ),
			esc_html( $global['sharing_label'] )
		);
	}
	$sharing_content .= '<div class="sd-content"><ul>';

	foreach ( $enabled['visible'] as $id => $service ) {
		switch ( $id ) {
			case 'email':
				$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="email"></amp-social-share></li>';
				break;
			case 'facebook':
				$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="facebook"></amp-social-share></li>';
				break;
			case 'linkedin':
				$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="linkedin"></amp-social-share></li>';
				break;
			case 'pinterest':
				$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="pinterest"></amp-social-share></li>';
				break;
			case 'google-plus-1':
				$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="gplus"></amp-social-share></li>';
				break;
			case 'tumblr':
				$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="tumblr"></amp-social-share></li>';
				break;
			case 'twitter':
				$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="twitter"></amp-social-share></li>';
				break;
		}
	}

	$sharing_content .= '<li class="share-end"></li></ul>';

	if ( count( $enabled['hidden'] ) > 0 ) {

		$parts = array();
		$parts[] = $visible;
		if ( count( $enabled['hidden'] ) > 0 ) {
			if ( count( $enabled['visible'] ) > 0 ) {
				$expand = esc_html__( 'More', 'jetpack' );
			} else {
				$expand = esc_html__( 'Share', 'jetpack' );
			}
			$id = 'sd-expand-' . uniqid( '', true ); // Just in case we need more than one per page, make it random.
			$parts[] = '<label for="' . esc_attr( $id ) . '" class="sharing-anchor sd-button share-more">' . esc_html( $expand ) . '</label><input type="checkbox" class="volt-sd-show-hidden" name="' . esc_attr( $id ) . '" id="' . esc_attr( $id ) . '">';
		}

		$sharing_content .= implode( '', $parts );

		$sharing_content .= '<div class="sharing-hidden"><div class="inner volt-sd-hidden-' . count( $enabled['hidden'] ) . '">';

		if ( 1 === count( $enabled['hidden'] ) ) {
			$sharing_content .= '<ul style="background-image:none;">';
		} else {
			$sharing_content .= '<ul>';
		}

		$count = 1;

		foreach ( $enabled['hidden'] as $id => $service ) {
			switch ( $id ) {
				case 'email':
					$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="email"></amp-social-share></li>';
					break;
				case 'facebook':
					$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="facebook"></amp-social-share></li>';
					break;
				case 'linkedin':
					$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="linkedin"></amp-social-share></li>';
					break;
				case 'pinterest':
					$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="pinterest"></amp-social-share></li>';
					break;
				case 'google-plus-1':
					$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="gplus"></amp-social-share></li>';
					break;
				case 'tumblr':
					$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="tumblr"></amp-social-share></li>';
					break;
				case 'twitter':
					$sharing_content .= '<li class="share-' . $service->get_class() . '"><amp-social-share type="twitter"></amp-social-share></li>';
					break;
			}
			if ( 0 === ( $count % 2 ) ) {
				$sharing_content .= '<li class="share-end"></li>';
			}

			$count ++;
		}
		$sharing_content .= '<li class="share-end"></li></ul></div></div>';
	}

	$sharing_content .= '</div></div></div>';

	// We don't want the extra footer stuff here, we're not going to use it.
	remove_action( 'wp_footer', 'sharing_add_footer' );

	return $sharing_content;
}
add_filter( 'jetpack_sharing_display_markup', 'volt_jetpack_sharing_ampify', 10, 1 );

//
// Related Posts
//
function volt_dequeue_jetpack_related_posts() {
	wp_dequeue_style( 'jetpack_related-posts' );
	wp_dequeue_script( 'jetpack_related-posts' );

	if ( is_single() ) {
		wp_enqueue_style( 'volt-style-relatedposts', get_stylesheet_directory_uri() . '/css/jetpack-relatedposts.css', array( 'volt-style' ) );
	}
}
add_action( 'wp', 'volt_dequeue_jetpack_related_posts', 15 );

/**
 * Remove the Related Posts placeholder and headline that gets hooked into the_content
 *
 * That placeholder is useless since we can't ouput, and don't want to output Related Posts in AMP.
 **/
function volt_disable_related_posts() {
	if ( class_exists( 'Jetpack_RelatedPosts' ) ) {
		$jprp = Jetpack_RelatedPosts::init();
		remove_filter( 'the_content', array( $jprp, 'filter_add_target_to_dom' ), 40 );
	}
}

function volt_jetpack_related_posts( $headline ) {
	global $post;

	$relatedposts_url = add_query_arg( array( 'relatedposts' => 1 ), get_permalink( $post->ID ) );

	$relatedposts_data = wp_cache_get( 'volt_jprp:' . $post->ID, 'volt' );
	if ( false === $relatedposts_data ) {
		$response = wp_remote_get( esc_url_raw( $relatedposts_url ) );
		if ( ! is_wp_error( $response ) ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		}
		$relatedposts_data = $response_body;
		wp_cache_set( 'volt_jprp:' . $post->ID, $relatedposts_data, 'volt', MINUTE_IN_SECONDS * 15 );
	}

	$relatedposts_markup = '';
	if ( is_array( $relatedposts_data ) && isset( $relatedposts_data['items'] ) ) {
		ob_start();
		echo '<div class="jp-relatedposts-items jp-relatedposts-items-visual">';
		foreach ( $relatedposts_data['items'] as $item ) {
			?>
				<div class="jp-relatedposts-post jp-relatedposts-post<?php echo absint( $item['id'] ); ?> <?php echo $has_image ? '' : 'jp-relatedposts-post-thumbs'; ?>" data-post-id="<?php echo absint( $item['id'] ); ?>" <?php echo $item['format'] ? '' : 'data-post-format="' . esc_attr( $item['format'] ) . '"'; ?>>
					<?php $has_image = ! empty( $item['img']['src'] ); ?>
					<a class="jp-relatedposts-post-a jp-relatedposts-post-a overlay" href="<?php echo esc_url( $item['url'] ); ?>" title="<?php echo esc_attr( $item['title'] ); ?>" rel="<?php echo esc_attr( $item['rel'] ); ?>">
						<?php if ( $has_image ) : ?>
						<amp-img src="<?php echo esc_url( $item['img']['src'] ); ?>" class="jp-relatedposts-post-img" width="<?php echo esc_attr( $item['img']['width'] ); ?>" height="<?php echo esc_attr( $item['img']['height'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" layout="responsive"></amp-img>
						<?php endif; ?>
					</a>
					<h4 class="jp-relatedposts-post-title">
						<a class="jp-relatedposts-post-a" href="<?php echo esc_url( $item['url'] ); ?>" title="<?php echo esc_attr( $item['title'] ); ?>" rel="<?php echo esc_attr( $item['rel'] ); ?>">
							<?php echo esc_html( $item['title'] ); ?>
						</a>
					</h4>
					<p class="jp-relatedposts-post-excerpt" style="max-height: 7.14286em;">
						<?php echo esc_html( $item['excerpt'] ); ?>
					</p>
					<p class="jp-relatedposts-post-date">
						<?php echo esc_html( $item['date'] ); ?>
					</p>
					<p class="jp-relatedposts-post-context">
						<?php echo esc_html( $item['context'] ); ?>
					</p>
				</div>
			<?php
		}
		echo '</div>';
		$relatedposts_markup = ob_get_contents();
		ob_end_clean();
	}

	return $headline . $relatedposts_markup;
}
add_filter( 'jetpack_relatedposts_filter_headline', 'volt_jetpack_related_posts', 10, 1 );

add_action( 'wp', function() {
	$text = '';
	$echo = false;

	global $post;

	require_once( JETPACK__PLUGIN_DIR . '/sync/class.jetpack-sync-settings.php' );
	if ( Jetpack_Sync_Settings::is_syncing() ) {
		return;
	}

	if ( empty( $post ) ) {
		return;
	}

	if ( ( is_preview() || is_admin() ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}

	// Don't output flair on excerpts
	if ( in_array( 'get_the_excerpt', (array) $wp_current_filter, true ) ) {
		return;
	}

	// check whether we are viewing the front page and whether the front page option is checked
	$options = get_option( 'sharing-options' );
	$display_options = $options['global']['show'];

	if ( is_front_page() && ( is_array( $display_options ) && ! in_array( 'index', $display_options, true ) ) ) {
		return;
	}

	if ( is_attachment() && in_array( 'the_excerpt', (array) $wp_current_filter, true ) ) {
		// Many themes run the_excerpt() conditionally on an attachment page, then run the_content().
		// We only want to output the sharing buttons once.  Let's stick with the_content().
		return;
	}

	$sharer = new Sharing_Service();
	$global = $sharer->get_global_options();

	$show = false;
	if ( ! is_feed() ) {
		if ( is_singular() && in_array( get_post_type(), $global['show'], true ) ) {
			$show = true;
		} elseif ( in_array( 'index', $global['show'], true ) && ( is_home() || is_front_page() || is_archive() || is_search() || in_array( get_post_type(), $global['show'], true ) ) ) {
			$show = true;
		}
	}

	/**
	 * Filter to decide if sharing buttons should be displayed.
	 *
	 * @module sharedaddy
	 *
	 * @since 1.1.0
	 *
	 * @param bool $show Should the sharing buttons be displayed.
	 * @param WP_Post $post The post to share.
	 */
	$show = apply_filters( 'sharing_show', $show, $post );

	// Disabled for this post?
	$switched_status = get_post_meta( $post->ID, 'sharing_disabled', false );

	if ( ! empty( $switched_status ) ) {
		$show = false;
	}

	$post_status = get_post_status( $post->ID );

	if ( 'private' === $post_status ) {
		$show = false;
	}

	if ( $show ) {
		wp_enqueue_script( 'amp-social-share', 'https://cdn.ampproject.org/v0/amp-social-share-0.1.js', array(), null );
		wp_enqueue_style( 'volt-style-jetpack-sharing', get_stylesheet_directory_uri() . '/css/jetpack-sharing.css', array( 'volt-style' ), $time );
	}
} );

//
// Likes, Comments
//

function volt_disable_likes( $modules, $min_version, $max_version ) {
	unset( $modules['likes'] );
	unset( $modules['comments'] );
	unset( $modules['gravatar-hovercards'] );
	unset( $modules['infinite-scroll'] );
	return $modules;
}
add_filter( 'jetpack_get_available_modules', 'volt_disable_likes', 20, 3 );


// No Snow, sorry! ☃️
add_filter( 'jetpack_is_holiday_snow_season', '__return_false' );

function volt_jetpack_disable_comments( $active_modules ) {
	$modules = array(
		'likes',
		'comments',
		'gravatar-hovercards',
		'minileven',
		'infinite-scroll',
		'holiday-snow',
	);
	foreach ( $modules as $module ) {
		$key = array_search( $module, $active_modules );
		if ( false !== $key ) {
			unset( $active_modules[ $key ] );
			remove_filter( 'option_jetpack_active_modules', 'volt_jetpack_disable_comments' );
			update_option( 'jetpack_active_modules', $active_modules );
			add_filter( 'option_jetpack_active_modules', 'volt_jetpack_disable_comments' );
		}
	}
	return $active_modules;
}
add_filter( 'option_jetpack_active_modules', 'volt_jetpack_disable_comments' );

//
// Photon
//
remove_action( 'wp_enqueue_scripts', array( Jetpack_Photon::instance(), 'action_wp_enqueue_scripts' ), 9 );
