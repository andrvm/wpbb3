--
-- Add the admin user to phpbb (Wordpress & phpbb3 integration)
--

-- add to phpbb_users table 
UPDATE `phpbb_users`, `wp_users` SET `phpbb_users`.`user_id`=`wp_users`.`ID`
         WHERE `wp_users`.`user_login`='admin' AND `phpbb_users`.`user_id`=2;

-- change phpbb_acl_users
UPDATE `phpbb_acl_users`, `wp_users` SET `phpbb_acl_users`.`user_id`=`wp_users`.`ID`
         WHERE `wp_users`.`user_login`='admin' AND `phpbb_acl_users`.`user_id`=2;

-- change phpbb_user_group
UPDATE `phpbb_user_group`, `wp_users` SET `phpbb_user_group`.`user_id`=`wp_users`.`ID`
         WHERE `wp_users`.`user_login`='admin' AND `phpbb_user_group`.`user_id` = 2;

