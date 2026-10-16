-- phpMyAdmin SQL Dump
-- version 2.11.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 30, 2026 at 05:17 AM
-- Server version: 5.0.51
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `baitkom_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL default '1',
  `total_price` decimal(10,2) default NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) default NULL,
  `notes` text,
  `status` enum('pending','assigned','in_progress','completed','cancelled') default 'pending',
  `completion_comment` text,
  `payment_status` enum('pending','paid','failed') default 'pending',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `service_id` (`service_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21 ;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `service_id`, `quantity`, `total_price`, `booking_date`, `booking_time`, `address`, `city`, `notes`, `status`, `completion_comment`, `payment_status`, `created_at`) VALUES
(17, 35, 4, 1, '100.00', '2026-10-01', '10:00:00', 'ÙƒÙˆØ³ØªÙŠ Ø­ÙŠ Ø§Ù„Ø§Ù†Ø¯Ù„Ø³', 'kosti', '', 'completed', '', 'paid', '2026-09-30 03:03:28'),
(18, 35, 4, 1, '100.00', '2026-10-01', '21:09:00', 'ÙƒÙˆØ³ØªÙŠ Ø­ÙŠ Ø§Ù„Ø§Ù†Ø¯Ù„Ø³', 'kosti', '', 'completed', '', 'paid', '2026-09-30 03:24:39'),
(19, 35, 7, 1, '20.00', '2026-10-01', '10:00:00', 'Ø±Ø¨Ùƒ Ø­ÙŠ Ø§Ù„Ø¹Ù…Ø§Ø±Ø© ØºØ±Ø¨ Ø§Ù„Ù‚Ø³Ù… Ø§Ù„Ø§ÙˆØ³Ø·', 'rabak', '', 'completed', '', 'pending', '2026-09-30 04:30:02'),
(20, 35, 7, 10, '200.00', '2026-10-01', '22:00:00', 'ÙƒÙˆØ³ØªÙŠ Ø­ÙŠ Ø§Ù„Ø§Ù†Ø¯Ù„Ø³', 'kosti', '', 'completed', '', 'paid', '2026-09-30 04:40:57');

-- --------------------------------------------------------

--
-- Table structure for table `commission_settings`
--

