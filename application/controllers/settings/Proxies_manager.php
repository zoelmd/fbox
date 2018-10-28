<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * @extends CI_Controller
 */
class Proxies_manager extends CI_Controller {

	private $settings;
	private $currentUser;

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

		// User must be an admin to access this area
		if(!$this->User_Model->HasPermission('admin')){
			show_404();
			exit();
		}

		$this->currentUser = $this->User_Model->currentuser();

		$this->load->model('Settings_Model');
		$this->load->model('FbAccount_Model');

		$this->settings = $this->Settings_Model->get();
		$this->twig->addGlobal('app_settings', $this->settings);
		
		$this->config->set_item('language', $this->currentUser['lang']);
		$this->lang->load(array("general"));
		
		$this->twig->addGlobal('fbaccountDetails', $this->FbAccount_Model->getFbAccountById($this->FbAccount_Model->UserDefaultFbAccount()));
		$this->twig->addGlobal('fbaccount', $this->FbAccount_Model);
		$this->twig->addGlobal('user', $this->User_Model);

	}

	public function index()
	{
		$this->load->model('Proxy_Model');

		$this->load->library('pagination');
		$this->load->helper("pagination");

		$twigData = array();

		$perPage = isset($this->settings['per_page']) || $this->settings['per_page'] != null ? $this->settings['per_page'] : 25;
		
		$config = pagination_config();
		$config['base_url'] = base_url()."/settings/proxies/";
		$config['total_rows'] = $this->Proxy_Model->count();
		
		$config['per_page'] = $perPage;

		$this->pagination->initialize($config);
		$pagination = $this->pagination->create_links();
		
		$proxies = $this->Proxy_Model->get((int)$this->input->get('per_page', TRUE),$perPage);

		$twigData['proxies']		= $proxies;
		$twigData['pagination']		= $pagination;
		$twigData['total_records']	= $config['total_rows'];
		$twigData['perPage']		= $perPage >= $config['total_rows'] ? $config['total_rows'] : $perPage;

		$this->twig->display('settings/proxies_manager',$twigData);	
	}


	public function delete(){

		$this->load->library('form_validation');

		$this->form_validation->set_rules('id', 'Proxy id', 'trim|required|integer');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'error',
				'message' => $this->form_validation->error_array()
			));
			exit;
		}

		$this->load->model('Proxy_Model');
		$this->Proxy_Model->setId($this->input->post('id',TRUE));

		if($this->Proxy_Model->delete()){
			display_json(array(
				'status' => 'success',
				'message' => $this->lang->s("The proxy has been deleted successfully")
			));
			exit;
		}

		display_json(array(
			'status' => 'error',
			'message' => $this->lang->s("Failed to delete the record")
		));
		exit;

	}

	public function add(){

		$this->load->library('form_validation');

		$this->form_validation->set_rules('host', 'Proxy host', 'trim|required');
		$this->form_validation->set_rules('port', 'Proxy port', 'trim|required');

		if($this->form_validation->run() === false) {
			display_json(array(
				'status' => 'error',
				'message' => $this->form_validation->error_array()
			));
			exit;
		}

		$this->load->model('Proxy_Model');

		$this->Proxy_Model->setHost($this->input->post("host",TRUE));
		$this->Proxy_Model->setPort($this->input->post("port",TRUE));
		$this->Proxy_Model->setUser($this->input->post("user",TRUE));
		$this->Proxy_Model->setPass($this->input->post("pass",TRUE));

		if($this->Proxy_Model->save()){
			display_json(array(
				'status' => 'success',
				'message' => $this->lang->s("The proxy has been deleted successfully")
			));
			exit;
		}

		display_json(array(
			'status' => 'error',
			'message' => $this->lang->s("Failed to delete the record")
		));
		exit;
	}

	public function disable($status = 0){
		$this->Settings_Model->update(array("use_proxy"=>(int)$status));
		$s = $this->Settings_Model->get();
		display_json(array(
			'status' => 'ok',
			'option_status' => $s['use_proxy']
		));
	}

}
