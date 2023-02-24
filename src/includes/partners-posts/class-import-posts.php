<?php
namespace Jeo_MPS;

class Importer {

    use Singleton;

    public $post_type = '_partners_sites';
    public $event = 'jeo_media_partners_import_posts';
    public $lang = false;

    protected function init() {
        add_filter( 'cron_schedules', [ $this, 'cron_schedules' ] );

        add_action( "save_post_{$this->post_type}", [ $this, 'schedule_cron' ], 9999, 3 );

        add_action( $this->event, [ $this, 'run_cron'], 10, 2 );
    }

    /**
     * Add custom schedules interval
     *
     * @param array $schedules
     * @return void
     */
    public function cron_schedules($schedules){
        if(! isset($schedules[ '30min' ])){
            $schedules[ '30min' ] = [
                'interval' => 30*60,
                'display' => 'Once every 30 minutes'
            ];
        }
        return $schedules;
    }

    /**
     * Schedule cron for every single site (post) on save action
     *
     * @param int $id
     * @param WP_POST|OBJECT $site
     * @param bool $update
     * @return void
     */
    public function schedule_cron( $id, $site, $update ) {
        if ( ! isset( $_POST[ 'run_import_now'] ) ) {
            return;
        }
        if ( 'auto_save' === $_POST[ 'run_import_now'] ) {
            return;
        }
        $args = [
            'id' => $id
        ];
        // get time interval
        $interval = ( isset( $_POST[ $this->post_type . '_interval' ] ) && ! empty( $_POST[ $this->post_type . '_interval' ] ) ) ? $_POST[ $this->post_type . '_interval' ] : 'hourly';
        if ( ! wp_next_scheduled( $this->event, $args ) ) {
            if ( 'disabled' != $interval ) {
                wp_schedule_event( time(), $interval, $this->event, $args );
            }
        } else {
            if ( 'disabled' != $interval ) {
                $time = wp_next_scheduled( $this->event, $args );
                wp_unschedule_event( $time, $this->event, $args );

                wp_schedule_event( time(), $interval, $this->event, $args );
            }
        }
        // Run first import after save first time
        // Run now if button Save and Run Now is clicked
        if ( ! $update || ( isset( $_POST[ 'run_import_now'] ) && 'true' == $_POST[ 'run_import_now'] )  ) {

            //$modified_date = get_post_meta( $args[ 'id'], '_jeo_mps_last_update', true );
            $modified_date = 0;
            if ( ! $modified_date ) {
                $modified_date = 0;
            }
            //echo $modified_date;
            //die();
            $modified_date = \DateTime::createFromFormat( 'U', $modified_date );
            $now = new \DateTime();
            $diff = $now->diff( $modified_date );
            $minutes = $diff->days * 24 * 60;
            $minutes += $diff->h * 60;
            $minutes += $diff->i;
            $modified_date = get_the_modified_date( 'Y-m-d H:i:s', $args[ 'id'] );
            $modified_date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $modified_date );


            if ( round( absint( $minutes ) ) <= 5 ) {
                wp_clear_scheduled_hook( $this->event, $args, false );
                if ( 'disabled' != $interval ) {
                    wp_schedule_event( time(), $interval, $this->event, $args );
                } else {
                    wp_schedule_single_event( time(), $this->event, $args );
                }

                //do_action( $this->event, $args[ 'id'] );

                remove_all_actions( "save_post_{$this->post_type}" );
                wp_update_post(
                    [
                        'ID'            => $args[ 'id' ],
                        'post_status'   => 'publish'
                    ],
                    true,
                    false
                );

                return;
            }
            do_action( $this->event, $args[ 'id'] );
            remove_all_actions( "save_post_{$this->post_type}" );

            wp_update_post(
                [
                    'ID'            => $args[ 'id' ],
                    'post_status'   => 'publish'
                ],
                true,
                false
            );
        }

    }

    /**
     * Set post thumbnail from image URL
     *
     * @param int $post_id
     * @param string $url
     * @return void
     */
    protected function upload_thumbnail( $post_id, $url ) {
        include_once( ABSPATH . 'wp-admin/includes/admin.php' );

        //echo ' imagem ' . $post_id . ' url ' . $url . ' ';

        $file = [];
        $file['name'] = $url;
        $file['tmp_name'] = download_url( $url );

        $image_id = media_handle_sideload( $file, $post_id );

        set_post_thumbnail( $post_id, $image_id );
    }

    /**
     * Run cron / Perfome HTTP GET to Site api and save posts
     *
     * @param array $args
     * @return void
     */
    public function run_cron( $id, $page = '1' ) {
        global $wpdb;
        //var_dump( $id );

        $request_params = [ 'per_page' => 5, 'page' => $page, '_embed' => true ];
        $data = get_post_meta( $id );
        if ( false === $data ) {

            return;
        }

        if ( ! isset( $data[ "{$this->post_type}_site_url" ] ) || ! filter_var( $data[ "{$this->post_type}_site_url" ][0], FILTER_VALIDATE_URL ) ) {
            return;
        }
        $date_timestamp = get_post_timestamp( $id, 'date' );
        //echo '<br>UNIX ANTES: ' . $date_timestamp;
        if( isset( $data[ "{$this->post_type}_date" ] ) ) {
            $date_timestamp = $data[ "{$this->post_type}_date" ][0];
        }
        $iso_date = date('c', $date_timestamp );
        $request_params[ 'after'] = $iso_date;
        //echo '<br>UNIX DPS: ' . $date_timestamp;

        //echo '<br>ISODATE<pre>';
        //print_r( $iso_date );
        //echo '</pre>';
        if ( isset( $_ṔOST[ "{$this->post_type}_remote_category_value" ] ) ) {
            $request_params[ 'categories' ] = [ $_ṔOST[ "{$this->post_type}_remote_category_value" ] ];
        } else {
            if( isset( $data[ "{$this->post_type}_remote_category_value" ] ) ) {
                if( $data[ "{$this->post_type}_remote_category_value" ][0] && is_numeric( $data[ "{$this->post_type}_remote_category_value" ][0] ) ) {
                    $request_params[ 'categories' ] = [ $data[ "{$this->post_type}_remote_category_value" ][0] ];
                }
            }
        }
        $URL = $data[ "{$this->post_type}_site_url" ][0];
        if ( '/' === substr( $URL, -1) ) {
            $URL = substr( $URL, 0, -1);
        }
        if ( function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE') ) {
            if ( isset( $data[ "{$this->post_type}_remote_lang" ] ) && 'none' != $data[ "{$this->post_type}_remote_lang" ][0] ) {
                $request_params[ 'lang' ] = $data[ "{$this->post_type}_remote_lang" ][0];
                $this->lang = $data[ "{$this->post_type}_remote_lang" ][0];
            }
            if( ! $this->lang ) {
                $this->lang = ICL_LANGUAGE_CODE;
            }
        }
        $base_url = $URL;
        $URL = $URL . '/wp-json/wp/v2/posts/?' . http_build_query( $request_params );

        $response = wp_remote_get( $URL, [] );
        if ( ! is_wp_error( $response ) && is_array( $response ) ) {
            $max_pages = absint( $response[ 'headers' ][ 'x-wp-totalpages' ] );
            //echo "n/r";
            //var_dump( $max_pages );
            //var_dump( $response[ 'body'] );

            $this->insert_posts( $response[ 'body'], $id, $base_url );
            if( '1' == $page && $max_pages > 1 ) {
                for ($i = 2; $i <= $max_pages; $i++) {
                    // schedule an event to import every page
                    $args = [ $id, $i ];
                    if ( ! wp_next_scheduled( $this->event, $args ) ) {
                        wp_schedule_single_event( time(), $this->event, $args );
                    }

                }
            }

        }
        if ( '1' == $page ) {
            update_post_meta( $id, '_jeo_mps_last_update', time() );
        }
    }

    /**
     *
     *
     * @param array $post
     * @return void
     */
    protected function get_thumbnail_from_yoast( $post ) {
        if( ! isset( $post[ 'yoast_head_json' ] ) ) {
            return false;
        }
        if( ! isset( $post[ 'yoast_head_json' ][ 'og_image'] ) ) {
            return false;
        }
        if( ! isset( $post[ 'yoast_head_json' ][ 'og_image'][0] ) ) {
            return false;
        }
        if( ! isset( $post[ 'yoast_head_json' ][ 'og_image'][0][ 'url' ] ) ) {
            return false;
        }
        return $post[ 'yoast_head_json' ][ 'og_image'][0][ 'url' ];
    }

    private function upload_avatar( $url ) {
        $filename = basename( $url );
        $file = wp_upload_bits( $filename, null, @file_get_contents( $url ) );

        if( !$file[ 'error' ] ) {
            $filetype = wp_check_filetype( $filename, null );

            $attachment = [
                'post_mime_type' => $filetype[ 'type' ],
                'post_status' => 'publish',
            ];

            $attachment_id = wp_insert_attachment( $attachment, $file[ 'file' ] );

            if( !is_wp_error( $attachment_id ) ) {
                $attachment_meta = wp_generate_attachment_metadata( $attachment_id, $file[ 'file' ] );
                wp_update_attachment_metadata( $attachment_id, $attachment_meta );
            }

            return $attachment_id;
        }
    }

    private function set_post_author( $post_id, $author ) {
        $user = get_user_by( 'slug', $author[ 'slug' ] );

        if ( !empty( $user ) ) {
            wp_update_post( [ 'ID' => $post_id, 'post_author' => $user->ID ] );
        } else {
            global $coauthors_plus;
            $coauthor = $coauthors_plus->get_coauthor_by( 'user_nicename', $author[ 'slug' ], true );

            if( !empty( $coauthor ) ) {
                $coauthors_plus->add_coauthors( $post_id, [ $coauthor->user_nicename ], false, 'user_nicename' );
            } elseif( $coauthors_plus->is_guest_authors_enabled() ) {
                $coauthor_id = $coauthors_plus->guest_authors->create( [
					/* `display_name` and `user_login` are required */
                    'display_name' => $author[ 'name' ],
                    'user_login' => $author[ 'slug' ],
                    'description' => $author[ 'description' ],
                    'avatar' => $this->upload_avatar( $author[ 'avatar_urls' ][ '96' ] ),
                ] );
                $coauthor = $coauthors_plus->get_coauthor_by( 'id', $coauthor_id );
                $coauthors_plus->add_coauthors( $post_id, [ $coauthor->user_nicename ], false, 'user_nicename' );
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $posts
     * @param [type] $id
     * @return void
     */
    protected function insert_posts( $posts, $id, $base_url ) {
        global $wpdb;

        $posts = json_decode( $posts, true );
        $category = get_post_meta( $id, $this->post_type. '_category', true );

        if( taxonomy_exists( 'partner' ) ) {
            if( isset( $_POST[ '_partners_sites_newspack_partner'] ) ) {
                $partner_term = get_term_by( 'slug', $_POST[ '_partners_sites_newspack_partner'], 'partner', OBJECT, 'raw' );
                $partner_terms = [ $partner_term ];
            } else {
                $partner_terms = wp_get_object_terms( $id, 'partner' );
            }
            if( is_wp_error( $partner_terms ) || empty( $partner_terms ) || ! $partner_terms ) {
                $partner_terms = false;
            }
        }

        foreach( $posts as $post ) {
            $partner_post_id = absint( $post[ 'id' ] );
            $link = esc_textarea( $post[ 'link' ] );

            $post_exists = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'partner-link' AND meta_value = '{$link}'");
            if ( $post_exists ) {
                continue;
            }

            $metadata = [
                'partner-link'          => esc_textarea( $post[ 'link'] ),
                'external-source-link'  => esc_textarea( $post[ 'link' ] ),
                'partner_post_id'       => $partner_post_id,
                'importer_site_id'      => $id
            ];

            // check if have jeo installed on target site

            if ( isset( $post['meta'][ '_related_point' ] ) && isset( $post['meta'][ '_related_point' ][0] ) ) {
                $metadata[ '_related_point' ] = $post['meta'][ '_related_point' ][0];
                $metadata[ '_related_point' ][ 'relevance'] = 'primary';
            }

            $post_args = [
                'post_title'        => $post[ 'title' ]['rendered'],
                'post_excerpt'      => wp_strip_all_tags( $post[ 'excerpt' ]['rendered'] ),
                'post_date'         => $post[ 'date' ],
                'post_name'         => $post[ 'slug' ],
                'meta_input'        => $metadata,
                'post_status'       => 'publish',
                'post_type'         => 'post',
            ];
            $post_inserted = wp_insert_post( $post_args, true, true );

            if ( $post_inserted && ! is_wp_error( $post_inserted ) ) {
                if ( isset( $post['_embedded'] ) && isset( $post['_embedded']['wp:featuredmedia'] ) ) {
                    if ( isset( $post['_embedded']['wp:featuredmedia'][0]['source_url'] ) ) {

                        $this->upload_thumbnail( $post_inserted, $post['_embedded']['wp:featuredmedia'][0]['source_url'] );

                    } else {
                        $yoast_image = $this->get_thumbnail_from_yoast( $post );
                        if ( $yoast_image ) {
                            $this->upload_thumbnail( $post_inserted, $yoast_image );
                        }
                    }

                }
                if ( isset( $post['_embedded'] ) && isset( $post['_embedded']['author'] ) ) {
                    $this->set_post_author( $post_inserted, $post['_embedded']['author'] );
                }
                if( taxonomy_exists( 'partner' ) ) {
                    if( $partner_terms && is_array( $partner_terms ) && is_object( $partner_terms[0] ) ) {
                        wp_set_object_terms( $post_inserted, [ $partner_terms[0]->term_id ], 'partner', true );
                    }
                }
                if ( $category ) {
                    wp_set_object_terms( $post_inserted, [ absint( $category[0] ) ], 'category', false );

                    /**
                     * Add support to Yoast Primary Term
                     */
                    if ( class_exists( 'WPSEO_Primary_Term' ) ) {
                        $primary_term_object = new \WPSEO_Primary_Term( 'category', $post_inserted );
                        $primary_term_object->set_primary_term( absint( $category[0] ) );
                    }
                }

                // set wpml post language
                if ( $this->lang ) {
                    if( ! function_exists( 'wpml_get_content_trid') ) {
                        // Include WPML API
                        include_once( WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php' );
                    }
                    $trid = wpml_get_content_trid( 'post_post', $post_inserted );

                    // Update the post language info
                    $language_args = [
                        'element_id' => $post_inserted,
                        'element_type' => 'post_post',
                        'trid' => $trid,
                        'language_code' => $this->lang,
                        'source_language_code' => null,
                    ];

                    do_action( 'wpml_set_element_language_details', $language_args );

                }
            }
        }
    }
}

$importer = \Jeo_MPS\Importer::get_instance();
