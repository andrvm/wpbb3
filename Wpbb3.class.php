<?php
/**
 * Project:			WPBB3 (Wordpress & phpbb3 integration)
 * File:			Wpbb3.class.php
 *
 * Description:		Class with hooks for wordpress
 *
 * @author:			Mamontov Andrey <andrvm@andrvm.ru>
 * @copyright		2011 Mamontov Andrey (andrvm)
 *
 * $Id$
 *
 */

class Wpbb3 {

    const COOKIE_PREFIX         = 'phpbb3_o244q';
    // SESSION_NAME = COOKIE_PREFIX . '_sid'
    const SESSION_NAME          = 'phpbb3_o244q_sid';
    const PHPBB3_TABLE_PREFIX   = 'phpbb_';

    // db instance
    protected $_db;
    // session_id
    protected $_sid;
    // cookie expires
    protected $_expiries;
    // user
    protected $_user_id;

    public  function __construct(){

        // get session id
        $this->_sid = isset($_COOKIE[self::SESSION_NAME]) && !empty($_COOKIE[self::SESSION_NAME]) ? $_COOKIE[self::SESSION_NAME] : Helpers::unique_id();
        // set expiries time for cookies
        $this->_expiries   = time() + 31536000; // 1 year
    }

    /**
     * Init
     */
    public function init(){

        global $wpdb, $user_ID;

        // db instance
        $this->_db = $wpdb;
        // set user id
        $this->_user_id    =  $user_ID ? (int) $user_ID : 0;
    }

    public function loadforum($admin = false){

        $content	= '';

        $browser	= !empty($_SERVER['HTTP_USER_AGENT'])	? $_SERVER['HTTP_USER_AGENT']	: '';
        $reffer		= !empty($_SERVER['HTTP_REFERER'])		? $_SERVER['HTTP_REFERER']		: '';

        $cookie		= self::SESSION_NAME . "={$this->_sid}; " . self::COOKIE_PREFIX . "_u={$this->_user_id}";

        $post = array();

        // post data
        if ( !empty($_POST) ){
            $post = Helpers::flatten_GP_array($_POST);
        }

        // file upload
        if ( !empty($_FILES) ){

            foreach ($_FILES as $param => $file) {

                if ( $file['tmp_name'] ){
                    $post[$param]		=	'@' . $file['tmp_name'];// . ';filename=' . $file['name'] . ';type=' . $file['type'];  // for libcurl/7.19 and higher
                    $post['_ftype']		=	$file['type'];
                    $post['_ffilename']	=	$file['name'];
                    $post['_ffileuploadname']	=	$param;
                }
            }
        }

        // processing links
        if ( isset($_REQUEST['pview']) && $_SERVER['QUERY_STRING'] ){

            $t_     = str_replace('pview=' . $_REQUEST['pview'] . '&', '', $_SERVER['QUERY_STRING']);
            $t_     = str_replace('pview=' . $_REQUEST['pview'], '', $t_);
            $query  = $_REQUEST['pview'] . '.php?' . $t_;

        }

        $frame_link = FORUM_PHPBB3_LINK . ($admin ? 'adm/' : '') . $query;

        /**
         * For admin section
         */
        if ( $admin ){

            $url_delim      = (strpos($frame_link, '?') === false) ? '?' : '&';
            $frame_link    .= !strstr($frame_link, 'sid') ? "{$url_delim}sid={$this->_sid}" : '';
        }

        if ( $curl = curl_init() ) {

            curl_setopt($curl, CURLOPT_URL, $frame_link);
            curl_setopt($curl, CURLOPT_USERAGENT, $browser);
            curl_setopt($curl, CURLOPT_REFERER, $reffer);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);

            if ( !empty($post) ) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            }

            $content = curl_exec($curl);

