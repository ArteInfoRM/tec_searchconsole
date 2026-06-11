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
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ]);
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
}
