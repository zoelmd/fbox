<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * @extends CI_Controller
 */
class Home extends MX_Controller {

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() {
		
		parent::__construct();
		if(!KPMIsActive("kp_faq")){
			redirect("errors/404");
		}
		$this->load->database();
		$this->load->library(array('session'));
		$this->load->helper(array('url','json_helper'));
		
		$this->load->model('User_Model');

		// If user is not logged in redirect to login page
		if(!$this->User_Model->isLoggedIn()){
			redirect('/login');
		}

		$this->currentUser = $this->User_Model->currentUser();

		// If the user account has expired show expiry page
		if($this->currentUser['expired'] == 1){
			redirect('account_expiry');
			return;
		}
		
		$this->load->model('Settings_Model');
		$this->load->helper(array('form'));

		$this->load->library('twig');

		$this->settings = $this->Settings_Model->get();

		$this->twig->addGlobal('app_settings', $this->settings);
		$this->twig->addGlobal('userdata', $this->User_Model->get($this->currentUser['user_id']));

		$this->config->set_item('language', $this->currentUser['lang']);
		$this->lang->load(array("general"));
		$this->twig->addGlobal('lang', $this->lang);

		$this->twig->addGlobal('user', $this->User_Model);

		// Set Date format
		$this->twig->addGlobal('date_format', $this->settings['date_format']);
		$this->load->helper('general_helper');
		$this->twig->addGlobal('date_format_js', php_date_to_js($this->settings['date_format']));
		
		// Set User Timezone
		date_default_timezone_set($this->currentUser['timezone']);

		$this->load->model('FbAccount_Model');
		$this->twig->addGlobal('fbaccount', $this->FbAccount_Model);
		$this->twig->addGlobal('fbaccountDetails', $this->FbAccount_Model->getFbAccountById($this->FbAccount_Model->UserDefaultFbAccount()));
	}
	
	public function index()
	{
		$twigData = array();
		$this->load->model("Faq_Model");

		$activeOnly = true;
		if($this->User_Model->HasPermission('admin')){
			$activeOnly = false;
		}

		$twigData['faqs'] = $this->Faq_Model->getAll($activeOnly);
		$this->twig->display('@faq/home',$twigData);
	}

	public function add(){

		$this->load->library('form_validation');

		$this->form_validation->set_rules('content', 'comment', 'trim|required');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'error',
				'message' => $this->form_validation->error_array()
			));
			return;
		}

		$this->Comment_Model->setUserId((int)$this->currentUser['user_id']);
		$this->Comment_Model->setContent($this->input->post("content",TRUE));

		if($commentID = $this->Comment_Model->save()){
			display_json(array(
				'status' => 'ok',
				'message' => $this->lang->s('Comment has been saved successfully'),
				'comment_id' => $commentID,
				'comment_content' => $this->input->post("content",TRUE)
			));
		}else{
			display_json(array(
				'status' => 'error',
				'message' => 'Unabe to save your comment'
			));
		}
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

		$this->Comment_Model->setUserId((int)$this->currentUser['user_id']);

		if($this->Comment_Model->deleteAll($ids)){
			display_json(array(
				'status' => 'success',
				'message' => $this->lang->s("Comment(s) has been deleted successfully")
			));
			return;
		}

		display_json(array(
			'status' => 'error',
			'message' => $this->lang->s("Enable to delete the requested records. Please try again")
		));
		exit;		
	}

	public function update(){
			
		$this->load->library('form_validation');

		$this->form_validation->set_rules('id', $this->lang->s('Comment id'), 'trim|required|integer');
		$this->form_validation->set_rules('content', $this->lang->s('Comment id'), 'trim|required');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'error',
				'message' => $this->form_validation->error_array()
			));
			exit;
		}

		$this->Comment_Model->setId((int)$this->input->post('id', TRUE));
		$this->Comment_Model->setUserId((int)$this->currentUser['user_id']);
		
		$newData = array();
		$newData['content'] = $this->input->post("content",TRUE);
	
		if($this->Comment_Model->update($newData)){
			display_json(array(
				'status' => 'ok',
				'message' => $this->lang->s('Comment has been update successfully')
			));
		}else{
			display_json(array(
				'status' => 'error',
				'message' => $this->lang->s('Nothing has been Updated')
			));
		}
	}

	public function details(){
		$this->load->library('form_validation');

		$this->form_validation->set_rules('id', 'Comment id', 'trim|required|integer');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'error',
				'message' => $this->form_validation->error_array()
			));
			return;
		}

		$this->Comment_Model->setId((int)$this->input->post('id', TRUE));
		$this->Comment_Model->setUserId((int)$this->currentUser['user_id']);
		$comment = $this->Comment_Model->getById();
		if($comment->row()){
			$details = array();
			$details['content'] = $comment->row('content');
			display_json(array(
				'status' => 'ok',
				'comment' => $details
			));
			return;
		}

		display_json(array(
			'status' => 'ok',
			'comment' => array()
		));
	}

}