<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * @extends CI_Controller
 */
class Notifications extends CI_Controller {

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
		if(!KPMIsActive("notifications")){
			redirect("errors/404");
		}
		$this->load->library(array('session'));
		$this->load->helper(array('flash_helper','json_helper'));
		$this->load->library('twig');
		$this->load->model('User_Model');

		// if the user is logged in redirect to home page
		if(!$this->User_Model->isLoggedIn()){
			redirect('/login');
		}	

		// User must be an admin to access this area
		if(!$this->User_Model->HasPermission('admin')){
			redirect("errors/404");
			exit();
		}

		$this->config->load("kp_modules");
		$kp_modules = (array)$this->config->item('kp_modules');

		// User must be an admin to access this area
		if(!isset($kp_modules['notifications'])){
			redirect("errors/404");
			exit();
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

		$this->load->model('FbAccount_Model');
		$this->twig->addGlobal('fbaccount',$this->FbAccount_Model);
		$this->twig->addGlobal('fbaccountDetails',$this->FbAccount_Model->getFbAccountById($this->FbAccount_Model->UserDefaultFbAccount()));
	}
	
	public function index() {
		$twigData = array();

		$this->load->library('pagination');
		$this->load->helper("pagination");

		$userOptions = $this->User_Model->options($this->currentUser['user_id']);
		$perPage = $userOptions->row('per_page');
		if(!$perPage) $perPage = 25;
		
		$config = pagination_config();
		$config['base_url'] = base_url()."/notifications/";
		$config['total_rows'] = $this->Notifications_Model->count();

		$config['per_page'] = $perPage;

		$this->pagination->initialize($config);
		$pagination = $this->pagination->create_links();
		
		$notifications = $this->Notifications_Model->get((int)$this->input->get('per_page', TRUE),$perPage);

		$twigData['notifications'] = $notifications;
		$twigData['pagination'] = $pagination;
		$twigData['User_Model'] = $this->User_Model;
		$twigData['total_notifications'] = $config['total_rows'];
		$twigData['perPage'] = $perPage >= $config['total_rows'] ? $config['total_rows'] : $perPage;
		
		$this->twig->display('@notifications/home',$twigData);
	}

	public function add()
	{

		$twigData = array();
		
		$this->load->library('form_validation');

		$this->form_validation->set_rules('title', $this->lang->s('Notification title'), 'trim|required');
		$this->form_validation->set_rules('type', $this->lang->s('Notification type'), 'trim|required');
		$this->form_validation->set_rules('content', $this->lang->s('Notification content'), 'trim|required');

		if($this->form_validation->run() === false) {
			$this->twig->display('@notifications/add',$twigData);
			return;
		}

		if(!in_array($this->input->post('type',TRUE),array("danger","warning","success","info","primary"))){
			$twigData['flash'][] = flash_bag($this->lang->s('Notification type not defined'),"warning",TRUE,TRUE,TRUE);
			$this->twig->display('@notifications/add',$twigData);
			return;
		}

		if(!in_array($this->input->post('delete_after',TRUE),array("close","seen"))){
			$twigData['flash'][] = flash_bag($this->lang->s('Delete after value is not defined'),"warning",TRUE,TRUE,TRUE);
			$this->twig->display('@notifications/add',$twigData);
			return;
		}
		
		$recipients = array();
		if($this->input->post('recipients-all',TRUE) == "all"){
			$recipients[] = 0;
		}else{		
			$recipients = (array)$this->input->post('recipients_ids',TRUE);
			$recipients = array_unique($recipients);
			if(count($recipients) == 0){
				$twigData['flash'][] = flash_bag($this->lang->s("At least one user must be selected"),"warning",TRUE,TRUE,TRUE);
				$this->twig->display('@notifications/add',$twigData);
				return;
			}	
		}

		// save the notification
		$this->Notifications_Model->setTitle($this->input->post('title',TRUE));
		$this->Notifications_Model->setContent($this->input->post('content',FALSE));
		$this->Notifications_Model->setIsHtml(1);
		$this->Notifications_Model->setDeleteAfter($this->input->post('delete_after',TRUE));
		$this->Notifications_Model->setType($this->input->post('type',TRUE));
		$this->Notifications_Model->setShowOn("home");

		if(!$n = $this->Notifications_Model->save()){
			$twigData['flash'][] = flash_bag($this->lang->s("Failed to save the Notification"),"warning",TRUE,TRUE,TRUE);
			$this->twig->display('@notifications/add',$twigData);
			return;
		}

		$this->load->model("UserNotifications_Model");
		$this->UserNotifications_Model->setNotification($n);
		$this->UserNotifications_Model->setIsSeen(0);
		$this->UserNotifications_Model->setActive(1);

		// Attached the notification to selected users
		if(count($recipients) == 1 && $recipients[0] == "0"){
			$this->UserNotifications_Model->setUserId(0);
			$this->UserNotifications_Model->setToAll(1);

			if($this->UserNotifications_Model->save()){
				$twigData['flash'][] = flash_bag($this->lang->s("Notification has been saved successfully"),"success",TRUE,TRUE,TRUE);
				redirect("notifications/edit/".$n);
				return;
			}else{
				$twigData['flash'][] = flash_bag($this->lang->s("Failed to attach the notification to selected users"),"success",TRUE,TRUE,TRUE);
				$this->twig->display('@notifications/add',$twigData);
				return;
			}
		}

		foreach ($recipients as $id) {
			$this->UserNotifications_Model->setUserId((int)$id);
			$this->UserNotifications_Model->save();
		}

		redirect("notifications/edit/".$n);
		$this->twig->display('@notifications/add',$twigData);
		
	}

