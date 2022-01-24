<?php
/**
 * @package           Jeo_MPS
 *
 * @wordpress-plugin
 * Plugin Name:       JEO Media Partner Sync
 * Description:       Sync posts from anothers sites using WordPress REST API
 * Version:           0.1
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       jeo-mps
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'JEO_MEDIA_PARTNERS_VERSION', '0.1' );

define( 'JEO_MEDIA_PARTNERS_BASEPATH', plugin_dir_path( __FILE__ ) );
define( 'JEO_MEDIA_PARTNERS_BASEURL', plugins_url('', __FILE__) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require JEO_MEDIA_PARTNERS_BASEPATH . 'includes/loaders.php';

jeo_mps();
