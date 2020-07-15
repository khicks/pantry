START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `app_config` (
    `name` varchar(32) NOT NULL,
    `data` varchar(128) NOT NULL,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `app_metadata` (
    `name` varchar(32) NOT NULL,
    `data` varchar(128) NOT NULL,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `courses` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `title` varchar(32) NOT NULL,
    `slug` varchar(32) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cuisines` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `title` varchar(32) NOT NULL,
    `slug` varchar(32) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `images` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `mime_type` varchar(20) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `recipes` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    `title` varchar(64) NOT NULL,
    `slug` varchar(40) NOT NULL,
    `blurb` varchar(100) NOT NULL,
    `description` mediumtext NOT NULL,
    `servings` tinyint(4) NOT NULL,
    `prep_time` mediumint(6) NOT NULL,
    `cook_time` mediumint(6) NOT NULL,
    `ingredients` mediumtext NOT NULL,
    `directions` mediumtext NOT NULL,
    `source` varchar(2084) NOT NULL,
    `visibility_level` tinyint(1) NOT NULL,
    `default_permission_level` tinyint(1) NOT NULL,
    `featured` bit(1) NOT NULL DEFAULT b'0',
    `author_id` char(36) DEFAULT NULL,
    `course_id` char(36) DEFAULT NULL,
    `cuisine_id` char(36) DEFAULT NULL,
    `image_id` char(36) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `created` (`created`),
    KEY `updated` (`updated`),
    KEY `author_id` (`author_id`),
    KEY `course_id` (`course_id`),
    KEY `cuisine_id` (`cuisine_id`),
    KEY `featured` (`id`),
    KEY `title` (`title`) USING BTREE,
    KEY `visibility_level` (`visibility_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `recipes_permissions` (
    `id` char(36) NOT NULL,
    `recipe_id` char(36) NOT NULL,
    `user_id` char(36) NOT NULL,
    `level` tinyint(1) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `recipe_id` (`recipe_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sessions` (
    `id` char(36) NOT NULL,
    `updated` datetime NOT NULL,
    `session_data` mediumtext NOT NULL,
    PRIMARY KEY (`id`),
    KEY `updated` (`updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `two_factor_keys` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `user_id` char(36) NOT NULL,
    `two_factor_key` char(64) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `two_factor_logins` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `user_id` char(36) NOT NULL,
    `verification_code` char(6) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `check_idx` (`user_id`,`verification_code`),
    KEY `created` (`created`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `two_factor_sessions` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `user_id` char(36) NOT NULL,
    `secret` char(128) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `check_idx` (`id`,`user_id`,`secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `username` varchar(32) NOT NULL,
    `password` varchar(255) NOT NULL,
    `is_admin` bit(1) NOT NULL,
    `is_disabled` bit(1) NOT NULL,
    `last_login` datetime DEFAULT NULL,
    `first_name` varchar(64) NOT NULL,
    `last_name` varchar(64) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    KEY `created` (`created`),
    KEY `is_admin` (`is_admin`),
    KEY `is_disabled` (`is_disabled`),
    KEY `last_login` (`last_login`),
    KEY `first_name` (`first_name`),
    KEY `last_name` (`last_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` char(36) NOT NULL,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    `session_id` char(36) NOT NULL,
    `user_id` char(36) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `created` (`created`),
    KEY `updated` (`updated`),
    KEY `session_id` (`session_id`),
    KEY `user_id` (`user_id`),
    KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
COMMIT;
