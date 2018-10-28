<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * @extends CI_Controller
 */
class My_notifications extends CI_Controller {

	private $settings;
	private $currentUser;
	private $userOptions;

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() {	
		parent::__construct();

		$this->load->library(array('session'));
		$this->load->helper(array('flash_helper','json_helper'));
		$this->load->library('twig');
		$this->load->model('User_Model');

		// if the user is logged in redirect to home page
		if(!$this->User_Model->isLoggedIn()){
			redirect('/login');
		}

		$this->currentUser = $this->User_Model->currentuser();

		// If the user account has expired show expiry page
		if($this->currentUser['expired'] == 1){
			redirect('account_expiry');
			exit();
		}

		$this->userOptions = $this->User_Model->options();

		$this->load->model('Settings_Model');

		$this->settings = $this->Settings_Model->get();
		$this->twig->addGlobal('app_settings', $this->settings);
		$this->twig->addGlobal('user', $this->User_Model);

		$this->config->set_item('language', $this->currentUser['lang']);
		$this->lang->load(array("general"));

		// Set Date format
		$this->twig->addGlobal('date_format', $this->settings['date_format']);
		$this->load->helper('general_helper');
		$this->twig->addGlobal('date_format_js', php_date_to_js($this->settings['date_format']));

		$this->load->model('Notifications_Model');
		$this->load->model('UserNotifications_Model');

		$this->load->model('FbAccount_Model');
		$this->twig->addGlobal('fbaccount',$this->FbAccount_Model);
		$this->twig->addGlobal('fbaccountDetails',$this->FbAccount_Model->getFbAccountById($this->FbAccount_Model->UserDefaultFbAccount()));
	}
	
	public function index(){
		$twigData = array();
		$userOptions = $this->User_Model->options($this->currentUser['user_id']);
		$perPage = $userOptions->row('per_page');
		if(!$perPage) $perPage = 25;

		$twigData['perPage'] = $perPage;

		$this->load->model('UserNotifications_Model');
		$this->UserNotifications_Model->setUserId($this->currentUser['user_id']);
		$twigData['getNotifications'] = $this->UserNotifications_Model->get(0,$perPage);
		$this->twig->display('notifications/my_notifications',$twigData);
	}

	public function load_more($startFrom=0){
		$userOptions = $this->User_Model->options($this->currentUser['user_id']);
		$perPage = $userOptions->row('per_page');
		if(!$perPage) $perPage = 25;
		$this->load->model('UserNotifications_Model');
		$this->UserNotifications_Model->setUserId($this->currentUser['user_id']);

		$res = $this->UserNotifications_Model->get((int)$startFrom,$perPage);

		$notifications = array();

		if($res){
			foreach ($res as $n) {
				$notifications[] = array(
					'id' 		=> $n->id,
					'unid' 		=> $n->unid,
					'type' 		=> $n->type,
					'title' 	=> $n->title,
					'is_seen' 	=> $n->is_seen,
					'content' 	=> strip_tags($n->content),
					'created_at' => fromUTC(dateFromFormat($this->settings['date_format']." H:i",$n->created_at),$this->currentUser['timezone'])
				);
			}
		}

		display_json(array(
			'status' => 'ok',
			'notifications' => $notifications,
			'next' => $startFrom*2,
		));
	}

	public function delete($id=0){
		$this->load->model('UserNotifications_Model');
		$this->UserNotifications_Model->setId((int)$id);
		$this->UserNotifications_Model->setUserId($this->currentUser['user_id']);
		if($this->UserNotifications_Model->delete()){
			display_json(array('status' => 'ok'));
			return;
		}
		display_json(array('status' => 'fail'));
	}


	public function read_status($id=null,$status=null){

		if(!$id) return;

		$this->load->model('UserNotifications_Model');
		$this->UserNotifications_Model->setId((int)$id);
		$this->UserNotifications_Model->setUserId($this->currentUser['user_id']);
		
		$this->UserNotifications_Model->update(array("is_seen"=>(int)$status))	;

		display_json(array('status' => 'ok'));
		return;	
	}
}
