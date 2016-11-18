<?php
/**
 * Volt functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Volt
 */

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
	}
endif;
add_action( 'after_setup_theme', 'volt_setup' );

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
	// Not AMP compatible.
	//wp_enqueue_style( 'volt-style', get_stylesheet_uri() );
	//wp_enqueue_script( 'volt-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true );
	//wp_enqueue_script( 'volt-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );
	wp_enqueue_script( 'amp-js', 'https://cdn.ampproject.org/v0.js', array(), null );
	wp_enqueue_script( 'amp-form', 'https://cdn.ampproject.org/v0/amp-form-0.1.js', array(), null );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'volt_scripts' );

function volt_get_current_url() {
	global $wp;
	return home_url( add_query_arg( array(), $wp->request ) );
}

function volt_namespace_async_scripts( $tag, $handle ) {
	// TODO: filterize this, rename to something more generic
	$async = array(
		'amp-js',
		'amp-form',
	);

	$custom = array(
		'amp-form' => array(
			'custom-element' => 'amp-form',
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
	wp_dequeue_script( 'wp-embed' );
}
add_action( 'wp_footer', 'volt_deregister_footer_scripts' );

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
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';