CREATE TABLE `commission_settings` (
  `id` int(11) NOT NULL auto_increment,
  `commission_rate` decimal(5,2) NOT NULL default '10.00',
  `updated_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `commission_settings`
--

INSERT INTO `commission_settings` (`id`, `commission_rate`, `updated_at`) VALUES
(1, '10.00', '2026-09-30 02:21:04');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) default NULL,
  `subject` varchar(200) default NULL,
  `message` text,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `name`, `email`, `phone`, `subject`, `message`, `created_at`) VALUES
(1, 'Sahar Mohamed', 'Sahar107@gmil.com', '0987654321', 'Ø§Ø³ØªÙØ³Ø§Ø± Ø¹Ø§Ù…', 'Ø§ÙŠÙ† Ø§Ù„Ù…Ù‚Ø± Ø§Ù„Ø±Ø¦ÙŠØ³ÙŠ Ù„Ù„Ø´Ø±ÙƒØ©', '2026-09-24 13:19:02');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL auto_increment,
  `email` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `password_resets`
--


-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) default NULL,
  `amount` decimal(10,2) NOT NULL,
  `commission_amount` decimal(12,2) default NULL,
  `provider_amount` decimal(12,2) default NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` varchar(50) default 'completed',
  `settled_at` timestamp NULL default NULL,
  `transaction_id` varchar(100) default NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `user_id` (`user_id`),
  KEY `booking_id` (`booking_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `booking_id`, `amount`, `commission_amount`, `provider_amount`, `payment_method`, `status`, `settled_at`, `transaction_id`, `created_at`) VALUES
(5, 35, NULL, '20.00', NULL, NULL, 'bankak', 'completed', NULL, 'TRX-6AB56042A3924', '2026-09-24 20:39:14'),
(6, 33, NULL, '0.00', '0.00', '0.00', 'bankak', 'completed', NULL, 'TRX-6ABC49B1A3770', '2026-09-30 02:28:49'),
(7, 33, NULL, '200.00', '20.00', '180.00', 'bankak', 'completed', NULL, 'TRX-6ABC509393AA6', '2026-09-30 02:58:11'),
(8, 35, 17, '100.00', '10.00', '90.00', 'bankak', 'completed', '2026-09-30 03:15:32', 'TRX-6ABC51DDDFE82', '2026-09-30 03:03:41'),
(9, 35, 18, '100.00', '10.00', '90.00', 'bankak', 'completed', '2026-09-30 03:32:23', 'TRX-6ABC56CF6DC7E', '2026-09-30 03:24:47'),
(10, 35, 20, '200.00', '20.00', '180.00', 'bankak', 'completed', '2026-09-30 05:13:33', 'TRX-6ABC68C246B97', '2026-09-30 04:41:22');

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `specialty` varchar(100) default NULL,
  `experience_years` int(11) default '0',
  `rating` decimal(2,1) default '0.0',
  `about` text,
  `portfolio_url` varchar(255) default NULL,
  `verified` tinyint(1) default '0',
  `working_hours` varchar(255) default NULL,
  `city` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `user_id`, `specialty`, `experience_years`, `rating`, `about`, `portfolio_url`, `verified`, `working_hours`, `city`) VALUES
(14, 33, 'Ø·Ø¨ÙŠØ®', 12, '4.0', NULL, NULL, 1, 'Ù…Ù†7 Øµ Ø§Ù„Ù‰ 10 Ù…Ø³Ø§Ø¡', 'rabak'),
(15, 36, 'ÙƒÙ‡Ø±Ø¨Ø§Ø¡', 5, '0.0', NULL, NULL, 0, 'Ù…Ù†7 Øµ Ø§Ù„Ù‰ 10 Ù…Ø³Ø§Ø¡', 'kosti');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `provider_id` int(11) default NULL,
  `booking_id` int(11) default NULL,
  `review_type` enum('service','provider','site') default 'service',
  `rating` int(11) NOT NULL,
  `comment` text,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `provider_id` (`provider_id`),
  KEY `booking_id` (`booking_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `provider_id`, `booking_id`, `review_type`, `rating`, `comment`, `created_at`) VALUES
(6, 33, NULL, NULL, 'site', 5, 'Ø§Ù„Ù…Ù†ØµØ© Ø¬ÙŠØ¯Ø© Ø¬Ø¯Ø§', '2026-09-24 20:00:27'),
(7, 35, 33, NULL, 'provider', 4, 'Ù…Ù„ÙƒØ© Ø§Ù„ØªØ¹Ø§Ù…Ù„ Ø§Ù„Ø±Ø§Ù‚ÙŠ', '2026-09-24 21:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL auto_increment,
  `provider_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `allow_quantity` tinyint(1) NOT NULL default '0',
  `max_quantity` int(11) default NULL,
  `category` varchar(50) default NULL,
  `icon` varchar(50) default 'fa-tools',
  `badge` varchar(50) default NULL,
  `image` varchar(255) default NULL,
  `status` enum('active','inactive') default 'active',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `provider_id` (`provider_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `provider_id`, `name`, `description`, `price`, `allow_quantity`, `max_quantity`, `category`, `icon`, `badge`, `image`, `status`, `created_at`) VALUES
(4, 36, 'ØµÙŠØ§Ù†Ø© Ø§Ù„Ù…ÙƒÙŠÙØ§Øª', 'ØªØ¹Ø¨Ø¦Ø© Ø§Ù„ØºØ§Ø²', '100.00', 0, NULL, 'ØªÙƒÙŠÙŠÙ', 'fa-tools', NULL, 'uploads/services/1790582423_6aba1e972a9d8.jpg', 'active', '2026-09-28 10:59:23'),
(7, 33, 'Ø´ÙŠØ² ÙƒÙŠÙƒØ© Ø¨Ø§Ù„ÙƒØ±Ø§Ù…ÙŠÙ„', 'Ø´ÙŠØ² ÙƒÙŠÙƒ Ø¨Ø·Ø¹Ù… Ø§Ù„ÙƒØ±Ø§Ù…ÙŠÙ„', '20.00', 1, 10, 'Ø·Ø¨ÙŠØ®', 'fa-tools', NULL, 'uploads/services/1790731697_6abc65b11de32.jpg', 'active', '2026-09-30 04:28:17');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL auto_increment,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'Ø¨ÙŠØªÙƒÙ…', '2026-08-21 00:20:00'),
(2, 'site_email', 'baitkom@gmail.com', '2026-08-21 00:20:00'),
(3, 'site_phone', '0912602109', '2026-08-21 00:20:00'),
(4, 'site_address', 'ÙˆÙ„Ø§ÙŠØ© Ø§Ù„Ù†ÙŠÙ„ Ø§Ù„Ø£Ø¨ÙŠØ¶ | Ø±Ø¨Ùƒ-ÙƒÙˆØ³ØªÙŠ', '2026-08-21 00:20:00'),
(5, 'site_description', 'Ù…Ù†ØµØ© Ø®Ø¯Ù…Ø§Øª Ù…Ù†Ø²Ù„ÙŠØ© Ù…ØªÙƒØ§Ù…Ù„Ø© ÙÙŠ ÙˆÙ„Ø§ÙŠØ© Ø§Ù„Ù†ÙŠÙ„ Ø§Ù„Ø£Ø¨ÙŠØ¶ Ø±Ø¨Ùƒ-ÙƒÙˆØ³ØªÙŠ', '2026-08-21 00:20:00'),
(6, 'site_slogan', 'خدمات منزلك بسهولة', '2026-09-24 11:26:32'),
(7, 'site_cities', 'rabak,kosti', '2026-09-24 11:26:32');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL auto_increment,
  `wallet_id` int(11) NOT NULL,
  `booking_id` int(11) default NULL,
  `amount` decimal(12,2) NOT NULL,
  `type` enum('deposit','commission','transfer','withdrawal','refund') NOT NULL,
  `description` varchar(255) default NULL,
  `balance_after` decimal(12,2) default NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `idx_wallet` (`wallet_id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `wallet_id`, `booking_id`, `amount`, `type`, `description`, `balance_after`, `created_at`) VALUES
(1, 1, 8, '0.00', 'deposit', 'Ø¯ÙØ¹Ø© Ù…Ù† Ø§Ù„Ø¹Ù…ÙŠÙ„ - Ø­Ø¬Ø² #8', '0.00', '2026-09-30 02:28:49'),
(2, 1, 16, '200.00', 'deposit', 'Ø¯ÙØ¹Ø© Ù…Ù† Ø§Ù„Ø¹Ù…ÙŠÙ„ - Ø­Ø¬Ø² #16', '200.00', '2026-09-30 02:58:11'),
(3, 1, 17, '100.00', 'deposit', 'Ø¯ÙØ¹Ø© Ù…Ù† Ø§Ù„Ø¹Ù…ÙŠÙ„ - Ø­Ø¬Ø² #17', '300.00', '2026-09-30 03:03:41'),
(4, 1, 17, '10.00', 'commission', 'Ø¹Ù…ÙˆÙ„Ø© Ø§Ù„Ù…Ù†ØµØ© (10%) Ù…Ù† Ø­Ø¬Ø² #17', '210.00', '2026-09-30 03:15:32'),
(5, 2, 17, '90.00', 'transfer', 'Ø£Ø±Ø¨Ø§Ø­ Ù…Ù† Ø­Ø¬Ø² #17 (Ø¨Ø¹Ø¯ Ø®ØµÙ… 10% Ø¹Ù…ÙˆÙ„Ø©)', '90.00', '2026-09-30 03:15:32'),
(6, 1, 18, '100.00', 'deposit', 'Ø¯ÙØ¹Ø© Ù…Ù† Ø§Ù„Ø¹Ù…ÙŠÙ„ - Ø­Ø¬Ø² #18', '300.00', '2026-09-30 03:24:47'),
(7, 1, 18, '10.00', 'commission', 'Ø¹Ù…ÙˆÙ„Ø© Ø§Ù„Ù…Ù†ØµØ© (10%) Ù…Ù† Ø­Ø¬Ø² #18', '210.00', '2026-09-30 03:32:23'),
(8, 2, 18, '90.00', 'transfer', 'Ø£Ø±Ø¨Ø§Ø­ Ù…Ù† Ø­Ø¬Ø² #18 (Ø¨Ø¹Ø¯ Ø®ØµÙ… 10% Ø¹Ù…ÙˆÙ„Ø©)', '180.00', '2026-09-30 03:32:23'),
(9, 1, 19, '2.00', 'commission', 'Ø¹Ù…ÙˆÙ„Ø© Ø§Ù„Ù…Ù†ØµØ© (10%) Ù…Ù† Ø­Ø¬Ø² #19', '182.00', '2026-09-30 04:31:48'),
(10, 3, 19, '18.00', 'transfer', 'Ø£Ø±Ø¨Ø§Ø­ Ù…Ù† Ø­Ø¬Ø² #19 (Ø¨Ø¹Ø¯ Ø®ØµÙ… 10% Ø¹Ù…ÙˆÙ„Ø©)', '18.00', '2026-09-30 04:31:48'),
(11, 1, 20, '200.00', 'deposit', 'Ø¯ÙØ¹Ø© Ù…Ù† Ø§Ù„Ø¹Ù…ÙŠÙ„ - Ø­Ø¬Ø² #20', '380.00', '2026-09-30 04:41:22'),
(12, 1, 20, '20.00', 'commission', 'Ø¹Ù…ÙˆÙ„Ø© Ø§Ù„Ù…Ù†ØµØ© (10%) Ù…Ù† Ø­Ø¬Ø² #20', '200.00', '2026-09-30 05:13:33'),
(13, 3, 20, '180.00', 'transfer', 'Ø£Ø±Ø¨Ø§Ø­ Ù…Ù† Ø­Ø¬Ø² #20 (Ø¨Ø¹Ø¯ Ø®ØµÙ… 10% Ø¹Ù…ÙˆÙ„Ø©)', '198.00', '2026-09-30 05:13:33'),
(14, 3, NULL, '100.00', 'withdrawal', 'Ø·Ù„Ø¨ Ø³Ø­Ø¨ Ù…Ù† Ù…Ù‚Ø¯Ù… Ø§Ù„Ø®Ø¯Ù…Ø©', '98.00', '2026-09-30 05:14:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `region` varchar(100) NOT NULL,
  `city` varchar(50) default NULL,
  `age` int(11) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','provider','receiver') default 'provider',
  `admin_role` enum('super_admin','main','providers','clients','reviews') default NULL,
  `manager_role` varchar(50) default NULL,
  `is_approved` enum('pending','approved','rejected') default 'pending',
  `profile_image` varchar(255) default NULL,
  `national_id_image` varchar(255) default NULL,
  `certificate_image` varchar(255) default NULL,
  `working_area` varchar(255) default NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `is_banned` tinyint(1) NOT NULL default '0',
  `welcome_shown` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_is_approved` (`is_approved`),
  KEY `idx_user_type` (`user_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=38 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `phone`, `region`, `city`, `age`, `service_type`, `password`, `user_type`, `admin_role`, `manager_role`, `is_approved`, `profile_image`, `national_id_image`, `certificate_image`, `working_area`, `created_at`, `is_banned`, `welcome_shown`) VALUES
(28, 'Malek Mohammed', 'Super_admin', 'Super admin@baitkom.com', '0987654321', 'Ù…Ø±Ø¨Ø¹ 27', 'kosti', 30, 'إدارة', 'e10adc3949ba59abbe56e057f20f883e', 'admin', 'super_admin', NULL, 'approved', 'uploads/users/1790273810_6ab569122ae38.jpg', NULL, NULL, '', '2026-09-24 09:18:09', 0, 0),
(29, 'Khaled Ali', 'Customers_admin', 'Customers admin@baitkom.com', '0910000002', 'كوستي', NULL, 30, 'إدارة', 'e10adc3949ba59abbe56e057f20f883e', 'admin', 'clients', NULL, 'approved', NULL, NULL, NULL, NULL, '2026-09-24 09:18:09', 0, 0),
(30, 'Ali Ahmed', 'reviews_admin', 'Reviews admin@baitkom.com', '0910000003', 'ربك', NULL, 30, 'إدارة', 'e10adc3949ba59abbe56e057f20f883e', 'admin', 'reviews', NULL, 'approved', NULL, NULL, NULL, NULL, '2026-09-24 09:18:09', 0, 0),
(31, 'Ahmed Mohammed', 'Service provider_admin', 'Service provider admin@baitkom.com', '0987654321', 'ÙƒÙ„ Ø§Ù„Ø§Ø­ÙŠØ§Ø¡', 'rabak', 30, 'إدارة', 'e10adc3949ba59abbe56e057f20f883e', 'admin', 'providers', NULL, 'approved', NULL, NULL, NULL, '', '2026-09-24 12:16:51', 0, 0),
(33, 'Sahar Mohamed', 'Sahar_Mohamed', 'Sahar108@gmil.com', '0961388112', 'Ø­ÙŠ Ø§Ù„Ø¹Ù…Ø§Ø±Ù‡', 'rabak', 25, 'Ø·Ø¨ÙŠØ®', '25d55ad283aa400af464c76d713c07ad', 'provider', NULL, NULL, 'approved', NULL, 'uploads/ids/1790267811_6ab551a3b2e51.jpg', 'uploads/certificates/1790267811_6ab551a3b3f91.jpg', 'ÙƒÙ„ Ø£Ø­ÙŠØ§Ø¡ Ø±Ø¨Ùƒ', '2026-09-24 19:36:51', 0, 1),
(35, 'Rawaa Mohamed', 'Rawaa_Mohamed', 'Rawaa109@gmil.com', '0987654322', 'Ø­ÙŠ Ø§Ù„Ù†ØµØ±', 'kosti', 25, '', '25f9e794323b453885f5181f1b624d0b', 'receiver', NULL, NULL, 'approved', 'uploads/users/1790270770_6ab55d3276633.png', NULL, NULL, '', '2026-09-24 20:26:10', 0, 0),
(36, 'ismabashiril', 'ismail1312', 'ismail32122@gmail.com', '0914511411', 'Ø§Ø¨Ùˆ Ø´Ø±ÙŠÙ', 'kosti', 23, 'ÙƒÙ‡Ø±Ø¨Ø§Ø¡', 'e10adc3949ba59abbe56e057f20f883e', 'provider', NULL, NULL, 'approved', 'uploads/users/1790581729_6aba1be163e80.jpg', 'uploads/ids/1790581729_6aba1be165624.jpg', NULL, 'ÙƒÙ„ Ø§Ø­ÙŠØ§Ø¡ ÙƒÙˆØ³ØªÙŠ', '2026-09-28 10:48:49', 0, 1),
(37, 'ahmed', 'mohammed', 'asd@gamil.com', '143341443141', 'Ø­ÙŠ Ø§Ù„Ù†ØµØ±', 'kosti', 43, '', '25d55ad283aa400af464c76d713c07ad', 'receiver', NULL, NULL, 'approved', 'uploads/users/1790584286_6aba25deb4011.jpg', NULL, NULL, '', '2026-09-28 11:31:26', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) default NULL,
  `type` enum('platform','provider') NOT NULL default 'provider',
  `balance` decimal(12,2) NOT NULL default '0.00',
  `total_earned` decimal(12,2) NOT NULL default '0.00',
  `total_withdrawn` decimal(12,2) NOT NULL default '0.00',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `updated_at` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `type`, `balance`, `total_earned`, `total_withdrawn`, `created_at`, `updated_at`) VALUES
(1, NULL, 'platform', '180.00', '600.00', '0.00', '2026-09-30 02:21:04', '2026-09-30 05:13:33'),
(2, 36, 'provider', '180.00', '180.00', '0.00', '2026-09-30 03:15:32', '2026-09-30 03:32:23'),
(3, 33, 'provider', '98.00', '198.00', '100.00', '2026-09-30 04:31:48', '2026-09-30 05:14:52');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `providers`
--
ALTER TABLE `providers`
  ADD CONSTRAINT `providers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
