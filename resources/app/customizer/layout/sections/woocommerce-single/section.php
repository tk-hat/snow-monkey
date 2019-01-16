<?php
/**
 * @package snow-monkey
 * @author inc2734
 * @license GPL-2.0+
 */

use Inc2734\WP_Customizer_Framework\Framework;

if ( ! is_customize_preview() ) {
	return;
}

Framework::section(
	'woocommerce-single',
	[
		'title'           => __( 'Page layout', 'snow-monkey' ),
		'description'     => __( 'By the type of page displayed on the preview screen on the right side of the screen, the display setting items switched.', 'snow-monkey' ) . __( 'Currently product page settings is displayed.', 'snow-monkey' ),
		'priority'        => 130,
		'active_callback' => function() {
			if ( ! class_exists( '\woocommerce' ) ) {
				return false;
			}
			return is_product();
		},
	]
);
