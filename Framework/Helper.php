<?php
/**
 * @package snow-monkey
 * @author inc2734
 * @license GPL-2.0+
 * @version 13.0.0
 */

namespace Framework;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Inc2734\WP_Helper;
use Inc2734\WP_Breadcrumbs;
use Inc2734\WP_View_Controller;

class Helper {

	use WP_Helper\Contract\Helper;
	use Contract\Helper\Page_Header;
	use Contract\Helper\Category_Thumbnail;
	use Contract\Helper\Homepage_Thumbnail;
	use Contract\Helper\Post_Type_Archive_Thumbnail;
	use Contract\Helper\WooCommerce_Archive_Thumbnail;
	use Contract\Helper\Deprecated;
	use WP_View_Controller\App\Contract\Template_Tag;

	/**
	 * Return loading method.
	 *
	 * @param string $method Loading method.
	 * @param string $path   Target directory.
	 * @return string
	 */
	protected static function _get_loading_method( $method, $path ) {
		return apply_filters( 'snow_monkey_loading_method', $method, $path );
	}

	/**
	 * Load files.
	 *
	 * @param string  $method             Loading method.
	 * @param string  $directory          Target directory.
	 * @param boolean $exclude_underscore Exclude if true.
	 * @return void
	 */
	public static function load_files( $method, $directory, $exclude_underscore = false ) {
		switch ( Helper::_get_loading_method( $method, $directory ) ) {
			case 'get_template_parts':
				Helper::get_template_parts( $directory, $exclude_underscore );
				break;
			case 'load_theme_files':
				Helper::load_theme_files( $directory, $exclude_underscore );
				break;
			default:
				Helper::include_files( $directory, $exclude_underscore );
		}
	}


	/**
	 * Return output positions of eyecatch.
	 *
	 * @return array
	 */
	public static function eyecatch_position_choices() {
		return [
			'page-header'          => __( 'Page header', 'snow-monkey' ),
			'title-on-page-header' => __( 'Title on page header', 'snow-monkey' ),
			'content-top'          => __( 'Top of contents', 'snow-monkey' ),
			'none'                 => __( 'None', 'snow-monkey' ),
		];
	}

	/**
	 * Return page header alignment choices.
	 *
	 * @return array
	 */
	public static function page_header_align_choices() {
		return [
			'left'   => __( 'Left', 'snow-monkey' ),
			'center' => __( 'Center', 'snow-monkey' ),
			'right'  => __( 'Right', 'snow-monkey' ),
		];
	}

	/**
	 * Returns page title from Breadcrumbs.
	 *
	 * @return string
	 */
	public static function get_page_title_from_breadcrumbs() {
		$breadcrumbs_items = static::get_breadcrumbs_items();
		$title_item        = end( $breadcrumbs_items );
		return array_key_exists( 'title', $title_item ) ? $title_item['title'] : '';
	}


	/**
	 * Return trimed title.
	 *
	 * @param string|null $title The post title.
	 * @return void
	 */
	public static function the_title_trimed( $title = null ) {
		// phpcs:disable WordPress.WP.I18n.MissingArgDomain
		$num_words            = 80;
		$excerpt_length_ratio = 55 / _x( '55', 'excerpt_length' );
		$num_words            = apply_filters( 'snow_monkey_entry_summary_title_num_words', $num_words * $excerpt_length_ratio );
		// phpcs:enable
		if ( $num_words ) {
			if ( is_null( $title ) ) {
				ob_start();
				the_title();
				$title = ob_get_clean();
			}

			$title = wp_trim_words( $title, $num_words );
			echo esc_html( $title );
		} else {
			if ( is_null( $title ) ) {
				the_title();
			} else {
				echo esc_html( $title );
			}
		}
	}

	/**
	 * Get global variable ( For template part ).
	 *
	 * @param string $name The variable name.
	 * @param string $default mixed The default value.
	 */
	public static function get_var( &$name, $default = null ) {
		return isset( $name ) ? $name : $default;
	}

	/**
	 * Return bread crumbs items of current page
	 *
	 * @return array
	 *   @var string $title
	 *   @var string $link
	 */
	public static function get_breadcrumbs_items() {
		static $breadcrumbs_items = [];
		if ( $breadcrumbs_items ) {
			return $breadcrumbs_items;
		}

		$breadcrumbs       = new WP_Breadcrumbs\Bootstrap();
		$breadcrumbs_items = apply_filters( 'snow_monkey_breadcrumbs', $breadcrumbs->get() );
		return $breadcrumbs_items;
	}

