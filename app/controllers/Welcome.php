<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Welcome extends Controller {
	public function index() {
		$this->call->view('welcome_page');
	}

	/** GET /api - tiny health check; handy to confirm a Render deploy is alive. */
	public function api_info() {
		header('Content-Type: application/json');
		echo json_encode([
			'name'    => 'Product Management API',
			'status'  => 'ok',
			'version' => '1.0',
		]);
	}
}
