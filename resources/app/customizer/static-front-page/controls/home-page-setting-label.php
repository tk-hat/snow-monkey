<?php
/**
 * @package snow-monkey
 * @author inc2734
 * @license GPL-2.0+
 * @version 7.0.0
 */

use Inc2734\WP_Customizer_Framework\Framework;

Framework::control(
	'content',
	'home-page-setting-label',
	[
		'label'           => __( 'Front page setting', 'snow-monkey' ),
		'priority'        => 100,
		'active_callback' => function() {
			return 'page' === get_option( 'show_on_front' ) && ! empty( get_option( 'page_on_front' ) );
		},
	]
);

if ( ! is_customize_preview() ) {
	return;
}

$section = Framework::get_section( 'static_front_page' );
$control = Framework::get_control( 'home-page-setting-label' );
$control->join( $section );