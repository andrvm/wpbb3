<?php
/**
 * Plugin Name: Wpbb3
 *
 * Plugin URI: https://github.com/andrvm/wpbb3
 *
 * Description: Wordpress & phpbb3 integration
 *
 * Version: 0.1.17
 *
 * Author: Mamontov Andrey <andrvm@andrvm.ru>
 * Author URI: http://andrvm.ru
 *
 * License: GNU GPL v2.0
*/

/**
 * Config
 */

// path to plugin
define('PHPBB3_PLUGIN_PATH', WP_PLUGIN_DIR . '/wpbb3');
// path to forum
define('FORUM_PHPBB3_PATH', PHPBB3_PLUGIN_PATH . '/forum');
// plugin name
define('PLUGIN_NAME', 'wpbb3');
// link to forum
define('FORUM_PHPBB3_LINK', get_option('siteurl') . '/wp-content/plugins/' . PLUGIN_NAME . '/forum/');
// forum page
define('FORUM_PAGE', '/forum/');

include_once PHPBB3_PLUGIN_PATH . '/Helpers.class.php';
include_once PHPBB3_PLUGIN_PATH . '/Wpbb3.class.php';

/**
 * Fronted section
 */
if ( !is_admin() ){

    $wpbb3 = new Wpbb3();
	
	// add phpbb3 style
	wp_enqueue_style(PLUGIN_NAME, get_option('siteurl') . '/wp-content/plugins/' . PLUGIN_NAME . '/' . PLUGIN_NAME . '.css');
    // clear query string
    add_filter('query_vars', array($wpbb3, 'clear'));
    // wpbb3 init
    add_action('init', array($wpbb3, 'init'));

    // phpbb3 login, logout, user registration
	add_action('wp_login',	array($wpbb3, 'login'));
	add_action('wp_logout', array($wpbb3, 'logout'));
	add_action('user_register', array($wpbb3, 'register'));
	
	// get forum's data
	if ( strstr($_SERVER['REQUEST_URI'], FORUM_PAGE) ){

        // time update
		add_action('init', array($wpbb3, 'last_time_update'));
        // load forum content
		add_filter('the_content', array($wpbb3, 'loadforum'));
        // set new page title
        add_action('wp_title', array($wpbb3, 'set_title'));
	}
	
}
/**
 * Admin section
 */
else{

    include_once PHPBB3_PLUGIN_PATH . '/Wpbb3.admin.class.php';

    $wpbb3_admin = new Wpbb3Admin();

    // wpbb3_admin init
    add_action('init', array($wpbb3_admin, 'init'));
    // add hook menu and load forum content
    add_action('admin_menu', array($wpbb3_admin, 'add_menu'));
    // updating user's info
    add_action('profile_update', array($wpbb3_admin, 'update_user_profile_fields'));
    // delete user
    add_action( 'delete_user', array($wpbb3_admin, 'delete_user'));

    // if use phpbb3 menu item
    if ( isset($_GET['page']) && $_GET['page'] == 'phpbb3' ){

        // add phpbb3 style
        wp_enqueue_style('phpbb3', get_option('siteurl') . '/wp-content/plugins/' . PLUGIN_NAME . '/' . PLUGIN_NAME . '_admin.css');
        //
        add_action('init', array($wpbb3_admin, 'last_time_update'));
   }

} // End