<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$moduleErrors = array();

// Disbale Database error reporting 
$this->db->db_debug = false;

$this->db->query("CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text CHARACTER SET utf8,
  `content` text CHARACTER SET utf8,
  `is_html` tinyint(4) NOT NULL DEFAULT '0',
  `delete_after` varchar(16) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `show_on` varchar(64) DEFAULT NULL,
  `active` tinyint(4) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$this->db->query("CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `to_all` tinyint(4) NOT NULL,
  `is_seen` tinyint(4) NOT NULL,
  `seen_at` datetime NOT NULL,
  `active` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

if (!$this->db->field_exists('is_sys_notification', 'notifications')){
  $this->db->query("ALTER TABLE `notifications` ADD `is_sys_notification` tinyint(4) NOT NULL DEFAULT '0'");
}

// Update the database
$this->db->db_debug = DB_DEBUG;

?>