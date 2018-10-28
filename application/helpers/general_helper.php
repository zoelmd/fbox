<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('php_date_to_js') )
{
        function php_date_to_js($string){
                return str_replace(
                  array('m','d','Y'), 
                  array('MM','DD','YYYY'),
                  $string
                );
        }
}

if ( ! function_exists('dateFromFormat') )
{
    function dateFromFormat($format,$string){
        return DateTime::createFromFormat($format,$string);
    }
}

if ( ! function_exists('toABSPath') )
{
    function toABSPath($URL,$BaseURL){
        $URL = urldecode($URL);
        return substr($URL, strlen($BaseURL));
    }
}

if ( ! function_exists('getVideoID') )
{
    function getVideoID($url){

      $youtube_reg = "/(youtube.com|youtu.be)\/(watch)?(\?v=)?(\S+)?/";
      if(preg_match($youtube_reg, $url, $match)){
         return array("youtube",$match[4]);
      }
      return false;
    }
}

if (!function_exists('getDomainFromURL')){
  function getDomainFromURL($url){
    preg_match("/[a-z0-9\-]{1,63}\.[a-z\.]{2,6}$/", parse_url($url, PHP_URL_HOST), $_domain_tld);
    return $_domain_tld[0];
  }
}

if (!function_exists('extractAccessToken')){
  function extractAccessToken($code){

    $res = array(
      "status" => FALSE,
      "message" => "Invalid Access token",
      "access_token" => "",
    );

    preg_match('~access_token=(.*)(?=&expires_in)~',$code,$m);
    if(isset($m[1])){
      $res['status'] = TRUE;
      $res['message'] = "";
      $res['access_token'] = $m[1];
      return $res;
    }

    $r = json_decode($code,TRUE);

    if(json_last_error() == JSON_ERROR_NONE){
      if(isset($r['access_token'])){
        $res['status'] = TRUE;
        $res['message'] = "";
        $res['access_token'] = $r['access_token'];
        return $res;
      }

      if(isset($r['error_data'])){
        $rr = json_decode($r['error_data'],TRUE);
        if(json_last_error() == JSON_ERROR_NONE){
          if(isset($rr['error_message'])){
            $res['status'] = FALSE;
            $res['message'] = $rr['error_message'];
            $res['access_token'] = "";
            return $res;
          }
        }
      }

      if(isset($r['error_msg'])){
        $res['status'] = FALSE;
        $res['message'] = $r['error_msg'];
        $res['access_token'] = "";
        return $res;
      }
    }

    preg_match('~"access_token":"(.*)(?=","machine_id)~',$code,$m);
    if(isset($m[1])){
      $res['status'] = TRUE;
      $res['message'] = "";
      $res['access_token'] = $m[1];
      return $res;
    }

    if(trim($code) != ""){
      $res['status'] = TRUE;
      $res['message'] = "";
      $res['access_token'] = $code;
    }
      
    return $res;
  }
}

if ( ! function_exists('writeToFile'))
{
	function writeToFile($fileLocation, $content)
	{
		$file = fopen($fileLocation, 'a+');
		flock($file, LOCK_EX);
		ftruncate($file, 0);
		fseek($file, 0);
		fwrite($file, $content.PHP_EOL);
		flock($file, LOCK_UN);
		fclose($file);
	}
}

function cronjob_exists($command){
    $cronjob_exists=false;
    exec('crontab -l', $crontab);
    if(isset($crontab)&&is_array($crontab)){
        $crontab = array_flip($crontab);
        if(isset($crontab[$command])){$cronjob_exists=true;}
    }
    return $cronjob_exists;
}

if ( ! function_exists('help_link'))
{
  function help_link($link)
  {
    $links = array();
    $links['add_lang'] = "https://pandisoft.com/kingposter/translation/";
    $links['create_pp_app'] = "https://pandisoft.com/kingposter/doc/CreatePaypalAPP";

    if(isset($links[$link])){
      return $links[$link];
    }

    return "";
  }
}

if ( ! function_exists('fromUTC')){
  function fromUTC($time, $timezone) {
    if(!in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL))){
      return $time;
    }
    $changetime = new DateTime($time, new DateTimeZone('UTC'));
    $changetime->setTimezone(new DateTimeZone($timezone));
    return $changetime->format('Y-m-d H:i:s');
  }
}

if ( ! function_exists('l')){
  function l($string,$p1 = null,$p2 = null,$p3 = null,$p4 = null,$p5 = null){
    $CI =& get_instance();
    return $CI->lang->s($string,$p1,$p2,$p3,$p4,$p5);
  }
}

if(!function_exists('get_user_notifications')){
  function getUserNotifications(){
      $CI =& get_instance();
      $notifications = null;
      if($CI->User_Model->isLoggedIn()){
        $userid = $CI->User_Model->currentuser()['user_id'];
        $CI->load->model('UserNotifications_Model');
        $CI->UserNotifications_Model->setUserId($userid);
      $notifications = array(
        "notifications"=> $CI->UserNotifications_Model->get(0,10),
        "count_notifications" => $CI->UserNotifications_Model->countUnSeen()
      );
      return $notifications;
    }
  }
}

if ( ! function_exists('KPMIsActive'))
{
  function KPMIsActive($module)
  {
    $kp_modules = (array)get_instance()->config->item('kp_modules');
    return isset($kp_modules[$module]);
  }
}