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
 * Add retention settings to the Search Console configuration table.
 *
 * @param Module $module Module instance
 *
 * @return bool
 */
function upgrade_module_1_0_2($module)
{
    unset($module);

    $table = _DB_PREFIX_ . 'tec_gsc_config';
    $queries = [];

    if (!tecSearchConsoleColumnExists($table, 'data_retention_months')) {
        $queries[] = 'ALTER TABLE `' . pSQL($table) . '`
            ADD `data_retention_months` INT(10) UNSIGNED NOT NULL DEFAULT 16
            AFTER `is_connected`';
    }

    if (!tecSearchConsoleColumnExists($table, 'alert_retention_days')) {
        $queries[] = 'ALTER TABLE `' . pSQL($table) . '`
            ADD `alert_retention_days` INT(10) UNSIGNED NOT NULL DEFAULT 180
            AFTER `data_retention_months`';
    }

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
function tecSearchConsoleColumnExists($table, $column)
{
    return (bool) Db::getInstance()->getValue(
        'SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = \'' . pSQL($table) . '\'
            AND COLUMN_NAME = \'' . pSQL($column) . '\''
    );
}
