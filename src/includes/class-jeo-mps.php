<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       leo.com
 * @since      1.0.0
 *
 * @package    Jeo
 * @subpackage Jeo/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.1
 * @package    Jeo Media Partners Sync
 * @subpackage Jeo Media Partners Sync/includes
 * @author     Leo <leo@Leo.leo>
 */
class Jeo_MPS {

	use Jeo_MPS\Singleton;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	protected function init() {

		\jeo_partners_sites();

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'jeo-mps',
			false,
			// this fixes the plugin renaming problem
			basename( plugin_dir_path(  dirname( __FILE__ , 1 ) ) ) . '/languages',
		);

		add_action( 'admin_init', [ $this, 'register_assets'], 10 );

	}


	public function register_assets() {
		$asset_file = include JEO_MEDIA_PARTNERS_BASEPATH . '/js/build/postsSidebar.asset.php';

		$deps = array_merge( array( 'lodash' ), $asset_file['dependencies'] );

		wp_register_script(
			'jeo-js',
			jeo_media_partners_BASEURL . '/js/build/postsSidebar.js',
			$deps,
			$asset_file['version']
		);

		wp_set_script_translations('jeo-js', 'jeo-mps', plugin_dir_path( __DIR__ ) . 'languages');


		wp_localize_script(
			'jeo-js',
			'jeo-mps',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

	}

	public function enqueue_scripts() {
	}

}
