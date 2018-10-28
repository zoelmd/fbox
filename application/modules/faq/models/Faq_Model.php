<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * @extends CI_Model
 */
class Faq_Model extends CI_Model {

    private $id;
    private $question;
    private $answer;
    private $sort;
    private $active;
    private $lang;
    private $createAt;

    // Setters and getters Auto Generate  
    public function __call($function, $args)
    {
        $functionType = strtolower(substr($function, 0, 3));
        $propName = lcfirst(substr($function, 3));
        switch ($functionType) {
            case 'get':
                if (property_exists($this, $propName)) {
                    return $this->$propName;
                }
                break;
            case 'set':
                if (property_exists($this, $propName)) {
                    $this->$propName = $args[0];
                }
                break;
        }
    }

    /**
      * __construct function.
      * 
      * @access public
      * @return void
      */
     public function __construct() {
      parent::__construct();
      $this->load->database();
      $this->load->library(array('session'));
     }


	public function save(){
        $this->db->set('question', $this->question);
        $this->db->set('answer', $this->answer);
        $this->db->set('sort', $this->sort);
        $this->db->set('active', $this->active);
	    $this->db->set('lang', $this->lang);
	    $this->db->set('created_at', date('Y-m-d H:i'));
	    $this->db->insert('faq');
	    return $this->db->affected_rows() > 0 ? $this->db->insert_id() : false;
	}

    public function update($data){
        foreach ($data as $key => $value) {
            $this->db->set($key, $value);
        }
        $this->db->where('id', $this->id);
        $this->db->update("faq");

        return $this->db->affected_rows() > 0;
    }

	public function delete(){
	    $this->db->where('id', $this->id)->delete('faq');
	    return $this->db->affected_rows() > 0;
	}
	   
    public function deleteAll($ids){

        if(!is_array($ids)){
            throw new Exception("Error : Expected array but received ". gettype($ids));
        }
        
        $this->db->where_in('id', $ids);
        $this->db->delete('faq');
        return $this->db->affected_rows() > 0;
    }

    public function count() {
	    return $this->db->count_all_results("faq");
	}

	// Fetch data according to per_page limit.
	public function get($offset = 0,$limit = 25) {
        $this->db->select('*');
	  	$this->db->from('faq');
	    $this->db->limit($limit,$offset);
        $this->db->order_by('id', 'DESC');
        $res = $this->db->get();
	    return $res ? $res->result() : false;
	}

    // Fetch data according to per_page limit.
    public function getAll($active = false) {
        $this->db->select('*');
        $this->db->from('faq');
        if($active){
            $this->db->where('active',1);
        }
        $this->db->order_by('sort', 'ACS');
        $res = $this->db->get();
        return $res ? $res->result() : false;
    }

    public function getById() {
        $this->db->select('*')->from('faq')->where('id',$this->id);
        return $this->db->get();
    }


    public function toggleActiveStatus(){
        $status = $this->getById($this->id)->row('active') == 1 ? 0 : 1 ;
        $this->db->set('active', $status);
        $this->db->where('id', $this->id);
        $this->db->update('faq');
        return $this->db->affected_rows() > 0;
    }
}