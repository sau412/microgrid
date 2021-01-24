SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `log` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `payouts` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `amount` decimal(16,8) NOT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'requested',
  `wallet_uid` int(11) DEFAULT NULL,
  `tx_id` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `projects` (
  `uid` tinyint(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `is_enabled` int(11) NOT NULL DEFAULT '1',
  `start_number` bigint(20) NOT NULL,
  `stop_number` bigint(20) NOT NULL,
  `max_stop_number` bigint(20) NOT NULL DEFAULT '0',
  `new_tasks_cache` text COLLATE utf8_unicode_ci NOT NULL,
  `step` bigint(20) NOT NULL,
  `retries` int(11) NOT NULL,
  `workunit_price` decimal(16,8) NOT NULL,
  `workunit_timeout` int(11) NOT NULL,
  `function` text COLLATE utf8_unicode_ci NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `sessions` (
  `uid` bigint(20) NOT NULL,
  `user_uid` int(11) DEFAULT NULL,
  `session` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `captcha` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `users` (
  `uid` bigint(20) NOT NULL,
  `mail` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `balance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `total_earned` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `total_results` int(11) NOT NULL DEFAULT '0',
  `valid_results` int(11) NOT NULL DEFAULT '0',
  `in_process` int(11) NOT NULL DEFAULT '0',
  `paid_results` int(11) NOT NULL DEFAULT '0',
  `not_paid_results` int(11) NOT NULL DEFAULT '0',
  `register_time` datetime NOT NULL,
  `login_time` datetime NOT NULL,
  `active_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `withdraw_address` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_admin` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `variables` (
  `uid` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `variables` (`uid`, `name`, `value`, `timestamp`) VALUES
(1, 'login_enabled', '1', '2021-01-24 00:00:00'),
(2, 'payouts_enabled', '1', '2021-01-24 00:00:00'),
(3, 'info', '', '2021-01-24 00:00:00'),
(4, 'global_message', '', '2021-01-24 00:00:00'),
(6, 'wallet_balance', '0', '2021-01-24 00:00:00'),
(7, 'rating_cache', '[]', '2021-01-24 00:00:00'),
(8, 'users', '0', '2021-01-24 00:00:00'),
(9, 'active_users', '0', '2021-01-24 00:00:00'),
(10, 'workunits', '0', '2021-01-24 00:00:00'),
(11, 'results', '0', '2021-01-24 00:00:00'),
(12, 'workunits_complete', '0', '2021-01-24 00:00:00'),
(13, 'results_complete', '0', '2021-01-24 00:00:00'),
(14, 'users_balance', '0', '2021-01-24 00:00:00');

CREATE TABLE `workunits` (
  `uid` bigint(20) NOT NULL,
  `project_uid` tinyint(11) NOT NULL,
  `start_number` bigint(20) NOT NULL,
  `stop_number` bigint(20) NOT NULL,
  `in_progress` tinyint(4) NOT NULL DEFAULT '0',
  `is_completed` tinyint(4) NOT NULL DEFAULT '0',
  `result` longtext COLLATE utf8_unicode_ci,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `workunit_results` (
  `uid` bigint(20) NOT NULL,
  `workunit_uid` bigint(20) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `is_valid` tinyint(4) DEFAULT NULL,
  `reward` decimal(16,8) DEFAULT NULL,
  `result_hash` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `log`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `payouts`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `status` (`status`);

ALTER TABLE `projects`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `is_enabled` (`is_enabled`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid_session` (`session`,`user_uid`) USING BTREE;

ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `mail` (`mail`),
  ADD KEY `active_time` (`active_time`);

ALTER TABLE `variables`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `workunits`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `project_uid` (`project_uid`,`stop_number`) USING BTREE,
  ADD KEY `start_number` (`start_number`),
  ADD KEY `is_completed` (`is_completed`,`in_progress`,`project_uid`) USING BTREE;

ALTER TABLE `workunit_results`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `user_uid` (`user_uid`),
  ADD KEY `workunit_uid` (`workunit_uid`,`result_hash`) USING BTREE,
  ADD KEY `created` (`created`),
  ADD KEY `result_hash` (`result_hash`);


ALTER TABLE `log`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `payouts`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `projects`
  MODIFY `uid` tinyint(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sessions`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `variables`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `workunits`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `workunit_results`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;