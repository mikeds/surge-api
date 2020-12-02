<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client_tx extends Client_Controller {
	public function after_init() {}

	public function history() {
		$this->load->model("api/transactions_model", "tx");

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$select = array(
				'*'
			);

			$where = array(
				''
			);

			$response = $this->tx->get_data(
				$select,
				$where
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

		// unauthorized access
		$this->output->set_status_header(401);	
	}
}
