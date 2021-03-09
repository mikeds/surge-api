<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Token extends Api_Controller {
	public function after_init() {}

	public function index() {
		$this->load->library('OAuth2', 'oauth2');
		$this->oauth2->get_token();
	}

	public function paynet() {
		$bank_code = "ALL";
		$acc_no = "0000000000000001";
		$acc_fname = "Marknel";
		$acc_lname = "Pineda";
		$note = "Instapay cash out";
		$amount = "100";

		$response = $this->paynet_instapay(
			$bank_code, 
			$acc_no, 
			$acc_fname, 
			$acc_lname, 
			$note, 
			$amount
		);

		if (isset($response['$txn_id'])) {
			echo $response['$txn_id'];
		}
	}
}
