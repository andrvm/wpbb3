<?php
/**
 * Project:			WPBB3 (Wordpress & phpbb3 integration)
 * File:			hook_phpbb3_wordpress.php
 *
 * Description:		Hook for change phpbb's links
 *
 * @author:			Mamontov Andrey <andrvm@andrvm.ru>
 * @copyright		2011 Mamontov Andrey (andrvm)
 *
 */

if (!defined('IN_PHPBB')) exit;

/**
 * Config
 */
define ('PHPBB3_PATH_PREFIX', 'forum');
define ('WPBB3_PATH_PREFIX', 'wpbb3');

$phpbb_hook->register(array('template', 'display'), 'is_hook_true');

// for direct access
if ( strstr($_SERVER['QUERY_STRING'], 'direct=true') ){

    $phpbb_hook->register('append_sid', '_append_sid');
    $phpbb_hook->register(array('template', 'display'), 'is_hook_false');

}
// for admin access from wordpress
elseif ( strstr($_SERVER['REQUEST_URI'], 'adm/') && !strstr($_SERVER['QUERY_STRING'], 'direct=true')) {

    $phpbb_hook->register('append_sid', 'wp_phpbb3_admin_append_sid');
    _files();

}
// for user access from wordpress
else{

    $phpbb_hook->register('append_sid', 'wp_phpbb3_append_sid');
    $phpbb_hook->register(array('template', 'display'), 'wp_phpbb3_correct_links');
    _files();

}

/**
 *  Changes forum's links for wordpress
 */
function wp_phpbb3_append_sid (&$hook, $url, $params = false, $is_amp = true, $session_id = false, $admin = false){

	$url = append_sid($url, $params, $is_amp, $session_id);

    $_up  = false;

    // exceptions
	if ( !$admin ){

        if ( strstr($url, 'cron.php') || strstr($url, 'mode=confirm') ){
            $url = str_replace('./', '', $url);
            return  '/wp-content/plugins/' . WPBB3_PATH_PREFIX . '/' . PHPBB3_PATH_PREFIX . '/' . $url;
        }
	}
    else{

        if ( strstr($url, 'captcha') ){
            $url = str_replace('./', '', $url);
            return  '/wp-content/plugins/' . WPBB3_PATH_PREFIX . '/' . PHPBB3_PATH_PREFIX . '/adm/' . $url;
        }

        $_up = strstr($url, '/../') ? true : false;
    }

    // cut relative paths (./, ./../)
    $url = preg_replace("#[\.\/a-z]+\/([a-z]+).php#isU", "pview=$1", $url);
    // processing direct links with file names
    $url = preg_replace("#^([a-z]+).php#isU", "pview=$1", $url);
    // replace ?
    $url = str_replace('?', '&', $url);
    $url = str_replace('&&', '&', $url);

    if ( $admin && strstr($url, 'page=phpbb3&') )
        $url = str_replace('page=phpbb3&', '', $url);

    $url = $admin & !$_up ? '/wp-admin/options-general.php?page=phpbb3&' . $url : '/' . PHPBB3_PATH_PREFIX . '/?' . $url;

  	return $url;
}

/**
 *
 */
function wp_phpbb3_admin_append_sid (&$hook, $url, $params = false, $is_amp = true, $session_id = false){

    return wp_phpbb3_append_sid ($hook, $url, $params, $is_amp, $session_id, true);
}

/**
 * A stopper fro phpbb
 */
function wp_phpbb3_correct_links(&$hook, $handle, $include_once = true){

	global $template, $user;
			
	$template->assign_vars(array(
	
        'U_REGISTER'    	=> '/wp-login.php?action=register',
		'U_LOGIN_LOGOUT'	=> $user->data['user_id'] != ANONYMOUS ? '/wp-login.php?action=logout&redirect_to=/' : '/wp-login.php',
        
	));
}

// for direct access to admin panel
function is_hook_true(&$hook, $handle, $include_once = true){

	global $template;

	$template->assign_vars(array(
	
        'IS_PHPBB_WP_HOOK'	=> true,

	));
}

// for direct access to admin panel
function is_hook_false(&$hook, $handle, $include_once = true){

    global $template;

    $template->assign_vars(array(

        'IS_PHPBB_WP_HOOK'	=> false,

    ));
}

function _append_sid (&$hook, $url, $params = false, $is_amp = true, $session_id = false){

	$url = append_sid($url, $params, $is_amp, $session_id);
	
	$url_delim = (strpos($url, '?') === false) ? '?' : (($is_amp) ? '&amp;' : '&');

	return $url . $url_delim . 'direct=true';

}

/**
 * curl $_FILES hack
 *
 * @see http://ru2.php.net/manual/en/function.curl-setopt.php#97591
 * @see http://stackoverflow.com/questions/8486710/fileupload-with-curl-php
 */
function _files(){

    if ( empty($_FILES) || !isset($_POST['_ffileuploadname']) ) return;

    $name = $_POST['_ffileuploadname'];

    $_FILES[$name]['type'] = $_POST['_ftype'];
    $_FILES[$name]['name'] = $_POST['_ffilename'];

}