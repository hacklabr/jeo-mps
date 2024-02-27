<?php

namespace Jeo_MPS;

trait Singleton {

	protected static $instance;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		$this->init();
	}

	private function __clone() {

	}

	public function __wakeup() {

	}

	/**
	* Check if 'edit' or 'new-post' screen of a
 	* given post type is opened
	*
 	* @param null $post_type name of post type to compare
 	*
 	* @return bool true or false
 	*/
	public function is_edit_screen_from_post_type( $post_type = null ) {
    	global $pagenow;

    	/**
     	* return false if not on admin page or
     	* post type to compare is null
     	*/
    	if ( ! is_admin() || $post_type === null ) {
    		return false;
    	}

    	/**
     	* if edit screen of a post type is active
     	*/
    	if ( $pagenow === 'post.php' ) {
        	// get post id, in case of view all cpt post id will be -1
        	$post_id = isset( $_GET[ 'post' ] ) ? $_GET[ 'post' ] : - 1;

        	// if no post id then return false
        	if ( $post_id === - 1 ) {
            	return false;
        	}

        	// get post type from post id
        	$get_post_type = get_post_type( $post_id );

        	// if post type is compared return true else false
        	if ( $post_type === $get_post_type ) {
            	return true;
        	} else {
            	return false;
        	}
    	} elseif ( $pagenow === 'post-new.php' ) { // is new-post screen of a post type is active
        	// get post type from $_GET array
        	$get_post_type = isset( $_GET[ 'post_type' ] ) ? $_GET[ 'post_type' ] : '';
        	// if post type matches return true else false.
        	if ( $post_type === $get_post_type ) {
            	return true;
        	} else {
            	return false;
        	}
    	} else {
        	// return false if on any other page.
        	return false;
    	}
	}

	public function get_all_langs_names( $lang = 'en' ){
		global $wpdb;
		$lang_data = array();
		$languages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT code, english_name, active, tag, name
				FROM {$wpdb->prefix}icl_languages lang
				INNER JOIN {$wpdb->prefix}icl_languages_translations trans
				ON lang.code = trans.language_code
				AND trans.display_language_code=%s"
				,
				$lang
				)
		);
		foreach($languages as $l){
			if ( '1' != $l->active ) {
				continue;
			}
			$lang_data[$l->code] = array(
				'english_name' => $l->english_name,
				'active' => $l->active,
				'tag' => $l->tag,
				'name' => $l->name,
				'code'	=> $l->code
			);
		}
		return $lang_data;
	}
	final public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected abstract function init();

}
