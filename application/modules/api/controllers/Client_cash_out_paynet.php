<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client_cash_out_paynet extends Client_Controller {
	public function after_init() {}

	public function instapay() {
		$legder_desc 	= "cash_out";
		$tx_type_id 	= "cashout2";
		$tx_limits 		= $this->get_tx_limit($tx_type_id);
		$min			= $tx_limits["min"];
		$max			= $tx_limits["max"];

		if ($this->JSON_POST() && $_SERVER['REQUEST_METHOD'] == 'POST') {
			$post = $this->get_post();

			$amount			= isset($post['amount']) ? $post['amount'] : "";
			$bank_code		= isset($post['bank_code']) ? $post['bank_code'] : "";
			$acc_no			= isset($post['acc_no']) ? $post['acc_no'] : "";
			$acc_fname		= isset($post['acc_fname']) ? $post['acc_fname'] : "";
			$acc_lname		= isset($post['acc_lname']) ? $post['acc_lname'] : "";
			$note			= isset($post['note']) ? $post['note'] : "";

			if (!is_numeric($amount)) {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Invalid amount format!",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			if (is_decimal($amount)) {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Not accepting float value!",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			if ($amount < $min) {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Invalid Amount, Minimun amount is {$min}!",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			$fee = 0; // set default

			// $fee = $this->get_tx_fee($tx_type_id);

			// if ($amount < $fee) {
			// 	echo json_encode(
			// 		array(
			// 			'error'		=> true,
			// 			'message'	=> "Invalid Amount, amount is not enough to cover the fees!",
			// 			'timestamp'	=> $this->_today
			// 		)
			// 	);
			// 	return;
			// }

			// tx logic
			$tx_by = $this->_oauth_bridge_id;
			$tx_to = $this->_oauth_bridge_parent_id;

			// calculate receiving fee
			$receiving_amount = ($amount - $fee);

			$tx_row = $this->create_transaction(
				$receiving_amount, 
				$fee, 
				$tx_type_id, 
				$tx_by, 
				$tx_to,
				$tx_by,
				60,
				$note
			);

			$tx_id  		= $tx_row['transaction_id'];
			$sender_ref_id  = $tx_row['sender_ref_id'];

			// paynet request
			$response = $this->paynet_instapay(
				$bank_code, 
				$acc_no, 
				$acc_fname, 
				$acc_lname, 
				$note, 
				$receiving_amount
			);
	
			if (!isset($response['txn_id'])) {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> isset($response['error_message']) ? $response['error_message'] : "Api gayeway error, Please try again!",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			$txn_id = $response['txn_id'];

			$this->transactions->update(
				$tx_id,
				array(
					'txn_id' => $txn_id
				)
			);

			echo json_encode(
				array(
					'message'	=> "Successfully requested cash-out via paynet!",
					'response' => array(
						'ref_id' 	=> $sender_ref_id,
						'amount' 	=> $amount,
						'fee'		=> $fee,
						'to_receive'=> $receiving_amount
					),
					'timestamp'	=> $this->_today
				)
			);
			return;
		}

		// unauthorized access
		$this->output->set_status_header(401);	
	}
}