	/**
	 * Return copyright text.
	 *
	 * @return string
	 */
	public static function get_copyright() {
		$theme_link = sprintf(
			'<a href="https://snow-monkey.2inc.org" target="_blank" rel="noreferrer">%s</a>',
			__( 'Snow Monkey', 'snow-monkey' )
		);

		$developer_link = sprintf(
			'<a href="https://2inc.org" target="_blank" rel="noreferrer">%s</a>',
			__( 'Monkey Wrench', 'snow-monkey' )
		);

		$wordpress_link = sprintf(
			'<a href="https://wordpress.org/" target="_blank" rel="noreferrer">%s</a>',
			__( 'WordPress', 'snow-monkey' )
		);

		$theme_by = sprintf(
			/* translators: %1$s: Theme link, %2$s: Developer link */
			__( '%1$s theme by %2$s', 'snow-monkey' ),
			$theme_link,
			$developer_link
		);

		$powered_by = sprintf(
			/* translators: %s: WordPress link */
			__( 'Powered by %s', 'snow-monkey' ),
			$wordpress_link
		);

		$copyright = $theme_by . ' ' . $powered_by;

		return apply_filters( 'snow_monkey_copyright', $copyright );
	}

	/**
	 * Return public taxonomy tied to the post.
	 *
	 * @param int|WP_Post $post The Post object.
	 * @return array
	 */
	public static function get_the_public_taxonomy( $post = 0 ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return [];
		}

		$cache_key         = $post->ID;
		$cache_group       = 'snow-monkey/public-taxonomies';
		$public_taxonomies = wp_cache_get( $cache_key, $cache_group );
		if ( is_array( $public_taxonomies ) ) {
			return $public_taxonomies;
		}

