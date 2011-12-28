<?php
/**
 * Project:			WPBB3 (Wordpress & phpbb3 integration)
 * File:			Wpbb3.admin.class.php
 *
 * Description:		Class with hooks for wordpress (admin section)
 *
 * @author:			Mamontov Andrey <andrvm@andrvm.ru>
 * @copyright		2011 Mamontov Andrey (andrvm)
 *
 */

class Wpbb3Admin extends Wpbb3 {

   /**
    * Add phpbb3 menu item
    */
    public function add_menu() {

        add_options_page('Форум phpbb3 :: администрирование', 'Форум phpbb3', 'manage_options', 'phpbb3', array($this, 'plugin_options'));
    }

    /**
     * Load forum into the admin page
     */
    public function plugin_options() {

        if (!current_user_can('manage_options'))  {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }

        echo $this->loadforum();
    }

    /**
     * Fill phpbb3 fields and add moderators
     */
    public function update_user_profile_fields($user_id) {

        // get user data
        $user = get_userdata($user_id);

        // update phpbb username
        if ( $user->display_name ){

            $name = $this->_db->_real_escape($user->display_name);
            $this->_db->query("UPDATE `" . self::PHPBB3_TABLE_PREFIX . "users` SET `username` = '{$name}', `username_clean` = '{$name}' WHERE `user_id` = {$user_id}");
        }

        // @TODO: to improve it!
        // delete existing rights
        $this->_db->query("SELECT * FROM `" . self::PHPBB3_TABLE_PREFIX . "user_group` WHERE (`group_id` = 4 OR `group_id` = 5) and `user_id` = {$user_id}");

        if ( $this->_db->num_rows > 0 ){

            $this->_db->query("DELETE FROM `" . self::PHPBB3_TABLE_PREFIX . "user_group` WHERE (`group_id` = 4 OR `group_id` = 5) and `user_id` = {$user_id}");
            $this->_db->query("UPDATE `" . self::PHPBB3_TABLE_PREFIX . "users` SET `user_permissions` = '' WHERE `user_id` = {$user_id}");
        }

        // check user level
        $group = $user->user_level == 7 ? 4 : ($user->user_level == 10 ? 5 : 2);

        // if a moderator or an administrator
        if ( $group == 4 || $group == 5 ){

            $this->_db->query("INSERT INTO `" . self::PHPBB3_TABLE_PREFIX . "user_group` (`group_id`, `user_id`, `user_pending`) VALUES ({$group}, {$user_id}, 0)");

        }

        // update user group
        $this->_db->query("UPDATE `" . self::PHPBB3_TABLE_PREFIX . "users` SET `group_id` = {$group} WHERE `user_id` = {$user_id}");
    }

    public function delete_user($user_id){

        // delete phpbb3 user
        $this->_db->query('DELETE FROM `' . self::PHPBB3_TABLE_PREFIX . "users` WHERE `user_id` = {$user_id}");
        // delete from user group table
        $this->_db->query("DELETE FROM `" . self::PHPBB3_TABLE_PREFIX . "user_group` WHERE `user_id`={$user_id}");
        // update users stat
        $this->_db->query("UPDATE `" . self::PHPBB3_TABLE_PREFIX . "config` SET `config_value`=`config_value`-1 WHERE `config_name`='num_users'");
    }

} // End Wpbb3Admin Class