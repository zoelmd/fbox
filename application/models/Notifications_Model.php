<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Statistic class.
 * 
 * @extends CI_Model
 */
class Notifications_Model extends CI_Model {

    private $id;
    private $title;
    private $content;
    private $isHtml;
    private $deleteAfter;
    private $type;
    private $showOn;
    private $active = 1;
    private $isSysNotification = 0;
    private $createdAt;

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
        $this->db->set('title', $this->title);
        $this->db->set('content', $this->content);
        $this->db->set('is_html', $this->isHtml);
        $this->db->set('delete_after', $this->deleteAfter);
        $this->db->set('type', $this->type);
        $this->db->set('show_on', $this->showOn);
        $this->db->set('is_sys_notification', $this->isSysNotification);
        $this->db->set('active', $this->active);
        $this->db->set('created_at', date('Y-m-d H:i'));
        $this->db->insert('notifications');
        return $this->db->affected_rows() > 0 ? $this->db->insert_id() : false;
    }

    public function count() {
        $this->db->where('is_sys_notification',0);
        return $this->db->count_all_results("notifications");
    }

    public function update(array $data){
        foreach ($data as $key => $value) {
            $this->db->set($key, $value);
        }
        $this->db->where("id",$this->id);
        $this->db->update("notifications");
        return $this->db->affected_rows() > 0;
    }

    public function get($offset = 0,$limit = 25,$term = false){
        $this->db->from('notifications');
        $this->db->where('is_sys_notification',0);
        $this->db->order_by('id','DESC');
        $this->db->limit($limit,$offset);
        return $this->db->get()->result();
    }

    public function getById(){
        $this->db->from('notifications');
        $this->db->where('id',$this->id);
        return $this->db->get();
    }

    public function delete($ids){

        if(!is_array($ids)){
            throw new Exception("Error : Expected array but received ". gettype($ids));
        }
        
        // Delete all user notifications first 
        $this->db->where_in('notification', $ids);
        $this->db->delete('user_notifications');

        $this->db->where_in('id', $ids);
        $this->db->delete('notifications');

        return $this->db->affected_rows() > 0;
    }

    public function getUserNotifications()
    {
        $notifications = $this->Notifications_Model->get();
        $res = array();
        if($notifications){
            $this->load->helper(array('flash_helper'));
            foreach ($notifications as $notification) {
                $res[] = flash_bag($notification->content,$notification->type,true,true,$notification->is_html);
                if($notification->delete_after == "seen"){
                    $this->Notifications_Model->setId($notification->id);
                    $this->Notifications_Model->delete();
                }
            }
        }
        return $res;
    }

}