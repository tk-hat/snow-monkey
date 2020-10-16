<?php
/**
 * @package snow-monkey
 * @author inc2734
 * @license GPL-2.0+
 * @version 11.6.0
 */

use Framework\Helper;

$_post_type           = get_post_type() ? get_post_type() : 'post';
$eyecatch_position    = 'post' === $_post_type
	? get_theme_mod( 'archive-eyecatch' )
	: get_theme_mod( 'archive-' . $_post_type . '-eyecatch' );
$display_entry_header = 'title-on-page-header' !== $eyecatch_position;
$display_eyecatch     = 'content-top' === $eyecatch_position;

$args = wp_parse_args(
	// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
	$args,
	// phpcs:enable
	[
		'_display_archive_top_widget_area' => false,
		'_display_description'             => false,
		'_display_entry_header'            => $display_entry_header,
		'_display_eyecatch'                => $display_eyecatch,
		'_post_type'                       => $_post_type,
	]
);
?>

<?php
if ( $args['_display_archive_top_widget_area'] ) {
	Helper::get_template_part( 'template-parts/widget-area/archive-top' );
}
?>

<div class="c-entry">
	<?php
	if ( $args['_display_entry_header'] ) {
		Helper::get_template_part( 'template-parts/archive/entry/header/header', $args['_post_type'] );
	}
	?>

	<div class="c-entry__body">
		<?php
		if ( $args['_display_eyecatch'] ) {
			Helper::get_template_part( 'template-parts/archive/eyecatch' );
		}
		?>

		<?php if ( $args['_display_description'] ) : ?>
			<div class="p-term-description p-entry-content">
				<?php echo wp_kses_post( term_description() ); ?>
			</div>
		<?php endif; ?>

		<?php
		Helper::get_template_part(
			'template-parts/archive/entry/content/content',
			$args['_post_type'],
			[
				'_entries_layout' => get_theme_mod( $_post_type . '-entries-layout' ),
				'_force_sm_1col'  => get_theme_mod( $_post_type . '-entries-layout-sm-1col' ),
			]
		);
		?>
	</div>
</div>
