<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lookup extends Api_Controller {

	public function after_init() {
        $this->global_validate_token();

        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            // unauthorized access
		    $this->output->set_status_header(401);	
        }
    }

    public function pastor_list() {
        $this->load->model("api/pastor_accounts_model", "accounts");

        $results = $this->accounts->get_data(
            array(
                'account_number',
                'account_fname',
                'account_mname',
                'account_lname',
                'account_email_address',
                'account_mobile_no'
            ),
            array(
                'account_status' => 1
            ),
            array(),
            array(),
            array(
                'filter'    => 'account_fname',
                'sort'      => 'ASC'
            )
        );

        echo json_encode(
            array(
                'message'	=> "Successfully fetch pastor accounts!",
                'timestamp'	=> $this->_today,
                'response'  => $results
            )
        );
    }
}
