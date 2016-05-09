DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_down_00100;
$$

CREATE PROCEDURE migrate_down_00100()
BEGIN
  drop table if exists mw_transactional_email_group;
  drop table if exists mw_compliance_level_ids;
  drop table if exists mw_transactional_email_compliance;
END
$$

GRANT EXECUTE ON PROCEDURE migrate_down_00100 TO CURRENT_USER();
$$
FLUSH PRIVILEGES;
$$
CALL migrate_down_00100();
$$
DROP PROCEDURE IF EXISTS migrate_down_00100;
$$
DROP PROCEDURE IF EXISTS migrate_up_00100;
$$
CREATE PROCEDURE migrate_up_00100()
BEGIN


 CREATE TABLE `market_hero`.`mw_transactional_email_group` (
   `transactional_email_group_id` INT NOT NULL,
   `transactional_email_group_uid` INT(11) NOT NULL,
   `customer_id` INT(11) NOT NULL,
   PRIMARY KEY (`transactional_email_group_id`),
   UNIQUE INDEX `transactional_email_group_uid_UNIQUE` (`transactional_email_group_uid` ASC));
 
 CREATE TABLE `mw_compliance_level_ids` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `threshold` float(10,2) DEFAULT NULL,
   PRIMARY KEY (`id`)
 ) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;
 
 INSERT INTO `mw_compliance_level_ids` (`id`, `threshold`)
 VALUES
 	(0, 0.00),
 	(1, 0.10),
 	(2, 0.20),
 	(3, 0.30),
 	(4, 0.00),
 	(5, 0.50),
 	(6, 0.60),
 	(7, 0.70),
 	(8, 0.80),
 	(9, 0.90),
 	(10, 1.00);
 
 CREATE TABLE `mw_transactional_email_compliance` (
   `transactional_email_group_id` int(11) NOT NULL,
   `compliance_level_type_id` int(11) DEFAULT NULL,
   `last_processed_id` bigint(8) DEFAULT NULL,
   `compliance_round` int(11) DEFAULT NULL,
   `compliance_approval_user_id` int(11) DEFAULT NULL,
   `date_added` datetime DEFAULT NULL,
   `last_updated` datetime DEFAULT NULL,
   PRIMARY KEY (`transactional_email_group_id`),
   KEY `fk_compliance_level1` (`compliance_level_type_id`),
   KEY `fk_approval_user_id` (`compliance_approval_user_id`),
   CONSTRAINT `fk_approval_user_id` FOREIGN KEY (`compliance_approval_user_id`) REFERENCES `mw_user` (`user_id`),
   CONSTRAINT `fk_transactional_email_group_id` FOREIGN KEY (`transactional_email_group_id`) REFERENCES `mw_transactional_email_group` (`transactional_email_group_id`),
   CONSTRAINT `fk_compliance_level1` FOREIGN KEY (`compliance_level_type_id`) REFERENCES `mw_compliance_level_ids` (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

END
$$
FLUSH PRIVILEGES;
$$
CALL migrate_up_00100();
$$
DROP PROCEDURE IF EXISTS migrate_down_00100;
$$