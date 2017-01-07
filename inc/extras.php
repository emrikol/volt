<?php
/**
 * Custom functions that act independently of the theme templates.
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Volt
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function volt_body_classes( $classes ) {
	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	return $classes;
}
add_filter( 'body_class', 'volt_body_classes' );

/**
 * Add a pingback url auto-discovery header for singularly identifiable articles.
 */
function volt_pingback_header() {
	if ( is_singular() && pings_open() ) {
		echo '<link rel="pingback" href="', bloginfo( 'pingback_url' ), '">';
	}
}
add_action( 'wp_head', 'volt_pingback_header' );

if ( ! function_exists( 'volt_remove_anonymous_object_filter' ) ) {
	/**
	* Remove an anonymous object filter.
	*
	* http://wordpress.stackexchange.com/a/57088/89
	*
	* @param  string $tag    Hook name.
	* @param  string $class  Class name
	* @param  string $method Method name
	*
	* @return void
	*/
	function volt_remove_anonymous_object_filter( $tag, $class, $method ) {
		$filters = false;

		if ( isset( $GLOBALS['wp_filter'][ $tag ] ) ) {
			$filters = $GLOBALS['wp_filter'][ $tag ];
		}

		if ( $filters ) {
			foreach ( $filters as $priority => $filter ) {
				foreach ( $filter as $identifier => $function ) {
					if ( ! is_array( $function ) ) {
						continue;
					}
					if ( ! $function['function'][0] instanceof $class ) {
						continue;
					}
					if ( $method == $function['function'][1] ) {
						remove_filter(
							$tag,
							array( $function['function'][0], $method ),
							$priority
						);
					}
				}
			}
		}
	}
}

function volt_pressable_extras() {
	// Remove forced JS from Pressable MU Plugins
	if ( defined( 'IS_PRESSABLE' ) && IS_PRESSABLE ) {
		volt_remove_anonymous_object_filter( 'wp_footer', 'Pressable_Mu_Plugin', 'gauges_init' );
	}
}
add_action( 'init', 'volt_pressable_extras' );