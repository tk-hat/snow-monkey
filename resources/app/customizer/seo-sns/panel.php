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

Framework::panel(
	'seo-sns',
	array(
		'title'    => __( 'SEO/SNS', 'snow-monkey' ),
		'priority' => 1040,
	)
);
