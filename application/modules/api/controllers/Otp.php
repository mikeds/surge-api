<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Otp extends Api_Controller {

	public function after_init() {
        $this->global_validate_token();

        if ($_SERVER['REQUEST_METHOD'] != 'POST' && $this->JSON_POST()) {
            // unauthorized access
		    $this->output->set_status_header(401);	
        }
    }

    public function client_submit() {
        $this->load->model("api/client_accounts_model", "accounts");

        $post = $this->get_post();

        $username   = isset($post["username"]) ? $post["username"] : "";
        $otp_pin    = isset($post["otp_pin"]) ? $post["otp_pin"] : "";

        $row = $this->accounts->get_datum(
            '',
            array(
                'account_email_address' => $username,
                'account_otp_pin'       => $otp_pin,
                'account_status'        => 0
            )
        )->row();

        if ($row == "") {
            echo json_encode(
                array(
                    'error'		=> true,
                    'message'	=> "Invalid OTP Pin!",
                    'timestamp'	=> $this->_today
                )
            );
            return;
        }

        $expiration_date = $row->account_otp_expiration;

        if (strtotime($this->_today) > strtotime($expiration_date)) {
            echo json_encode(
                array(
                    'error'		=> true,
                    'message'	=> "OTP Pin is expired!",
                    'timestamp'	=> $this->_today
                )
            );
            return;
        }

        $this->accounts->update(
            $row->account_number,
            array(
                'account_status' => 1
            )
        );

        echo json_encode(
            array(
                'message'	=> "Successfully activated!",
                'timestamp'	=> $this->_today
            )
        );
    }

    public function client_resend() {
        $this->load->model("api/client_accounts_model", "accounts");

        $post = $this->get_post();

        $username = isset($post["username"]) ? $post["username"] : "";

        $row = $this->accounts->get_datum(
            '',
            array(
                'account_email_address' => $username,
                'account_status'        => 0
            )
        )->row();

        if ($row == "") {
            echo json_encode(
                array(
                    'error'		=> true,
                    'message'	=> "Invalid request otp!",
                    'timestamp'	=> $this->_today
                )
            );
            return;
        }

        $email_address = $row->account_email_address;

        $pin 	= generate_code(4, 2);

        // generate expiration datetime
        $time = new DateTime($this->_today);
        $time->add(new DateInterval('PT' . 3 . 'M'));
        $expiration_date = $time->format('Y-m-d H:i:s');

        // update otp pin
        $this->accounts->update(
            $row->account_number,
            array(
                'account_otp_pin'           => $pin,
                'account_otp_expiration'    => $expiration_date
            )
        );

        // send email otp
        $this->send_email_activation(
            $email_address,
            $pin,
            $expiration_date
        );

        echo json_encode(
            array(
                'message'	=> "Successfully resend otp!",
                'timestamp'	=> $this->_today
            )
        );
    }
}
