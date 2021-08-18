DROP TABLE IF EXISTS `auth_confirm`;
DROP TABLE IF EXISTS `auth_grant`;
DROP TABLE IF EXISTS `auth_recovery`;
DROP TABLE IF EXISTS `auth_session`;
DROP TABLE IF EXISTS `user`;

-- --------------------------------------------------------

--
-- Структура таблицы `auth_confirm`
--

CREATE TABLE `auth_confirm` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` tinyint(1) NOT NULL,
  `to` varchar(60) NOT NULL,
  `code` char(7) NOT NULL,
  `create_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `complete_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `auth_grant`
--

CREATE TABLE `auth_grant` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `source` varchar(8) NOT NULL,
  `source_key` varchar(60) NOT NULL,
  `create_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `auth_recovery`
--

CREATE TABLE `auth_recovery` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` tinyint(1) NOT NULL,
  `to` varchar(60) NOT NULL,
  `code` char(7) NOT NULL,
  `create_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `complete_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `auth_session`
--

CREATE TABLE `auth_session` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` char(30) NOT NULL,
  `auth_grant_id` int(10) UNSIGNED NOT NULL,
  `grant_token` varchar(250) DEFAULT NULL,
  `user_ip` char(15) DEFAULT NULL,
  `user_agent` varchar(250) DEFAULT NULL,
  `user_os` char(60) DEFAULT NULL,
  `user_browser` char(15) DEFAULT NULL,
  `create_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

CREATE TABLE `user` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(30) DEFAULT NULL,
  `last_name` varchar(30) DEFAULT NULL,
  `email` char(60) DEFAULT NULL,
  `phone` char(16) DEFAULT NULL,
  `login` char(30) DEFAULT NULL,
  `create_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `auth_confirm`
--
ALTER TABLE `auth_confirm`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`,`code`);

--
-- Индексы таблицы `auth_grant`
--
ALTER TABLE `auth_grant`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `source` (`source`,`source_key`);

--
-- Индексы таблицы `auth_recovery`
--
ALTER TABLE `auth_recovery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`,`code`);

--
-- Индексы таблицы `auth_session`
--
ALTER TABLE `auth_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `auth_grant_id` (`auth_grant_id`);

--
-- Индексы таблицы `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `auth_confirm`
--
ALTER TABLE `auth_confirm`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `auth_grant`
--
ALTER TABLE `auth_grant`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `auth_recovery`
--
ALTER TABLE `auth_recovery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `auth_session`
--
ALTER TABLE `auth_session`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `user`
--
ALTER TABLE `user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
