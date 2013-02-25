-- phpMyAdmin SQL Dump
-- version 3.1.2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Jun 08, 2009 at 11:23 AM
-- Server version: 5.1.31
-- PHP Version: 5.2.8

SET NAMES 'utf8';
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- 
-- Database: `sharky_cluster`
-- 

-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

-- 
-- Table structure for table `oauth_log`
-- 

CREATE TABLE IF NOT EXISTS `oauth_log` (
  `olg_id` int(11) NOT NULL AUTO_INCREMENT,
  `olg_osr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_ost_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_ocr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_oct_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `olg_usa_id_ref` int(11) DEFAULT NULL,
  `olg_received` text NOT NULL,
  `olg_sent` text NOT NULL,
  `olg_base_string` text NOT NULL,
  `olg_notes` text NOT NULL,
  `olg_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `olg_remote_ip` bigint(20) NOT NULL,
  PRIMARY KEY (`olg_id`),
  KEY `olg_osr_consumer_key` (`olg_osr_consumer_key`,`olg_id`),
  KEY `olg_ost_token` (`olg_ost_token`,`olg_id`),
  KEY `olg_ocr_consumer_key` (`olg_ocr_consumer_key`,`olg_id`),
  KEY `olg_oct_token` (`olg_oct_token`,`olg_id`),
  KEY `olg_usa_id_ref` (`olg_usa_id_ref`,`olg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `oauth_log`
-- 


-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

-- 
-- Table structure for table `oauth_server_nonce`
-- 

CREATE TABLE IF NOT EXISTS `oauth_server_nonce` (
  `osn_id` int(11) NOT NULL AUTO_INCREMENT,
  `osn_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osn_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osn_timestamp` bigint(20) NOT NULL,
  `osn_nonce` varchar(80) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`osn_id`),
  UNIQUE KEY `osn_consumer_key` (`osn_consumer_key`,`osn_token`,`osn_timestamp`,`osn_nonce`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `oauth_server_nonce`
-- 


-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

-- 
-- Table structure for table `oauth_server_registry`
-- 

CREATE TABLE IF NOT EXISTS `oauth_server_registry` (
  `osr_id` int(11) NOT NULL AUTO_INCREMENT,
  `osr_usa_id_ref` int(11) DEFAULT NULL,
  `osr_consumer_key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osr_consumer_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `osr_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `osr_status` varchar(16) NOT NULL,
  `osr_requester_name` varchar(64) NOT NULL,
  `osr_requester_email` varchar(64) NOT NULL,
  `osr_callback_uri` varchar(255) NOT NULL,
  `osr_application_uri` varchar(255) NOT NULL,
  `osr_application_title` varchar(80) NOT NULL,
  `osr_application_descr` text NOT NULL,
  `osr_application_notes` text NOT NULL,
  `osr_application_type` varchar(20) NOT NULL,
  `osr_application_commercial` tinyint(1) NOT NULL DEFAULT '0',
  `osr_issue_date` datetime NOT NULL,
  `osr_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`osr_id`),
  UNIQUE KEY `osr_consumer_key` (`osr_consumer_key`),
  KEY `osr_usa_id_ref` (`osr_usa_id_ref`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- 
-- Dumping data for table `oauth_server_registry`
-- 

INSERT INTO `oauth_server_registry` (`osr_id`, `osr_usa_id_ref`, `osr_consumer_key`, `osr_consumer_secret`, `osr_enabled`, `osr_status`, `osr_requester_name`, `osr_requester_email`, `osr_callback_uri`, `osr_application_uri`, `osr_application_title`, `osr_application_descr`, `osr_application_notes`, `osr_application_type`, `osr_application_commercial`, `osr_issue_date`, `osr_timestamp`) VALUES
(1, 1, '4d28afe1a6a2792e07732758561bf9c004a160e76', '2d90548bcf30e0afe5c31c8909766597', 1, 'active', 'bill', 'chinalu@gmail.com', 'http://www.mcc.com/oauth/callback', 'http://www.mcc.com/', '', '', '', '', 0, '2009-05-22 10:31:18', '2009-05-22 10:31:18'),
(3, 1, '33140034d3ec5c19542b0d23a23fd53104a164643', '3a1a5ca4fc83fcc6fdef96597184dd4d', 0, '', 'bill', 'chinalu@gmail.com', 'http://www.mcc.com/callback', 'http://www.mcc.com', 'Desker', '桌面', '', '', 0, '2009-05-22 14:29:23', '0000-00-00 00:00:00');

-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

-- 
-- Table structure for table `oauth_server_token`
-- 

CREATE TABLE IF NOT EXISTS `oauth_server_token` (
  `ost_id` int(11) NOT NULL AUTO_INCREMENT,
  `ost_osr_id_ref` int(11) NOT NULL,
  `ost_usa_id_ref` int(11) NOT NULL,
  `ost_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ost_token_secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ost_token_type` enum('request','access') DEFAULT NULL,
  `ost_authorized` tinyint(1) NOT NULL DEFAULT '0',
  `ost_referrer_host` varchar(128) NOT NULL,
  `ost_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ost_id`),
  UNIQUE KEY `ost_token` (`ost_token`),
  KEY `ost_osr_id_ref` (`ost_osr_id_ref`),
  KEY `ost_usa_id_ref` (`ost_usa_id_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `oauth_server_token`
-- 



-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

-- 
-- Table structure for table `sharky_nodes`
-- 

CREATE TABLE IF NOT EXISTS `sharky_nodes` (
  `node_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `shard_id` int(11) unsigned NOT NULL COMMENT '所属Shard ID',
  `host` varchar(50) NOT NULL,
  `db_name` varchar(50) NOT NULL,
  `user` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  PRIMARY KEY (`node_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Shard节点' AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `sharky_nodes`
-- 

INSERT INTO `sharky_nodes` (`node_id`, `shard_id`, `host`, `db_name`, `user`, `password`) VALUES
(1, 1, 'localhost', 'sharky_shard_001', 'root', '123456'),
(2, 2, 'localhost', 'sharky_shard_002', 'root', '123456');

-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

-- 
-- Table structure for table `sharky_routing`
-- 

CREATE TABLE IF NOT EXISTS `sharky_routing` (
  `sharding_clue` varchar(100) NOT NULL,
  `shard_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`sharding_clue`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `sharky_routing`
-- 

INSERT INTO `sharky_routing` (`sharding_clue`, `shard_id`) VALUES
('user_id@1', 1),
('user_id@2', 1),
('user_id@3', 1),
('user_id@4', 1),
('user_id@5', 1),
('user_id@6', 1),
('user_id@7', 1),
('user_id@8', 1);

-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

-- 
-- Table structure for table `sharky_shards`
-- 

CREATE TABLE IF NOT EXISTS `sharky_shards` (
  `shard_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Shard ID',
  `created_at` date NOT NULL COMMENT '创建时间',
  `status` tinyint(1) NOT NULL COMMENT '状态（可用，不可用，移动）',
  PRIMARY KEY (`shard_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Shard表' AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `sharky_shards`
-- 

INSERT INTO `sharky_shards` (`shard_id`, `created_at`, `status`) VALUES
(1, '2009-04-17', 0),
(2, '2009-04-20', 0);

-- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

-- 
-- Table structure for table `sk_users`
-- 

CREATE TABLE IF NOT EXISTS `sk_users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(32) CHARACTER SET utf8 NOT NULL COMMENT '用户名',
  `password` varchar(45) CHARACTER SET utf8 NOT NULL COMMENT '密码',
  `email` varchar(100) CHARACTER SET utf8 NOT NULL COMMENT '电子邮件',
  `icon_bucket` varchar(32) NOT NULL,
  `icon_key` varchar(100) NOT NULL,
  `roles` varchar(255) NOT NULL COMMENT '用户角色',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `idx_email` (`email`),
  UNIQUE KEY `idx_username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='用户表' AUTO_INCREMENT=9 ;

-- 
-- Dumping data for table `sk_users`
-- 

INSERT INTO `sk_users` (`user_id`, `username`, `password`, `email`, `icon_bucket`, `icon_key`, `roles`) VALUES
(1, 'bill', 'e9f59da0bef5ab1be6e61e30143935a404a34b89', 'bill@gmail.com', '', '', ''),
(2, 'test001', 'e9f59da0bef5ab1be6e61e30143935a404a34b89', 'test001@msn.com', '', '', ''),
(3, 'test002', 'e9f59da0bef5ab1be6e61e30143935a404a34b89', 'test002@msn.com', '', '', '');


CREATE TABLE IF NOT EXISTS `sk_user_tokens` (
  `user_id` int(11) unsigned NOT NULL,
  `token` varchar(32) NOT NULL,
  `ip` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL,
  `expire_date` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Constraints for dumped tables
-- 

-- 
-- Constraints for table `oauth_server_token`
-- 
ALTER TABLE `oauth_server_token`
  ADD CONSTRAINT `oauth_server_token_ibfk_1` FOREIGN KEY (`ost_osr_id_ref`) REFERENCES `oauth_server_registry` (`osr_id`) ON DELETE CASCADE ON UPDATE CASCADE;