		$taxonomies        = get_object_taxonomies( get_post_type( $post ), 'object' );
		$public_taxonomies = [];

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! $taxonomy->public ) {
				continue;
			}

			$public_taxonomies[ $taxonomy->name ] = $taxonomy;
		}

		wp_cache_set( $cache_key, $public_taxonomies, $cache_group );
		return $public_taxonomies;
	}

	/**
	 * Return public terms tied to the post.
	 *
	 * @param int|WP_Post $post The Post object.
	 * @return array
	 */
	public static function get_the_public_terms( $post = 0 ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return [];
		}

		$cache_key    = $post->ID;
		$cache_group  = 'snow-monkey/the-public-terms';
		$public_terms = wp_cache_get( $cache_key, $cache_group );
		if ( is_array( $public_terms ) ) {
			return $public_terms;
		}

		$public_taxonomies = static::get_the_public_taxonomy( $post );
		$public_terms      = [];

		foreach ( $public_taxonomies as $public_taxonomy ) {
			$_terms = get_the_terms( $post, $public_taxonomy->name );
			if ( ! empty( $_terms ) && is_array( $_terms ) && ! is_wp_error( $_terms ) ) {
				$public_terms = array_merge( $public_terms, $_terms );
			}
		}

		wp_cache_set( $cache_key, $public_terms, $cache_group );
		return $public_terms;
	}

	/**
	 * Return specific terms.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array
	 */
	public static function get_terms( $taxonomy ) {
		$cache_key = 'snow-monkey-all-' . $taxonomy;
		$terms     = wp_cache_get( $cache_key );
		if ( is_array( $terms ) ) {
			return $terms;
		}

		$terms = get_terms( [ $taxonomy ] );
		wp_cache_set( $cache_key, $terms );
		if ( is_array( $terms ) ) {
			return $terms;
		}

		return [];
	}

	/**
	 * Return all users.
	 *
	 * @return array
	 */
	public static function get_users() {
		$users = wp_cache_get( 'snow-monkey-all-users' );
		if ( is_array( $users ) ) {
			return $users;
		}

		$users = get_users();
		wp_cache_set( 'snow-monkey-all-users', $users );
		if ( is_array( $users ) ) {
			return $users;
		}

		return [];
	}

	/**
	 * Return editor color palette settings
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 *
	 * @return array
	 */
	public static function get_color_palette() {
		return apply_filters(
			'snow_monkey_editor_color_palette',
			[
				[
					'name'  => __( 'White', 'snow-monkey' ),
					'slug'  => 'white',
					'color' => '#fff',
				],
				[
					'name'  => __( 'Black', 'snow-monkey' ),
					'slug'  => 'black',
					'color' => '#000',
				],
				[
					'name'  => __( 'Text color', 'snow-monkey' ),
					'slug'  => 'text-color',
					'color' => '#333',
				],
				[
					'name'  => __( 'Dark gray', 'snow-monkey' ),
					'slug'  => 'dark-gray',
					'color' => '#999',
				],
				[
					'name'  => __( 'Gray', 'snow-monkey' ),
					'slug'  => 'gray',
					'color' => '#ccc',
				],
				[
					'name'  => __( 'Light gray', 'snow-monkey' ),
					'slug'  => 'very-light-gray',
					'color' => '#eee',
				],
				[
					'name'  => __( 'Lightest gray', 'snow-monkey' ),
					'slug'  => 'lightest-grey',
					'color' => '#f7f7f7',
				],
				[
					'name'  => __( 'Accent color', 'snow-monkey' ),
					'slug'  => 'accent-color',
					'color' => get_theme_mod( 'accent-color', '#cd162c' ), // On after_setup_theme, default value not set yet.
				],
				[
					'name'  => __( 'Sub accent color', 'snow-monkey' ),
					'slug'  => 'sub-accent-color',
					'color' => get_theme_mod( 'sub-accent-color', '#707593' ), // On after_setup_theme, default value not set yet.
				],
				[
					'name'     => __( 'Pale pink', 'snow-monkey' ),
					'slug'     => 'pale-pink',
					'color'    => '#f78da7',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Vivid red', 'snow-monkey' ),
					'slug'     => 'vivid-red',
					'color'    => '#cf2e2e',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Luminous vivid orange', 'snow-monkey' ),
					'slug'     => 'luminous-vivid-orange',
					'color'    => '#ff6900',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Luminous vivid amber', 'snow-monkey' ),
					'slug'     => 'luminous-vivid-amber',
					'color'    => '#fcb900',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Light green cyan', 'snow-monkey' ),
					'slug'     => 'light-green-cyan',
					'color'    => '#7bdcb5',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Vivid green cyan', 'snow-monkey' ),
					'slug'     => 'vivid-green-cyan',
					'color'    => '#00d084',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Pale cyan blue', 'snow-monkey' ),
					'slug'     => 'pale-cyan-blue',
					'color'    => '#8ed1fc',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Vivid cyan blue', 'snow-monkey' ),
					'slug'     => 'vivid-cyan-blue',
					'color'    => '#0693e3',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Cyan bluish gray', 'snow-monkey' ),
					'slug'     => 'cyan-bluish-gray',
					'color'    => '#abb8c3',
					'_builtin' => true,
				],
				[
					'name'     => __( 'Very dark gray', 'snow-monkey' ),
					'slug'     => 'very-dark-gray',
					'color'    => '#313131',
					'_builtin' => true,
				],
			]
		);
	}

	/**
	 * Retrun header classes.
	 *
	 * @return array
	 */
	public static function get_header_classes() {
		$header_layout      = get_theme_mod( 'header-layout' );
		$header_position    = get_theme_mod( 'header-position' );
		$header_position_lg = get_theme_mod( 'header-position-lg' );
		$classes            = [ 'l-header', 'l-header--' . $header_layout ];

		if ( $header_position ) {
			$classes[] = 'l-header--' . $header_position . '-sm';
		}

		if ( $header_position_lg ) {
			$classes[] = 'l-header--' . $header_position_lg . '-lg';
		}

		return $classes;
	}

	/**
	 * Return true if has the drop-nav.
	 *
	 * @return boolean
	 */
	public static function has_drop_nav() {
		$return = false;

		if ( has_nav_menu( 'global-nav' ) ) {
			$has_drop_nav       = in_array( get_theme_mod( 'header-position' ), [ '', 'overlay' ], true );
			$has_drop_nav_on_pc = in_array( get_theme_mod( 'header-position-lg' ), [ '', 'overlay' ], true );

			if ( $has_drop_nav || $has_drop_nav_on_pc ) {
				$return = true;
			}
		}

		return apply_filters( 'snow_monkey_has_drop_nav', $return );
	}

	/**
	 * Return hooked value of snow_monkey_use_auto_custom_logo_size.
	 * If return true, the logo size setting in customizer is applyed.
	 *
	 * @return boolean
	 */
	public static function use_auto_custom_logo_size() {
		return apply_filters( 'snow_monkey_use_auto_custom_logo_size', true );
	}

	/**
	 * get_template_part php files
	 *
	 * @param string  $directory Target directory.
	 * @param boolean $exclude_underscore Exclude if true.
	 * @return void
	 */
	public static function get_template_parts( $directory, $exclude_underscore = false ) {
		$directory = realpath( $directory );
		if ( ! is_dir( $directory ) ) {
			return;
		}

		$template_directory = realpath( get_template_directory() );

		$iterator = new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS );
		$iterator = new RecursiveIteratorIterator( $iterator );

		$files = [];

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}

			if ( 'php' !== $file->getExtension() ) {
				continue;
			}

			if ( $exclude_underscore && 0 === strpos( $file->getBasename(), '_' ) ) {
				continue;
			}

			$files[] = realpath( $file->getPathname() );
		}

		if ( ! $files ) {
			return;
		}

		usort(
			$files,
			function( $a, $b ) {
				$adeps = substr_count( $a, DIRECTORY_SEPARATOR );
				$bdeps = substr_count( $b, DIRECTORY_SEPARATOR );

				if ( $adeps === $bdeps ) {
					return 0;
				}

				return $adeps > $bdeps ? 1 : -1;
			}
		);

		foreach ( $files as $filepath ) {
			$template_name = str_replace(
				[ $template_directory . DIRECTORY_SEPARATOR, '.php' ],
				'',
				$filepath
			);

			WP_View_Controller\Helper::get_template_part( $template_name );
		}
	}

	/**
	 * Return true when IE.
	 *
	 * @return boolean
	 */
	public static function is_ie() {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$browser = strtolower( $_SERVER['HTTP_USER_AGENT'] );
		return strstr( $browser, 'trident' ) || strstr( $browser, 'msie' );
	}
}
