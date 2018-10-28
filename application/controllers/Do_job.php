<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 
 * @extends CI_Controller
 */
class Do_job extends CI_Controller {
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() {	
		parent::__construct();
		$this->load->database();
	}
	
	public function check_expired_accounts() {
		$this->load->model("User_Model");
		$this->User_Model->checkExpiredAccounts();
	}
	
	public function check_proxies(){
		// Get and test proxies
		$this->load->library('Cron_lib');
		if($this->cron_lib->lock("check_proxies")) return false;

		$this->load->model("Proxy_Model");
		$availabelProxies = $this->Proxy_Model->getAllProxies();
		$proxiesToRemove = array();
		foreach ($availabelProxies as $p) {
			$waitTimeoutInSeconds = 10; 
			if($fp = @fsockopen($p['host'],$p['port'],$errCode,$errStr,$waitTimeoutInSeconds)){
			   fclose($fp);
			} else {
			   $proxiesToRemove[] = $p['host'];
			}
		}
		// Remove failed proxies
		foreach ($proxiesToRemove as $ptr) {
			$this->Proxy_Model->setHost($ptr);
			$this->Proxy_Model->deleteByIp();
		}
	}

	public function runServices(){
		
		$availableServices = array(
			"post" 				=> "schedules/schedule_run",
			"comment" 			=> "comments/schedule_run",
			"like" 				=> "autolike/schedule_run",
			"join_groups" 		=> "join_groups/schedule_run",
			"invite_joingroups" => "invite_join_groups/schedule_run"
		);

		$services = implode(',', $this->input->get('services'));
		foreach ($services as $service) {
			if(!isset($availableServices[$service])) continue;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, base_url($availableServices[$service])); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 1);
			echo curl_exec($ch);
			curl_close($ch);
		}
	}
}
