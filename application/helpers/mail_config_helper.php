<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('mail_config') )
{
  function mail_config()
  {
    $config = array();
    $config['protocol'] = MAIL_PROTOCOL;
	$config['smtp_host'] = SMTP_HOST;
	$config['smtp_port'] = SMTP_PORT;
	$config['smtp_user'] = SMTP_USER; 
	$config['smtp_pass'] = SMTP_PASS;
	$config['charset'] = "utf-8";
	$config['mailtype'] = "html";
	$config['newline'] = "\r\n";
	$config['useragent'] = "Kingposter";
	return $config;
  }
}

