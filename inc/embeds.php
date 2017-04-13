<?php

function volt_embeds_enqueue() {
	if ( volt_check_posts_for_embed( '_volt_embeds_twitter' ) ) {
		wp_enqueue_script( 'amp-twitter', 'https://cdn.ampproject.org/v0/amp-twitter-0.1.js', array( 'amp-js' ), null );
	}
	if ( volt_check_posts_for_embed( '_volt_embeds_youtube' ) ) {
		wp_enqueue_script( 'amp-youtube', 'https://cdn.ampproject.org/v0/amp-youtube-0.1.js', array( 'amp-js' ), null );
	}
}
add_action( 'wp', 'volt_embeds_enqueue' );

function volt_check_posts_for_embed( $embed ) {
	$has_volt_embed = false;
	if ( is_front_page() || is_archive() ) {
		global $wp_query;
		$ids = array();
		foreach ( $wp_query->posts as $post ) {
			$ids[] = $post->ID;
		}
		$args = array(
			'post__in' => $ids,
			'posts_per_page' => 1,
			'suppress_filters' => false,
			'meta_query' => array(
				'meta_key' => $embed,
				'meta_compare' => 'EXISTS',
			),
			'fields' => 'ids',
			'no_found_rows' => true,
			'update_post_term_cache' => false,
		);
		$has_volt_embed = empty( get_posts( $args ) ) ? false : true;
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