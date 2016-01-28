-- phpMyAdmin SQL Dump
-- version 4.2.10
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Jan 25, 2016 at 04:24 PM
-- Server version: 5.5.38
-- PHP Version: 5.6.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `market_hero`
--
CREATE DATABASE IF NOT EXISTS `market_hero` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `market_hero`;

-- --------------------------------------------------------

--
-- Table structure for table `mw_article`
--

DROP TABLE IF EXISTS `mw_article`;
CREATE TABLE `mw_article` (
`article_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `status` char(15) NOT NULL DEFAULT 'published',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_article_category`
--

DROP TABLE IF EXISTS `mw_article_category`;
CREATE TABLE `mw_article_category` (
`category_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(250) NOT NULL,
  `description` text,
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_article_to_category`
--

DROP TABLE IF EXISTS `mw_article_to_category`;
CREATE TABLE `mw_article_to_category` (
  `article_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_backup_manager_snapshot`
--

DROP TABLE IF EXISTS `mw_backup_manager_snapshot`;
CREATE TABLE `mw_backup_manager_snapshot` (
`snapshot_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `size` int(11) NOT NULL,
  `meta_data` blob,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_bounce_server`
--

DROP TABLE IF EXISTS `mw_bounce_server`;
CREATE TABLE `mw_bounce_server` (
`server_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `hostname` varchar(150) NOT NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `service` enum('imap','pop3') NOT NULL DEFAULT 'imap',
  `port` int(5) NOT NULL DEFAULT '143',
  `protocol` enum('ssl','tls','notls') NOT NULL DEFAULT 'notls',
  `validate_ssl` enum('yes','no') NOT NULL DEFAULT 'no',
  `locked` enum('yes','no') NOT NULL DEFAULT 'no',
  `disable_authenticator` varchar(50) DEFAULT NULL,
  `search_charset` varchar(50) NOT NULL DEFAULT 'UTF-8',
  `delete_all_messages` enum('yes','no') NOT NULL DEFAULT 'no',
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign`
--

DROP TABLE IF EXISTS `mw_campaign`;
CREATE TABLE `mw_campaign` (
`campaign_id` int(11) NOT NULL,
  `campaign_uid` char(13) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `segment_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `type` char(15) NOT NULL DEFAULT 'regular',
  `name` varchar(255) NOT NULL,
  `from_name` varchar(100) DEFAULT NULL,
  `from_email` varchar(100) NOT NULL,
  `to_name` varchar(255) NOT NULL DEFAULT '[EMAIL]',
  `reply_to` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `send_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `delivery_logs_archived` enum('yes','no') NOT NULL DEFAULT 'no',
  `status` char(15) NOT NULL DEFAULT 'draft',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2171 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_abuse_report`
--

DROP TABLE IF EXISTS `mw_campaign_abuse_report`;
CREATE TABLE `mw_campaign_abuse_report` (
`report_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `list_id` int(11) DEFAULT NULL,
  `subscriber_id` int(11) DEFAULT NULL,
  `customer_info` varchar(255) NOT NULL,
  `campaign_info` varchar(255) NOT NULL,
  `list_info` varchar(255) NOT NULL,
  `subscriber_info` varchar(255) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `log` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=459 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_attachment`
--

DROP TABLE IF EXISTS `mw_campaign_attachment`;
CREATE TABLE `mw_campaign_attachment` (
`attachment_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `size` int(11) NOT NULL DEFAULT '0',
  `extension` char(10) NOT NULL,
  `mime_type` varchar(50) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_bounce_log`
--

DROP TABLE IF EXISTS `mw_campaign_bounce_log`;
CREATE TABLE `mw_campaign_bounce_log` (
`log_id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `message` text,
  `bounce_type` enum('hard','soft') NOT NULL DEFAULT 'hard',
  `processed` enum('yes','no') NOT NULL DEFAULT 'no',
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=18753 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_delivery_log`
--

DROP TABLE IF EXISTS `mw_campaign_delivery_log`;
CREATE TABLE `mw_campaign_delivery_log` (
`log_id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `message` text,
  `processed` enum('yes','no') NOT NULL DEFAULT 'no',
  `retries` int(1) NOT NULL DEFAULT '0',
  `max_retries` int(1) NOT NULL DEFAULT '3',
  `email_message_id` varchar(255) DEFAULT NULL,
  `status` char(15) NOT NULL DEFAULT 'success',
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=12957838 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_delivery_log_archive`
--

DROP TABLE IF EXISTS `mw_campaign_delivery_log_archive`;
CREATE TABLE `mw_campaign_delivery_log_archive` (
`log_id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `message` text,
  `processed` enum('yes','no') NOT NULL DEFAULT 'no',
  `retries` int(1) NOT NULL DEFAULT '0',
  `max_retries` int(1) NOT NULL DEFAULT '3',
  `email_message_id` varchar(255) DEFAULT NULL,
  `status` char(15) NOT NULL DEFAULT 'success',
  `date_added` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_forward_friend`
--

DROP TABLE IF EXISTS `mw_campaign_forward_friend`;
CREATE TABLE `mw_campaign_forward_friend` (
`forward_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) DEFAULT NULL,
  `to_email` varchar(150) NOT NULL,
  `to_name` varchar(150) NOT NULL,
  `from_email` varchar(150) NOT NULL,
  `from_name` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ip_address` char(15) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_group`
--

DROP TABLE IF EXISTS `mw_campaign_group`;
CREATE TABLE `mw_campaign_group` (
`group_id` int(11) NOT NULL,
  `group_uid` char(13) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_open_action_list_field`
--

DROP TABLE IF EXISTS `mw_campaign_open_action_list_field`;
CREATE TABLE `mw_campaign_open_action_list_field` (
`action_id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=216 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_open_action_subscriber`
--

DROP TABLE IF EXISTS `mw_campaign_open_action_subscriber`;
CREATE TABLE `mw_campaign_open_action_subscriber` (
`action_id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `action` char(5) NOT NULL DEFAULT 'copy',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=286 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_option`
--

DROP TABLE IF EXISTS `mw_campaign_option`;
CREATE TABLE `mw_campaign_option` (
  `campaign_id` int(11) NOT NULL,
  `url_tracking` enum('yes','no') NOT NULL DEFAULT 'no',
  `json_feed` enum('yes','no') NOT NULL DEFAULT 'no',
  `xml_feed` enum('yes','no') NOT NULL DEFAULT 'no',
  `embed_images` enum('yes','no') NOT NULL DEFAULT 'no',
  `plain_text_email` enum('yes','no') NOT NULL DEFAULT 'yes',
  `autoresponder_event` char(20) NOT NULL DEFAULT 'AFTER-SUBSCRIBE',
  `autoresponder_time_unit` varchar(6) NOT NULL DEFAULT 'day',
  `autoresponder_time_value` int(11) NOT NULL DEFAULT '0',
  `autoresponder_open_campaign_id` int(11) DEFAULT NULL,
  `autoresponder_include_imported` enum('yes','no') NOT NULL DEFAULT 'no',
  `email_stats` varchar(255) NOT NULL,
  `send_referral_url` int(11) DEFAULT NULL,
  `regular_open_unopen_action` char(10) DEFAULT NULL,
  `regular_open_unopen_campaign_id` int(11) DEFAULT NULL,
  `cronjob` varchar(255) DEFAULT NULL,
  `cronjob_enabled` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_template`
--

DROP TABLE IF EXISTS `mw_campaign_template`;
CREATE TABLE `mw_campaign_template` (
`template_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `customer_template_id` int(11) DEFAULT NULL,
  `content` longtext NOT NULL,
  `inline_css` enum('yes','no') NOT NULL DEFAULT 'no',
  `minify` enum('yes','no') NOT NULL DEFAULT 'no',
  `plain_text` text,
  `only_plain_text` enum('yes','no') NOT NULL DEFAULT 'no',
  `auto_plain_text` enum('yes','no') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB AUTO_INCREMENT=2125 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_template_url_action_list_field`
--

DROP TABLE IF EXISTS `mw_campaign_template_url_action_list_field`;
CREATE TABLE `mw_campaign_template_url_action_list_field` (
`url_id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `field_value` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_template_url_action_subscriber`
--

DROP TABLE IF EXISTS `mw_campaign_template_url_action_subscriber`;
CREATE TABLE `mw_campaign_template_url_action_subscriber` (
`url_id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `action` char(5) NOT NULL DEFAULT 'copy',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_temporary_source`
--

DROP TABLE IF EXISTS `mw_campaign_temporary_source`;
CREATE TABLE `mw_campaign_temporary_source` (
`source_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `segment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_to_delivery_server`
--

DROP TABLE IF EXISTS `mw_campaign_to_delivery_server`;
CREATE TABLE `mw_campaign_to_delivery_server` (
  `campaign_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_track_open`
--

DROP TABLE IF EXISTS `mw_campaign_track_open`;
CREATE TABLE `mw_campaign_track_open` (
`id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `location_id` bigint(20) DEFAULT NULL,
  `ip_address` char(15) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=864549 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_track_unsubscribe`
--

DROP TABLE IF EXISTS `mw_campaign_track_unsubscribe`;
CREATE TABLE `mw_campaign_track_unsubscribe` (
`id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `location_id` bigint(20) DEFAULT NULL,
  `ip_address` char(15) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=18366 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_track_url`
--

DROP TABLE IF EXISTS `mw_campaign_track_url`;
CREATE TABLE `mw_campaign_track_url` (
`id` bigint(20) NOT NULL,
  `url_id` bigint(20) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `location_id` bigint(20) DEFAULT NULL,
  `ip_address` char(15) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=197329 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_campaign_url`
--

DROP TABLE IF EXISTS `mw_campaign_url`;
CREATE TABLE `mw_campaign_url` (
`url_id` bigint(20) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `hash` char(40) NOT NULL,
  `destination` text NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6395 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_company_type`
--

DROP TABLE IF EXISTS `mw_company_type`;
CREATE TABLE `mw_company_type` (
`type_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_country`
--

DROP TABLE IF EXISTS `mw_country`;
CREATE TABLE `mw_country` (
`country_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` char(3) NOT NULL,
  `status` char(10) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_currency`
--

DROP TABLE IF EXISTS `mw_currency`;
CREATE TABLE `mw_currency` (
`currency_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` char(3) NOT NULL,
  `value` decimal(15,8) NOT NULL DEFAULT '0.00000000',
  `is_default` enum('yes','no') NOT NULL DEFAULT 'no',
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer`
--

DROP TABLE IF EXISTS `mw_customer`;
CREATE TABLE `mw_customer` (
`customer_id` int(11) NOT NULL,
  `customer_uid` char(13) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` char(34) NOT NULL,
  `timezone` varchar(50) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `hourly_quota` int(11) NOT NULL DEFAULT '0',
  `removable` enum('yes','no') NOT NULL DEFAULT 'yes',
  `confirmation_key` char(40) DEFAULT NULL,
  `oauth_uid` bigint(20) DEFAULT NULL,
  `oauth_provider` char(10) DEFAULT NULL,
  `status` char(15) NOT NULL DEFAULT 'inactive',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=185 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_action_log`
--

DROP TABLE IF EXISTS `mw_customer_action_log`;
CREATE TABLE `mw_customer_action_log` (
`log_id` bigint(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'info',
  `reference_id` int(11) NOT NULL DEFAULT '0',
  `reference_relation_id` int(11) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=31965 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_api_key`
--

DROP TABLE IF EXISTS `mw_customer_api_key`;
CREATE TABLE `mw_customer_api_key` (
`key_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `public` char(40) NOT NULL,
  `private` char(40) NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_auto_login_token`
--

DROP TABLE IF EXISTS `mw_customer_auto_login_token`;
CREATE TABLE `mw_customer_auto_login_token` (
`token_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `token` char(40) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=28047 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_company`
--

DROP TABLE IF EXISTS `mw_customer_company`;
CREATE TABLE `mw_customer_company` (
`company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `type_id` int(11) DEFAULT NULL,
  `country_id` int(11) NOT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address_1` varchar(255) NOT NULL,
  `address_2` varchar(255) DEFAULT NULL,
  `zone_name` varchar(150) DEFAULT NULL,
  `city` varchar(150) NOT NULL,
  `zip_code` char(10) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `fax` varchar(32) DEFAULT NULL,
  `vat_number` varchar(100) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_email_template`
--

DROP TABLE IF EXISTS `mw_customer_email_template`;
CREATE TABLE `mw_customer_email_template` (
`template_id` int(11) NOT NULL,
  `template_uid` char(13) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `content_hash` char(40) NOT NULL,
  `create_screenshot` enum('yes','no') NOT NULL DEFAULT 'yes',
  `screenshot` varchar(255) DEFAULT NULL,
  `inline_css` enum('yes','no') NOT NULL DEFAULT 'no',
  `minify` enum('yes','no') NOT NULL DEFAULT 'no',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_group`
--

DROP TABLE IF EXISTS `mw_customer_group`;
CREATE TABLE `mw_customer_group` (
`group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_default` enum('yes','no') NOT NULL DEFAULT 'no',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_group_option`
--

DROP TABLE IF EXISTS `mw_customer_group_option`;
CREATE TABLE `mw_customer_group_option` (
`option_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `is_serialized` tinyint(1) NOT NULL DEFAULT '0',
  `value` longblob,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=401 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_password_reset`
--

DROP TABLE IF EXISTS `mw_customer_password_reset`;
CREATE TABLE `mw_customer_password_reset` (
`request_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `reset_key` char(40) NOT NULL,
  `ip_address` char(15) DEFAULT NULL,
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_quota_mark`
--

DROP TABLE IF EXISTS `mw_customer_quota_mark`;
CREATE TABLE `mw_customer_quota_mark` (
`mark_id` bigint(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=254 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_customer_referral_url`
--

DROP TABLE IF EXISTS `mw_customer_referral_url`;
CREATE TABLE `mw_customer_referral_url` (
  `customer_id` int(11) NOT NULL,
  `jvz_referral_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mw_delivery_server`
--

DROP TABLE IF EXISTS `mw_delivery_server`;
CREATE TABLE `mw_delivery_server` (
`server_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `bounce_server_id` int(11) DEFAULT NULL,
  `tracking_domain_id` int(11) DEFAULT NULL,
  `type` char(20) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `hostname` varchar(150) NOT NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `port` int(5) DEFAULT '25',
  `protocol` char(10) DEFAULT NULL,
  `timeout` int(3) DEFAULT '30',
  `from_email` varchar(150) NOT NULL,
  `from_name` varchar(150) DEFAULT NULL,
  `reply_to_email` varchar(150) DEFAULT NULL,
  `probability` int(3) NOT NULL DEFAULT '100',
  `hourly_quota` int(11) NOT NULL DEFAULT '0',
  `meta_data` blob,
  `confirmation_key` char(40) DEFAULT NULL,
  `locked` enum('yes','no') NOT NULL DEFAULT 'no',
  `use_for` char(15) NOT NULL DEFAULT 'all',
  `use_queue` enum('yes','no') NOT NULL DEFAULT 'no',
  `signing_enabled` enum('yes','no') NOT NULL DEFAULT 'yes',
  `force_from` varchar(50) NOT NULL DEFAULT 'never',
  `force_reply_to` varchar(50) NOT NULL DEFAULT 'never',
  `status` char(15) NOT NULL DEFAULT 'inactive',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=237 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_delivery_server_domain_policy`
--

DROP TABLE IF EXISTS `mw_delivery_server_domain_policy`;
CREATE TABLE `mw_delivery_server_domain_policy` (
`domain_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `domain` varchar(64) NOT NULL,
  `policy` char(15) NOT NULL DEFAULT 'allow',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_delivery_server_to_customer_group`
--

DROP TABLE IF EXISTS `mw_delivery_server_to_customer_group`;
CREATE TABLE `mw_delivery_server_to_customer_group` (
  `server_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_delivery_server_usage_log`
--

DROP TABLE IF EXISTS `mw_delivery_server_usage_log`;
CREATE TABLE `mw_delivery_server_usage_log` (
`log_id` bigint(20) NOT NULL,
  `server_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `delivery_for` char(15) NOT NULL DEFAULT 'system',
  `customer_countable` enum('yes','no') NOT NULL DEFAULT 'yes',
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2481003 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_email_blacklist`
--

DROP TABLE IF EXISTS `mw_email_blacklist`;
CREATE TABLE `mw_email_blacklist` (
`email_id` bigint(20) NOT NULL,
  `subscriber_id` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `reason` text NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=28723 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_feedback_loop_server`
--

DROP TABLE IF EXISTS `mw_feedback_loop_server`;
CREATE TABLE `mw_feedback_loop_server` (
`server_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `hostname` varchar(150) NOT NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `service` enum('imap','pop3') NOT NULL DEFAULT 'imap',
  `port` int(5) NOT NULL DEFAULT '143',
  `protocol` enum('ssl','tls','notls') NOT NULL DEFAULT 'notls',
  `validate_ssl` enum('yes','no') NOT NULL DEFAULT 'no',
  `locked` enum('yes','no') NOT NULL DEFAULT 'no',
  `disable_authenticator` varchar(50) DEFAULT NULL,
  `search_charset` varchar(50) NOT NULL DEFAULT 'UTF-8',
  `delete_all_messages` enum('yes','no') NOT NULL DEFAULT 'no',
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_guest_fail_attempt`
--

DROP TABLE IF EXISTS `mw_guest_fail_attempt`;
CREATE TABLE `mw_guest_fail_attempt` (
`attempt_id` bigint(20) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `ip_address_hash` char(32) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `place` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=649 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_ip_location`
--

DROP TABLE IF EXISTS `mw_ip_location`;
CREATE TABLE `mw_ip_location` (
`location_id` bigint(20) NOT NULL,
  `ip_address` char(15) NOT NULL,
  `country_code` char(3) NOT NULL,
  `country_name` varchar(150) NOT NULL,
  `zone_name` varchar(150) DEFAULT NULL,
  `city_name` varchar(150) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_language`
--

DROP TABLE IF EXISTS `mw_language`;
CREATE TABLE `mw_language` (
`language_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `language_code` char(2) NOT NULL,
  `region_code` char(2) DEFAULT NULL,
  `is_default` enum('yes','no') NOT NULL DEFAULT 'no',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list`
--

DROP TABLE IF EXISTS `mw_list`;
CREATE TABLE `mw_list` (
`list_id` int(11) NOT NULL,
  `list_uid` char(13) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `visibility` char(15) NOT NULL DEFAULT 'public',
  `opt_in` enum('double','single') NOT NULL DEFAULT 'double',
  `opt_out` enum('double','single') NOT NULL DEFAULT 'single',
  `merged` enum('yes','no') NOT NULL DEFAULT 'no',
  `welcome_email` enum('yes','no') NOT NULL DEFAULT 'no',
  `subscriber_404_redirect` varchar(255) DEFAULT NULL,
  `meta_data` blob,
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=753 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_company`
--

DROP TABLE IF EXISTS `mw_list_company`;
CREATE TABLE `mw_list_company` (
  `list_id` int(11) NOT NULL,
  `type_id` int(11) DEFAULT NULL,
  `country_id` int(11) NOT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `address_1` varchar(255) NOT NULL,
  `address_2` varchar(255) DEFAULT NULL,
  `zone_name` varchar(150) DEFAULT NULL,
  `city` varchar(150) NOT NULL,
  `zip_code` char(10) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `address_format` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_customer_notification`
--

DROP TABLE IF EXISTS `mw_list_customer_notification`;
CREATE TABLE `mw_list_customer_notification` (
  `list_id` int(11) NOT NULL,
  `daily` enum('yes','no') NOT NULL DEFAULT 'no',
  `subscribe` enum('yes','no') NOT NULL DEFAULT 'no',
  `unsubscribe` enum('yes','no') NOT NULL DEFAULT 'no',
  `daily_to` varchar(255) DEFAULT NULL,
  `subscribe_to` varchar(255) DEFAULT NULL,
  `unsubscribe_to` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_default`
--

DROP TABLE IF EXISTS `mw_list_default`;
CREATE TABLE `mw_list_default` (
  `list_id` int(11) NOT NULL,
  `from_name` varchar(100) NOT NULL,
  `from_email` varchar(100) NOT NULL,
  `reply_to` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_field`
--

DROP TABLE IF EXISTS `mw_list_field`;
CREATE TABLE `mw_list_field` (
`field_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `tag` varchar(50) NOT NULL,
  `default_value` varchar(255) DEFAULT NULL,
  `help_text` varchar(255) DEFAULT NULL,
  `required` enum('yes','no') NOT NULL DEFAULT 'no',
  `visibility` enum('visible','hidden') NOT NULL DEFAULT 'visible',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4989 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_field_option`
--

DROP TABLE IF EXISTS `mw_list_field_option`;
CREATE TABLE `mw_list_field_option` (
`option_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  `is_default` enum('yes','no') NOT NULL DEFAULT 'no',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_field_type`
--

DROP TABLE IF EXISTS `mw_list_field_type`;
CREATE TABLE `mw_list_field_type` (
`type_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `identifier` varchar(50) NOT NULL,
  `class_alias` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_field_value`
--

DROP TABLE IF EXISTS `mw_list_field_value`;
CREATE TABLE `mw_list_field_value` (
`value_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=30263632 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_form_custom_asset`
--

DROP TABLE IF EXISTS `mw_list_form_custom_asset`;
CREATE TABLE `mw_list_form_custom_asset` (
`asset_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `asset_url` text NOT NULL,
  `asset_type` varchar(10) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_form_custom_redirect`
--

DROP TABLE IF EXISTS `mw_list_form_custom_redirect`;
CREATE TABLE `mw_list_form_custom_redirect` (
`redirect_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `timeout` int(11) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_form_custom_webhook`
--

DROP TABLE IF EXISTS `mw_list_form_custom_webhook`;
CREATE TABLE `mw_list_form_custom_webhook` (
`webhook_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `request_url` text NOT NULL,
  `request_type` varchar(10) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_page`
--

DROP TABLE IF EXISTS `mw_list_page`;
CREATE TABLE `mw_list_page` (
  `list_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `meta_data` longblob,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_page_type`
--

DROP TABLE IF EXISTS `mw_list_page_type`;
CREATE TABLE `mw_list_page_type` (
`type_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `content` longtext NOT NULL,
  `full_html` enum('yes','no') NOT NULL DEFAULT 'no',
  `meta_data` longblob,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_segment`
--

DROP TABLE IF EXISTS `mw_list_segment`;
CREATE TABLE `mw_list_segment` (
`segment_id` int(11) NOT NULL,
  `segment_uid` char(13) NOT NULL,
  `list_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `operator_match` enum('any','all') NOT NULL DEFAULT 'any',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_segment_condition`
--

DROP TABLE IF EXISTS `mw_list_segment_condition`;
CREATE TABLE `mw_list_segment_condition` (
`condition_id` int(11) NOT NULL,
  `segment_id` int(11) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_segment_operator`
--

DROP TABLE IF EXISTS `mw_list_segment_operator`;
CREATE TABLE `mw_list_segment_operator` (
`operator_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_subscriber`
--

DROP TABLE IF EXISTS `mw_list_subscriber`;
CREATE TABLE `mw_list_subscriber` (
`subscriber_id` int(11) NOT NULL,
  `subscriber_uid` char(13) NOT NULL,
  `list_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` char(15) DEFAULT NULL,
  `source` enum('web','api','import') NOT NULL DEFAULT 'web',
  `status` char(15) NOT NULL DEFAULT 'unconfirmed',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4795519 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_list_subscriber_action`
--

DROP TABLE IF EXISTS `mw_list_subscriber_action`;
CREATE TABLE `mw_list_subscriber_action` (
`action_id` int(11) NOT NULL,
  `source_list_id` int(11) NOT NULL,
  `source_action` char(15) NOT NULL DEFAULT 'subscribe',
  `target_list_id` int(11) NOT NULL,
  `target_action` char(15) NOT NULL DEFAULT 'unsubscribe'
) ENGINE=InnoDB AUTO_INCREMENT=736 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_option`
--

DROP TABLE IF EXISTS `mw_option`;
CREATE TABLE `mw_option` (
  `category` varchar(150) NOT NULL,
  `key` varchar(150) NOT NULL,
  `value` longblob NOT NULL,
  `is_serialized` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_price_plan`
--

DROP TABLE IF EXISTS `mw_price_plan`;
CREATE TABLE `mw_price_plan` (
`plan_id` int(11) NOT NULL,
  `plan_uid` char(13) NOT NULL,
  `group_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `description` text NOT NULL,
  `recommended` enum('yes','no') NOT NULL DEFAULT 'no',
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_price_plan_order`
--

DROP TABLE IF EXISTS `mw_price_plan_order`;
CREATE TABLE `mw_price_plan_order` (
`order_id` int(11) NOT NULL,
  `order_uid` char(13) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `promo_code_id` int(11) DEFAULT NULL,
  `tax_id` int(11) DEFAULT NULL,
  `currency_id` int(11) NOT NULL,
  `subtotal` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `tax_percent` decimal(4,2) NOT NULL DEFAULT '0.00',
  `tax_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `discount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `status` char(15) NOT NULL DEFAULT 'incomplete',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_price_plan_order_note`
--

DROP TABLE IF EXISTS `mw_price_plan_order_note`;
CREATE TABLE `mw_price_plan_order_note` (
`note_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `note` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_price_plan_order_transaction`
--

DROP TABLE IF EXISTS `mw_price_plan_order_transaction`;
CREATE TABLE `mw_price_plan_order_transaction` (
`transaction_id` int(11) NOT NULL,
  `transaction_uid` char(13) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_gateway_name` varchar(50) NOT NULL,
  `payment_gateway_transaction_id` varchar(100) NOT NULL,
  `payment_gateway_response` text NOT NULL,
  `status` char(15) NOT NULL DEFAULT 'failed',
  `date_added` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_price_plan_promo_code`
--

DROP TABLE IF EXISTS `mw_price_plan_promo_code`;
CREATE TABLE `mw_price_plan_promo_code` (
`promo_code_id` int(11) NOT NULL,
  `code` char(15) NOT NULL,
  `type` enum('percentage','fixed amount') NOT NULL DEFAULT 'fixed amount',
  `discount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `total_usage` tinyint(4) NOT NULL DEFAULT '0',
  `customer_usage` tinyint(4) NOT NULL DEFAULT '0',
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_sending_domain`
--

DROP TABLE IF EXISTS `mw_sending_domain`;
CREATE TABLE `mw_sending_domain` (
`domain_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `name` varchar(64) NOT NULL,
  `dkim_private_key` text NOT NULL,
  `dkim_public_key` text NOT NULL,
  `locked` enum('yes','no') NOT NULL DEFAULT 'no',
  `verified` enum('yes','no') NOT NULL DEFAULT 'no',
  `signing_enabled` enum('yes','no') NOT NULL DEFAULT 'yes',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_session`
--

DROP TABLE IF EXISTS `mw_session`;
CREATE TABLE `mw_session` (
  `id` char(32) NOT NULL,
  `expire` int(11) DEFAULT NULL,
  `data` longblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_support_ticket`
--

DROP TABLE IF EXISTS `mw_support_ticket`;
CREATE TABLE `mw_support_ticket` (
`ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `priority_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `replies_count` int(11) NOT NULL DEFAULT '0',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `ip_address` varchar(15) NOT NULL,
  `status` char(30) NOT NULL DEFAULT 'open',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_support_ticket_department`
--

DROP TABLE IF EXISTS `mw_support_ticket_department`;
CREATE TABLE `mw_support_ticket_department` (
`department_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_support_ticket_priority`
--

DROP TABLE IF EXISTS `mw_support_ticket_priority`;
CREATE TABLE `mw_support_ticket_priority` (
`priority_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_support_ticket_reply`
--

DROP TABLE IF EXISTS `mw_support_ticket_reply`;
CREATE TABLE `mw_support_ticket_reply` (
`reply_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(15) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_tag_registry`
--

DROP TABLE IF EXISTS `mw_tag_registry`;
CREATE TABLE `mw_tag_registry` (
`tag_id` int(11) NOT NULL,
  `tag` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_tax`
--

DROP TABLE IF EXISTS `mw_tax`;
CREATE TABLE `mw_tax` (
`tax_id` int(11) NOT NULL,
  `country_id` int(11) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `percent` decimal(4,2) NOT NULL DEFAULT '0.00',
  `is_global` enum('yes','no') NOT NULL DEFAULT 'no',
  `status` char(15) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_trace_logs`
--

DROP TABLE IF EXISTS `mw_trace_logs`;
CREATE TABLE `mw_trace_logs` (
`id` int(11) NOT NULL,
  `execution` decimal(9,4) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `log` text,
  `delteted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=125781 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mw_tracking_domain`
--

DROP TABLE IF EXISTS `mw_tracking_domain`;
CREATE TABLE `mw_tracking_domain` (
`domain_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_transactional_email`
--

DROP TABLE IF EXISTS `mw_transactional_email`;
CREATE TABLE `mw_transactional_email` (
`email_id` bigint(20) NOT NULL,
  `email_uid` char(13) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
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
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=444 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_transactional_email_log`
--

DROP TABLE IF EXISTS `mw_transactional_email_log`;
CREATE TABLE `mw_transactional_email_log` (
`log_id` bigint(20) NOT NULL,
  `email_id` bigint(20) NOT NULL,
  `message` text NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=457 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_user`
--

DROP TABLE IF EXISTS `mw_user`;
CREATE TABLE `mw_user` (
`user_id` int(11) NOT NULL,
  `user_uid` char(13) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(34) NOT NULL,
  `timezone` varchar(50) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `removable` enum('yes','no') NOT NULL DEFAULT 'yes',
  `status` char(15) NOT NULL DEFAULT 'inactive',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_user_auto_login_token`
--

DROP TABLE IF EXISTS `mw_user_auto_login_token`;
CREATE TABLE `mw_user_auto_login_token` (
`token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` char(40) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_user_group`
--

DROP TABLE IF EXISTS `mw_user_group`;
CREATE TABLE `mw_user_group` (
`group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_user_group_route_access`
--

DROP TABLE IF EXISTS `mw_user_group_route_access`;
CREATE TABLE `mw_user_group_route_access` (
`route_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `route` varchar(255) NOT NULL,
  `access` enum('allow','deny') NOT NULL DEFAULT 'allow',
  `date_added` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=216 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_user_password_reset`
--

DROP TABLE IF EXISTS `mw_user_password_reset`;
CREATE TABLE `mw_user_password_reset` (
`request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_key` char(40) NOT NULL,
  `ip_address` char(15) DEFAULT NULL,
  `status` char(15) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_user_to_support_ticket_department`
--

DROP TABLE IF EXISTS `mw_user_to_support_ticket_department`;
CREATE TABLE `mw_user_to_support_ticket_department` (
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mw_zone`
--

DROP TABLE IF EXISTS `mw_zone`;
CREATE TABLE `mw_zone` (
`zone_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `status` char(10) NOT NULL DEFAULT 'active',
  `date_added` datetime NOT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3970 DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mw_article`
--
ALTER TABLE `mw_article`
 ADD PRIMARY KEY (`article_id`), ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `mw_article_category`
--
ALTER TABLE `mw_article_category`
 ADD PRIMARY KEY (`category_id`), ADD UNIQUE KEY `slug` (`slug`), ADD KEY `fk_article_category_article_category1_idx` (`parent_id`);

--
-- Indexes for table `mw_article_to_category`
--
ALTER TABLE `mw_article_to_category`
 ADD PRIMARY KEY (`article_id`,`category_id`), ADD KEY `fk_article_to_category_article_category1_idx` (`category_id`), ADD KEY `fk_article_to_category_article1_idx` (`article_id`);

--
-- Indexes for table `mw_backup_manager_snapshot`
--
ALTER TABLE `mw_backup_manager_snapshot`
 ADD PRIMARY KEY (`snapshot_id`);

--
-- Indexes for table `mw_bounce_server`
--
ALTER TABLE `mw_bounce_server`
 ADD PRIMARY KEY (`server_id`), ADD KEY `fk_bounce_server_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_campaign`
--
ALTER TABLE `mw_campaign`
 ADD PRIMARY KEY (`campaign_id`), ADD UNIQUE KEY `campaign_uid_UNIQUE` (`campaign_uid`), ADD KEY `fk_campaign_list1_idx` (`list_id`), ADD KEY `fk_campaign_list_segment1_idx` (`segment_id`), ADD KEY `fk_campaign_customer1_idx` (`customer_id`), ADD KEY `fk_campaign_campaign_group1_idx` (`group_id`), ADD KEY `type` (`type`), ADD KEY `status_delivery_logs_archived_campaign_id` (`status`,`delivery_logs_archived`,`campaign_id`);

--
-- Indexes for table `mw_campaign_abuse_report`
--
ALTER TABLE `mw_campaign_abuse_report`
 ADD PRIMARY KEY (`report_id`), ADD KEY `fk_campaign_abuse_report_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_abuse_report_customer1_idx` (`customer_id`), ADD KEY `fk_campaign_abuse_report_list1_idx` (`list_id`), ADD KEY `fk_campaign_abuse_report_list_subscriber1_idx` (`subscriber_id`);

--
-- Indexes for table `mw_campaign_attachment`
--
ALTER TABLE `mw_campaign_attachment`
 ADD PRIMARY KEY (`attachment_id`), ADD KEY `fk_campaign_attachment_campaign1_idx` (`campaign_id`);

--
-- Indexes for table `mw_campaign_bounce_log`
--
ALTER TABLE `mw_campaign_bounce_log`
 ADD PRIMARY KEY (`log_id`), ADD KEY `fk_campaign_bounce_log_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_bounce_log_list_subscriber1_idx` (`subscriber_id`), ADD KEY `sub_proc_bt` (`subscriber_id`,`processed`,`bounce_type`);

--
-- Indexes for table `mw_campaign_delivery_log`
--
ALTER TABLE `mw_campaign_delivery_log`
 ADD PRIMARY KEY (`log_id`), ADD KEY `fk_campaign_delivery_log_list_subscriber1_idx` (`subscriber_id`), ADD KEY `fk_campaign_delivery_log_campaign1_idx` (`campaign_id`), ADD KEY `sub_proc_status` (`subscriber_id`,`processed`,`status`), ADD KEY `email_message_id` (`email_message_id`);

--
-- Indexes for table `mw_campaign_delivery_log_archive`
--
ALTER TABLE `mw_campaign_delivery_log_archive`
 ADD PRIMARY KEY (`log_id`), ADD KEY `fk_campaign_delivery_log_archive_list_subscriber1_idx` (`subscriber_id`), ADD KEY `fk_campaign_delivery_log_archive_campaign1_idx` (`campaign_id`), ADD KEY `sub_proc_status` (`subscriber_id`,`processed`,`status`), ADD KEY `email_message_id` (`email_message_id`);

--
-- Indexes for table `mw_campaign_forward_friend`
--
ALTER TABLE `mw_campaign_forward_friend`
 ADD PRIMARY KEY (`forward_id`), ADD KEY `fk_campaign_forward_friend_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_forward_friend_list_subscriber1_idx` (`subscriber_id`);

--
-- Indexes for table `mw_campaign_group`
--
ALTER TABLE `mw_campaign_group`
 ADD PRIMARY KEY (`group_id`), ADD UNIQUE KEY `group_uid` (`group_uid`), ADD KEY `fk_campaign_group_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_campaign_open_action_list_field`
--
ALTER TABLE `mw_campaign_open_action_list_field`
 ADD PRIMARY KEY (`action_id`), ADD KEY `fk_campaign_open_action_list_field_list1_idx` (`list_id`), ADD KEY `fk_campaign_open_action_list_field_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_open_action_list_field_list_field1_idx` (`field_id`);

--
-- Indexes for table `mw_campaign_open_action_subscriber`
--
ALTER TABLE `mw_campaign_open_action_subscriber`
 ADD PRIMARY KEY (`action_id`), ADD KEY `fk_campaign_open_action_subscriber_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_open_action_subscriber_list1_idx` (`list_id`);

--
-- Indexes for table `mw_campaign_option`
--
ALTER TABLE `mw_campaign_option`
 ADD PRIMARY KEY (`campaign_id`), ADD KEY `fk_campaign_option_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_option_campaign2_idx` (`autoresponder_open_campaign_id`), ADD KEY `fk_campaign_option_campaign3_idx` (`regular_open_unopen_campaign_id`);

--
-- Indexes for table `mw_campaign_template`
--
ALTER TABLE `mw_campaign_template`
 ADD PRIMARY KEY (`template_id`), ADD KEY `fk_customer_email_template1_idx` (`customer_template_id`), ADD KEY `fk_campaign_template_campaign1_idx` (`campaign_id`);

--
-- Indexes for table `mw_campaign_template_url_action_list_field`
--
ALTER TABLE `mw_campaign_template_url_action_list_field`
 ADD PRIMARY KEY (`url_id`), ADD KEY `fk_campaign_template_url_action_list_field_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_template_url_action_list_field_list1_idx` (`list_id`), ADD KEY `fk_campaign_template_url_action_list_field_campaign_temp_idx` (`template_id`), ADD KEY `fk_campaign_template_url_action_list_field_list_field1_idx` (`field_id`);

--
-- Indexes for table `mw_campaign_template_url_action_subscriber`
--
ALTER TABLE `mw_campaign_template_url_action_subscriber`
 ADD PRIMARY KEY (`url_id`), ADD KEY `fk_campaign_template_url_action_subscriber_campaign_t_idx` (`template_id`), ADD KEY `fk_campaign_template_url_action_subscriber_list1_idx` (`list_id`), ADD KEY `fk_campaign_template_url_action_subscriber_campaign1_idx` (`campaign_id`);

--
-- Indexes for table `mw_campaign_temporary_source`
--
ALTER TABLE `mw_campaign_temporary_source`
 ADD PRIMARY KEY (`source_id`), ADD KEY `fk_campaign_temporary_source_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_temporary_source_list1_idx` (`list_id`), ADD KEY `fk_campaign_temporary_source_list_segment1_idx` (`segment_id`);

--
-- Indexes for table `mw_campaign_to_delivery_server`
--
ALTER TABLE `mw_campaign_to_delivery_server`
 ADD PRIMARY KEY (`campaign_id`,`server_id`), ADD KEY `fk_campaign_to_delivery_server_delivery_server1_idx` (`server_id`), ADD KEY `fk_campaign_to_delivery_server_campaign1_idx` (`campaign_id`);

--
-- Indexes for table `mw_campaign_track_open`
--
ALTER TABLE `mw_campaign_track_open`
 ADD PRIMARY KEY (`id`), ADD KEY `fk_campaign_track_open_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_track_open_list_subscriber1_idx` (`subscriber_id`), ADD KEY `fk_campaign_track_open_ip_location1_idx` (`location_id`);

--
-- Indexes for table `mw_campaign_track_unsubscribe`
--
ALTER TABLE `mw_campaign_track_unsubscribe`
 ADD PRIMARY KEY (`id`), ADD KEY `fk_campaign_track_unsubscribe_campaign1_idx` (`campaign_id`), ADD KEY `fk_campaign_track_unsubscribe_list_subscriber1_idx` (`subscriber_id`), ADD KEY `fk_campaign_track_unsubscribe_ip_location1_idx` (`location_id`), ADD KEY `date_added` (`date_added`);

--
-- Indexes for table `mw_campaign_track_url`
--
ALTER TABLE `mw_campaign_track_url`
 ADD PRIMARY KEY (`id`), ADD KEY `fk_campaign_track_url_list_subscriber1_idx` (`subscriber_id`), ADD KEY `fk_campaign_track_url_ip_location1_idx` (`location_id`), ADD KEY `fk_campaign_track_url_campaign_url1_idx` (`url_id`);

--
-- Indexes for table `mw_campaign_url`
--
ALTER TABLE `mw_campaign_url`
 ADD PRIMARY KEY (`url_id`), ADD KEY `campaign_hash` (`campaign_id`,`hash`), ADD KEY `fk_campaign_url_campaign1_idx` (`campaign_id`);

--
-- Indexes for table `mw_company_type`
--
ALTER TABLE `mw_company_type`
 ADD PRIMARY KEY (`type_id`), ADD UNIQUE KEY `name_UNIQUE` (`name`);

--
-- Indexes for table `mw_country`
--
ALTER TABLE `mw_country`
 ADD PRIMARY KEY (`country_id`);

--
-- Indexes for table `mw_currency`
--
ALTER TABLE `mw_currency`
 ADD PRIMARY KEY (`currency_id`), ADD UNIQUE KEY `code_UNIQUE` (`code`);

--
-- Indexes for table `mw_customer`
--
ALTER TABLE `mw_customer`
 ADD PRIMARY KEY (`customer_id`), ADD UNIQUE KEY `customer_uid_UNIQUE` (`customer_uid`), ADD UNIQUE KEY `email_UNIQUE` (`email`), ADD KEY `fk_customer_language1_idx` (`language_id`), ADD KEY `fk_customer_customer_group1_idx` (`group_id`), ADD KEY `oauth` (`oauth_uid`,`oauth_provider`);

--
-- Indexes for table `mw_customer_action_log`
--
ALTER TABLE `mw_customer_action_log`
 ADD PRIMARY KEY (`log_id`), ADD KEY `fk_customer_notification_log_customer1_idx` (`customer_id`), ADD KEY `customer_category_reference` (`customer_id`,`category`,`reference_id`);

--
-- Indexes for table `mw_customer_api_key`
--
ALTER TABLE `mw_customer_api_key`
 ADD PRIMARY KEY (`key_id`), ADD UNIQUE KEY `public_UNIQUE` (`public`), ADD UNIQUE KEY `private_UNIQUE` (`private`), ADD KEY `fk_customer_api_key_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_customer_auto_login_token`
--
ALTER TABLE `mw_customer_auto_login_token`
 ADD PRIMARY KEY (`token_id`), ADD KEY `fk_customer_auto_login_token_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_customer_company`
--
ALTER TABLE `mw_customer_company`
 ADD PRIMARY KEY (`company_id`), ADD KEY `fk_customer_company_country1_idx` (`country_id`), ADD KEY `fk_customer_company_zone1_idx` (`zone_id`), ADD KEY `fk_customer_company_customer1_idx` (`customer_id`), ADD KEY `fk_customer_company_company_type1_idx` (`type_id`);

--
-- Indexes for table `mw_customer_email_template`
--
ALTER TABLE `mw_customer_email_template`
 ADD PRIMARY KEY (`template_id`), ADD KEY `fk_customer_email_template_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_customer_group`
--
ALTER TABLE `mw_customer_group`
 ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `mw_customer_group_option`
--
ALTER TABLE `mw_customer_group_option`
 ADD PRIMARY KEY (`option_id`), ADD KEY `fk_customer_group_option_customer_group1_idx` (`group_id`), ADD KEY `group_code` (`group_id`,`code`);

--
-- Indexes for table `mw_customer_password_reset`
--
ALTER TABLE `mw_customer_password_reset`
 ADD PRIMARY KEY (`request_id`), ADD KEY `fk_customer_password_reset_customer1` (`customer_id`), ADD KEY `key_status` (`reset_key`,`status`);

--
-- Indexes for table `mw_customer_quota_mark`
--
ALTER TABLE `mw_customer_quota_mark`
 ADD PRIMARY KEY (`mark_id`), ADD KEY `fk_customer_quota_mark_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_customer_referral_url`
--
ALTER TABLE `mw_customer_referral_url`
 ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `mw_delivery_server`
--
ALTER TABLE `mw_delivery_server`
 ADD PRIMARY KEY (`server_id`), ADD KEY `fk_delivery_server_bounce_server1_idx` (`bounce_server_id`), ADD KEY `idx_gen0` (`status`,`hourly_quota`,`probability`), ADD KEY `fk_delivery_server_customer1_idx` (`customer_id`), ADD KEY `fk_delivery_server_tracking_domain1_idx` (`tracking_domain_id`);

--
-- Indexes for table `mw_delivery_server_domain_policy`
--
ALTER TABLE `mw_delivery_server_domain_policy`
 ADD PRIMARY KEY (`domain_id`), ADD KEY `fk_delivery_server_domain_policy_delivery_server1_idx` (`server_id`), ADD KEY `server_domain_policy` (`server_id`,`domain`,`policy`);

--
-- Indexes for table `mw_delivery_server_to_customer_group`
--
ALTER TABLE `mw_delivery_server_to_customer_group`
 ADD PRIMARY KEY (`server_id`,`group_id`), ADD KEY `fk_delivery_server_to_customer_group_customer_group1_idx` (`group_id`), ADD KEY `fk_delivery_server_to_customer_group_delivery_server1_idx` (`server_id`);

--
-- Indexes for table `mw_delivery_server_usage_log`
--
ALTER TABLE `mw_delivery_server_usage_log`
 ADD PRIMARY KEY (`log_id`), ADD KEY `fk_delivery_server_usage_log_delivery_server1_idx` (`server_id`), ADD KEY `fk_delivery_server_usage_log_customer1_idx` (`customer_id`), ADD KEY `server_date` (`server_id`,`date_added`), ADD KEY `customer_countable_date` (`customer_id`,`customer_countable`,`date_added`);

--
-- Indexes for table `mw_email_blacklist`
--
ALTER TABLE `mw_email_blacklist`
 ADD PRIMARY KEY (`email_id`), ADD UNIQUE KEY `email` (`email`), ADD KEY `fk_email_blacklist_list_subscriber1_idx` (`subscriber_id`);

--
-- Indexes for table `mw_feedback_loop_server`
--
ALTER TABLE `mw_feedback_loop_server`
 ADD PRIMARY KEY (`server_id`), ADD KEY `fk_feedback_loop_server_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_guest_fail_attempt`
--
ALTER TABLE `mw_guest_fail_attempt`
 ADD PRIMARY KEY (`attempt_id`), ADD KEY `ip_hash_date` (`ip_address_hash`,`date_added`);

--
-- Indexes for table `mw_ip_location`
--
ALTER TABLE `mw_ip_location`
 ADD PRIMARY KEY (`location_id`), ADD UNIQUE KEY `ip_address_UNIQUE` (`ip_address`);

--
-- Indexes for table `mw_language`
--
ALTER TABLE `mw_language`
 ADD PRIMARY KEY (`language_id`), ADD KEY `is_default` (`is_default`);

--
-- Indexes for table `mw_list`
--
ALTER TABLE `mw_list`
 ADD PRIMARY KEY (`list_id`), ADD UNIQUE KEY `unique_id_UNIQUE` (`list_uid`), ADD KEY `fk_list_customer1_idx` (`customer_id`), ADD KEY `status_visibility` (`status`,`visibility`);

--
-- Indexes for table `mw_list_company`
--
ALTER TABLE `mw_list_company`
 ADD PRIMARY KEY (`list_id`), ADD KEY `fk_customer_company_country1_idx` (`country_id`), ADD KEY `fk_customer_company_zone1_idx` (`zone_id`), ADD KEY `fk_list_company_company_type1_idx` (`type_id`);

--
-- Indexes for table `mw_list_customer_notification`
--
ALTER TABLE `mw_list_customer_notification`
 ADD PRIMARY KEY (`list_id`);

--
-- Indexes for table `mw_list_default`
--
ALTER TABLE `mw_list_default`
 ADD PRIMARY KEY (`list_id`);

--
-- Indexes for table `mw_list_field`
--
ALTER TABLE `mw_list_field`
 ADD PRIMARY KEY (`field_id`), ADD KEY `fk_list_field_list1_idx` (`list_id`), ADD KEY `fk_list_field_list_field_type1_idx` (`type_id`), ADD KEY `list_tag` (`list_id`,`tag`);

--
-- Indexes for table `mw_list_field_option`
--
ALTER TABLE `mw_list_field_option`
 ADD PRIMARY KEY (`option_id`), ADD KEY `fk_list_field_option_list_field1_idx` (`field_id`);

--
-- Indexes for table `mw_list_field_type`
--
ALTER TABLE `mw_list_field_type`
 ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `mw_list_field_value`
--
ALTER TABLE `mw_list_field_value`
 ADD PRIMARY KEY (`value_id`), ADD KEY `fk_list_field_value_list_field1_idx` (`field_id`), ADD KEY `fk_list_field_value_list_subscriber1_idx` (`subscriber_id`), ADD KEY `field_subscriber` (`field_id`,`subscriber_id`), ADD KEY `field_id_value` (`field_id`,`value`);

--
-- Indexes for table `mw_list_form_custom_asset`
--
ALTER TABLE `mw_list_form_custom_asset`
 ADD PRIMARY KEY (`asset_id`), ADD KEY `fk_list_form_custom_asset_list1_idx` (`list_id`), ADD KEY `fk_list_form_custom_asset_list_page_type1_idx` (`type_id`);

--
-- Indexes for table `mw_list_form_custom_redirect`
--
ALTER TABLE `mw_list_form_custom_redirect`
 ADD PRIMARY KEY (`redirect_id`), ADD KEY `fk_list_form_custom_redirect_list1_idx` (`list_id`), ADD KEY `fk_list_form_custom_redirect_list_page_type1_idx` (`type_id`);

--
-- Indexes for table `mw_list_form_custom_webhook`
--
ALTER TABLE `mw_list_form_custom_webhook`
 ADD PRIMARY KEY (`webhook_id`), ADD KEY `fk_list_form_custom_webhook_list1_idx` (`list_id`), ADD KEY `fk_list_form_custom_webhook_list_page_type1_idx` (`type_id`);

--
-- Indexes for table `mw_list_page`
--
ALTER TABLE `mw_list_page`
 ADD PRIMARY KEY (`list_id`,`type_id`), ADD KEY `fk_list_page_list_page_type1_idx` (`type_id`);

--
-- Indexes for table `mw_list_page_type`
--
ALTER TABLE `mw_list_page_type`
 ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `mw_list_segment`
--
ALTER TABLE `mw_list_segment`
 ADD PRIMARY KEY (`segment_id`), ADD UNIQUE KEY `segment_uid` (`segment_uid`), ADD KEY `fk_list_segment_list1_idx` (`list_id`);

--
-- Indexes for table `mw_list_segment_condition`
--
ALTER TABLE `mw_list_segment_condition`
 ADD PRIMARY KEY (`condition_id`), ADD KEY `fk_list_segment_condition_list_segment_operator1_idx` (`operator_id`), ADD KEY `fk_list_segment_condition_list_segment1_idx` (`segment_id`), ADD KEY `fk_list_segment_condition_list_field1_idx` (`field_id`);

--
-- Indexes for table `mw_list_segment_operator`
--
ALTER TABLE `mw_list_segment_operator`
 ADD PRIMARY KEY (`operator_id`);

--
-- Indexes for table `mw_list_subscriber`
--
ALTER TABLE `mw_list_subscriber`
 ADD PRIMARY KEY (`subscriber_id`), ADD UNIQUE KEY `unique_id_UNIQUE` (`subscriber_uid`), ADD KEY `fk_list_subscriber_list1_idx` (`list_id`), ADD KEY `list_email` (`list_id`,`email`), ADD KEY `status_last_updated` (`status`,`last_updated`), ADD KEY `list_id_status` (`list_id`,`status`);

--
-- Indexes for table `mw_list_subscriber_action`
--
ALTER TABLE `mw_list_subscriber_action`
 ADD PRIMARY KEY (`action_id`), ADD KEY `fk_list_subscriber_action_list1_idx` (`source_list_id`), ADD KEY `fk_list_subscriber_action_list2_idx` (`target_list_id`);

--
-- Indexes for table `mw_option`
--
ALTER TABLE `mw_option`
 ADD PRIMARY KEY (`category`,`key`);

--
-- Indexes for table `mw_price_plan`
--
ALTER TABLE `mw_price_plan`
 ADD PRIMARY KEY (`plan_id`), ADD UNIQUE KEY `plan_uid_UNIQUE` (`plan_uid`), ADD KEY `fk_price_plan_customer_group1_idx` (`group_id`);

--
-- Indexes for table `mw_price_plan_order`
--
ALTER TABLE `mw_price_plan_order`
 ADD PRIMARY KEY (`order_id`), ADD UNIQUE KEY `order_uid_UNIQUE` (`order_uid`), ADD KEY `fk_price_plan_order_price_plan1_idx` (`plan_id`), ADD KEY `fk_price_plan_order_customer1_idx` (`customer_id`), ADD KEY `fk_price_plan_order_price_plan_promo_code1_idx` (`promo_code_id`), ADD KEY `fk_price_plan_order_currency1_idx` (`currency_id`), ADD KEY `fk_price_plan_order_price_plan_tax1_idx` (`tax_id`);

--
-- Indexes for table `mw_price_plan_order_note`
--
ALTER TABLE `mw_price_plan_order_note`
 ADD PRIMARY KEY (`note_id`), ADD KEY `fk_price_plan_order_note_price_plan_order1_idx` (`order_id`), ADD KEY `fk_price_plan_order_note_customer1_idx` (`customer_id`), ADD KEY `fk_price_plan_order_note_user1_idx` (`user_id`);

--
-- Indexes for table `mw_price_plan_order_transaction`
--
ALTER TABLE `mw_price_plan_order_transaction`
 ADD PRIMARY KEY (`transaction_id`), ADD UNIQUE KEY `transaction_uid_UNIQUE` (`transaction_uid`), ADD KEY `fk_price_plan_order_transaction_price_plan_order1_idx` (`order_id`);

--
-- Indexes for table `mw_price_plan_promo_code`
--
ALTER TABLE `mw_price_plan_promo_code`
 ADD PRIMARY KEY (`promo_code_id`);

--
-- Indexes for table `mw_sending_domain`
--
ALTER TABLE `mw_sending_domain`
 ADD PRIMARY KEY (`domain_id`), ADD KEY `fk_sending_domain_customer1_idx` (`customer_id`), ADD KEY `name_verified_customer` (`name`,`verified`,`customer_id`);

--
-- Indexes for table `mw_session`
--
ALTER TABLE `mw_session`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mw_support_ticket`
--
ALTER TABLE `mw_support_ticket`
 ADD PRIMARY KEY (`ticket_id`), ADD KEY `fk_support_ticket_support_ticket_priority1_idx` (`priority_id`), ADD KEY `fk_support_ticket_support_ticket_department1_idx` (`department_id`), ADD KEY `fk_support_ticket_user1_idx` (`user_id`), ADD KEY `fk_support_ticket_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_support_ticket_department`
--
ALTER TABLE `mw_support_ticket_department`
 ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `mw_support_ticket_priority`
--
ALTER TABLE `mw_support_ticket_priority`
 ADD PRIMARY KEY (`priority_id`);

--
-- Indexes for table `mw_support_ticket_reply`
--
ALTER TABLE `mw_support_ticket_reply`
 ADD PRIMARY KEY (`reply_id`), ADD KEY `fk_support_ticket_reply_support_ticket_idx` (`ticket_id`), ADD KEY `fk_support_ticket_reply_user1_idx` (`user_id`), ADD KEY `fk_support_ticket_reply_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_tag_registry`
--
ALTER TABLE `mw_tag_registry`
 ADD PRIMARY KEY (`tag_id`), ADD UNIQUE KEY `tag_UNIQUE` (`tag`);

--
-- Indexes for table `mw_tax`
--
ALTER TABLE `mw_tax`
 ADD PRIMARY KEY (`tax_id`), ADD KEY `fk_tax_zone1_idx` (`zone_id`), ADD KEY `fk_tax_country1_idx` (`country_id`);

--
-- Indexes for table `mw_trace_logs`
--
ALTER TABLE `mw_trace_logs`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mw_tracking_domain`
--
ALTER TABLE `mw_tracking_domain`
 ADD PRIMARY KEY (`domain_id`), ADD KEY `fk_tracking_domain_customer1_idx` (`customer_id`);

--
-- Indexes for table `mw_transactional_email`
--
ALTER TABLE `mw_transactional_email`
 ADD PRIMARY KEY (`email_id`), ADD UNIQUE KEY `email_uid_UNIQUE` (`email_uid`), ADD KEY `fk_transactional_email_customer1_idx` (`customer_id`), ADD KEY `status_send_at_retries_max_retries` (`status`,`send_at`,`retries`,`max_retries`);

--
-- Indexes for table `mw_transactional_email_log`
--
ALTER TABLE `mw_transactional_email_log`
 ADD PRIMARY KEY (`log_id`), ADD KEY `fk_transactional_email_log_transactional_email1_idx` (`email_id`);

--
-- Indexes for table `mw_user`
--
ALTER TABLE `mw_user`
 ADD PRIMARY KEY (`user_id`), ADD UNIQUE KEY `user_uid_UNIQUE` (`user_uid`), ADD UNIQUE KEY `email_UNIQUE` (`email`), ADD KEY `fk_user_language1_idx` (`language_id`), ADD KEY `fk_user_user_group1_idx` (`group_id`);

--
-- Indexes for table `mw_user_auto_login_token`
--
ALTER TABLE `mw_user_auto_login_token`
 ADD PRIMARY KEY (`token_id`), ADD KEY `fk_user_auto_login_token_user1_idx` (`user_id`);

--
-- Indexes for table `mw_user_group`
--
ALTER TABLE `mw_user_group`
 ADD PRIMARY KEY (`group_id`), ADD UNIQUE KEY `name_UNIQUE` (`name`);

--
-- Indexes for table `mw_user_group_route_access`
--
ALTER TABLE `mw_user_group_route_access`
 ADD PRIMARY KEY (`route_id`), ADD KEY `fk_user_group_route_access_user_group1_idx` (`group_id`), ADD KEY `group_route_access` (`group_id`,`route`,`access`);

--
-- Indexes for table `mw_user_password_reset`
--
ALTER TABLE `mw_user_password_reset`
 ADD PRIMARY KEY (`request_id`), ADD KEY `fk_user_password_reset_user1_idx` (`user_id`), ADD KEY `key_status` (`reset_key`,`status`);

--
-- Indexes for table `mw_user_to_support_ticket_department`
--
ALTER TABLE `mw_user_to_support_ticket_department`
 ADD PRIMARY KEY (`user_id`,`department_id`), ADD KEY `fk_user_to_support_ticket_department_support_ticket_departm_idx` (`department_id`), ADD KEY `fk_user_to_support_ticket_department_user1_idx` (`user_id`);

--
-- Indexes for table `mw_zone`
--
ALTER TABLE `mw_zone`
 ADD PRIMARY KEY (`zone_id`), ADD KEY `fk_zone_country1_idx` (`country_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mw_article`
--
ALTER TABLE `mw_article`
MODIFY `article_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `mw_article_category`
--
ALTER TABLE `mw_article_category`
MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mw_backup_manager_snapshot`
--
ALTER TABLE `mw_backup_manager_snapshot`
MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=94;
--
-- AUTO_INCREMENT for table `mw_bounce_server`
--
ALTER TABLE `mw_bounce_server`
MODIFY `server_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `mw_campaign`
--
ALTER TABLE `mw_campaign`
MODIFY `campaign_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2171;
--
-- AUTO_INCREMENT for table `mw_campaign_abuse_report`
--
ALTER TABLE `mw_campaign_abuse_report`
MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=459;
--
-- AUTO_INCREMENT for table `mw_campaign_attachment`
--
ALTER TABLE `mw_campaign_attachment`
MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_campaign_bounce_log`
--
ALTER TABLE `mw_campaign_bounce_log`
MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18753;
--
-- AUTO_INCREMENT for table `mw_campaign_delivery_log`
--
ALTER TABLE `mw_campaign_delivery_log`
MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12957838;
--
-- AUTO_INCREMENT for table `mw_campaign_delivery_log_archive`
--
ALTER TABLE `mw_campaign_delivery_log_archive`
MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_campaign_forward_friend`
--
ALTER TABLE `mw_campaign_forward_friend`
MODIFY `forward_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `mw_campaign_group`
--
ALTER TABLE `mw_campaign_group`
MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=52;
--
-- AUTO_INCREMENT for table `mw_campaign_open_action_list_field`
--
ALTER TABLE `mw_campaign_open_action_list_field`
MODIFY `action_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=216;
--
-- AUTO_INCREMENT for table `mw_campaign_open_action_subscriber`
--
ALTER TABLE `mw_campaign_open_action_subscriber`
MODIFY `action_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=286;
--
-- AUTO_INCREMENT for table `mw_campaign_template`
--
ALTER TABLE `mw_campaign_template`
MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2125;
--
-- AUTO_INCREMENT for table `mw_campaign_template_url_action_list_field`
--
ALTER TABLE `mw_campaign_template_url_action_list_field`
MODIFY `url_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=173;
--
-- AUTO_INCREMENT for table `mw_campaign_template_url_action_subscriber`
--
ALTER TABLE `mw_campaign_template_url_action_subscriber`
MODIFY `url_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mw_campaign_temporary_source`
--
ALTER TABLE `mw_campaign_temporary_source`
MODIFY `source_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=104;
--
-- AUTO_INCREMENT for table `mw_campaign_track_open`
--
ALTER TABLE `mw_campaign_track_open`
MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=864549;
--
-- AUTO_INCREMENT for table `mw_campaign_track_unsubscribe`
--
ALTER TABLE `mw_campaign_track_unsubscribe`
MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18366;
--
-- AUTO_INCREMENT for table `mw_campaign_track_url`
--
ALTER TABLE `mw_campaign_track_url`
MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=197329;
--
-- AUTO_INCREMENT for table `mw_campaign_url`
--
ALTER TABLE `mw_campaign_url`
MODIFY `url_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6395;
--
-- AUTO_INCREMENT for table `mw_company_type`
--
ALTER TABLE `mw_company_type`
MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=46;
--
-- AUTO_INCREMENT for table `mw_country`
--
ALTER TABLE `mw_country`
MODIFY `country_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=240;
--
-- AUTO_INCREMENT for table `mw_currency`
--
ALTER TABLE `mw_currency`
MODIFY `currency_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mw_customer`
--
ALTER TABLE `mw_customer`
MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=185;
--
-- AUTO_INCREMENT for table `mw_customer_action_log`
--
ALTER TABLE `mw_customer_action_log`
MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=31965;
--
-- AUTO_INCREMENT for table `mw_customer_api_key`
--
ALTER TABLE `mw_customer_api_key`
MODIFY `key_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=83;
--
-- AUTO_INCREMENT for table `mw_customer_auto_login_token`
--
ALTER TABLE `mw_customer_auto_login_token`
MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=28047;
--
-- AUTO_INCREMENT for table `mw_customer_company`
--
ALTER TABLE `mw_customer_company`
MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=59;
--
-- AUTO_INCREMENT for table `mw_customer_email_template`
--
ALTER TABLE `mw_customer_email_template`
MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=150;
--
-- AUTO_INCREMENT for table `mw_customer_group`
--
ALTER TABLE `mw_customer_group`
MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `mw_customer_group_option`
--
ALTER TABLE `mw_customer_group_option`
MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=401;
--
-- AUTO_INCREMENT for table `mw_customer_password_reset`
--
ALTER TABLE `mw_customer_password_reset`
MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=55;
--
-- AUTO_INCREMENT for table `mw_customer_quota_mark`
--
ALTER TABLE `mw_customer_quota_mark`
MODIFY `mark_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=254;
--
-- AUTO_INCREMENT for table `mw_delivery_server`
--
ALTER TABLE `mw_delivery_server`
MODIFY `server_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=237;
--
-- AUTO_INCREMENT for table `mw_delivery_server_domain_policy`
--
ALTER TABLE `mw_delivery_server_domain_policy`
MODIFY `domain_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=47;
--
-- AUTO_INCREMENT for table `mw_delivery_server_usage_log`
--
ALTER TABLE `mw_delivery_server_usage_log`
MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2481003;
--
-- AUTO_INCREMENT for table `mw_email_blacklist`
--
ALTER TABLE `mw_email_blacklist`
MODIFY `email_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=28723;
--
-- AUTO_INCREMENT for table `mw_feedback_loop_server`
--
ALTER TABLE `mw_feedback_loop_server`
MODIFY `server_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `mw_guest_fail_attempt`
--
ALTER TABLE `mw_guest_fail_attempt`
MODIFY `attempt_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=649;
--
-- AUTO_INCREMENT for table `mw_ip_location`
--
ALTER TABLE `mw_ip_location`
MODIFY `location_id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_language`
--
ALTER TABLE `mw_language`
MODIFY `language_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_list`
--
ALTER TABLE `mw_list`
MODIFY `list_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=753;
--
-- AUTO_INCREMENT for table `mw_list_field`
--
ALTER TABLE `mw_list_field`
MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4989;
--
-- AUTO_INCREMENT for table `mw_list_field_option`
--
ALTER TABLE `mw_list_field_option`
MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=35;
--
-- AUTO_INCREMENT for table `mw_list_field_type`
--
ALTER TABLE `mw_list_field_type`
MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `mw_list_field_value`
--
ALTER TABLE `mw_list_field_value`
MODIFY `value_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=30263632;
--
-- AUTO_INCREMENT for table `mw_list_form_custom_asset`
--
ALTER TABLE `mw_list_form_custom_asset`
MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_list_form_custom_redirect`
--
ALTER TABLE `mw_list_form_custom_redirect`
MODIFY `redirect_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=38;
--
-- AUTO_INCREMENT for table `mw_list_form_custom_webhook`
--
ALTER TABLE `mw_list_form_custom_webhook`
MODIFY `webhook_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT for table `mw_list_page_type`
--
ALTER TABLE `mw_list_page_type`
MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `mw_list_segment`
--
ALTER TABLE `mw_list_segment`
MODIFY `segment_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=63;
--
-- AUTO_INCREMENT for table `mw_list_segment_condition`
--
ALTER TABLE `mw_list_segment_condition`
MODIFY `condition_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=79;
--
-- AUTO_INCREMENT for table `mw_list_segment_operator`
--
ALTER TABLE `mw_list_segment_operator`
MODIFY `operator_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `mw_list_subscriber`
--
ALTER TABLE `mw_list_subscriber`
MODIFY `subscriber_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4795519;
--
-- AUTO_INCREMENT for table `mw_list_subscriber_action`
--
ALTER TABLE `mw_list_subscriber_action`
MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=736;
--
-- AUTO_INCREMENT for table `mw_price_plan`
--
ALTER TABLE `mw_price_plan`
MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_price_plan_order`
--
ALTER TABLE `mw_price_plan_order`
MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_price_plan_order_note`
--
ALTER TABLE `mw_price_plan_order_note`
MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_price_plan_order_transaction`
--
ALTER TABLE `mw_price_plan_order_transaction`
MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_price_plan_promo_code`
--
ALTER TABLE `mw_price_plan_promo_code`
MODIFY `promo_code_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_sending_domain`
--
ALTER TABLE `mw_sending_domain`
MODIFY `domain_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=76;
--
-- AUTO_INCREMENT for table `mw_support_ticket`
--
ALTER TABLE `mw_support_ticket`
MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `mw_support_ticket_department`
--
ALTER TABLE `mw_support_ticket_department`
MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `mw_support_ticket_priority`
--
ALTER TABLE `mw_support_ticket_priority`
MODIFY `priority_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mw_support_ticket_reply`
--
ALTER TABLE `mw_support_ticket_reply`
MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `mw_tag_registry`
--
ALTER TABLE `mw_tag_registry`
MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=106;
--
-- AUTO_INCREMENT for table `mw_tax`
--
ALTER TABLE `mw_tax`
MODIFY `tax_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_trace_logs`
--
ALTER TABLE `mw_trace_logs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=125781;
--
-- AUTO_INCREMENT for table `mw_tracking_domain`
--
ALTER TABLE `mw_tracking_domain`
MODIFY `domain_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `mw_transactional_email`
--
ALTER TABLE `mw_transactional_email`
MODIFY `email_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=444;
--
-- AUTO_INCREMENT for table `mw_transactional_email_log`
--
ALTER TABLE `mw_transactional_email_log`
MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=457;
--
-- AUTO_INCREMENT for table `mw_user`
--
ALTER TABLE `mw_user`
MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `mw_user_auto_login_token`
--
ALTER TABLE `mw_user_auto_login_token`
MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=90;
--
-- AUTO_INCREMENT for table `mw_user_group`
--
ALTER TABLE `mw_user_group`
MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `mw_user_group_route_access`
--
ALTER TABLE `mw_user_group_route_access`
MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=216;
--
-- AUTO_INCREMENT for table `mw_user_password_reset`
--
ALTER TABLE `mw_user_password_reset`
MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mw_zone`
--
ALTER TABLE `mw_zone`
MODIFY `zone_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3970;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `mw_article_category`
--
ALTER TABLE `mw_article_category`
ADD CONSTRAINT `fk_article_category_article_category1` FOREIGN KEY (`parent_id`) REFERENCES `mw_article_category` (`category_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_article_to_category`
--
ALTER TABLE `mw_article_to_category`
ADD CONSTRAINT `fk_article_to_category_article1` FOREIGN KEY (`article_id`) REFERENCES `mw_article` (`article_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_article_to_category_article_category1` FOREIGN KEY (`category_id`) REFERENCES `mw_article_category` (`category_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_bounce_server`
--
ALTER TABLE `mw_bounce_server`
ADD CONSTRAINT `fk_bounce_server_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign`
--
ALTER TABLE `mw_campaign`
ADD CONSTRAINT `fk_campaign_campaign_group1` FOREIGN KEY (`group_id`) REFERENCES `mw_campaign_group` (`group_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_list_segment1` FOREIGN KEY (`segment_id`) REFERENCES `mw_list_segment` (`segment_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_abuse_report`
--
ALTER TABLE `mw_campaign_abuse_report`
ADD CONSTRAINT `fk_campaign_abuse_report_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_abuse_report_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_abuse_report_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_abuse_report_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_attachment`
--
ALTER TABLE `mw_campaign_attachment`
ADD CONSTRAINT `fk_campaign_attachment_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_bounce_log`
--
ALTER TABLE `mw_campaign_bounce_log`
ADD CONSTRAINT `fk_campaign_bounce_log_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_bounce_log_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_delivery_log`
--
ALTER TABLE `mw_campaign_delivery_log`
ADD CONSTRAINT `fk_campaign_delivery_log_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_delivery_log_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_delivery_log_archive`
--
ALTER TABLE `mw_campaign_delivery_log_archive`
ADD CONSTRAINT `fk_campaign_delivery_log_archive_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_delivery_log_archive_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_forward_friend`
--
ALTER TABLE `mw_campaign_forward_friend`
ADD CONSTRAINT `fk_campaign_forward_friend_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_forward_friend_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_group`
--
ALTER TABLE `mw_campaign_group`
ADD CONSTRAINT `fk_campaign_group_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_open_action_list_field`
--
ALTER TABLE `mw_campaign_open_action_list_field`
ADD CONSTRAINT `fk_campaign_open_action_list_field_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_open_action_list_field_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_open_action_list_field_list_field1` FOREIGN KEY (`field_id`) REFERENCES `mw_list_field` (`field_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_open_action_subscriber`
--
ALTER TABLE `mw_campaign_open_action_subscriber`
ADD CONSTRAINT `fk_campaign_open_action_subscriber_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_open_action_subscriber_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_option`
--
ALTER TABLE `mw_campaign_option`
ADD CONSTRAINT `fk_campaign_option_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_option_campaign2` FOREIGN KEY (`autoresponder_open_campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_option_campaign3` FOREIGN KEY (`regular_open_unopen_campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_template`
--
ALTER TABLE `mw_campaign_template`
ADD CONSTRAINT `fk_campaign_template_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_customer_email_template1` FOREIGN KEY (`customer_template_id`) REFERENCES `mw_customer_email_template` (`template_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_template_url_action_list_field`
--
ALTER TABLE `mw_campaign_template_url_action_list_field`
ADD CONSTRAINT `fk_campaign_template_url_action_list_field_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_template_url_action_list_field_campaign_templa1` FOREIGN KEY (`template_id`) REFERENCES `mw_campaign_template` (`template_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_template_url_action_list_field_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_template_url_action_list_field_list_field1` FOREIGN KEY (`field_id`) REFERENCES `mw_list_field` (`field_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_template_url_action_subscriber`
--
ALTER TABLE `mw_campaign_template_url_action_subscriber`
ADD CONSTRAINT `fk_campaign_template_url_action_subscriber_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_template_url_action_subscriber_campaign_tem1` FOREIGN KEY (`template_id`) REFERENCES `mw_campaign_template` (`template_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_template_url_action_subscriber_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_temporary_source`
--
ALTER TABLE `mw_campaign_temporary_source`
ADD CONSTRAINT `fk_campaign_temporary_source_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_temporary_source_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_temporary_source_list_segment1` FOREIGN KEY (`segment_id`) REFERENCES `mw_list_segment` (`segment_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_to_delivery_server`
--
ALTER TABLE `mw_campaign_to_delivery_server`
ADD CONSTRAINT `fk_campaign_to_delivery_server_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_to_delivery_server_delivery_server1` FOREIGN KEY (`server_id`) REFERENCES `mw_delivery_server` (`server_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_track_open`
--
ALTER TABLE `mw_campaign_track_open`
ADD CONSTRAINT `fk_campaign_track_open_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_track_open_ip_location1` FOREIGN KEY (`location_id`) REFERENCES `mw_ip_location` (`location_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_track_open_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_track_unsubscribe`
--
ALTER TABLE `mw_campaign_track_unsubscribe`
ADD CONSTRAINT `fk_campaign_track_unsubscribe_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_track_unsubscribe_ip_location1` FOREIGN KEY (`location_id`) REFERENCES `mw_ip_location` (`location_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_track_unsubscribe_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_track_url`
--
ALTER TABLE `mw_campaign_track_url`
ADD CONSTRAINT `fk_campaign_track_url_campaign_url1` FOREIGN KEY (`url_id`) REFERENCES `mw_campaign_url` (`url_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_track_url_ip_location1` FOREIGN KEY (`location_id`) REFERENCES `mw_ip_location` (`location_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_campaign_track_url_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_campaign_url`
--
ALTER TABLE `mw_campaign_url`
ADD CONSTRAINT `fk_campaign_url_campaign1` FOREIGN KEY (`campaign_id`) REFERENCES `mw_campaign` (`campaign_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer`
--
ALTER TABLE `mw_customer`
ADD CONSTRAINT `fk_customer_customer_group1` FOREIGN KEY (`group_id`) REFERENCES `mw_customer_group` (`group_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_customer_language1` FOREIGN KEY (`language_id`) REFERENCES `mw_language` (`language_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_action_log`
--
ALTER TABLE `mw_customer_action_log`
ADD CONSTRAINT `fk_customer_notification_log_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_api_key`
--
ALTER TABLE `mw_customer_api_key`
ADD CONSTRAINT `fk_customer_api_key_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_auto_login_token`
--
ALTER TABLE `mw_customer_auto_login_token`
ADD CONSTRAINT `fk_customer_auto_login_token_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_company`
--
ALTER TABLE `mw_customer_company`
ADD CONSTRAINT `fk_customer_company_company_type1` FOREIGN KEY (`type_id`) REFERENCES `mw_company_type` (`type_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_customer_company_country10` FOREIGN KEY (`country_id`) REFERENCES `mw_country` (`country_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_customer_company_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_customer_company_zone10` FOREIGN KEY (`zone_id`) REFERENCES `mw_zone` (`zone_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_email_template`
--
ALTER TABLE `mw_customer_email_template`
ADD CONSTRAINT `fk_customer_email_template_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_group_option`
--
ALTER TABLE `mw_customer_group_option`
ADD CONSTRAINT `fk_customer_group_option_customer_group1` FOREIGN KEY (`group_id`) REFERENCES `mw_customer_group` (`group_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_password_reset`
--
ALTER TABLE `mw_customer_password_reset`
ADD CONSTRAINT `fk_customer_password_reset_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_quota_mark`
--
ALTER TABLE `mw_customer_quota_mark`
ADD CONSTRAINT `fk_customer_quota_mark_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_customer_referral_url`
--
ALTER TABLE `mw_customer_referral_url`
ADD CONSTRAINT `customer_id` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `mw_delivery_server`
--
ALTER TABLE `mw_delivery_server`
ADD CONSTRAINT `fk_delivery_server1` FOREIGN KEY (`bounce_server_id`) REFERENCES `mw_bounce_server` (`server_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_delivery_server_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_delivery_server_tracking_domain1` FOREIGN KEY (`tracking_domain_id`) REFERENCES `mw_tracking_domain` (`domain_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_delivery_server_domain_policy`
--
ALTER TABLE `mw_delivery_server_domain_policy`
ADD CONSTRAINT `fk_delivery_server_domain_policy_delivery_server1` FOREIGN KEY (`server_id`) REFERENCES `mw_delivery_server` (`server_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_delivery_server_to_customer_group`
--
ALTER TABLE `mw_delivery_server_to_customer_group`
ADD CONSTRAINT `fk_delivery_server_to_customer_group_customer_group1` FOREIGN KEY (`group_id`) REFERENCES `mw_customer_group` (`group_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_delivery_server_to_customer_group_delivery_server1` FOREIGN KEY (`server_id`) REFERENCES `mw_delivery_server` (`server_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_delivery_server_usage_log`
--
ALTER TABLE `mw_delivery_server_usage_log`
ADD CONSTRAINT `fk_delivery_server_usage_log_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_delivery_server_usage_log_delivery_server1` FOREIGN KEY (`server_id`) REFERENCES `mw_delivery_server` (`server_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_email_blacklist`
--
ALTER TABLE `mw_email_blacklist`
ADD CONSTRAINT `fk_email_blacklist1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_feedback_loop_server`
--
ALTER TABLE `mw_feedback_loop_server`
ADD CONSTRAINT `fk_feedback_loop_server_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list`
--
ALTER TABLE `mw_list`
ADD CONSTRAINT `fk_list_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_company`
--
ALTER TABLE `mw_list_company`
ADD CONSTRAINT `fk_customer_company_country100` FOREIGN KEY (`country_id`) REFERENCES `mw_country` (`country_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_customer_company_zone100` FOREIGN KEY (`zone_id`) REFERENCES `mw_zone` (`zone_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_company_company_type1` FOREIGN KEY (`type_id`) REFERENCES `mw_company_type` (`type_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_company_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_customer_notification`
--
ALTER TABLE `mw_list_customer_notification`
ADD CONSTRAINT `fk_list_notification_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_default`
--
ALTER TABLE `mw_list_default`
ADD CONSTRAINT `fk_list_default_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_field`
--
ALTER TABLE `mw_list_field`
ADD CONSTRAINT `fk_list_field_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_field_list_field_type1` FOREIGN KEY (`type_id`) REFERENCES `mw_list_field_type` (`type_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_field_option`
--
ALTER TABLE `mw_list_field_option`
ADD CONSTRAINT `fk_list_field_option_list_field1` FOREIGN KEY (`field_id`) REFERENCES `mw_list_field` (`field_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_field_value`
--
ALTER TABLE `mw_list_field_value`
ADD CONSTRAINT `fk_list_field_value_list_field1` FOREIGN KEY (`field_id`) REFERENCES `mw_list_field` (`field_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_field_value_list_subscriber1` FOREIGN KEY (`subscriber_id`) REFERENCES `mw_list_subscriber` (`subscriber_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_form_custom_asset`
--
ALTER TABLE `mw_list_form_custom_asset`
ADD CONSTRAINT `fk_list_form_custom_asset_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_form_custom_asset_list_page_type1` FOREIGN KEY (`type_id`) REFERENCES `mw_list_page_type` (`type_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_form_custom_redirect`
--
ALTER TABLE `mw_list_form_custom_redirect`
ADD CONSTRAINT `fk_list_form_custom_redirect_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_form_custom_redirect_list_page_type1` FOREIGN KEY (`type_id`) REFERENCES `mw_list_page_type` (`type_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_form_custom_webhook`
--
ALTER TABLE `mw_list_form_custom_webhook`
ADD CONSTRAINT `fk_list_form_custom_webhook_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_form_custom_webhook_list_page_type1` FOREIGN KEY (`type_id`) REFERENCES `mw_list_page_type` (`type_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_page`
--
ALTER TABLE `mw_list_page`
ADD CONSTRAINT `fk_list_page_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_page_list_page_type1` FOREIGN KEY (`type_id`) REFERENCES `mw_list_page_type` (`type_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_segment`
--
ALTER TABLE `mw_list_segment`
ADD CONSTRAINT `fk_list_segment_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_segment_condition`
--
ALTER TABLE `mw_list_segment_condition`
ADD CONSTRAINT `fk_list_segment_condition_list_field1` FOREIGN KEY (`field_id`) REFERENCES `mw_list_field` (`field_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_segment_condition_list_segment1` FOREIGN KEY (`segment_id`) REFERENCES `mw_list_segment` (`segment_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_segment_condition_list_segment_operator1` FOREIGN KEY (`operator_id`) REFERENCES `mw_list_segment_operator` (`operator_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_subscriber`
--
ALTER TABLE `mw_list_subscriber`
ADD CONSTRAINT `fk_subscriber_list1` FOREIGN KEY (`list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_list_subscriber_action`
--
ALTER TABLE `mw_list_subscriber_action`
ADD CONSTRAINT `fk_list_subscriber_action_list1` FOREIGN KEY (`source_list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_list_subscriber_action_list2` FOREIGN KEY (`target_list_id`) REFERENCES `mw_list` (`list_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_price_plan`
--
ALTER TABLE `mw_price_plan`
ADD CONSTRAINT `fk_price_plan_customer_group1` FOREIGN KEY (`group_id`) REFERENCES `mw_customer_group` (`group_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_price_plan_order`
--
ALTER TABLE `mw_price_plan_order`
ADD CONSTRAINT `fk_price_plan_order_currency1` FOREIGN KEY (`currency_id`) REFERENCES `mw_currency` (`currency_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_price_plan_order_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_price_plan_order_price_plan1` FOREIGN KEY (`plan_id`) REFERENCES `mw_price_plan` (`plan_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_price_plan_order_price_plan_promo_code1` FOREIGN KEY (`promo_code_id`) REFERENCES `mw_price_plan_promo_code` (`promo_code_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_price_plan_order_tax1` FOREIGN KEY (`tax_id`) REFERENCES `mw_tax` (`tax_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_price_plan_order_note`
--
ALTER TABLE `mw_price_plan_order_note`
ADD CONSTRAINT `fk_price_plan_order_note_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_price_plan_order_note_price_plan_order1` FOREIGN KEY (`order_id`) REFERENCES `mw_price_plan_order` (`order_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_price_plan_order_note_user1` FOREIGN KEY (`user_id`) REFERENCES `mw_user` (`user_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_price_plan_order_transaction`
--
ALTER TABLE `mw_price_plan_order_transaction`
ADD CONSTRAINT `fk_price_plan_order_transaction_price_plan_order1` FOREIGN KEY (`order_id`) REFERENCES `mw_price_plan_order` (`order_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_sending_domain`
--
ALTER TABLE `mw_sending_domain`
ADD CONSTRAINT `fk_sending_domain_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_support_ticket`
--
ALTER TABLE `mw_support_ticket`
ADD CONSTRAINT `fk_support_ticket_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_support_ticket_support_ticket_department1` FOREIGN KEY (`department_id`) REFERENCES `mw_support_ticket_department` (`department_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_support_ticket_support_ticket_priority1` FOREIGN KEY (`priority_id`) REFERENCES `mw_support_ticket_priority` (`priority_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_support_ticket_user1` FOREIGN KEY (`user_id`) REFERENCES `mw_user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_support_ticket_reply`
--
ALTER TABLE `mw_support_ticket_reply`
ADD CONSTRAINT `fk_support_ticket_reply_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_support_ticket_reply_support_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `mw_support_ticket` (`ticket_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_support_ticket_reply_user1` FOREIGN KEY (`user_id`) REFERENCES `mw_user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_tax`
--
ALTER TABLE `mw_tax`
ADD CONSTRAINT `fk_tax_country1` FOREIGN KEY (`country_id`) REFERENCES `mw_country` (`country_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_tax_zone1` FOREIGN KEY (`zone_id`) REFERENCES `mw_zone` (`zone_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_tracking_domain`
--
ALTER TABLE `mw_tracking_domain`
ADD CONSTRAINT `fk_tracking_domain_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_transactional_email`
--
ALTER TABLE `mw_transactional_email`
ADD CONSTRAINT `fk_transactional_email_customer1` FOREIGN KEY (`customer_id`) REFERENCES `mw_customer` (`customer_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_transactional_email_log`
--
ALTER TABLE `mw_transactional_email_log`
ADD CONSTRAINT `fk_transactional_email_log_transactional_email1` FOREIGN KEY (`email_id`) REFERENCES `mw_transactional_email` (`email_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_user`
--
ALTER TABLE `mw_user`
ADD CONSTRAINT `fk_user_language1` FOREIGN KEY (`language_id`) REFERENCES `mw_language` (`language_id`) ON DELETE SET NULL ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_user_group1` FOREIGN KEY (`group_id`) REFERENCES `mw_user_group` (`group_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `mw_user_auto_login_token`
--
ALTER TABLE `mw_user_auto_login_token`
ADD CONSTRAINT `fk_user_auto_login_token_user1` FOREIGN KEY (`user_id`) REFERENCES `mw_user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_user_group_route_access`
--
ALTER TABLE `mw_user_group_route_access`
ADD CONSTRAINT `fk_user_group_route_access_user_group1` FOREIGN KEY (`group_id`) REFERENCES `mw_user_group` (`group_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_user_password_reset`
--
ALTER TABLE `mw_user_password_reset`
ADD CONSTRAINT `fk_user_password_reset_user1` FOREIGN KEY (`user_id`) REFERENCES `mw_user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_user_to_support_ticket_department`
--
ALTER TABLE `mw_user_to_support_ticket_department`
ADD CONSTRAINT `fk_user_to_support_ticket_department_support_ticket_department1` FOREIGN KEY (`department_id`) REFERENCES `mw_support_ticket_department` (`department_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
ADD CONSTRAINT `fk_user_to_support_ticket_department_user1` FOREIGN KEY (`user_id`) REFERENCES `mw_user` (`user_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `mw_zone`
--
ALTER TABLE `mw_zone`
ADD CONSTRAINT `fk_zone_country1` FOREIGN KEY (`country_id`) REFERENCES `mw_country` (`country_id`) ON DELETE CASCADE ON UPDATE NO ACTION;