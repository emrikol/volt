<?php

function volt_embeds_enqueue() {
	if ( volt_check_posts_for_embed( '_volt_embeds_twitter' ) ) {
		wp_enqueue_script( 'amp-twitter', 'https://cdn.ampproject.org/v0/amp-twitter-0.1.js', array( 'amp-js' ), null );
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
