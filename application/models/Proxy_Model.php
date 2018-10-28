<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Proxy class.
 * 
 * @extends CI_Model
 */
class Proxy_model extends CI_Model {

    private $id;
    private $host;
    private $port;
    private $user;
	private $pass;

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
        $this->db->set('host', $this->host);
        $this->db->set('port', $this->port);
        $this->db->set('user', $this->user);
        $this->db->set('pass', $this->pass);
        $this->db->set('created_at', date('Y-m-d H:i'));
        $this->db->insert('proxies');
        return $this->db->affected_rows() > 0;
    }

    public function delete(){
        $this->db->delete('proxies', array('id' => $this->id)); 
        return $this->db->affected_rows() > 0;
    }

    public function deleteByIp(){
        $this->db->delete('proxies', array('host' => $this->host)); 
        return $this->db->affected_rows() > 0;
    }

    // Count all record of table "instagram_accounts" in database.
    public function count() {
        return $this->db->count_all_results("proxies");
    }

    // Fetch data according to per_page limit.
    public function get($offset = 0,$limit = 25) {
        $this->db->from('proxies');
        $this->db->limit($limit,$offset);
        $this->db->order_by('id', 'DESC');
        return $this->db->get()->result();
    }

    public function getById() {
        $this->db->from('proxies');
        $this->db->where('id', $this->id);
        return $this->db->get();
    }

    public function getByRand(){
        $this->db->select('id');
        $this->db->from('proxies');
        $this->db->order_by('rand()');
        $this->db->limit(1);
        return $this->db->get();
    }

    // Fetch data according to per_page limit.
    public function getAllProxies() {
        $this->db->distinct();
        $this->db->from('proxies');
        return $this->db->get()->result_array();
    }
}