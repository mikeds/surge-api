<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Token extends Api_Controller {
	public function after_init() {}

	public function index() {
		$this->load->library('OAuth2', 'oauth2');
		$this->oauth2->get_token();
	}

	public function paynet() {
		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => PAYNET_BASE_URL .'cx/login',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => 'loginId='. PAYNET_USERNAME .'&loginPass='. PAYNET_PASSWORD .'&loginHost=' . PAYNET_HOST,
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/x-www-form-urlencoded'
		),
		));

		$response = curl_exec($curl);
		$response = json_decode($response);

		curl_close($curl);

		if (!isset($response->tokenId)) {
			$results = array(
				'error' 			=> true,
				'error_description'	=> "Invalid client credentials"
			);

			echo json_encode($results);
			die();
		}

		$results = array(
			'access_token' 	=> $response->tokenId,
			'expires_in'	=> 1800,
			'token_type'	=> 'x-auth'
		);

		echo json_encode($results);
		die();		
	}
}
