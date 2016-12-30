<?php
/*
 * Modified from https://github.com/Automattic/nginx-http-concat/blob/master/cssconcat.php
 */

if ( ! defined( 'ALLOW_GZIP_COMPRESSION' ) ) {
	define( 'ALLOW_GZIP_COMPRESSION', true );
}

class WPcom_CSS_Concat extends WP_Styles {
	private $old_styles;
	public $allow_gzip_compression;

	function __construct( $styles ) {
		if ( empty( $styles ) || ! ( $styles instanceof WP_Styles ) ) {
			$this->old_styles = new WP_Styles();
		} else {
			$this->old_styles = $styles;
		}

		// Unset all the object properties except our private copy of the styles object.
		// We have to unset everything so that the overload methods talk to $this->old_styles->whatever
		// instead of $this->whatever.
		foreach ( array_keys( get_object_vars( $this ) ) as $key ) {
			if ( 'old_styles' === $key ) {
				continue;
			}
			unset( $this->$key );
		}
	}

	function do_items( $handles = false, $group = false ) {
		$handles = false === $handles ? $this->queue : (array) $handles;
		$stylesheets = array();
		$siteurl = site_url();

		$this->all_deps( $handles );

		$stylesheet_group_index = 0;
		foreach ( $this->to_do as $key => $handle ) {
			$obj = $this->registered[ $handle ];
			$obj->src = apply_filters( 'style_loader_src', $obj->src, $obj->handle );

			// Core is kind of broken and returns "true" for src of "colors" handle
			// http://core.trac.wordpress.org/attachment/ticket/16827/colors-hacked-fixed.diff
			// http://core.trac.wordpress.org/ticket/20729
			if ( 'colors' === $obj->handle && true === $obj->src ) {
				$css_url = wp_parse_url( wp_style_loader_src( $obj->src, $obj->handle ) );
			} else {
				$css_url = wp_parse_url( $obj->src );
			}
			$extra = $obj->extra;

			// Don't concat by default
			$do_concat = false;

			// Only try to concat static css files
			if ( false !== strpos( $css_url['path'], '.css' ) ) {
				$do_concat = true;
			}

			// Don't try to concat styles which are loaded conditionally (like IE stuff)
			if ( isset( $extra['conditional'] ) ) {
				$do_concat = false;
			}

			// Don't concat rtl stuff for now until concat supports it correctly
			if ( 'rtl' === $this->text_direction && ! empty( $extra['rtl'] ) ) {
				$do_concat = false;
			}

			// Don't try to concat externally hosted scripts
			if ( ( isset( $css_url['host'] ) && ( preg_replace( '/https?:\/\//', '', $siteurl ) !== $css_url['host'] ) ) ) {
				$do_concat = false;
			}

			// Concat and canonicalize the paths only for
			// existing scripts that aren't outside ABSPATH
			$css_realpath = realpath( ABSPATH . $css_url['path'] );
			if ( ! $css_realpath || 0 !== strpos( $css_realpath, ABSPATH ) ) {
				$do_concat = false;
			} else { $css_url['path'] = substr( $css_realpath, strlen( ABSPATH ) - 1 );
			}

			// Allow plugins to disable concatenation of certain stylesheets.
			$do_concat = apply_filters( 'css_do_concat', $do_concat, $handle );

			if ( true === $do_concat ) {
				$media = $obj->args;
				if ( empty( $media ) ) {
					$media = 'all';
				}
				if ( ! isset( $stylesheets[ $stylesheet_group_index ] ) || ( isset( $stylesheets[ $stylesheet_group_index ] ) && ! is_array( $stylesheets[ $stylesheet_group_index ] ) ) ) {
					$stylesheets[ $stylesheet_group_index ] = array();
				}

				$stylesheets[ $stylesheet_group_index ][ $media ][ $handle ] = $css_url['path'];
				$this->done[] = $handle;
			} else {
				$stylesheet_group_index++;
				$stylesheets[ $stylesheet_group_index ]['noconcat'][] = $handle;
				$stylesheet_group_index++;
			}
			unset( $this->to_do[ $key ] );
		}

		// AMP styles can't be in the footer.
		if ( doing_action( 'wp_footer' ) ) {
			return $this->done;
		}

		require_once( 'cssmin.php' );
		$css_minify = new CSSmin();
		echo '<style amp-custom>' . PHP_EOL; // @codingStandardsIgnoreLine.
		foreach ( $stylesheets as $idx => $stylesheets_group ) {
			foreach ( $stylesheets_group as $media => $css ) {
				if ( 'noconcat' === $media ) {

					foreach ( $css as $handle ) {
						if ( $this->do_item( $handle, $group ) ) {
							$this->done[] = $handle;
						}
					}
					continue;
				} elseif ( count( $css ) > 1 ) {
					$paths = array_map( function( $url ) { return ABSPATH . $url;
					}, $css );
					$mtime = max( array_map( 'filemtime', $paths ) );
					$path_str = implode( $css, ',' ) . "?m={$mtime}";

					if ( $this->allow_gzip_compression ) {
						$path_64 = base64_encode( gzcompress( $path_str ) );
						if ( strlen( $path_str ) > ( strlen( $path_64 ) + 1 ) ) {
							$path_str = '-' . $path_64;
						}
					}

					$href = $siteurl . '/_static/??' . $path_str;
				} else {
					$href = $this->cache_bust_mtime( $siteurl . current( $css ) );
				}

				$css_size = 0;
				foreach ( $css as $css_url ) {
					$cache_key = md5( $css_url . ':' . $mtime );
					$css_header = '/* CSS: ' . $css_url . ':' . $mtime . ' */';

					$cached_minfied_css = wp_cache_get( $cache_key, 'volt_concat_css' );
					if ( false === $cached_minfied_css ) {
						$cached_minified_css = $css_minify->run( file_get_contents( $siteurl . $css_url ) ); // @codingStandardsIgnoreLine.
						wp_cache_set( $cache_key, $cached_minified_css, 'volt_concat_css', HOUR_IN_SECONDS * 24 );
					}

					echo wp_kses_post( $css_header . PHP_EOL );
					echo wp_kses_post( $cached_minified_css . PHP_EOL );

					$css_size += strlen( $css_header . PHP_EOL ) + strlen( $cached_minified_css . PHP_EOL );
				}

				// AMP requires custom css to be under 50,000 bytes
				if ( $css_size >= 50000 && ! is_user_logged_in() ) {
					set_transient( 'volt_big_css', $css_size );
				} else {
					delete_transient( 'volt_big_css' );
				}
				array_map( array( $this, 'print_inline_style' ), array_keys( $css ) );
			}
		}
		echo '</style>' . PHP_EOL; // @codingStandardsIgnoreLine.

		return $this->done;
	}

