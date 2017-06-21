<?php

function volt_namespace_async_scripts( $tag, $handle ) {
	// TODO: filterize this, rename to something more generic
	$async = array(
		'amp-js',
		'amp-form',
		'amp-audio',
		'amp-social-share',
		'amp-twitter',
		'amp-youtube',
		'amp-instagram',
	);

	$custom = array(
		'amp-form' => array(
			'custom-element' => 'amp-form',
		),
		'amp-audio' => array(
			'custom-element' => 'amp-audio',
		),
		'amp-social-share' => array(
			'custom-element' => 'amp-social-share',
		),
		'amp-twitter' => array(
			'custom-element' => 'amp-twitter',
		),
		'amp-youtube' => array(
			'custom-element' => 'amp-youtube',
		),
		'amp-instagram' => array(
			'custom-element' => 'amp-instagram',
		),
	);

	// Add async attribute.
	if ( in_array( $handle, $async, true ) ) {
		$tag = str_replace( ' src', ' async src', $tag );
	}

	// Custom attributes.
	if ( isset( $custom[ $handle ] ) && ! empty( $custom[ $handle ] ) ) {
		foreach ( $custom[ $handle ] as $attribute => $value ) {
			$tag = str_replace( ' src', ' ' . wp_kses_post( $attribute ) . '="' . esc_attr( $value ) . '" src', $tag );
		}
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'volt_namespace_async_scripts', 10, 2 );

function volt_embeds_enqueue() {
	if ( volt_check_posts_for_embed( '_volt_embeds_twitter' ) ) {
		wp_enqueue_script( 'amp-twitter', 'https://cdn.ampproject.org/v0/amp-twitter-0.1.js', array( 'amp-js' ), null );
	}
	if ( volt_check_posts_for_embed( '_volt_embeds_youtube' ) ) {
		wp_enqueue_script( 'amp-youtube', 'https://cdn.ampproject.org/v0/amp-youtube-0.1.js', array( 'amp-js' ), null );
	}
	if ( volt_check_posts_for_embed( '_volt_embeds_instagram' ) ) {
		wp_enqueue_script( 'amp-instagram', 'https://cdn.ampproject.org/v0/amp-instagram-0.1.js', array( 'amp-js' ), null );
	}
	if ( volt_check_posts_for_embed( '_volt_embeds_forms' ) ) {
		wp_enqueue_script( 'amp-instagram', 'https://cdn.ampproject.org/v0/amp-instagram-0.1.js', array( 'amp-js' ), null );
	}
}
add_action( 'wp', 'volt_embeds_enqueue' );

function volt_check_posts_for_embed( $embed ) {
	$has_volt_embed = false;
	if ( is_front_page() || is_archive() ) {
		global $wp_query;

		$cache_key = $embed . ':' . $wp_query->query_vars_hash;
		$has_volt_embed = wp_cache_get( $cache_key, 'volt_embeds' );
		if ( false !== $has_volt_embed ) {
			return $has_volt_embed;
		}

		$ids = array();
		foreach ( $wp_query->posts as $post ) {
			$ids[] = $post->ID;
		}
		$args = array(
			'post__in' => $ids,
			'posts_per_page' => count( $ids ),
			'suppress_filters' => false,
			'meta_query' => array( // @codingStandardsIgnoreLine.
				'meta_key' => $embed, // @codingStandardsIgnoreLine.
				'meta_compare' => 'EXISTS',
			),
			'fields' => 'ids',
			'no_found_rows' => true,
			'update_post_term_cache' => false,
		);
		$has_volt_embed = empty( get_posts( $args ) ) ? '0' : true; // 0 == false, good enough for our check. @codingStandardsIgnoreLine.
		wp_cache_set( $cache_key, $has_volt_embed, 'volt_embeds', HOUR_IN_SECONDS );
	} elseif ( is_single() ) {
		global $post;
		$has_volt_embed = get_post_meta( $post->ID, $embed, true );
	}

	return $has_volt_embed;
}

// Twitter oEmbeds
add_filter( 'embed_oembed_html', 'volt_embed_tweets', 10, 4 );
function volt_embed_tweets( $html, $url, $attr, $post_id ) {
	if ( false !== strpos( $url, 'https://twitter.com/' ) ) {
		if ( 1 === preg_match( '/\/status\/(\d+)/', $url, $matches ) && isset( $matches[1] ) ) {
			$volt_embeds_twitter = get_post_meta( $post_id, '_volt_embeds_twitter' );
			if ( ! $volt_embeds_twitter ) {
				update_post_meta( $post_id, '_volt_embeds_twitter', true );
			}
			$tweet_id = absint( $matches[1] );
			$html = '<div class="embed"><amp-twitter layout="responsive" width="300" height="300" data-tweetid="' . esc_attr( $tweet_id ) . '"></amp-twitter></div>';
		}
	}
	return $html;
}

// YouTube oEmbeds
remove_action( 'init', 'wpcom_youtube_embed_crazy_url_init' );
add_filter( 'embed_oembed_html', 'volt_embed_youtube', 10, 4 );
function volt_embed_youtube( $html, $url, $attr, $post_id ) {
	if ( false !== strpos( $url, 'https://www.youtube.com/watch' ) ) {
		$video_id = jetpack_get_youtube_id( $url );
		if ( false !== $video_id ) {
			$volt_embeds_youtube = get_post_meta( $post_id, '_volt_embeds_youtube' );
			if ( ! $volt_embeds_youtube ) {
				update_post_meta( $post_id, '_volt_embeds_youtube', true );
			}
			$html = '<amp-youtube data-videoid="' . esc_attr( $video_id ) . '" layout="responsive" width="480" height="270"></amp-youtube>';
		}
	}
	return $html;
}

if ( ! function_exists( 'jetpack_get_youtube_id' ) ) {
	function jetpack_get_youtube_id( $url ) {
		// Do we have an $atts array?  Get first att
		if ( is_array( $url ) ) {
			$url = reset( $url );
		}

		$url = youtube_sanitize_url( $url );
		$url = wp_parse_url( $url );
		$id  = false;

		if ( ! isset( $url['query'] ) ) {
			return false;
		}

		parse_str( $url['query'], $qargs );

		if ( ! isset( $qargs['v'] ) && ! isset( $qargs['list'] ) ) {
			return false;
		}

		if ( isset( $qargs['list'] ) ) {
			$id = preg_replace( '|[^_a-z0-9-]|i', '', $qargs['list'] );
		}

		if ( empty( $id ) ) {
			$id = preg_replace( '|[^_a-z0-9-]|i', '', $qargs['v'] );
		}

		return $id;
	}
}
if ( ! function_exists( 'youtube_sanitize_url' ) ) {
	/**
	 * Normalizes a YouTube URL to include a v= parameter and a query string free of encoded ampersands.
	 *
	 * @param string $url
	 * @return string The normalized URL
	 */
	function youtube_sanitize_url( $url ) {
		$url = trim( $url, ' "' );
		$url = trim( $url );
		$url = str_replace( array( 'youtu.be/', '/v/', '#!v=', '&amp;', '&#038;', 'playlist' ), array( 'youtu.be/?v=', '/?v=', '?v=', '&', '&', 'videoseries' ), $url );

		// Replace any extra question marks with ampersands - the result of a URL like "http://www.youtube.com/v/9FhMMmqzbD8?fs=1&hl=en_US" being passed in.
		$query_string_start = strpos( $url, '?' );

		if ( false !== $query_string_start ) {
			$url = substr( $url, 0, $query_string_start + 1 ) . str_replace( '?', '&', substr( $url, $query_string_start + 1 ) );
		}

		return $url;
	}
}

// Instagram oEmbeds
if ( function_exists( 'jetpack_instagram_handler' ) ) {
	wp_embed_unregister_handler( 'jetpack_instagram' );
	wp_oembed_add_provider( '#https?://(www\.)?instagr(\.am|am\.com)/p/.*#i', 'https://api.instagram.com/oembed', true );
}
add_filter( 'embed_oembed_html', 'volt_embed_instagram', 10, 4 );
function volt_embed_instagram( $html, $url, $attr, $post_id ) {
	if ( false !== strpos( $url, 'https://www.instagram.com' ) ) {
		if ( 1 === preg_match( '/\/p\/(.*)\//', $url, $matches ) && isset( $matches[1] ) ) {
			$volt_embeds_instagram = get_post_meta( $post_id, '_volt_embeds_instagram' );
			if ( ! $volt_embeds_instagram ) {
				update_post_meta( $post_id, '_volt_embeds_instagram', true );
			}
			$shortcode = $matches[1];
			$html = '<amp-instagram data-shortcode="' . esc_attr( $shortcode ) . '" width="400" height="400" layout="responsive"></amp-instagram>';
		}
	}
	return $html;
}

// Forms
function volt_save_form_embed_status( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( false !== strpos( $post->post_content, '<form' ) ) {
		update_post_meta( $post_id, '_volt_embeds_forms', true );
	} else {
		delete_post_meta( $post_id, '_volt_embeds_forms' );
	}
}
add_action( 'save_post', 'volt_save_form_embed_status', 10, 2 );
