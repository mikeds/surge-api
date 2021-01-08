<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pastor_tx extends Pastor_Controller {
	public function after_init() {}

	public function history() {
		$this->load->model("api/transactions_model", "tx");

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$select = array(
				'*'
			);

			$where = array(
				'oauth_bridge_id' => $this->_oauth_bridge_id
			);

			$response = $this->tx->get_data(
				$select,
				array(),
				array(
					'transaction_requested_by' => $this->_oauth_bridge_id,
					'transaction_requested_to' => $this->_oauth_bridge_id,
				)
			);

			echo json_encode(
				array(
					'message'	=> "Successfully retrieve transaction history!",
					'response' => $response,
					'timestamp'	=> $this->_today
				)
			);
			return;
		}

		// 0020230237933

		// unauthorized access
		$this->output->set_status_header(401);	
	}
}
