<?php
namespace Jeo_MPS;

require 'class-import-posts.php';

class Partners_Sites {

	use Singleton;

	public $post_type = '_partners_sites';

	protected function init() {
		add_action( 'init', [$this, 'register_post_type'], 30 );
		add_action('admin_init', [ $this, 'add_capabilities' ]);
		add_action( 'admin_init', [ $this, 'load_assets' ] );
		add_action( 'cmb2_init', [ $this, 'add_cmb2_fields'] );
		add_action( 'admin_head', [ $this, 'remove_metaboxes'], 9999 );

		// post published and updated terms update
		add_filter( 'gettext', [ $this, 'update_gettext'], 10, 3 );

		//remove autosave
		add_filter( 'posts_search', [ $this, 'remove_autosave' ], 10, 2 );

	}

	public function remove_metaboxes() {
		global $wp_meta_boxes;

		if( ! is_array( $wp_meta_boxes ) ) {
			return;
		}
		
		if( isset( $wp_meta_boxes[$this->post_type] ) && is_array( $wp_meta_boxes[$this->post_type] ) ) {
			foreach( $wp_meta_boxes[$this->post_type] as $position => $content ) {
				if( $wp_meta_boxes[$this->post_type][ $position ] && is_array( $wp_meta_boxes[$this->post_type][ $position ] ) && ! empty( $wp_meta_boxes[$this->post_type][ $position ] ) ) {
					foreach( $wp_meta_boxes[$this->post_type][ $position ] as $priority => $content ) {
						if ( ! $wp_meta_boxes[$this->post_type][ $position ][ $priority ] ) {
							continue;
						}
						if ( ! is_array( $wp_meta_boxes[$this->post_type][ $position ][ $priority ] ) ) {
							continue;
						}
						if ( empty( $wp_meta_boxes[$this->post_type][ $position ][ $priority ] ) ) {
							continue;
						}
						foreach( $wp_meta_boxes[$this->post_type][ $position ][ $priority ] as $metabox => $content ) {
							if ( strpos( $metabox, $this->post_type ) !== false ) {
								continue;
							}
							if ( 'submitdiv' == $metabox ) {
								continue;
							}
							unset( $wp_meta_boxes[$this->post_type][ $position ][ $priority ][ $metabox ] );

						}
					}
				}
			}
		}
	}
	public function register_post_type() {

		$labels = array(
			'name' => __('Partners Sites', 'jeo-mps'),
			'singular_name' => __('Partner Site', 'jeo-mps'),
			'add_new' => __('Add new site', 'jeo-mps'),
			'add_new_item' => __('Add new site', 'jeo-mps'),
			'edit_item' => __('Edit site', 'jeo-mps'),
			'new_item' => __('New site', 'jeo-mps'),
			'view_item' => __('View site', 'jeo-mps'),
			'view_items' => __( 'View sites', 'jeo-mps' ),
			'search_items' => __('Search sites', 'jeo-mps'),
			'not_found' => __('No site found', 'jeo-mps'),
			'not_found_in_trash' => __('No site found in the trash', 'jeo-mps'),
			'menu_name' => __('Partners Sites', 'jeo-mps'),
			'item_published' => __('Site added.', 'jeo-mps'),
			'item_updated'	=> __( 'Site updated', 'jeo-mps' )
		);
		// check if jeo plugin is installed
		if ( function_exists( 'jeo' ) ) {
			$args = array(
				'labels' => $labels,
				'hierarchical' => true,
				'description' => __('JEO Partners Sites Sync', 'jeo-mps'),
				'supports' => array( 'title'),
				'rewrite' => false,
				'public' => true,
				'show_in_menu' => 'jeo-main-menu',
				'show_in_rest' => false,
				'menu_position' => 999,
				'has_archive' => false,
				'exclude_from_search' => true,
			);	
		} else {
			$labels[ 'menu_name' ] = __( 'JEO Partners Sites Sync', 'jeo-mps' );
			$args = array(
				'labels' => $labels,
				'hierarchical' => true,
				'description' => __('JEO Partners Sites Sync', 'jeo-mps'),
				'supports' => array( 'title'),
				'rewrite' => false,
				'public' => true,
				'show_in_menu' => true,
				'menu_icon'	=> 'dashicons-update-alt',
				'show_in_rest' => false,
				'menu_position' => 999,
				'has_archive' => false,
				'exclude_from_search' => true,
				'show_in_nav_menus' 	=> false,
				'show_in_admin_bar' 	=> false,
				'publicly_queryable'	=> false, 
			);
		}

		register_post_type($this->post_type, $args);

	}
	public function load_assets() {
		if ( ! $this->is_edit_screen_from_post_type( $this->post_type ) ) {
			return;
		}
		//do_action( 'enqueue_block_editor_assets' );
		$styles = [ 'wp-block-editor'];
		foreach( $styles as $style ) {
			wp_enqueue_style( $style );
		}
		$asset_file = include JEO_MEDIA_PARTNERS_BASEPATH . 'js/build/partnersPosts.asset.php';

		wp_enqueue_script(
			'jeo-partners-posts',
			JEO_MEDIA_PARTNERS_BASEURL . '/js/build/partnersPosts.js',
			array_merge($asset_file['dependencies']),
			$asset_file['version']
		);

		wp_set_script_translations( 'jeo-partners-posts', 'jeo-mps', plugin_dir_path(  dirname( __FILE__ , 2 ) ) . 'languages' );

	}
	public function add_cmb2_fields() {
		$prefix = $this->post_type;
		$post_id = false;
		if ( isset( $_GET[ 'post'] ) && ! empty( $_GET[ 'post'] ) ) {
			$post_id = $_GET[ 'post'];
		}

		$site_info_box = \new_cmb2_box( array(
			'id'           => $prefix . '_site_info',
			'title'        => __( 'Site information', 'jeo-mps' ),
			'object_types' => array( $this->post_type ),
			'context'      => 'advanced',
			'priority'     => 'high',
		) );
	
		$site_info_box->add_field( array(
			'name' => __( 'Site URL', 'jeo-mps' ),
			'id' => $prefix . '_site_url',
			'type' => 'text',
		) );
		if ( function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE') ) {
			$options = [
				'none' => __( 'None - Default language from partner site', 'jeo-mps')
			];
			foreach( $this->get_all_langs_names( ICL_LANGUAGE_CODE ) as $lang ) {
				$options[ $lang['code'] ] = $lang['name'];
			}
			$site_info_box->add_field( array(
				'name' 				=> __( 'Get posts by language (WPML)', 'jeo-mps' ),
				'id' 				=> $prefix . '_remote_lang',
				'type' 				=> 'select',
				'show_option_none' 	=> false,
				'options'			=> $options,
			) );
	
	   	}
		$site_info_box->add_field( array(
			'name' 				=> __( 'Get posts from a specific category', 'jeo-mps' ),
			'id' 				=> $prefix . '_remote_category',
			'type' 				=> 'select',
			'show_option_none' 	=> true,
			'options'			=> [],
		) );
        $site_info_box->add_field( array(
            'name' => __( 'Import posts published from date', 'jeo-mps' ),
            'id'   => $prefix . '_date',
            'type' => 'text_date_timestamp',
            'date_format' => 'Y-m-d',
			'default' => time()
        ) );
		$current_remote_category = '';
		if ( $post_id ) {
			$current_remote_category = wp_get_post_categories( $post_id, [ 'fields' => 'ids' ] ); 
		}
		$site_info_box->add_field( array(
			'id'   		=> $prefix . '_remote_category_value',
			'type' 		=> 'hidden',
			'default' 	=> $current_remote_category,
		) );
		


		$site_info_box->add_field( array(
			'id'   		=> 'run_import_now',
			'type' 		=> 'hidden',
			'default' 	=> 'false',
		) );

		$site_info_box->add_field( array(
			'name' 				=> __( 'Time interval for search new posts', 'jeo-mps' ),
			'id' 				=> $prefix . '_interval',
			'type' 				=> 'select',
			'show_option_none' 	=> false,
			'default'			=> 'hourly',
			'options'			=> [
				'disabled'			=> __( 'Disabled', 'jeo-mps' ),
				'30min' 			=> __( 'Every 30 minutes', 'jeo-mps' ),
				'hourly' 			=> __( 'Every hour', 'jeo-mps' ),
				'twicedaily'		=> __( 'Twice a day', 'jeo-mps' ),
				'daily'				=> __( 'Every day', 'jeo-mps' ),
				'weekly'			=> __( 'Every Week', 'jeo-mps' )
			],
		) );

		$post_config_box = \new_cmb2_box( array(
			'id'           => $prefix . '_post_config',
			'title'        => __( 'Post configuration', 'jeo-mps' ),
			'object_types' => array( $this->post_type ),
			'context'      => 'advanced',
			'priority'     => 'high',
		) );
		
		if ( function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE') ) {
			global $sitepress;
		
			// remove WPML term filters
			remove_filter('get_terms_args', array($sitepress, 'get_terms_args_filter'));
			remove_filter('get_term', array($sitepress,'get_term_adjust_id'));
			remove_filter('terms_clauses', array($sitepress,'terms_clauses'));
		}

 		$post_config_box->add_field( array(
			'name' 				=> __( 'Post category on your site', 'jeo-mps' ),
			'id' 				=> $prefix . '_local_category',
			'taxonomy'			=> 'category',
			'type'				=> 'taxonomy_select',
		) );
		if ( taxonomy_exists( 'partner' ) ) {
			$post_config_box->add_field( array(
				'name' 				=> __( 'Newspack Media Partner', 'jeo-mps' ),
				'id' 				=> $prefix . '_newspack_partner',
				'taxonomy'			=> 'partner',
				'type'				=> 'taxonomy_select',
			) );	
		}
	}
	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function add_capabilities() {
		$roles = ['editor', 'administrator'];
		foreach ($roles as $role) {

			// var_dump($role);
			
			$role_obj = get_role($role);

			$role_obj->add_cap( 'edit_partner_site' );
			$role_obj->add_cap( 'edit_partners_sites' );
			$role_obj->add_cap( 'edit_others_partner_sites' );
			$role_obj->add_cap( 'publish_partners_sites' );
			$role_obj->add_cap( 'read_partners_site' );
			$role_obj->add_cap( 'read_private_partners_sites' );
			$role_obj->add_cap( 'delete_partners_site' );
			$role_obj->add_cap( 'delete__partners_site' );
			$role_obj->add_cap( 'edit_published_blocks' );
			$role_obj->add_cap( 'delete__partners_sites' );
			$role_obj->add_cap( 'delete__partners_sitess' );

		}
	}
	public function remove_bulk_actions( $actions, $post ){
		if( $post->post_type != $this->post_type ) {
			return $actions;
		}
		unset($actions['edit']);
		unset($actions['trash']);
		unset($actions['view']);
		unset($actions['inline hide-if-no-js']);   
        unset( $actions['inline'] );
        return $actions;
    }

	public function update_gettext( $translated_text, $untranslated_text, $domain ) {
		if ( $untranslated_text == 'Post updated.' ) {
			return __( 'Site updated and synchronization process is running in the background.', 'jeo-mps' );
		}
		if ( $untranslated_text == 'Post published.' ) {
			return __( 'Site updated and synchronization process is running in the background', 'jeo-mps' );
		}
		return $translated_text;
	}
	/**
	 * Remove autosave posts from dashboard list
	 *
	 * @param string $search
	 * @param object $wp_query
	 * @return string
	 */
	public function remove_autosave( $search, $wp_query ) {
		if( ! is_admin() ) {
			return;
		}
		if ( ! isset( $_GET[ 'post_type'] ) ) {
			return;
		}
		if ( $this->post_type != $_GET[ 'post_type'] ) {
			return;
		}
		global $wpdb;
		$term = __( 'Auto Draft' );
		$search .= "AND $wpdb->posts.post_title != '$term'";
		return $search;
	}
}