	function cache_bust_mtime( $url ) {
		if ( strpos( $url, '?m=' ) ) {
			return $url;
		}

		$parts = wp_parse_url( $url );
		if ( ! isset( $parts['path'] ) || empty( $parts['path'] ) ) {
			return $url;
		}

		$file = ABSPATH . ltrim( $parts['path'], '/' );

		$mtime = false;
		if ( file_exists( $file ) ) {
			$mtime = filemtime( $file );
		}

		if ( ! $mtime ) {
			return $url;
		}

		if ( false === strpos( $url, '?' ) ) {
			$q = '';
		} else {
			list( $url, $q ) = explode( '?', $url, 2 );
			if ( strlen( $q ) ) {
				$q = '&amp;' . $q;
			}
		}

		return "$url?m={$mtime}g{$q}";
	}

	function __isset( $key ) {
		return isset( $this->old_styles->$key );
	}

	function __unset( $key ) {
		unset( $this->old_styles->$key );
	}

	function &__get( $key ) {
		return $this->old_styles->$key;
	}

	function __set( $key, $value ) {
		$this->old_styles->$key = $value;
	}
}

function css_concat_init() {
	global $wp_styles;

	$wp_styles = new WPcom_CSS_Concat( $wp_styles );
	$wp_styles->allow_gzip_compression = ALLOW_GZIP_COMPRESSION;
}

add_action( 'init', 'css_concat_init' );
