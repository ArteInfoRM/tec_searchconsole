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
use Exception;
use Google\Client;
use PrestaShopLogger;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Handles Google OAuth 2.0 for Search Console.
 */
class GscOAuthHandler
{
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    /**
     * @var int Shop identifier
     */
    private $idShop;

    /**
     * @var GscConfigRepository Configuration repository
     */
    private $repository;

    /**
     * @var Client Google client
     */
    private $client;

    /**
     * @param int $idShop Shop identifier
     */
    public function __construct(int $idShop)
    {
        $this->idShop = $idShop;
        $this->repository = new GscConfigRepository();
        $this->loadGoogleAutoloader();

        if (!class_exists(Client::class)) {
            throw new Exception('Google API Client is not bundled with this module package.');
        }

        $config = $this->repository->getConfig($idShop);
        $this->client = new Client();
        $this->client->setClientId(isset($config['client_id']) ? (string) $config['client_id'] : '');
        $this->client->setClientSecret(isset($config['client_secret']) ? (string) $config['client_secret'] : '');
        $this->client->setRedirectUri($this->getCallbackUrl());
        $this->client->setScopes([self::SCOPE]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setIncludeGrantedScopes(true);
    }

    /**
     * Build the Google authorization URL.
     *
     * @return string Authorization URL
     */
    public function getAuthUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        Configuration::updateValue('TEC_GSC_OAUTH_STATE_' . (int) $this->idShop, $state);
        $this->client->setState($state);

        return $this->client->createAuthUrl();
    }

    /**
     * Exchange an authorization code for OAuth tokens.
     *
     * @param string $code Authorization code
     *
     * @return bool True on success
     */
    public function exchangeCodeForTokens(string $code): bool
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC token exchange failed: ' . $exception->getMessage(), 3);

            return false;
        }

        if (!is_array($token) || isset($token['error']) || empty($token['access_token'])) {
            $message = isset($token['error_description']) ? (string) $token['error_description'] : 'Unknown OAuth error';
            PrestaShopLogger::addLog('GSC OAuth error: ' . $message, 3);

            return false;
        }

        $this->saveTokenPayload($token);

        return true;
    }

    /**
     * Get a valid access token, refreshing it when needed.
     *
     * @return string|null Access token or null when disconnected
     */
    public function getValidAccessToken(): ?string
    {
        $config = $this->repository->getConfig($this->idShop);
        $cipher = new GscTokenCipher();
        $refreshToken = $cipher->decrypt(isset($config['refresh_token']) ? (string) $config['refresh_token'] : '');

        if ($refreshToken === '') {
            return null;
        }

        $tokenExpires = isset($config['token_expires']) ? (int) $config['token_expires'] : 0;
        if (time() >= ($tokenExpires - 60)) {
            return $this->refreshAccessToken($refreshToken);
        }

        $accessToken = $cipher->decrypt(isset($config['access_token']) ? (string) $config['access_token'] : '');

        return $accessToken !== '' ? $accessToken : null;
    }

    /**
     * Revoke the current authorization.
     *
     * @return bool True on success
     */
    public function revokeAccess(): bool
    {
        $config = $this->repository->getConfig($this->idShop);
        $cipher = new GscTokenCipher();
        $refreshToken = $cipher->decrypt(isset($config['refresh_token']) ? (string) $config['refresh_token'] : '');

        try {
            if ($refreshToken !== '') {
                $this->client->revokeToken($refreshToken);
            }
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC token revoke failed: ' . $exception->getMessage(), 2);
        }

        $this->repository->clearTokens($this->idShop);

        return true;
    }

    /**
     * Get the callback URL configured in Google Cloud Console.
     *
     * @return string Callback URL
     */
    public function getCallbackUrl(): string
    {
        return Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . 'modules/tec_searchconsole/callback.php';
    }

    /**
     * Validate the OAuth state value.
     *
     * @param int $idShop Shop identifier
     * @param string $state Received state
     *
     * @return bool True when state is valid
     */
    public static function validateState(int $idShop, string $state): bool
    {
        $expectedState = (string) Configuration::get('TEC_GSC_OAUTH_STATE_' . (int) $idShop);

        return $expectedState !== '' && hash_equals($expectedState, $state);
    }

    /**
     * Find the shop matching an OAuth state.
     *
     * @param string $state Received state
     *
     * @return int Matching shop identifier, zero when not found
     */
    public static function findShopIdByState(string $state): int
    {
        if ($state === '') {
            return 0;
        }

        $shops = \Shop::getShops(false, null, true);
        foreach ($shops as $idShop) {
            $idShop = (int) $idShop;
            if (self::validateState($idShop, $state)) {
                return $idShop;
            }
        }

        return 0;
    }

    /**
     * Clear the stored OAuth state.
     *
     * @param int $idShop Shop identifier
     *
     * @return void
     */
    public static function clearState(int $idShop): void
    {
        Configuration::deleteByName('TEC_GSC_OAUTH_STATE_' . (int) $idShop);
    }

    /**
     * Refresh an expired access token.
     *
     * @param string $refreshToken Refresh token
     *
     * @return string|null Access token
     */
    private function refreshAccessToken(string $refreshToken): ?string
    {
        try {
            $token = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC token refresh failed: ' . $exception->getMessage(), 3);

            return null;
        }

        if (!is_array($token) || empty($token['access_token'])) {
            return null;
        }

        $this->saveTokenPayload($token);

        return (string) $token['access_token'];
    }

    /**
     * Persist an OAuth token payload.
     *
     * @param array<string, mixed> $token Token payload
     *
     * @return void
     */
    private function saveTokenPayload(array $token): void
    {
        $expiresIn = isset($token['expires_in']) ? (int) $token['expires_in'] : 3600;
        $this->repository->saveTokens(
            $this->idShop,
            (string) $token['access_token'],
            isset($token['refresh_token']) ? (string) $token['refresh_token'] : '',
            time() + $expiresIn
        );
    }

    /**
     * Load Google API Client dependencies only when an OAuth operation needs them.
     *
     * @return void
     */
    private function loadGoogleAutoloader(): void
    {
        $autoload = dirname(__DIR__) . '/lib/google_vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
    }
}
