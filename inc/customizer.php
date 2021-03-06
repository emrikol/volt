<?php
/**
 * Volt Theme Customizer.
 *
 * @package Volt
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function volt_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	$wp_customize->add_setting(
		'volt_archive_excerpt',
		array(
			'default' => true,
			'transport' => 'refresh',
		)
	);
	$wp_customize->add_control( 'volt_archive_excerpt', array(
		'type' => 'checkbox',
		'section' => 'static_front_page',
		'label' => esc_html__( 'Excerpt on Archives' ),
		'description' => esc_html__( 'Show excerpts, instead of full posts, on archive pages (includes front page).' ),
	) );

}
add_action( 'customize_register', 'volt_customize_register' );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function volt_customize_preview_js() {
	wp_enqueue_script( 'volt_customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20151215', true );
}
add_action( 'customize_preview_init', 'volt_customize_preview_js' );
