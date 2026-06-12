<?php
/**
 * 2009-2026 Tecnoacquisti.com
 *
 * For support feel free to contact us on our website at https://www.tecnoacquisti.com
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2009-2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Add SEOZoom configuration and cache storage.
 *
 * @param Module $module Module instance
 *
 * @return bool
 */
function upgrade_module_1_0_4($module)
{
    unset($module);

    $table = _DB_PREFIX_ . 'tec_gsc_config';
    $queries = [];

    if (!tecSearchConsoleColumnExists104($table, 'seozoom_api_key')) {
        $queries[] = 'ALTER TABLE `' . pSQL($table) . '`
            ADD `seozoom_api_key` VARCHAR(255) NOT NULL DEFAULT \'\'
            AFTER `alert_retention_days`';
    }

    if (!tecSearchConsoleColumnExists104($table, 'seozoom_db')) {
        $queries[] = 'ALTER TABLE `' . pSQL($table) . '`
            ADD `seozoom_db` VARCHAR(5) NOT NULL DEFAULT \'it\'
            AFTER `seozoom_api_key`';
    }

    if (!tecSearchConsoleColumnExists104($table, 'seozoom_cache_hours')) {
        $queries[] = 'ALTER TABLE `' . pSQL($table) . '`
            ADD `seozoom_cache_hours` INT(10) UNSIGNED NOT NULL DEFAULT 24
            AFTER `seozoom_db`';
    }

    $queries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tec_gsc_seozoom_domain_metrics` (
        `id_metric` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
        `db` VARCHAR(5) NOT NULL DEFAULT \'it\',
        `domain` VARCHAR(255) NOT NULL DEFAULT \'\',
        `main_domain` VARCHAR(255) NOT NULL DEFAULT \'\',
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

    $queries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tec_gsc_seozoom_keyword_metrics` (
        `id_metric` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
        `db` VARCHAR(5) NOT NULL DEFAULT \'it\',
        `keyword` VARCHAR(255) NOT NULL DEFAULT \'\',
        `search_volume` INT(10) UNSIGNED NOT NULL DEFAULT 0,
        `units_used` INT(10) UNSIGNED NOT NULL DEFAULT 0,
        `units_remaining` INT(10) UNSIGNED DEFAULT NULL,
        `raw_payload` MEDIUMTEXT,
        `date_add` DATETIME NOT NULL,
        `date_upd` DATETIME NOT NULL,
        PRIMARY KEY (`id_metric`),
        UNIQUE KEY `idx_shop_db_keyword` (`id_shop`, `db`, `keyword`),
        KEY `idx_date_upd` (`date_upd`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

    foreach ($queries as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    return true;
}

/**
 * Check whether a database column exists.
 *
 * @param string $table Database table name
 * @param string $column Database column name
 *
 * @return bool True when the column exists
 */
function tecSearchConsoleColumnExists104($table, $column)
{
    return (bool) Db::getInstance()->getValue(
        'SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = \'' . pSQL($table) . '\'
            AND COLUMN_NAME = \'' . pSQL($column) . '\''
    );
}
