<?php

namespace Jeo\Tests;

/**
 * Basic test case for api calls
 * @author medialab
 *
 */
class jeo_media_partners_UnitApiTestCase extends jeo_media_partners_UnitTestCase {
	/**
	 * Test REST Server
	 * @var \WP_REST_Server
	 */
	protected $server;
	
	public function setUp(){
		parent::setUp();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server;

		do_action( 'rest_api_init' );
	}
}