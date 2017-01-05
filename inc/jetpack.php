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