	public function delete(){

		$this->load->library('form_validation');

		$this->form_validation->set_rules('ids', 'IDs', 'trim|required');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'error',
				'message' => $this->form_validation->error_array()
			));
			exit;
		}

		$ids = (array)json_decode($this->input->post('ids'),true);
		
		if(count($ids) == 0) {
			display_json(array(
				'status' => 'error',
				'message' => $this->lang->s("No record has been specified")
			));
			exit;
		}

		if($this->Notifications_Model->delete($ids)){
			display_json(array(
				'status' => 'success',
				'message' => $this->lang->s("Notification(s) has been deleted successfully")
			));
			return;
		}

		display_json(array(
			'status' => 'error',
			'message' => $this->lang->s("Enable to delete the requested records. Please try again")
		));
		exit;
		
	}

	public function details(){
		$this->load->library('form_validation');

		$this->form_validation->set_rules('id', 'Notification id', 'trim|required|integer');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'error',
				'message' => $this->form_validation->error_array()
			));
			return;
		}

		$this->Notifications_Model->setId((int)$this->input->post('id', TRUE));
		$notification = $this->Notifications_Model->getById();

		if($notification->row()){
			$details = "<ul class='list-group list-group-flush'>";

			$details .= "<li class='list-group-item'>";
			$details .= "<strong>".$this->lang->s("Title")."</strong> : ".$notification->row('title');
			$details .= "</li>";

			$details .= "<li class='list-group-item'>";
			$details .= "<strong>".$this->lang->s("Type")."</strong> : ".$notification->row('type');
			$details .= "</li>";

			$details .= "<li class='list-group-item'>";
			$details .= "<strong>".$this->lang->s("content")."</strong> : ".$notification->row('content');
			$details .= "</li>";

			$this->load->model("UserNotifications_Model");
			$this->UserNotifications_Model->setNotification((int)$this->input->post('id', TRUE));
			$uns = $this->UserNotifications_Model->getByNotifications((int)$this->input->post('id', TRUE));

			$recipients = "";	
			
			if($uns){
				foreach ($uns as $un) {
					if($un->to_all == 1){
						$recipients .= $this->lang->s("All users");
						break;
					}
					$recipients .= "<span class='badge'>";

					if($un->is_seen == 1){
						$recipients .= " <i class='fa fa-check'></i> ";
					}
					$recipients .= $un->username."</span>";
				}
			}

			$details .= "<li class='list-group-item'>";
			$details .= "<strong>".$this->lang->s("Recipients")."</strong> : ".$recipients;
			$details .= "</li>";

			$details .= "<li class='list-group-item'>";
			$details .= "<strong>".$this->lang->s("Created at")."</strong> : ".$notification->row('created_at');
			$details .= "</li>";

			display_json(array(
				'status' => 'ok',
				'content' => $details
			));

		}else{
			display_json(array(
				'status' => 'error',
				'message' => 'Unabe to get notification details'
			));
		}
	}

	public function edit($id=0){

		$this->Notifications_Model->setId($id);
		$notification = $this->Notifications_Model->getById();

		if(!$notification->row()){
			redirect("notifications");
		}

		$twigData = array();
		$twigData['notification'] = $notification;

		$this->load->model("UserNotifications_Model");
		$this->UserNotifications_Model->setNotification((int)$id);
		$uns = $this->UserNotifications_Model->getByNotifications();

		$recipients = array();
		if($uns){
			foreach ($uns as $un) {
				if($un->to_all == 1){
					$recipients = 0;
					break;
				}
				$recipients[] = array("id"=>$un->user_id,"name"=>$un->username);
			}
		}

		$twigData['recipients'] = $recipients;

		$this->load->library('form_validation');

		$this->form_validation->set_rules('title', $this->lang->s('Notification title'), 'trim|required');
		$this->form_validation->set_rules('type', $this->lang->s('Notification type'), 'trim|required');
		$this->form_validation->set_rules('content', $this->lang->s('Notification content'), 'trim|required');

		if($this->form_validation->run() === false) {
			$this->twig->display('@notifications/edit',$twigData);
			return;
		}

		if(!in_array($this->input->post('type',TRUE),array("danger","warning","success","info","primary"))){
			$twigData['flash'][] = flash_bag($this->lang->s('Notification type not defined'),"warning",TRUE,TRUE,TRUE);
			$this->twig->display('@notifications/add',$twigData);
			return;
		}

		if(!in_array($this->input->post('delete_after',TRUE),array("close","seen"))){
			$twigData['flash'][] = flash_bag($this->lang->s('Delete after value is not defined'),"warning",TRUE,TRUE,TRUE);
			$this->twig->display('@notifications/add',$twigData);
			return;
		}

		// update the notification
		$newData = array();
		$newData['title'] = $this->input->post('title',TRUE);
		$newData['content'] = $this->input->post('content',FALSE);
		$newData['is_html'] = 1;
		$newData['delete_after'] = $this->input->post('delete_after',TRUE);
		$newData['type'] = $this->input->post('type',TRUE);
		$newData['show_on'] = "home";

		$this->Notifications_Model->update($newData);

		$this->load->model("UserNotifications_Model");
		$this->UserNotifications_Model->setNotification((int)$id);

		$this->UserNotifications_Model->deleteByNotification();

		$this->UserNotifications_Model->setIsSeen(0);
		$this->UserNotifications_Model->setActive(1);

		// Attached the notification to selected users
		if($this->input->post('recipients-all',TRUE) == "all"){
			$this->UserNotifications_Model->setUserId(0);
			$this->UserNotifications_Model->setToAll(1);

			if(!$this->UserNotifications_Model->save()){
				$twigData['flash'][] = flash_bag($this->lang->s("Failed to attach the notification to selected users"),"success",TRUE,TRUE,TRUE);
				$this->twig->display('@notifications/edit',$twigData);
			}
		}else{		
			$newRecipients = (array)$this->input->post('recipients_ids',TRUE);
			$newRecipients = array_unique($newRecipients);
			if(count($newRecipients) == 0){
				$twigData['flash'][] = flash_bag($this->lang->s("At least one user must be selected"),"warning",TRUE,TRUE,TRUE);
				$this->twig->display('@notifications/add',$twigData);
				return;
			}else{
				foreach ($newRecipients as $userid) {
					$this->UserNotifications_Model->setUserId((int)$userid);
					$this->UserNotifications_Model->save();
				}
			}
		}

		// Reload the user notification
		$this->load->model("UserNotifications_Model");
		$this->UserNotifications_Model->setNotification((int)$id);
		$uns = $this->UserNotifications_Model->getByNotifications();

		$recipients = array();
		if($uns){
			foreach ($uns as $un) {
				if($un->to_all == 1){
					$recipients = 0;
					break;
				}
				$recipients[] = array("id"=>$un->user_id,"name"=>$un->username);
			}
		}
		
		$twigData['recipients'] = $recipients;

		$twigData['flash'][] = flash_bag($this->lang->s("Notification has been saved successfully"),"success",TRUE,TRUE,TRUE);
 
		$this->twig->display('@notifications/edit',$twigData);
		
	}
	
}
