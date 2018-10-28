<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$moduleErrors = array();

// Disbale Database error reporting 
$this->db->db_debug = false;

$this->db->query("CREATE TABLE IF NOT EXISTS `faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text CHARACTER SET utf8 NOT NULL,
  `answer` text CHARACTER SET utf8 NOT NULL,
  `sort` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `lang` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

// Update the database
$this->db->db_debug = DB_DEBUG;

?>