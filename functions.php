<?php
/**
 * Volt functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Volt
 */

if ( ! is_user_logged_in() ) {
	// Frontend CSS needs to all be concatenated together, and under 50,000 bytes
	require get_template_directory() . '/inc/cssconcat.php';
}

if ( ! function_exists( 'volt_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function volt_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on Volt, use a find and replace
		 * to change 'volt' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'volt', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'primary' => esc_html__( 'Primary', 'volt' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'volt_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Remove Emoji scripts/css, not AMP compatible.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		add_image_size( 'featured-imagex2', 1490, 746, true );
		add_image_size( 'featured-imagex1.5', 1176, 560, true );
		add_image_size( 'featured-image', 745, 373, true );
	}
endif;
add_action( 'after_setup_theme', 'volt_setup' );

// Commenting uses way too many form stuffs that aren't AMP compatible
function volt_force_comment_registration( $value ) {
	if ( ! is_admin() ) {
		return true;
	}
	return $value;
}
add_filter( 'option_comment_registration', 'volt_force_comment_registration' );
function volt_show_comment_registration_admin_notice() {
	$screen = get_current_screen();

	if ( 'options-discussion' === $screen->id ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( 'Your current theme (Volt) forces comment registration on the front end for AMP compatibility.', 'volt' ); ?></p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'volt_show_comment_registration_admin_notice' );

function volt_filter_search_form( $form ) {
	// AMP requires a form target of _top or _blank.
	return str_replace( '<form role="search"', '<form target="_blank" role="search"', $form );
}
add_filter( 'get_search_form', 'volt_filter_search_form', 10, 1 );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function volt_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'volt_content_width', 640 );
}
add_action( 'after_setup_theme', 'volt_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function volt_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'volt' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'volt' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'volt_widgets_init' );

// Remove Recent Comments widget style, not AMP compatible.
function volt_remove_recent_comments_widget_style() {
	global $wp_widget_factory;
	remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
}
add_action( 'widgets_init', 'volt_remove_recent_comments_widget_style' );

/**
 * Enqueue scripts and styles.
 */
function volt_scripts() {
	wp_enqueue_style( 'volt-style', get_stylesheet_uri() );

	$time = filemtime( get_template_directory() . '/css/menu.css' );
	wp_enqueue_style( 'volt-style-menu', get_stylesheet_directory_uri() . '/css/menu.css', array( 'volt-style' ), $time );

	// Not AMP compatible.
	wp_enqueue_script( 'amp-js', 'https://cdn.ampproject.org/v0.js', array(), null );
	// Find a way to auto-enqueue form JS when needed
	wp_enqueue_script( 'amp-form', 'https://cdn.ampproject.org/v0/amp-form-0.1.js', array( 'amp-js' ), null );
}
add_action( 'wp_enqueue_scripts', 'volt_scripts' );

function volt_dequeue_default_script() {
	// We don't ever need this on the frontend.
	wp_deregister_script( 'jquery' );
	wp_deregister_script( 'jquery-migrate' );
}
add_action( 'wp_enqueue_scripts', 'volt_dequeue_default_script', 1 );


function volt_get_current_url() {
	global $wp;
	return home_url( add_query_arg( array(), $wp->request ) );
}

function volt_namespace_async_scripts( $tag, $handle ) {
	// TODO: filterize this, rename to something more generic
	$async = array(
		'amp-js',
		'amp-form',
		'amp-audio',
		'amp-social-share',
		'amp-twitter',
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

function volt_deregister_footer_scripts() {
	// Not AMP compatible.
	if ( ! is_admin() ) {
		wp_dequeue_script( 'wp-embed' );
	}
}
add_action( 'wp_footer', 'volt_deregister_footer_scripts' );

if ( ! function_exists( 'volt_get_content_template' ) ) {
	function volt_get_content_template() {

		/* Blog */
		if ( is_home() ) {
			get_template_part( 'template-parts/content', 'archive' );
		} /* Post */
		elseif ( is_singular( 'post' ) ) {
			get_template_part( 'template-parts/content' );
		} /* Page */
		elseif ( is_page() ) {
			get_template_part( 'content', 'page' );
		} /* Attachment */
		elseif ( is_attachment() ) {
			get_template_part( 'content', 'attachment' );
		} /* Archive */
		elseif ( is_archive() ) {
			get_template_part( 'content', 'archive' );
		} /* Custom Post Type */
		else {
			get_template_part( 'content' );
		}
	}
}

if ( ! function_exists( 'volt_featured_image' ) ) {
	function volt_featured_image() {
		global $post;
		$featured_image = '';

		if ( has_post_thumbnail( $post->ID ) ) {
			$image_url = get_the_post_thumbnail_url( $post->ID, 'featured-image' );
			$image_src = wp_prepare_attachment_for_js( get_post_thumbnail_id( $post->ID ) );
			$image_width = $image_src['width'];
			$image_height = $image_src['height'];
			$image_alt = $image_src['alt'];
			$image_srcset = wp_get_attachment_image_srcset( get_post_thumbnail_id( $post->ID ) ) ? wp_get_attachment_image_srcset( get_post_thumbnail_id( $post->ID ) ) : '';

			$image_amp_tag = sprintf( '<amp-img layout="responsive" src="%s" srcset="%s" alt="%s" width="%d" height="%d"></amp-img>',
				esc_url( $image_url ),
				wp_kses_post( $image_srcset ),
				esc_attr( $image_alt ),
				absint( $image_width ),
				absint( $image_height )
			);
			if ( is_singular() ) {
				$featured_image = '<div class="featured-image">' . $image_amp_tag . '</div>';
			} else {
				$featured_image = '<div class="featured-image"><a href="' . esc_url( get_permalink() ) . '">' . $image_amp_tag . '</a></div>';
			}
		}

		$featured_image = apply_filters( 'volt_featured_image', $featured_image );

		echo wp_kses_post( $featured_image );
	}
}

if ( ! function_exists( 'volt_excerpt' ) ) {
	function volt_excerpt() {
		global $post;
		$show_archive_excerpt = get_theme_mod( 'volt_archive_excerpt' );
		$read_more_text = get_theme_mod( 'read_more_text' );
		$ismore = strpos( $post->post_content, '<!--more-->' );

		if ( ( false === $show_archive_excerpt ) && ! is_search() ) {
			if ( $ismore ) {
				// Has to be written this way because i18n text CANNOT be stored in a variable
				if ( ! empty( $read_more_text ) ) {
					the_content( esc_html( $read_more_text ) . " <span class='screen-reader-text'>" . esc_html( get_the_title() ) . '</span>' );
				} else {
					the_content( esc_html__( 'Continue reading', 'volt' ) . " <span class='screen-reader-text'>" . esc_html( get_the_title() ) . '</span>' );
				}
			} else {
				the_content();
			}
		} elseif ( $ismore ) {
			if ( ! empty( $read_more_text ) ) {
				the_content( esc_html( $read_more_text ) . " <span class='screen-reader-text'>" . esc_html( get_the_title() ) . '</span>' );
			} else {
				the_content( esc_html__( 'Continue reading', 'volt' ) . " <span class='screen-reader-text'>" . esc_html( get_the_title() ) . '</span>' );
			}
		} else {
			the_excerpt();
		}
	}
}

if ( ! function_exists( ( 'volt_post_class' ) ) ) {
	function volt_post_class( $classes ) {
		$classes[] = 'entry';

		return $classes;
	}
}
add_filter( 'post_class', 'volt_post_class' );

if ( ! function_exists( 'volt_excerpt_read_more_link' ) ) {
	function volt_excerpt_read_more_link( $output ) {
		$read_more_text = get_theme_mod( 'read_more_text' );

		if ( ! empty( $read_more_text ) ) {
			return $output . "<p><a class='more-link' href='" . esc_url( get_permalink() ) . "'>" . esc_html( $read_more_text ) . " <span class='screen-reader-text'>" . esc_html( get_the_title() ) . '</span></a></p>';
		} else {
			return $output . "<p><a class='more-link' href='" . esc_url( get_permalink() ) . "'>" . esc_html__( 'Continue reading', 'volt' ) . " <span class='screen-reader-text'>" . esc_html( get_the_title() ) . '</span></a></p>';
		}
	}
}
add_filter( 'the_excerpt', 'volt_excerpt_read_more_link' );

add_filter( 'wp_kses_allowed_html', 'volt_amp_allowed_tags', 10, 2 );
if ( ! function_exists( 'volt_amp_allowed_tags ' ) ) {
	function volt_amp_allowed_tags( $allowed_tags, $context ) {
		$amp_tags = array();

		$amp_tags['amp-img']   = array(
			'alt'         => true,
			'attribution' => true,
			'class'       => true,
			'height'      => true,
			'layout'      => true,
			'src'         => true,
			'srcset'      => true,
			'width'       => true,
		);

		$amp_tags['amp-audio'] = array(
			'width' => true,
			'height' => true,
			'src' => true,
		);

		$amp_tags['amp-social-share'] = array(
			'type' => true,
			'width' => true,
			'height' => true,
			'data-share-endpoint' => true,
			'data-param-text' => true,
			'data-param-url' => true,
			'data-param-subject' => true,
			'data-param-body' => true,
			'data-param-href' => true,
			'data-param-app_id' => true,
			'layout' => true,
		);

		$amp_tags['amp-twitter'] = array(
			'width' => true,
			'height' => true,
			'data-tweetid' => true,
			'data-cards' => true,
			'layout' => true,
		);

		$amp_tags['amp-youtube'] = array(
			'width' => true,
			'height' => true,
			'data-videoid' => true,
			'layout' => true,
		);

		$amp_tags['amp-instagram'] = array(
			'width' => true,
			'height' => true,
			'data-shortcode' => true,
			'data-captioned' => true,
			'layout' => true,
		);

		$amp_tags['source'] = array(
			'type' => true,
			'src' => true,
		);

		$amp_tags['blockquote'] = array(
			'placeholder' => true,
		);

		$amp_tags['div'] = array(
			'fallback' => true,
		);

		// Required for password protected posts.
		$allowed_tags['form']['action-xhr'] = true;
		$allowed_tags['input']['name'] = true;
		$allowed_tags['input']['id'] = true;
		$allowed_tags['input']['type'] = true;
		$allowed_tags['input']['size'] = true;
		$allowed_tags['input']['class'] = true;
		$allowed_tags['input']['value'] = true;

		if ( 'post' === $context ) {
			$allowed_tags = array_merge( $amp_tags, $allowed_tags );
		}

		if ( ! is_admin() ) {
			foreach ( $allowed_tags as $key => $tag ) {
				if ( isset( $allowed_tags[ $key ]['style'] ) ) {
					unset( $allowed_tags[ $key ]['style'] );
				}
			}
		}

		return $allowed_tags;
	}
}

add_filter( 'wp_kses_allowed_html', 'volt_svg_allowed_tags', 5, 2 );
if ( ! function_exists( 'volt_svg_allowed_tags ' ) ) {
	function volt_svg_allowed_tags( $allowed_tags, $context ) {
		$svg_tags = array();

		$svg_tags['svg'] = array(
			'xmlns' => true,
			'width' => true,
			'height' => true,
		);

		$svg_tags['g'] = array(
			'fill' => true,
			'fill-rule' => true,
		);

		$svg_tags['path'] = array(
			'd' => true,
			'class' => true,
		);

		$allowed_tags = array_merge( $svg_tags, $allowed_tags );

		return $allowed_tags;
	}
}

if ( ! function_exists( 'volt_content_strip_styles' ) ) {
	function volt_content_strip_styles( $content ) {
		return wp_kses_post( $content );
	}
}
add_filter( 'the_content', 'volt_content_strip_styles', 10000, 1 );


if ( ! function_exists( 'volt_content_transform_images' ) ) {
	function volt_content_transform_images( $content ) {
		$img_tag_regex = '/(?<img><\\s*img\s+.*>)/Ui';

		$result_count = preg_match_all( $img_tag_regex, $content, $matches );

		if ( ! $result_count ) {
			return $content;
		}

		foreach ( $matches['img'] as $img_tag ) {
			$img_attr = array(
				'src' => false,
				'class' => false,
				'title' => false,
				'alt' => false,
				'srcset' => false,
				'width' => false,
				'height' => false,
				'layout' => 'responsive', // Needed for amp-img tag.
			);


			foreach ( $img_attr as $attr => $value ) {
				$result_count = preg_match( '/<\\s*img\\s+.*' . $attr . '=["\'](?<' . $attr . '>.*)["\'].*>/Ui', $img_tag, $tag_matches );
				if ( ! $result_count ) {
					continue; // Attribute not found in this tag, go to next attribute.
				}
				$img_attr[ $attr ] = trim( $tag_matches[ $attr ] );
			}

			// layout needs to be fixed if alignleft or alignright is used, otherwise the AMP img to amp-img JS freaks out
			$classes = explode( ' ', $img_attr['class'] );
			if ( in_array( 'alignright', $classes, true ) || in_array( 'alignleft', $classes, true ) ) {
				$img_attr['layout'] = 'fixed';
			}

			// Borrowed from http://stackoverflow.com/a/18081767
			$amp_img = '<amp-img ' . join( ' ', array_map( function( $key ) use ( $img_attr ) {
				if ( is_bool( $img_attr[ $key ] ) ) {
					return $img_attr[ $key ] ? $key : '';
				}
				return $key . '="' . $img_attr[ $key ] . '"';
			}, array_keys( $img_attr ) ) ) . '></amp-img>';

			// Now replace $img_tag with $amp_img in $content
			$content = str_replace( $img_tag, $amp_img, $content );
		}

		return $content;
	}
}
add_filter( 'the_content', 'volt_content_transform_images', 10010, 1 ); // Run _very_ late, just in case any other filters add images.

add_filter( 'wp_audio_shortcode_override', 'volt_audio_shortcode', 10, 3 );
function volt_audio_shortcode( $attr, $content = '' ) {
	$post_id = get_post() ? get_the_ID() : 0;

	static $instance = 0;
	$instance++;

	$audio = null;

	$default_types = wp_get_audio_extensions();
	$defaults_atts = array(
		'src'      => '',
		'loop'     => '',
		'autoplay' => '',
		'preload'  => 'none',
		'class'    => 'wp-audio-shortcode',
		'style'    => 'width: 100%;',
	);
	foreach ( $default_types as $type ) {
		$defaults_atts[ $type ] = '';
	}

	$atts = shortcode_atts( $defaults_atts, $attr, 'audio' );

	$primary = false;
	$no_support = false;
	if ( ! empty( $atts['src'] ) ) {
		$type = wp_check_filetype( $atts['src'], wp_get_mime_types() );
		if ( ! in_array( strtolower( $type['ext'] ), $default_types, true ) ) {
			$no_support = sprintf( '<a class="wp-embedded-audio" href="%s">%s</a>', esc_url( $atts['src'] ), esc_html( $atts['src'] ) );
		}
		$primary = true;
		array_unshift( $default_types, 'src' );
	} else {
		foreach ( $default_types as $ext ) {
			if ( ! empty( $atts[ $ext ] ) ) {
				$type = wp_check_filetype( $atts[ $ext ], wp_get_mime_types() );
				if ( strtolower( $type['ext'] ) === $ext ) {
					$primary = true;
				}
			}
		}
	}

	if ( ! $primary ) {
		$audios = get_attached_media( 'audio', $post_id );
		if ( empty( $audios ) ) {
			return;
		}

		$audio = reset( $audios );
		$atts['src'] = wp_get_attachment_url( $audio->ID );
		if ( empty( $atts['src'] ) ) {
			return;
		}

		array_unshift( $default_types, 'src' );
	}

	/**
	 * Filters the class attribute for the audio shortcode output container.
	 *
	 * @since 3.6.0
	 *
	 * @param string $class CSS class or list of space-separated classes.
	 */
	$atts['class'] = apply_filters( 'wp_audio_shortcode_class', $atts['class'] );

	$html_atts = array(
		'class'    => $atts['class'],
		'id'       => sprintf( 'audio-%d-%d', $post_id, $instance ),
		'loop'     => wp_validate_boolean( $atts['loop'] ),
		'autoplay' => $atts['autoplay'],
	);

	// These ones should just be omitted altogether if they are blank
	foreach ( array( 'loop', 'autoplay' ) as $a ) {
		if ( empty( $html_atts[ $a ] ) ) {
			unset( $html_atts[ $a ] );
		}
	}

	$attr_strings = array();
	foreach ( $html_atts as $k => $v ) {
		$attr_strings[] = $k . '="' . esc_attr( $v ) . '"';
	}

	$html = '';

	$html .= sprintf( '<amp-audio %s>', join( ' ', $attr_strings ) );

	$fileurl = '';
	$source = '<source type="%s" src="%s">';
	foreach ( $default_types as $fallback ) {
		if ( ! empty( $atts[ $fallback ] ) ) {
			if ( empty( $fileurl ) ) {
				$fileurl = $atts[ $fallback ];
			}
			$type = wp_check_filetype( $atts[ $fallback ], wp_get_mime_types() );
			$url = add_query_arg( '_', $instance, $atts[ $fallback ] );
			$html .= sprintf( $source, $type['type'], esc_url( $url ) );
		}
	}

	if ( $no_support ) {
		$html .= '<div fallback>' . $no_support . '</div>';
	}
	$html .= '</amp-audio>';
	return $html;
}

add_filter( 'wp', 'volt_amp_stuff' );
function volt_amp_stuff() {
	global $post;

	if ( is_object( $post ) && has_shortcode( $post->post_content, 'audio' ) ) {
		wp_enqueue_script( 'amp-audio', 'https://cdn.ampproject.org/v0/amp-audio-0.1.js', array(), null );
	}
}

if ( ! function_exists( 'volt_remove_comment_reply_link_style' ) ) {
	function volt_remove_comment_reply_link_style( $formatted_link, $link, $text ) {
		return preg_replace( '/(<[^>]+) style=".*?"/i', '$1', $formatted_link );
	}
}
add_filter( 'cancel_comment_reply_link', 'volt_remove_comment_reply_link_style', 10, 3 );

if ( ! function_exists( 'volt_comment_form' ) ) {
	function volt_comment_form() {
		ob_start();
		comment_form();
		$form_html = ob_get_contents();
		ob_end_clean();

		echo str_replace( '<form action=', '<form target="_blank" action-xhr=', $form_html ); // @codingStandardsIgnoreLine.
	}
}

if ( ! function_exists( 'volt_filter_password_form' ) ) {
	function volt_filter_password_form( $form_html ) {
		return str_replace( '<form action=', '<form target="_blank" action-xhr=', $form_html );
	}
}
add_filter( 'the_password_form', 'volt_filter_password_form', 10, 1 );


if ( ! function_exists( 'volt_big_css_admin_notice' ) ) {
	function volt_big_css_admin_notice() {
		$css_size = get_transient( 'volt_big_css' );
		if ( false === $css_size ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p><?php echo wp_kses_post( sprintf( 'AMP CSS is too long!  It is %d bytes and the limit is 50000 bytes.  <a href="https://www.ampproject.org/docs/reference/spec#maximum-size">Learn more</a>', absint( $css_size ) ), 'volt' ); ?></p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'volt_big_css_admin_notice' );

function volt_filter_post_password_form() {
	global $post;
	return 'This page is protected.  Please <a href="' . esc_url( wp_login_url( get_permalink( $post->ID ) ) ) . '" title="Login">Login</a>.';
}
add_filter( 'the_password_form', 'volt_filter_post_password_form' );


if ( ! function_exists( ( 'volt_customize_comments' ) ) ) {
	function volt_customize_comments( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment;
		global $post;
		?>
		<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<article id="comment-<?php comment_ID(); ?>" class="comment">
			<div class="comment-author">
				<?php
				$size = 36;
				$image_amp_tag = sprintf( '<amp-img layout="fixed" src="%s" srcset="%s" width="%d" height="%d" class="avatar avatar-' . $size . ' photo"></amp-img>',
					esc_url( get_avatar_url( get_comment_author_email(), array( 'size' => $size ) ) ),
					esc_attr( get_avatar_url( get_comment_author_email(), array( 'size' => $size ) ) . '2x 2x' ),
					absint( $size ),
					absint( $size )
				);
				echo wp_kses_post( $image_amp_tag );
				?>
				<span class="author-name"><?php comment_author_link(); ?></span>
			</div>
			<div class="comment-content">
				<?php if ( '0' === $comment->comment_approved ) : ?>
					<em><?php esc_html_e( 'Your comment is awaiting moderation.', 'volt' ) ?></em>
					<br/>
				<?php endif; ?>
				<?php comment_text(); ?>
			</div>
			<div class="comment-footer">
				<span class="comment-date"><?php comment_date(); ?></span>
				<?php comment_reply_link( array_merge( $args, array(
					'reply_text' => esc_html__( 'Reply', 'volt' ),
					'depth'      => $depth,
					'max_depth'  => $args['max_depth'],
				) ) ); ?>
				<?php edit_comment_link( esc_html__( 'Edit', 'volt' ) ); ?>
			</div>
		</article>
		<?php
	}
}

if ( ! function_exists( 'volt_admin_bar_additions' ) ) {
	function volt_admin_bar_additions() {
		$custom_css = "@media screen and (max-width: 600px) {
				#wpadminbar {
					margin-top: -46px;
				}
			}";
		wp_add_inline_style( 'admin-bar', $custom_css );
	}
}
add_action( 'wp_enqueue_scripts', 'volt_admin_bar_additions' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Custom embed functionality.
 */
require get_template_directory() . '/inc/embeds.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';
