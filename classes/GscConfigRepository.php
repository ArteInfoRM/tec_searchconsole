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

namespace Tecnoacquisti\SearchConsole;

use Configuration;
use Db;
use Shop;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Reads and writes Google Search Console module configuration.
 */
class GscConfigRepository
{
    /**
     * Get configuration for one shop, creating a row when missing.
     *
     * @param int $idShop Shop identifier
     *
     * @return array<string, mixed> Configuration row
     */
    public function getConfig(int $idShop): array
    {
        $this->ensureRetentionColumns();

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'tec_gsc_config`
            WHERE id_shop = ' . (int) $idShop
        );

        if (is_array($row) && !empty($row)) {
            return $row;
        }

        $this->ensureConfigRow($idShop);

        $createdRow = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'tec_gsc_config`
            WHERE id_shop = ' . (int) $idShop
        );

        return is_array($createdRow) ? $createdRow : [];
    }

    /**
     * Save merchant editable settings.
     *
     * @param int $idShop Shop identifier
     * @param string $clientId Google OAuth client id
     * @param string $clientSecret Google OAuth client secret or masked value
     * @param string $siteUrl Search Console property URL
     *
     * @return void
     */
    public function saveSettings(int $idShop, string $clientId, string $clientSecret, string $siteUrl): void
    {
        $config = $this->getConfig($idShop);
        $storedSecret = isset($config['client_secret']) ? (string) $config['client_secret'] : '';
        $secretToStore = $this->isMaskedValue($clientSecret) || $clientSecret === ''
            ? $storedSecret
            : $clientSecret;

        Db::getInstance()->update('tec_gsc_config', [
            'client_id' => pSQL($clientId),
            'client_secret' => pSQL($secretToStore),
            'site_url' => pSQL($siteUrl),
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_shop = ' . (int) $idShop);
    }

    /**
     * Save retention settings.
     *
     * @param int $idShop Shop identifier
     * @param int $dataRetentionMonths Search Console data retention in months
     * @param int $alertRetentionDays Alert retention in days
     *
     * @return void
     */
    public function saveRetentionSettings(int $idShop, int $dataRetentionMonths, int $alertRetentionDays): void
    {
        $this->ensureRetentionColumns();

        $retention = new GscDataRetention();

        Db::getInstance()->update('tec_gsc_config', [
            'data_retention_months' => (int) $retention->normalizeDataRetentionMonths($dataRetentionMonths),
            'alert_retention_days' => (int) $retention->normalizeAlertRetentionDays($alertRetentionDays),
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_shop = ' . (int) $idShop);
    }

    /**
     * Get normalized retention settings from a configuration row.
     *
     * @param array<string, mixed> $config Configuration row
     *
     * @return array<string, int> Retention settings
     */
    public function getRetentionSettings(array $config): array
    {
        $retention = new GscDataRetention();
        $dataRetentionMonths = isset($config['data_retention_months'])
            ? (int) $config['data_retention_months']
            : GscDataRetention::DEFAULT_DATA_RETENTION_MONTHS;
        $alertRetentionDays = isset($config['alert_retention_days'])
            ? (int) $config['alert_retention_days']
            : GscDataRetention::DEFAULT_ALERT_RETENTION_DAYS;

        return [
            'data_retention_months' => $retention->normalizeDataRetentionMonths($dataRetentionMonths),
            'alert_retention_days' => $retention->normalizeAlertRetentionDays($alertRetentionDays),
        ];
    }

    /**
     * Save encrypted OAuth tokens.
     *
     * @param int $idShop Shop identifier
     * @param string $accessToken Access token
     * @param string $refreshToken Refresh token
     * @param int $expiresAt Expiration timestamp
     *
     * @return void
     */
    public function saveTokens(int $idShop, string $accessToken, string $refreshToken, int $expiresAt): void
    {
        $cipher = new GscTokenCipher();
        $config = $this->getConfig($idShop);
        $refreshTokenToStore = $refreshToken !== ''
            ? $refreshToken
            : $cipher->decrypt(isset($config['refresh_token']) ? (string) $config['refresh_token'] : '');

        Db::getInstance()->update('tec_gsc_config', [
            'access_token' => pSQL($cipher->encrypt($accessToken)),
            'refresh_token' => pSQL($cipher->encrypt($refreshTokenToStore)),
            'token_expires' => (int) $expiresAt,
            'is_connected' => 1,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_shop = ' . (int) $idShop);
    }

    /**
     * Clear OAuth tokens.
     *
     * @param int $idShop Shop identifier
     *
     * @return void
     */
    public function clearTokens(int $idShop): void
    {
        Db::getInstance()->update('tec_gsc_config', [
            'access_token' => '',
            'refresh_token' => '',
            'token_expires' => 0,
            'is_connected' => 0,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_shop = ' . (int) $idShop);
    }

    /**
     * Update sync timestamp.
     *
     * @param int $idShop Shop identifier
     *
     * @return void
     */
    public function updateLastSync(int $idShop): void
    {
        Db::getInstance()->update('tec_gsc_config', [
            'last_sync' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_shop = ' . (int) $idShop);
    }

    /**
     * Create missing configuration rows for all shops.
     *
     * @return void
     */
    public function ensureAllShopRows(): void
    {
        $this->ensureRetentionColumns();

        $shops = Shop::getShops(false, null, true);
        foreach ($shops as $idShop) {
            $this->ensureConfigRow((int) $idShop);
        }
    }

    /**
     * Ensure one configuration row exists.
     *
     * @param int $idShop Shop identifier
     *
     * @return void
     */
    public function ensureConfigRow(int $idShop): void
    {
        $this->ensureRetentionColumns();

        $exists = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'tec_gsc_config`
            WHERE id_shop = ' . (int) $idShop
        );

        if ($exists > 0) {
            return;
        }

        Db::getInstance()->insert('tec_gsc_config', [
            'id_shop' => (int) $idShop,
            'client_id' => '',
            'client_secret' => '',
            'access_token' => '',
            'refresh_token' => '',
            'token_expires' => 0,
            'site_url' => '',
            'is_connected' => 0,
            'data_retention_months' => GscDataRetention::DEFAULT_DATA_RETENTION_MONTHS,
            'alert_retention_days' => GscDataRetention::DEFAULT_ALERT_RETENTION_DAYS,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Ensure retention columns exist on upgraded installations.
     *
     * @return void
     */
    public function ensureRetentionColumns(): void
    {
        $table = _DB_PREFIX_ . 'tec_gsc_config';
        $queries = [];

        if (!$this->columnExists($table, 'data_retention_months')) {
            $queries[] = 'ALTER TABLE `' . pSQL($table) . '`
                ADD `data_retention_months` INT(10) UNSIGNED NOT NULL DEFAULT 16
                AFTER `is_connected`';
        }

        if (!$this->columnExists($table, 'alert_retention_days')) {
            $queries[] = 'ALTER TABLE `' . pSQL($table) . '`
                ADD `alert_retention_days` INT(10) UNSIGNED NOT NULL DEFAULT 180
                AFTER `data_retention_months`';
        }

        foreach ($queries as $query) {
            Db::getInstance()->execute($query);
        }
    }

    /**
     * Get masked client secret for back office forms.
     *
     * @param string $clientSecret Stored secret
     *
     * @return string Masked secret
     */
    public function maskSecret(string $clientSecret): string
    {
        if ($clientSecret === '') {
            return '';
        }

        $suffix = substr($clientSecret, -4);

        return '********' . $suffix;
    }

    /**
     * Read or create the module cron token.
     *
     * @return string Cron token
     */
    public function getCronToken(): string
    {
        $token = (string) Configuration::get('TEC_GSC_CRON_TOKEN');
        if ($token !== '') {
            return $token;
        }

        $token = bin2hex(random_bytes(24));
        Configuration::updateValue('TEC_GSC_CRON_TOKEN', $token);

        return $token;
    }

    /**
     * Detect masked submitted secrets.
     *
     * @param string $value Submitted value
     *
     * @return bool
     */
    private function isMaskedValue(string $value): bool
    {
        return preg_match('/^\*{4,}.{0,}$/', $value) === 1;
    }

    /**
     * Check whether a database column exists.
     *
     * @param string $table Database table name
     * @param string $column Database column name
     *
     * @return bool True when the column exists
     */
    private function columnExists(string $table, string $column): bool
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = \'' . pSQL($table) . '\'
                AND COLUMN_NAME = \'' . pSQL($column) . '\''
        );
    }
}
