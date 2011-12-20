--
-- Changes some phpbb3' tables (Wordpress & phpbb3 integration)
--

-- delete AUTO_INCREMENT property 
ALTER TABLE `phpbb_users` CHANGE `user_id` `user_id` MEDIUMINT( 8 ) UNSIGNED NOT NULL;

-- delete unigue index `username_clean`, set index `username_clean`
ALTER TABLE `phpbb_users` DROP INDEX `username_clean` ,
ADD INDEX `username_clean` ( `username_clean` );

-- set guest user id = 0
UPDATE `phpbb_users` SET `user_id` = '0' WHERE `phpbb_users`.`user_id` =1;

-- correcting group for guest user
UPDATE `phpbb_user_group` SET `user_id` = '0' WHERE `phpbb_user_group`.`group_id` =1 AND `phpbb_user_group`.`user_id` =1 AND `phpbb_user_group`.`group_leader` =0 AND `phpbb_user_group`.`user_pending` =0 LIMIT 1;

-- truncate session table
TRUNCATE TABLE `phpbb_sessions`;
