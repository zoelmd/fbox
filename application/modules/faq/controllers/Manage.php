<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * @extends CI_Controller
 */
class Manage extends MX_Controller {

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

		// User must be an admin to access this area
		if(!$this->User_Model->HasPermission('admin')){
			redirect('/errors/404');
			exit();
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

		$this->load->model('Faq_Model');

		$this->load->model('FbAccount_Model');
		$this->twig->addGlobal('fbaccount', $this->FbAccount_Model);
		$this->twig->addGlobal('fbaccountDetails', $this->FbAccount_Model->getFbAccountById($this->FbAccount_Model->UserDefaultFbAccount()));
	}
	
	public function index()
	{
		$twigData = array();
		$this->load->library('pagination');
		$this->load->helper("pagination");
		$this->Faq_Model->setUserId($this->currentUser['user_id']);

		$this->User_Model->setId($this->currentUser['user_id']);
		$userOptions = $this->User_Model->userSettings();

		$perPage = $userOptions['per_page'];
		if(!$perPage) $perPage = 25;
		
		$config = pagination_config();
		$config['base_url'] = base_url()."/faqs/";
		$config['total_rows'] = $this->Faq_Model->count();

		$config['per_page'] = $perPage;

		$this->pagination->initialize($config);
		$pagination = $this->pagination->create_links();
		
		$faqs = $this->Faq_Model->get((int)$this->input->get('per_page', TRUE),$perPage);

		$twigData['faqs'] = $faqs;
		$twigData['pagination'] = $pagination;
		$twigData['User_Model'] = $this->User_Model;
		$twigData['total_faqs'] = $config['total_rows'];
		$twigData['perPage'] = $perPage >= $config['total_rows'] ? $config['total_rows'] : $perPage;

		$this->twig->display('@faq/manage',$twigData);
	}

	public function add(){

		$this->load->library('form_validation');
		$this->load->helper(array('flash_helper'));
		$twigData = array();

		$this->form_validation->set_rules('question', $this->lang->s('Question'), 'trim|required');
		$this->form_validation->set_rules('answer', $this->lang->s('Answer'), 'trim|required');

		if($this->form_validation->run() === TRUE) {
			$this->Faq_Model->setQuestion($this->input->post("question",TRUE));
			$this->Faq_Model->setAnswer($this->input->post("answer",FALSE));
			$this->Faq_Model->setSort((int)$this->input->post("sort"));
			$this->Faq_Model->setActive($this->input->post("active") == "on" ? 1 : 0);
			
			if($faqId = $this->Faq_Model->save()){
				redirect("faq/manage/edit/".$faqId);
			}else{
				$twigData['flash'][] = flash_bag($this->lang->s("Failed to save data, Please try again"),"danger",true,true);
			}
		}

		$this->twig->display('@faq/add',$twigData);
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

		if($this->Faq_Model->deleteAll($ids)){
			display_json(array(
				'status' => 'success',
				'message' => $this->lang->s("Faq(s) has been deleted successfully")
			));
			return;
		}

		display_json(array(
			'status' => 'error',
			'message' => $this->lang->s("Enable to delete the requested records. Please try again")
		));
		exit;		
	}

	public function edit($faqId = false){

		if($faqId === false) redirect("faq/manage");

		// get FAQ
		$this->Faq_Model->setId((int)$faqId);
		$faq = $this->Faq_Model->getById();

		if(!$faq->row()) redirect("faq/manage");
			
		$this->load->library('form_validation');
		$this->load->helper(array('flash_helper'));
		$twigData = array();

		$twigData['faq'] = $faq;

		$this->form_validation->set_rules('question', $this->lang->s('Question'), 'trim|required');
		$this->form_validation->set_rules('answer', $this->lang->s('Answer'), 'trim|required');

		if($this->form_validation->run() === TRUE) {
			$newData = array();
			$newData['question'] = $this->input->post("question",TRUE);
			$newData['answer'] = $this->input->post("answer",FALSE);
			$newData['sort'] = (int)$this->input->post("sort");
			$newData['active'] = $this->input->post("active") == "on" ? 1 : 0;
			
			if($this->Faq_Model->update($newData)){
				$twigData['flash'][] = flash_bag($this->lang->s("Changes has been saved successfully"),"success",true,true);
			}else{
				$twigData['flash'][] = flash_bag($this->lang->s("Nothing has been changed"),"info",true,true);
			}
		}

		$this->twig->display('@faq/edit',$twigData);
	}

	public function details(){
		$this->load->library('form_validation');

		$this->form_validation->set_rules('id', 'Faq id', 'trim|required|integer');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'error',
				'message' => $this->form_validation->error_array()
			));
			return;
		}

		$this->Faq_Model->setId((int)$this->input->post('id', TRUE));
		$this->Faq_Model->setUserId((int)$this->currentUser['user_id']);
		$comment = $this->Faq_Model->getById();
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

	public function toggle_active(){
		$this->load->library('form_validation');
		$this->load->helper(array('json_helper'));

		$this->form_validation->set_rules('id', $this->lang->s('FAQ id'), 'trim|required|integer');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'fail',
				'message' => $this->form_validation->error_array()
			));
		}else{

		$this->Faq_Model->setId((int)$this->input->post('id', TRUE));

		if($this->Faq_Model->toggleActiveStatus()){
				display_json(array(
					'status' => 'ok',
					'message' => $this->lang->s('Faq status updated')
				));
			}else{
				display_json(array(
					'status' => 'fail',
					'message' => $this->lang->s('Unabe to update faq status')
				));
			}
		}
	}

}