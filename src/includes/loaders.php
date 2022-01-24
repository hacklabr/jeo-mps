<?php

/**
 *  Composer Autoload
 */
if ( is_readable( JEO_MEDIA_PARTNERS_BASEPATH . '/vendor/autoload.php' ) ) {
    require JEO_MEDIA_PARTNERS_BASEPATH . '/vendor/autoload.php';
}

spl_autoload_register('jeo_mps_autoload');

function jeo_mps_autoload($class_name) {

	$class_path = explode('\\', $class_name);

	$subfolder = '';
	if ( sizeof($class_path) > 2 ) {
		$subfolder = strtolower( $class_path[ sizeof($class_path) -2 ] ) . DIRECTORY_SEPARATOR;
	}

	$class_name = end($class_path);

	$filename = 'class-'. strtolower(str_replace('_', '-' , $class_name)) . '.php';

	$folders = ['.', 'traits', 'partners-posts'];

	foreach ($folders as $folder) {
		$check = __DIR__ . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $subfolder . $filename;
		if ( \file_exists($check) ) {
			require_once($check);
			break;
		}
	}

}

/**
 * Gets the instance of the Storymap
 * @return \Storymap Storymap instance
 */
function jeo_partners_sites() {
	return \Jeo_MPS\Partners_Sites::get_instance();
}
function jeo_mps() {
	return \Jeo_MPS::get_instance();
}