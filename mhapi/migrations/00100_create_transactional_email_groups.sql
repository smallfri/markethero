DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_down_00100;
$$

CREATE PROCEDURE migrate_down_00100()
BEGIN
  drop table if exists mw_group_email_options;
  drop table if exists mw_group_email_groups;
  drop table if exists mw_compliance_level_ids;
  drop table if exists mw_compliance_levels;
  drop table if exists mw_group_email;
  drop table if exists mw_group_email_compliance;
  drop table if exists mw_group_email_abuse_report;
  drop table if exists mw_group_email_log;
  drop table if exists mw_group_email_bounce_log;
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

  CREATE TABLE `mw_group_email_options` (
    `id` int(11) NOT NULL,
    `groups_at_once` int(11) DEFAULT NULL,
    `emails_at_once` int(11) DEFAULT NULL,
    `change_server_at` int(11) DEFAULT NULL,
    `compliance_limit` int(11) DEFAULT NULL,
    `compliance_abuse_range` float(9,3) DEFAULT NULL,
    `compliance_unsub_range` float(9,3) DEFAULT NULL,
    `compliance_bounce_range` float(9,3) DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

    INSERT INTO `mw_group_email_options`
    (`id`, `groups_at_once`, `emails_at_once`, `change_server_at`, `compliance_limit` )
    VALUES
    (1, 25, 100, 1000, 1000);

 CREATE TABLE `mw_group_email_groups` (
   `group_email_id` int(11) NOT NULL AUTO_INCREMENT,
   `group_email_uid` char(13) NOT NULL,
   `customer_id` int(11) NOT NULL,
   `send_at` datetime DEFAULT NULL,
   `status` varchar(45) DEFAULT NULL,
   `finished_at` datetime DEFAULT NULL,
   `emails_sent` int(11) DEFAULT NULL,
   `date_added` date DEFAULT NULL,
   PRIMARY KEY (`group_email_id`),
   UNIQUE KEY `group_email_uid_UNIQUE` (`group_email_uid`)
 ) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=latin1;

  CREATE TABLE `mw_compliance_levels` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `threshold` float(10,2) DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

  INSERT INTO `mw_compliance_levels` (`id`, `threshold`)
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



  CREATE TABLE `mw_group_email` (
    `email_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `email_uid` char(13) NOT NULL,
    `customer_id` int(11) DEFAULT NULL,
    `group_email_id` int(11) DEFAULT NULL,
    `to_email` varchar(150) NOT NULL,
    `to_name` varchar(150) NOT NULL,
    `from_email` varchar(150) NOT NULL,
    `from_name` varchar(150) NOT NULL,
    `reply_to_email` varchar(150) DEFAULT NULL,
    `reply_to_name` varchar(150) DEFAULT NULL,
    `subject` varchar(255) NOT NULL,
    `body` longblob NOT NULL,
    `plain_text` longblob NOT NULL,
    `priority` tinyint(1) NOT NULL DEFAULT '5',
    `retries` tinyint(1) NOT NULL DEFAULT '0',
    `max_retries` tinyint(1) NOT NULL DEFAULT '3',
    `send_at` datetime NOT NULL,
    `status` char(15) NOT NULL DEFAULT 'unsent',
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`email_id`),
    UNIQUE KEY `email_uid_UNIQUE` (`email_uid`),
    KEY `customer_id` (`customer_id`),
    KEY `group_email_id` (`group_email_id`),
    CONSTRAINT `mw_group_email_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`),
    CONSTRAINT `mw_group_email_ibfk_2` FOREIGN KEY (`group_email_id`) REFERENCES `mw_customer_group` (`group_id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=334 DEFAULT CHARSET=utf8;

  CREATE TABLE `mw_group_email_compliance` (
    `group_email_id` int(11) NOT NULL,
    `compliance_level_type_id` int(11) DEFAULT NULL,
    `last_processed_id` bigint(8) DEFAULT NULL,
    `compliance_round` int(11) DEFAULT NULL,
    `compliance_approval_user_id` int(11) DEFAULT NULL,
    `date_added` datetime DEFAULT NULL,
    `last_updated` datetime DEFAULT NULL,
    `offset` int(11) NOT NULL,
    `compliance_status` varchar(45) NOT NULL,
    PRIMARY KEY (`group_email_id`),
    KEY `compliance_level_type_id` (`compliance_level_type_id`),
    KEY `compliance_approval_user_id` (`compliance_approval_user_id`),
    CONSTRAINT `mw_group_email_compliance_ibfk_3` FOREIGN KEY (`group_email_id`) REFERENCES `mw_group_email_groups` (`group_email_id`),
    CONSTRAINT `mw_group_email_compliance_ibfk_1` FOREIGN KEY (`compliance_level_type_id`) REFERENCES `mw_compliance_levels` (`id`),
    CONSTRAINT `mw_group_email_compliance_ibfk_2` FOREIGN KEY (`compliance_approval_user_id`) REFERENCES `mw_user` (`user_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

  CREATE TABLE `mw_group_email_abuse_report` (
    `report_id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) DEFAULT NULL,
    `customer_info` varchar(255) NOT NULL,
    `campaign_info` varchar(255) NOT NULL,
    `list_info` varchar(255) NOT NULL,
    `subscriber_info` varchar(255) NOT NULL,
    `reason` varchar(255) NOT NULL,
    `log` varchar(255) DEFAULT NULL,
    `date_added` datetime NOT NULL,
    `last_updated` datetime NOT NULL,
    PRIMARY KEY (`report_id`),
    KEY `customer_id` (`customer_id`),
    CONSTRAINT `mw_group_email_abuse_report_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

  CREATE TABLE `mw_group_email_log` (
    `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `email_id` bigint(20) NOT NULL,
    `message` text NOT NULL,
    `date_added` datetime NOT NULL,
    PRIMARY KEY (`log_id`),
    CONSTRAINT `mw_group_email_log_ibfk_2` FOREIGN KEY (`email_id`) REFERENCES `mw_group_email` (`email_id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8;

 CREATE TABLE `mw_group_email_bounce_log` (
   `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
   `customer_id` int(11) NOT NULL,
   `group_email_id` int(11) NOT NULL,
   `email` varchar(45) DEFAULT NULL,
   `message` text,
   `bounce_type` enum('hard','soft') NOT NULL DEFAULT 'hard',
   `processed` enum('yes','no') NOT NULL DEFAULT 'no',
   `date_added` datetime NOT NULL,
   PRIMARY KEY (`log_id`),
   UNIQUE KEY `group_uid` (`group_email_id`),
   KEY `customer_id` (`customer_id`),
   CONSTRAINT `mw_group_email_bounce_log_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`)
 ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

CREATE TABLE `mw_group_email_unsubscribe` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `group_email_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` char(15) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_email_id` (`group_email_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `mw_group_email_unsubscribe_ibfk_1` FOREIGN KEY (`group_email_id`) REFERENCES `mw_group_email_groups` (`group_email_id`),
  CONSTRAINT `mw_group_email_unsubscribe_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `mw_group_email_compliance_score` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bounce_report` float(9,2) DEFAULT NULL,
  `abuse_report` float(9,2) DEFAULT NULL,
  `unsubscribe_report` float(9,2) DEFAULT NULL,
  `score` float(9,3) DEFAULT NULL,
  `result` varchar(45) DEFAULT NULL,
  `date_added` datetime DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

END
$$
FLUSH PRIVILEGES;
$$
CALL migrate_up_00100();
$$
DROP PROCEDURE IF EXISTS migrate_down_00100;
$$