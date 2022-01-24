<?php
/**
 * Class ApiMaps
 *
 * @package Jeo
 */

namespace Jeo\Tests;

class ApiLayers extends jeo_media_partners_UnitApiTestCase {

	function test_create() {

		$request = new \WP_REST_Request('POST', '/wp/v2/map-layer');

		$request_body = [
			'title' => 'Test layer',
			'content' => 'Sample content',
			'meta' => [
				'type' => 'mapbox',
				'layer_type_options' => [
					'style_id' => 'infoamazonia/123123123213'
				]
			]
		];

		$request->set_query_params($request_body);

		$response = $this->server->dispatch($request);

		$data = $response->get_data();

		$this->assertEquals(201, $response->get_status());

		$this->assertEquals('Test layer', $data['title']['raw']);
		$this->assertEquals('mapbox', $data['meta']['type']);
		$this->assertEquals('map-layer', $data['type']);

	}

	function test_meta_validation() {

		$request = new \WP_REST_Request('POST', '/wp/v2/map-layer');

		$request_body = [
			'title' => 'Test layer',
			'content' => 'Sample content',
			'meta' => [
				'type' => 'unregistered_type'
			]
		];

		$request->set_query_params($request_body);

		$response = $this->server->dispatch($request);

		$data = $response->get_data();

		$this->assertEquals(400, $response->get_status());

	}


}