            curl_close ($curl);

        }
        else
            $content =  '<p>Форум временно недоступен.</p>';

       return $content;

    }

    /**
     * Update last user visit on the phpbb3
     * @TODO: set time limit
     *
     * @return void
     */
    public function last_time_update(){

        $browser = (!empty($_SERVER['HTTP_USER_AGENT'])) ? htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']) : '';

        $admin	 = current_user_can('manage_options') ? 1 : 0; // current_user_can is function of the wp

        // is a bot or not a bot?
        $this->_db->query("select instr('$browser', `bot_agent`) as `bt`  from `" . self::PHPBB3_TABLE_PREFIX . "bots` having `bt` > 0");

        if ( $this->_db->num_rows > 0 ) return;

        $this->_db->query("select * from `" . self::PHPBB3_TABLE_PREFIX . "sessions` where session_id = '" . $this->_db->_real_escape($this->_sid) . "'");

        if ( $this->_db->num_rows > 0  ){

            $this->_db->query("update `" . self::PHPBB3_TABLE_PREFIX . "sessions` set session_user_id = " . $this->_user_id . ", session_time = " . time() . ",
							session_admin = " . $admin . ",
							session_page = '" .  $this->_db->_real_escape($_SERVER['REQUEST_URI']) . "'
							where session_id = '" .  $this->_db->_real_escape($this->_sid) . "'");


        }
        else{

            $this->_db->query("insert into `" . self::PHPBB3_TABLE_PREFIX . "sessions`
		                       (session_id, session_user_id, session_last_visit, session_start, session_time,
		                        session_viewonline, session_browser, session_ip, session_page, session_admin)
		                        values('{$this->_sid}', {$this->_user_id}, " . time() . ", " . time() . ", " . time() . ", 1,
									   '{$browser}', '" . $this->_db->_real_escape($_SERVER['REMOTE_ADDR']) . "', '" .
                                        $this->_db->_real_escape($_SERVER['REQUEST_URI']) . "', {$admin})");

            // set session cookie
            Helpers::set_cookie(self::SESSION_NAME, $this->_sid, $this->_expires);
            // write user's id in the cookie
            Helpers::set_cookie(self::COOKIE_PREFIX . '_u', $this->_user_id, $this->_expiries);
        }
    }

    /**
     * Login to phbb3
     * @param $username
     */
    public function login($username){

       // @TODO: think about the compatibility
       $user 	 = get_user_by('login', $username); // get_user_by is function of the wp

       $user_id = isset($user->ID) ?  $user->ID : 0;

       // set session cookie
       Helpers::set_cookie(self::SESSION_NAME, $this->_sid, $this->_expires);
        // write user's id in the cookie
       Helpers::set_cookie(self::COOKIE_PREFIX . '_u', $user_id, $this->_expiries);
    }

    /**
     * Register a new user on the forum
     *
     * @param $user_id
     */
    public function register($user_id) {

        if ( !$user_id ){
            // ERROR
            trigger_error('ERROR: user_id is empty', E_USER_ERROR);
        }
        else{

            $user = get_user_by('id', $user_id);
            $_t = time();
            // add phpbb3 user
            $this->_db->query('INSERT INTO `' . self::PHPBB3_TABLE_PREFIX . "users` (`user_id`, `group_id`, `username`, `username_clean`, `user_regdate`) VALUES ({$user_id}, 2, '{$user->user_login}', '{$user->user_login}', {$_t})");
            // set group
            $this->_db->query("INSERT INTO `" . self::PHPBB3_TABLE_PREFIX . "user_group` (`user_id`, `user_pending`, `group_id`) VALUES ({$user_id}, 0, 2)");
            // update users num
            $this->_db->query("UPDATE `" . self::PHPBB3_TABLE_PREFIX . "config` SET `config_value`=`config_value`+1 WHERE `config_name`='num_users'");
            // update registered user name
            $this->_db->query("UPDATE `" . self::PHPBB3_TABLE_PREFIX . "config` SET `config_value`='{$user->user_login}' WHERE `config_name`='newest_username'");
        }
    }

    /**
     * Logout from the forum
     */
    public function logout() {

        if ( $this->_sid )
            $this->_db->query("DELETE FROM `" . self::PHPBB3_TABLE_PREFIX . "sessions` WHERE `session_id` = '" . $this->_db->_real_escape($this->_sid) . "'");

    }

    /**
     * Clean query parameters
     */
    public function clear($public_query_vars) {

        // TODO: Think about correct removing parameters from $public_query_vars
        // remove from $public_query_vars parameter "p"
        // it's uses in the phpbb3
        unset ($public_query_vars[1]);

        //return $public_query_vars;
        return $public_query_vars;
    }

} // End Wpbb3 Class