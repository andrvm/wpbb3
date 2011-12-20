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

        echo $this->loadforum(true);
    }

    /**
     * Fill phpbb3 fields and add moderators
     */
    public function update_user_profile_fields($user_id) {

        // change forum's username
        $this->_db->query("UPDATE `{$this->_db->users}` SET `username` = `display_name`, `username_clean` = `display_name` WHERE `ID` = {$user_id}");

        // check user level
        $user = get_userdata($user_id);

        // @TODO: to improve it!
        // delete existing rights
        $this->_db->query("SELECT * FROM `" . PHPBB3_TABLE_PREFIX . "user_group` WHERE (`group_id` = 4 OR `group_id` = 5) and `user_id` = {$user_id}");

        if ( $this->_db->num_rows > 0 ){

            $this->_db->query("DELETE FROM `" . PHPBB3_TABLE_PREFIX . "user_group` WHERE (`group_id` = 4 OR `group_id` = 5) and `user_id` = {$user_id}");
        }

        $group = $user->user_level == 7 ? 4 : ($user->user_level == 10 ? 5 : 0);

        // if a moderator or an administrator
        if ( $group == 4 || $group == 5 ){

            $this->_db->query("INSERT INTO `" . PHPBB3_TABLE_PREFIX . "user_group` (`group_id`, `user_id`, `user_pending`) VALUES ({$group}, {$user_id}, 0)");

        }
    }

} // End Wpbb3Admin Class