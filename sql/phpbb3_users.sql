--
-- Copy wp' users to phpbb  (Wordpress & phpbb3 integration)
--

-- copy  ordinary users
INSERT INTO `phpbb_users` (`user_id`, `group_id`, `username`, `username_clean`, `user_regdate`, `user_email`)
SELECT u.`ID`, 2, (CASE u. `display_name` WHEN u.`display_name` != '' THEN u.`display_name` ELSE u.`user_login` END),
(CASE u. `display_name` WHEN u.`display_name` != '' THEN u.`display_name` ELSE u.`user_login` END),
UNIX_TIMESTAMP(u.`user_registered`), `user_email` FROM `wp_users` u
INNER JOIN (
        SELECT `meta_value` as `level`, `user_id`
                    FROM `wp_usermeta`
                    WHERE `meta_key` = 'wp_user_level'
           ) meta ON meta.`user_id` = u.`ID`
WHERE meta.`level` < 7;

-- copy  admin users
INSERT INTO `phpbb_users` (`user_id`, `group_id`, `username`, `username_clean`, `user_regdate`, `user_email`)
SELECT u.`ID`, 5, (CASE u. `display_name` WHEN u.`display_name` != '' THEN u.`display_name` ELSE u.`user_login` END),
(CASE u. `display_name` WHEN u.`display_name` != '' THEN u.`display_name` ELSE u.`user_login` END),
UNIX_TIMESTAMP(u.`user_registered`), `user_email` FROM `wp_users` u
INNER JOIN (
        SELECT `meta_value` as `level`, `user_id`
                    FROM `wp_usermeta`
                    WHERE `meta_key` = 'wp_user_level'
           ) meta ON meta.`user_id` = u.`ID`
WHERE meta.`level` = 10 and u.`user_login` !='admin';

-- copy moderator users
INSERT INTO `phpbb_users` (`user_id`, `group_id`, `username`, `username_clean`, `user_regdate`, `user_email`)
SELECT u.`ID`, 4, (CASE u. `display_name` WHEN u.`display_name` != '' THEN u.`display_name` ELSE u.`user_login` END),
(CASE u. `display_name` WHEN u.`display_name` != '' THEN u.`display_name` ELSE u.`user_login` END),
UNIX_TIMESTAMP(u.`user_registered`), `user_email` FROM `wp_users` u
INNER JOIN (
        SELECT `meta_value` as `level`, `user_id`
                    FROM `wp_usermeta`
                    WHERE `meta_key` = 'wp_user_level'
           ) meta ON meta.`user_id` = u.`ID`
WHERE meta.`level` = 7;

-- add phpbb user's group
INSERT INTO `phpbb_user_group` (`user_id`, `group_id`, `user_pending`)
SELECT `user_id`, `group_id`, 0 FROM `phpbb_users` WHERE `username` != 'admin' AND `user_id` >0;

-- update user num information
UPDATE `phpbb_config` SET `config_value` = (SELECT count(*) FROM `phpbb_users` WHERE `user_id`>0)
WHERE `config_name`='num_users'
