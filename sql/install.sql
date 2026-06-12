CREATE TABLE IF NOT EXISTS `PREFIX_tec_gsc_config` (
    `id_config` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
    `client_id` VARCHAR(255) NOT NULL DEFAULT '',
    `client_secret` VARCHAR(255) NOT NULL DEFAULT '',
    `access_token` TEXT,
    `refresh_token` TEXT,
    `token_expires` INT(11) DEFAULT 0,
    `site_url` VARCHAR(255) DEFAULT '',
    `is_connected` TINYINT(1) DEFAULT 0,
    `data_retention_months` INT(10) UNSIGNED NOT NULL DEFAULT 16,
    `alert_retention_days` INT(10) UNSIGNED NOT NULL DEFAULT 180,
    `seozoom_api_key` VARCHAR(255) NOT NULL DEFAULT '',
    `seozoom_db` VARCHAR(5) NOT NULL DEFAULT 'it',
    `seozoom_cache_hours` INT(10) UNSIGNED NOT NULL DEFAULT 24,
    `last_sync` DATETIME DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_config`),
    UNIQUE KEY `idx_shop` (`id_shop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_tec_gsc_seozoom_domain_metrics` (
    `id_metric` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
    `db` VARCHAR(5) NOT NULL DEFAULT 'it',
    `domain` VARCHAR(255) NOT NULL DEFAULT '',
    `main_domain` VARCHAR(255) NOT NULL DEFAULT '',
    `zoom_authority` DECIMAL(10,2) DEFAULT NULL,
    `zoom_trust` DECIMAL(10,2) DEFAULT NULL,
    `organic_traffic` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `organic_keywords` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `units_used` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `units_remaining` INT(10) UNSIGNED DEFAULT NULL,
    `raw_payload` MEDIUMTEXT,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_metric`),
    UNIQUE KEY `idx_shop_db_domain` (`id_shop`, `db`, `domain`),
    KEY `idx_date_upd` (`date_upd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_tec_gsc_seozoom_keyword_metrics` (
    `id_metric` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
    `db` VARCHAR(5) NOT NULL DEFAULT 'it',
    `keyword` VARCHAR(255) NOT NULL DEFAULT '',
    `search_volume` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `units_used` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `units_remaining` INT(10) UNSIGNED DEFAULT NULL,
    `raw_payload` MEDIUMTEXT,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_metric`),
    UNIQUE KEY `idx_shop_db_keyword` (`id_shop`, `db`, `keyword`),
    KEY `idx_date_upd` (`date_upd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_tec_gsc_data` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
    `data_date` DATE NOT NULL,
    `query` VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL,
    `page` VARCHAR(1000) DEFAULT NULL,
    `device` ENUM('DESKTOP','MOBILE','TABLET','ALL') DEFAULT 'ALL',
    `country` VARCHAR(3) DEFAULT NULL,
    `clicks` INT(10) UNSIGNED DEFAULT 0,
    `impressions` INT(10) UNSIGNED DEFAULT 0,
    `ctr` FLOAT DEFAULT 0,
    `position` FLOAT DEFAULT 0,
    `is_anonymized` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_unique_row` (`id_shop`, `data_date`, `query`(200), `page`(200), `device`),
    KEY `idx_date` (`data_date`),
    KEY `idx_page` (`page`(191)),
    KEY `idx_query` (`query`(191)),
    KEY `idx_shop` (`id_shop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_tec_gsc_alerts` (
    `id_alert` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
    `alert_type` ENUM('POSITION_DROP','CTR_DROP','IMPRESSIONS_DROP','NEW_KEYWORD') NOT NULL,
    `page` VARCHAR(1000) DEFAULT NULL,
    `query` VARCHAR(500) DEFAULT NULL,
    `value_before` FLOAT DEFAULT NULL,
    `value_after` FLOAT DEFAULT NULL,
    `delta_pct` FLOAT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_alert`),
    KEY `idx_shop_date` (`id_shop`, `date_add`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
