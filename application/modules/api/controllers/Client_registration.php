<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Client_registration extends Api_Controller {
	public function index() {
		$this->load->model("api/client_accounts_model", "accounts");
		$this->load->model("api/pastor_accounts_model", "pastor_accounts");
		$this->load->model("api/oauth_bridges_model", "bridges");

		if ($this->JSON_POST() && $_SERVER['REQUEST_METHOD'] == 'POST') {
			$post = $this->get_post();

			$fname			= isset($post['first_name']) ? $post['first_name'] : "";
			$lname			= isset($post['last_name']) ? $post['last_name'] : "";
			$mobile_no		= isset($post['mobile_no']) ? $post['mobile_no'] : "";
			$email_address	= isset($post['email_address']) ? $post['email_address'] : "";
			$dob			= isset($post['dob']) ? $post['dob'] : "";
			$gender			= isset($post['gender']) ? $post['gender'] : "";

			$house_no		= isset($post['house_no']) ? $post['house_no'] : "";
			$street			= isset($post['street']) ? $post['street'] : "";
			$brgy			= isset($post['brgy']) ? $post['brgy'] : "";
			$city			= isset($post['city']) ? $post['city'] : "";
			$province_id	= isset($post['province_id']) ? $post['province_id'] : "";
			$others			= isset($post['others']) ? $post['others'] : "";

			$pastor_id		= isset($post['pastor_id']) ? $post['pastor_id'] : "";

			$password 		= isset($post['password']) ? $post['password'] : "";

			if ($fname == "" || $lname == "" || $email_address == "" || $mobile_no == "" || $password == "" || $dob == "" || $pastor_id == "" || $province_id == "" || $city = "") {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Please complete the parameters! Note: [pastor_id, first_name, last_name, mobile_no, email_address, dob, password, confirm_password, province_id, city] are required.",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			if (strlen($password) < 7) {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Minimum lenght of password is at-least 7 characters!",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			if (!is_email($email_address)) {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Invalid email address!",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			if (!is_date($dob)) {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Invalid date format! Note: [yyyy-mm-dd] is the correct format.",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			if ($this->validate_email($email_address)) {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Email address already used!",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			$pastor_row = $this->pastor_accounts->get_datum(
				'',
				array(
					'account_number' => $pastor_id,
					'account_status' => 1 
				)
			)->row();

			if ($pastor_row == "") {
				echo json_encode(
					array(
						'error'		=> true,
						'message'	=> "Invalid pastor id!",
						'timestamp'	=> $this->_today
					)
				);
				return;
			}

			$account_number = $this->generate_code(
				array(
					"client",
					// $this->_oauth_bridge_parent_id,
					$email_address,
					$this->_today
				),
				"crc32"
			);

			$bridge_id = $this->generate_code(
				array(
					"client",
					$account_number,
					$this->_today
				)
			);

			$password = hash("sha256", $password);

			$pin 	= generate_code(4, 2);

			// generate expiration datetime
			$time = new DateTime($this->_today);
			$time->add(new DateInterval('PT' . 3 . 'M'));
			$expiration_date = $time->format('Y-m-d H:i:s');

			// create account
			$this->accounts->insert(
				array(
					'pastor_account_number'	=> $pastor_id,
					'account_number'		=> $account_number,
					'oauth_bridge_id'		=> $bridge_id,
					'account_fname'			=> $fname,
					'account_lname'			=> $lname,
					'account_mobile_no'		=> $mobile_no,
					'account_password'		=> $password,
					'account_email_address'	=> $email_address,
					'account_dob'			=> $dob,
					'account_gender'		=> $gender == 'female' ? 2 : 1,
					'account_date_added'	=> $this->_today,
					'account_otp_pin'		=> $pin,
					'account_otp_expiration'=> $expiration_date,
					'account_house_no'		=> $house_no,
					'account_street'		=> $street,
					'account_brgy'			=> $brgy,
					'account_city'			=> $city,
					'province_id'			=> $province_id,
					'account_others'		=> $others,
				)
			);

			// create bridge access
			$this->bridges->insert(
				array(
					'oauth_bridge_id' 			=> $bridge_id,
					'oauth_bridge_parent_id'	=> $this->_oauth_bridge_parent_id,
					'oauth_bridge_date_added'	=> $this->_today
				)
			);

			// create wallet address
			$this->create_wallet_address($account_number, $bridge_id, $this->_oauth_bridge_parent_id);

			// create token auth for api
			$this->create_token_auth($account_number, $bridge_id);

			// send email otp
			$this->send_email_activation(
				$email_address,
				$pin,
				$expiration_date
			);

			echo json_encode(
				array(
					'message'	=> "Successfully registered!",
					'timestamp'	=> $this->_today
				)
			);
			return;
		}

		// unauthorized access
		$this->output->set_status_header(401);	
	}
}
