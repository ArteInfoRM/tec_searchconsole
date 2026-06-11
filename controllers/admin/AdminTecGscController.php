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

use Tecnoacquisti\SearchConsole\GscAlertEngine;
use Tecnoacquisti\SearchConsole\GscApiClient;
use Tecnoacquisti\SearchConsole\GscConfigRepository;
use Tecnoacquisti\SearchConsole\GscDataSync;
use Tecnoacquisti\SearchConsole\GscOAuthHandler;

/**
 * Back-office dashboard controller for Search Console data.
 */
class AdminTecGscController extends ModuleAdminController
{
    /**
     * Admin controller constructor.
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->meta_title = 'Search Console SEO';
        parent::__construct();

        if (method_exists($this->module, 'loadModuleAutoloader')) {
            $this->module->loadModuleAutoloader();
        }
    }

    /**
     * Process back-office actions.
     *
     * @return void
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitTecGscConfig')) {
            $this->saveConfig();
        }

        if (Tools::isSubmit('submitTecGscVerification')) {
            $this->saveVerificationTag();
        }

        if (Tools::getIsset('connect_google')) {
            $this->connectGoogle();
        }

        if (Tools::getIsset('disconnect_google')) {
            $this->disconnectGoogle();
        }

        if (Tools::getIsset('sync_now')) {
            $this->runManualSync();
        }

        parent::postProcess();
    }

    /**
     * Render the dashboard.
     *
     * @return void
     */
    public function initContent()
    {
        parent::initContent();

        $repository = new GscConfigRepository();
        $idShop = (int) $this->context->shop->id;
        $config = $repository->getConfig($idShop);
        $cronToken = $repository->getCronToken();
        $baseUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__;

        $this->context->smarty->assign([
            'gsc_config' => $this->getTemplateConfig($config, $repository),
            'gsc_stats' => $this->getDashboardStats($idShop, $config),
            'gsc_alerts' => $this->getUnreadAlerts($idShop),
            'gsc_sitemaps' => $this->getSitemaps($idShop, $config),
            'gsc_callback_url' => $baseUrl . 'modules/tec_searchconsole/callback.php',
            'gsc_cron_url' => $baseUrl . 'modules/tec_searchconsole/cron.php?token=' . rawurlencode($cronToken),
            'gsc_form_action' => $this->context->link->getAdminLink('AdminTecGsc'),
            'gsc_verification_tag' => $this->getVerificationTagValue($idShop),
            'gsc_connect_url' => $this->context->link->getAdminLink('AdminTecGsc') . '&connect_google=1',
            'gsc_disconnect_url' => $this->context->link->getAdminLink('AdminTecGsc') . '&disconnect_google=1',
            'gsc_sync_url' => $this->context->link->getAdminLink('AdminTecGsc') . '&sync_now=1',
            'gsc_vendor_ready' => is_file(_PS_MODULE_DIR_ . 'tec_searchconsole/lib/google_vendor/autoload.php'),
        ]);

        $this->content .= $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'tec_searchconsole/views/templates/admin/dashboard.tpl');
        $this->context->smarty->assign('content', $this->content);
    }

    /**
     * Save OAuth configuration.
     *
     * @return void
     */
    private function saveConfig()
    {
        $clientId = trim((string) Tools::getValue('client_id'));
        $clientSecret = trim((string) Tools::getValue('client_secret'));
        $siteUrl = trim((string) Tools::getValue('site_url'));

        if ($clientId === '') {
            $this->errors[] = $this->module->l('Google Client ID is required.');
        }

        if ($siteUrl !== '' && !$this->isValidSiteUrl($siteUrl)) {
            $this->errors[] = $this->module->l('Search Console property URL must be a valid absolute URL or sc-domain property.');
        }

        if (!empty($this->errors)) {
            return;
        }

        (new GscConfigRepository())->saveSettings((int) $this->context->shop->id, $clientId, $clientSecret, $siteUrl);
        $this->confirmations[] = $this->module->l('Settings saved.');
    }

    /**
     * Save the Google Search Console verification meta tag.
     *
     * @return void
     */
    private function saveVerificationTag()
    {
        $submittedTag = trim((string) Tools::getValue('verification_tag'));
        $verificationToken = $this->extractVerificationToken($submittedTag);
        if ($submittedTag !== '' && $verificationToken === '') {
            $this->errors[] = $this->module->l('Verification tag must be a valid Google Search Console meta tag.');

            return;
        }

        Configuration::updateValue(
            'TEC_GSC_VERIFICATION_TOKEN',
            $verificationToken,
            false,
            null,
            (int) $this->context->shop->id
        );
        if (method_exists($this->module, 'registerHook')) {
            $this->module->registerHook('displayHeader');
        }

        $this->confirmations[] = $this->module->l('Verification tag saved.');
    }

    /**
     * Redirect the merchant to Google authorization.
     *
     * @return void
     */
    private function connectGoogle()
    {
        try {
            $idShop = (int) $this->context->shop->id;
            Configuration::updateValue(
                'TEC_GSC_ADMIN_RETURN_URL_' . $idShop,
                $this->context->link->getAdminLink('AdminTecGsc')
            );
            $oauth = new GscOAuthHandler($idShop);
            Tools::redirectAdmin($oauth->getAuthUrl());
        } catch (Exception $exception) {
            $this->errors[] = $exception->getMessage();
        }
    }

    /**
     * Disconnect the current Google account.
     *
     * @return void
     */
    private function disconnectGoogle()
    {
        try {
            $oauth = new GscOAuthHandler((int) $this->context->shop->id);
            $oauth->revokeAccess();
            $this->confirmations[] = $this->module->l('Google account disconnected.');
        } catch (Exception $exception) {
            $this->errors[] = $exception->getMessage();
        }
    }

    /**
     * Run a manual synchronization.
     *
     * @return void
     */
    private function runManualSync()
    {
        try {
            $idShop = (int) $this->context->shop->id;
            $config = (new GscConfigRepository())->getConfig($idShop);
            $siteUrl = isset($config['site_url']) ? (string) $config['site_url'] : '';
            if ($siteUrl === '') {
                $this->errors[] = $this->module->l('Search Console property URL is required before synchronization.');

                return;
            }

            $oauth = new GscOAuthHandler($idShop);
            $apiClient = new GscApiClient($oauth, $siteUrl);
            $processedRows = (new GscDataSync($apiClient, $idShop))->syncRecentDays(30);
            $createdAlerts = (new GscAlertEngine($idShop))->analyzeAndGenerateAlerts();
            $this->confirmations[] = sprintf($this->module->l('Synchronization completed: %d rows processed, %d alerts created.'), $processedRows, $createdAlerts);
        } catch (Exception $exception) {
            $this->errors[] = $exception->getMessage();
        }
    }

    /**
     * Prepare config data for Smarty.
     *
     * @param array<string, mixed> $config Configuration row
     * @param GscConfigRepository $repository Configuration repository
     *
     * @return array<string, mixed> Template config
     */
    private function getTemplateConfig(array $config, GscConfigRepository $repository)
    {
        return [
            'client_id' => isset($config['client_id']) ? (string) $config['client_id'] : '',
            'client_secret' => $repository->maskSecret(isset($config['client_secret']) ? (string) $config['client_secret'] : ''),
            'site_url' => isset($config['site_url']) ? (string) $config['site_url'] : '',
            'is_connected' => !empty($config['is_connected']),
            'last_sync' => isset($config['last_sync']) ? (string) $config['last_sync'] : '',
        ];
    }

    /**
     * Get the verification tag value shown in the back-office textarea.
     *
     * @param int $idShop Shop identifier
     *
     * @return string Verification meta tag
     */
    private function getVerificationTagValue($idShop)
    {
        $token = trim((string) Configuration::get('TEC_GSC_VERIFICATION_TOKEN', null, null, (int) $idShop));
        if ($token === '') {
            return '';
        }

        return '<meta name="google-site-verification" content="' . $token . '">';
    }

    /**
     * Extract a verification token from a full meta tag or plain token.
     *
     * @param string $submittedTag Submitted tag or token
     *
     * @return string Verification token
     */
    private function extractVerificationToken($submittedTag)
    {
        if ($submittedTag === '') {
            return '';
        }

        if (strpos($submittedTag, '<') === false && preg_match('/^[a-zA-Z0-9_-]{8,255}$/', $submittedTag) === 1) {
            return $submittedTag;
        }

        if (preg_match('/<meta\b[^>]*\bname=["\']google-site-verification["\'][^>]*\bcontent=["\']([^"\']+)["\'][^>]*>/i', $submittedTag, $matches) !== 1) {
            if (preg_match('/<meta\b[^>]*\bcontent=["\']([^"\']+)["\'][^>]*\bname=["\']google-site-verification["\'][^>]*>/i', $submittedTag, $matches) !== 1) {
                return '';
            }
        }

        $token = trim((string) $matches[1]);

        return preg_match('/^[a-zA-Z0-9_-]{8,255}$/', $token) === 1 ? $token : '';
    }

    /**
     * Load dashboard metrics.
     *
     * @param int $idShop Shop identifier
     *
     * @return array<string, mixed> Dashboard metrics
     */
    private function getDashboardStats($idShop, array $config)
    {
        $dateLimit = date('Y-m-d', strtotime('-28 days'));
        $periodEnd = date('Y-m-d', strtotime('-2 days'));
        $periodStart = date('Y-m-d', strtotime('-29 days'));

        return [
            'last_28_days' => $this->getSearchConsoleTotals($idShop, $config, $periodStart, $periodEnd),
            'top_pages' => $this->getSearchConsoleTopPages($idShop, $config, $periodStart, $periodEnd),
            'top_queries' => $this->getSearchConsoleTopQueries($idShop, $config, $periodStart, $periodEnd),
            'low_ctr_opportunities' => $this->getRows(
                'SELECT
                    page,
                    SUM(position * impressions) / NULLIF(SUM(impressions), 0) AS position,
                    SUM(clicks) / NULLIF(SUM(impressions), 0) AS ctr,
                    SUM(impressions) AS impressions
                FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
                WHERE id_shop = ' . (int) $idShop . '
                    AND data_date >= \'' . pSQL($dateLimit) . '\'
                    AND position BETWEEN 1 AND 20
                GROUP BY page
                HAVING ctr < 0.02 AND impressions > 500
                ORDER BY impressions DESC
                LIMIT 10'
            ),
        ];
    }

    /**
     * Load top pages ordered by clicks.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Configuration row
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     *
     * @return array<int, array<string, mixed>> Top pages
     */
    private function getSearchConsoleTopPages($idShop, array $config, $startDate, $endDate)
    {
        $apiClient = $this->getApiClient($idShop, $config);
        if ($apiClient instanceof GscApiClient) {
            $rows = $apiClient->getTopPages((string) $startDate, (string) $endDate, 10);
            if (!empty($rows)) {
                return $rows;
            }
        }

        $dateLimit = date('Y-m-d', strtotime('-28 days'));

        return $this->getRows(
            'SELECT
                page,
                SUM(clicks) AS clicks,
                SUM(impressions) AS impressions,
                SUM(clicks) / NULLIF(SUM(impressions), 0) AS ctr,
                SUM(position * impressions) / NULLIF(SUM(impressions), 0) AS position
            FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE id_shop = ' . (int) $idShop . '
                AND data_date >= \'' . pSQL($dateLimit) . '\'
            GROUP BY page
            ORDER BY clicks DESC
            LIMIT 10'
        );
    }

    /**
     * Load top queries ordered by clicks.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Configuration row
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     *
     * @return array<int, array<string, mixed>> Top queries
     */
    private function getSearchConsoleTopQueries($idShop, array $config, $startDate, $endDate)
    {
        $apiClient = $this->getApiClient($idShop, $config);
        if ($apiClient instanceof GscApiClient) {
            $rows = $apiClient->getTopQueries((string) $startDate, (string) $endDate, 20);
            if (!empty($rows)) {
                return $rows;
            }
        }

        $dateLimit = date('Y-m-d', strtotime('-28 days'));

        return $this->getRows(
            'SELECT
                query,
                SUM(clicks) AS clicks,
                SUM(impressions) AS impressions,
                SUM(clicks) / NULLIF(SUM(impressions), 0) AS ctr,
                SUM(position * impressions) / NULLIF(SUM(impressions), 0) AS position
            FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE id_shop = ' . (int) $idShop . '
                AND data_date >= \'' . pSQL($dateLimit) . '\'
                AND query IS NOT NULL
                AND query != \'\'
            GROUP BY query
            ORDER BY clicks DESC
            LIMIT 20'
        );
    }

    /**
     * Load aggregate Search Console totals for the dashboard KPI cards.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Configuration row
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     *
     * @return array<string, mixed> Aggregate totals
     */
    private function getSearchConsoleTotals($idShop, array $config, $startDate, $endDate)
    {
        $apiClient = $this->getApiClient($idShop, $config);
        if ($apiClient instanceof GscApiClient) {
            return $apiClient->getDailyTotals((string) $startDate, (string) $endDate);
        }

        return $this->getRow(
            'SELECT
                SUM(clicks) AS clicks,
                SUM(impressions) AS impressions,
                SUM(clicks) / NULLIF(SUM(impressions), 0) AS ctr,
                SUM(position * impressions) / NULLIF(SUM(impressions), 0) AS position
            FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE id_shop = ' . (int) $idShop . '
                AND data_date BETWEEN \'' . pSQL($startDate) . '\' AND \'' . pSQL($endDate) . '\''
        );
    }

    /**
     * Create a Search Console API client when the account is connected.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Configuration row
     *
     * @return GscApiClient|null API client
     */
    private function getApiClient($idShop, array $config)
    {
        $siteUrl = isset($config['site_url']) ? (string) $config['site_url'] : '';
        if ($siteUrl === '' || empty($config['is_connected'])) {
            return null;
        }

        try {
            $oauth = new GscOAuthHandler((int) $idShop);

            return new GscApiClient($oauth, $siteUrl);
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC API client error: ' . $exception->getMessage(), 2);

            return null;
        }
    }

    /**
     * Get unread alerts.
     *
     * @param int $idShop Shop identifier
     *
     * @return array<int, array<string, mixed>> Alert rows
     */
    private function getUnreadAlerts($idShop)
    {
        return $this->getRows(
            'SELECT *
            FROM `' . _DB_PREFIX_ . 'tec_gsc_alerts`
            WHERE id_shop = ' . (int) $idShop . '
                AND is_read = 0
            ORDER BY date_add DESC
            LIMIT 20'
        );
    }

    /**
     * Load submitted sitemaps from Search Console.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Configuration row
     *
     * @return array<int, array<string, mixed>> Sitemap rows
     */
    private function getSitemaps($idShop, array $config)
    {
        $siteUrl = isset($config['site_url']) ? (string) $config['site_url'] : '';
        if ($siteUrl === '' || empty($config['is_connected'])) {
            return [];
        }

        try {
            $oauth = new GscOAuthHandler((int) $idShop);
            $apiClient = new GscApiClient($oauth, $siteUrl);

            return $apiClient->listSitemaps();
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC dashboard sitemaps error: ' . $exception->getMessage(), 2);

            return [];
        }
    }

    /**
     * Read one database row.
     *
     * @param string $sql SQL query
     *
     * @return array<string, mixed> Row
     */
    private function getRow($sql)
    {
        $row = Db::getInstance()->getRow($sql);

        return is_array($row) ? $row : [];
    }

    /**
     * Read database rows.
     *
     * @param string $sql SQL query
     *
     * @return array<int, array<string, mixed>> Rows
     */
    private function getRows($sql)
    {
        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Validate a Search Console property URL.
     *
     * @param string $siteUrl Property URL
     *
     * @return bool True when valid
     */
    private function isValidSiteUrl($siteUrl)
    {
        if (strpos($siteUrl, 'sc-domain:') === 0) {
            return (bool) preg_match('/^sc-domain:[a-z0-9.-]+\.[a-z]{2,}$/i', $siteUrl);
        }

        $scheme = parse_url($siteUrl, PHP_URL_SCHEME);

        return filter_var($siteUrl, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) $scheme), ['http', 'https'], true);
    }
}
