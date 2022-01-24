<?php

namespace Jeo\Tests;

class jeo_media_partners_UnitTestCase extends \WP_UnitTestCase {
	protected $user_id;

	public function setUp(){
		parent::setUp();
		
		$new_admin_user = $this->factory()->user->create(array( 'role' => 'administrator' ));
		wp_set_current_user($new_admin_user);
		$this->user_id = $new_admin_user;
		
		// workaround for https://core.trac.wordpress.org/ticket/48300
		\jeo_media_partners_maps()->register_post_type();
		\jeo_media_partners_layers()->register_post_type();
	}
}