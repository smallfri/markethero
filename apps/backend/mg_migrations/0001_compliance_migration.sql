DELIMITER $$

DROP PROCEDURE IF EXISTS migrate_down_00001;
$$

CREATE PROCEDURE migrate_down_00001()
BEGIN

	IF EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'mw_customer' AND CONSTRAINT_NAME = 'fk_compliance_level') THEN
    ALTER TABLE mw_customer
    DROP FOREIGN KEY fk_compliance_level;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'mw_campaign_compliance' AND CONSTRAINT_NAME = 'fk_approval_user_id') THEN
    ALTER TABLE mw_campaign_compliance
    DROP FOREIGN KEY fk_approval_user_id;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'mw_campaign_compliance' AND CONSTRAINT_NAME = 'fk_campaign_id') THEN
    ALTER TABLE mw_campaign_compliance
    DROP FOREIGN KEY fk_campaign_id;
  END IF;
  IF EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'mw_campaign_compliance' AND CONSTRAINT_NAME = 'fk_compliance_level1') THEN
    ALTER TABLE mw_campaign_compliance
    DROP FOREIGN KEY fk_compliance_level1;
  END IF;

	IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = database() AND table_name = 'mw_customer' AND column_name = 'compliance_levels_id') THEN
    alter table mw_customer
    DROP COLUMN compliance_levels_id;
  END IF;

  DROP TABLE IF EXISTS `mw_campaign_compliance`
	$$

	DROP PROCEDURE IF EXISTS `mw_compliance_levels`
	$$

END;
$$

GRANT EXECUTE ON PROCEDURE migrate_down_00001 TO CURRENT_USER();
$$
FLUSH PRIVILEGES;
$$
CALL migrate_down_00001();
$$
DROP PROCEDURE IF EXISTS migrate_down_00001;
$$
DROP PROCEDURE IF EXISTS migrate_up_00001;
$$
CREATE PROCEDURE migrate_up_00001()
BEGIN

	alter table mw_customer
	add column compliance_levels_id INT NOT NULL DEFAULT 2;

	CREATE TABLE `mw_compliance_levels` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `threshold` float(10,2) DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

	CREATE TABLE `mw_campaign_compliance` (
	  `campaign_id` int(11) NOT NULL,
	  `compliance_levels_id` int(11) DEFAULT NULL,
	  `last_processed_id` bigint(8) DEFAULT NULL,
	  `compliance_round` int(11) DEFAULT NULL,
	  `compliance_approval_user_id` int(11) DEFAULT NULL,
	  `date_added` datetime DEFAULT NULL,
	  `last_updated` datetime DEFAULT NULL,
	  PRIMARY KEY (`campaign_id`),
	  KEY `fk_compliance_level1` (`compliance_levels_id`),
	  KEY `fk_approval_user_id` (`compliance_approval_user_id`),
	  CONSTRAINT `fk_approval_user_id` FOREIGN KEY (`compliance_approval_user_id`) REFERENCES `mw_user` (`user_id`),
	  CONSTRAINT `fk_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`),
	  CONSTRAINT `fk_compliance_level1` FOREIGN KEY (`compliance_levels_id`) REFERENCES `mw_compliance_levels` (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;

END;
$$

GRANT EXECUTE ON PROCEDURE migrate_up_00001 TO CURRENT_USER();
$$
CALL migrate_up_00001();
$$
DROP PROCEDURE IF EXISTS migrate_up_00001;
$$

