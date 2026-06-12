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
use Tecnoacquisti\SearchConsole\GscDataExporter;
use Tecnoacquisti\SearchConsole\GscDataRetention;
use Tecnoacquisti\SearchConsole\GscDataSync;
use Tecnoacquisti\SearchConsole\GscOAuthHandler;
use Tecnoacquisti\SearchConsole\GscSeoZoomService;

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
        if (Tools::getIsset('export_gsc_data')) {
            $this->exportData();

            return;
        }

        if (Tools::isSubmit('submitTecGscConfig')) {
            $this->saveConfig();
        }

        if (Tools::isSubmit('submitTecGscRefreshSeoZoom')) {
            $this->refreshSeoZoomDomainMetrics();
        }

        if (Tools::isSubmit('submitTecGscVerification')) {
            $this->saveVerificationTag();
        }

        if (Tools::isSubmit('submitTecGscRetention')) {
            $this->saveRetentionSettings();
        }

        if (Tools::isSubmit('submitTecGscExportSettings')) {
            $this->saveExportSettings();
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

        if (Tools::isSubmit('submitTecGscCleanRetention')) {
            $this->cleanOldData();
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
        $retention = new GscDataRetention();
        $retentionSettings = $repository->getRetentionSettings($config);
        $cronToken = $repository->getCronToken();
        $baseUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__;

        $this->context->smarty->assign([
            'gsc_config' => $this->getTemplateConfig($config, $repository),
            'gsc_stats' => $this->getDashboardStats($idShop, $config),
            'gsc_alerts' => $this->getUnreadAlerts($idShop),
            'gsc_sitemaps' => $this->getSitemaps($idShop, $config),
            'gsc_callback_url' => $baseUrl . 'modules/tec_searchconsole/callback.php',
            'gsc_cron_url' => $baseUrl . 'modules/tec_searchconsole/cron.php?token=' . rawurlencode($cronToken),
            'gsc_search_console_url' => $this->getSearchConsoleUrl($config),
            'gsc_seozoom_domain_metrics' => $this->getSeoZoomDomainMetrics($idShop, $config),
            'gsc_form_action' => $this->context->link->getAdminLink('AdminTecGsc'),
            'gsc_export_action' => $this->context->link->getAdminLink('AdminTecGsc'),
            'gsc_export_controller' => 'AdminTecGsc',
            'gsc_export_token' => Tools::getAdminTokenLite('AdminTecGsc'),
            'gsc_export_settings' => $this->getExportSettings($idShop),
            'gsc_verification_tag' => $this->getVerificationTagValue($idShop),
            'gsc_retention' => $retentionSettings,
            'gsc_retention_stats' => $retention->getStats(
                $idShop,
                $retentionSettings['data_retention_months'],
                $retentionSettings['alert_retention_days']
            ),
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
        $seoZoomApiKey = trim((string) Tools::getValue('seozoom_api_key'));
        $repository = new GscConfigRepository();
        $seoZoomDb = $repository->normalizeSeoZoomDb((string) Tools::getValue('seozoom_db'));
        $seoZoomCacheHours = $repository->normalizeSeoZoomCacheHours((int) Tools::getValue('seozoom_cache_hours'));

        if ($siteUrl !== '' && !$this->isValidSiteUrl($siteUrl)) {
            $this->errors[] = $this->trans('Search Console property URL must be a valid absolute URL or sc-domain property.', [], 'Modules.Tecsearchconsole.Admin');
        }

        if ($seoZoomApiKey !== '' && strpos($seoZoomApiKey, '****') !== 0 && preg_match('/^[a-zA-Z0-9._-]{8,255}$/', $seoZoomApiKey) !== 1) {
            $this->errors[] = $this->trans('SEOZoom API key contains unsupported characters.', [], 'Modules.Tecsearchconsole.Admin');
        }

        if (!empty($this->errors)) {
            return;
        }

        $idShop = (int) $this->context->shop->id;
        $repository->saveSettings($idShop, $clientId, $clientSecret, $siteUrl);
        $repository->saveSeoZoomSettings($idShop, $seoZoomApiKey, $seoZoomDb, $seoZoomCacheHours);
        $this->confirmations[] = $this->trans('Settings saved.', [], 'Modules.Tecsearchconsole.Admin');
    }

    /**
     * Refresh cached SEOZoom domain metrics.
     *
     * @return void
     */
    private function refreshSeoZoomDomainMetrics()
    {
        $idShop = (int) $this->context->shop->id;
        $config = (new GscConfigRepository())->getConfig($idShop);
        $metrics = (new GscSeoZoomService())->getDomainMetrics($idShop, $config, true);

        if (!empty($metrics['error'])) {
            $this->errors[] = (string) $metrics['error'];

            return;
        }

        if (empty($metrics['has_data'])) {
            $this->errors[] = $this->trans('SEOZoom API key and property URL are required before refreshing metrics.', [], 'Modules.Tecsearchconsole.Admin');

            return;
        }

        $this->confirmations[] = $this->trans('SEOZoom domain metrics refreshed.', [], 'Modules.Tecsearchconsole.Admin');
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
            $this->errors[] = $this->trans('Verification tag must be a valid Google Search Console meta tag.', [], 'Modules.Tecsearchconsole.Admin');

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

        $this->confirmations[] = $this->trans('Verification tag saved.', [], 'Modules.Tecsearchconsole.Admin');
    }

    /**
     * Save retention settings.
     *
     * @return void
     */
    private function saveRetentionSettings()
    {
        $dataRetentionMonths = $this->getValidatedRetentionValue(
            Tools::getValue('data_retention_months'),
            [0, 3, 6, 12, 16]
        );
        $alertRetentionDays = $this->getValidatedRetentionValue(
            Tools::getValue('alert_retention_days'),
            [0, 90, 180, 365]
        );

        if ($dataRetentionMonths === null || $alertRetentionDays === null) {
            $this->errors[] = $this->trans('Retention settings contain an unsupported value.', [], 'Modules.Tecsearchconsole.Admin');

            return;
        }

        (new GscConfigRepository())->saveRetentionSettings(
            (int) $this->context->shop->id,
            $dataRetentionMonths,
            $alertRetentionDays
        );
        $this->confirmations[] = $this->trans('Retention settings saved.', [], 'Modules.Tecsearchconsole.Admin');
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
            $this->confirmations[] = $this->trans('Google account disconnected.', [], 'Modules.Tecsearchconsole.Admin');
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
                $this->errors[] = $this->trans('Search Console property URL is required before synchronization.', [], 'Modules.Tecsearchconsole.Admin');

                return;
            }

            $oauth = new GscOAuthHandler($idShop);
            $apiClient = new GscApiClient($oauth, $siteUrl);
            $processedRows = (new GscDataSync($apiClient, $idShop))->syncRecentDays(30);
            $createdAlerts = (new GscAlertEngine($idShop))->analyzeAndGenerateAlerts();
            $this->confirmations[] = sprintf(
                $this->trans('Synchronization completed: %d rows processed, %d alerts created.', [], 'Modules.Tecsearchconsole.Admin'),
                $processedRows,
                $createdAlerts
            );
        } catch (Exception $exception) {
            $this->errors[] = $exception->getMessage();
        }
    }

    /**
     * Clean data older than the configured retention period.
     *
     * @return void
     */
    private function cleanOldData()
    {
        try {
            $idShop = (int) $this->context->shop->id;
            $repository = new GscConfigRepository();
            $config = $repository->getConfig($idShop);
            $retentionSettings = $repository->getRetentionSettings($config);
            $retention = new GscDataRetention();
            $deletedDataRows = $retention->cleanupData($idShop, $retentionSettings['data_retention_months']);
            $deletedAlertRows = $retention->cleanupAlerts($idShop, $retentionSettings['alert_retention_days']);

            $this->confirmations[] = sprintf(
                $this->trans('Cleanup completed: %d data rows and %d alert rows removed.', [], 'Modules.Tecsearchconsole.Admin'),
                $deletedDataRows,
                $deletedAlertRows
            );
        } catch (Exception $exception) {
            $this->errors[] = $exception->getMessage();
        }
    }

    /**
     * Save default export settings.
     *
     * @return void
     */
    private function saveExportSettings()
    {
        $idShop = (int) $this->context->shop->id;
        $exporter = new GscDataExporter($idShop, (int) $this->context->language->id, $this->context->link);
        $format = $exporter->normalizeFormat((string) Tools::getValue('export_format'));
        $period = $exporter->normalizePeriod((string) Tools::getValue('export_period'));

        if ($format === '' || $period === '') {
            $this->errors[] = $this->trans('Export settings contain an unsupported value.', [], 'Modules.Tecsearchconsole.Admin');

            return;
        }

        Configuration::updateValue('TEC_GSC_EXPORT_FORMAT', $format, false, null, $idShop);
        Configuration::updateValue('TEC_GSC_EXPORT_PERIOD', $period, false, null, $idShop);
        $this->confirmations[] = $this->trans('Export settings saved.', [], 'Modules.Tecsearchconsole.Admin');
    }

    /**
     * Export locally stored Search Console data.
     *
     * @return void
     */
    private function exportData()
    {
        $idProduct = (int) Tools::getValue('id_product', 0);
        $exporter = new GscDataExporter(
            (int) $this->context->shop->id,
            (int) $this->context->language->id,
            $this->context->link
        );
        $format = $exporter->normalizeFormat((string) Tools::getValue('export_format'));
        $period = $exporter->normalizePeriod((string) Tools::getValue('export_period'));
        $settings = $this->getExportSettings((int) $this->context->shop->id);
        if ($format === '') {
            $format = $settings['format'];
        }

        if ($period === '') {
            $period = $settings['period'];
        }

        if ($format === '' || $period === '') {
            $this->errors[] = $this->trans('Export settings contain an unsupported value.', [], 'Modules.Tecsearchconsole.Admin');

            return;
        }

        if ($idProduct < 0) {
            $this->errors[] = $this->trans('Product export target is invalid.', [], 'Modules.Tecsearchconsole.Admin');

            return;
        }

        $rows = $exporter->getRows($period, $idProduct);
        $content = $exporter->render($rows, $format, $period, $idProduct);
        $filename = $exporter->getFilename($format, $period, $idProduct);

        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: ' . $exporter->getContentType($format));
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $content;
        exit;
    }

    /**
     * Get default export settings.
     *
     * @param int $idShop Shop identifier
     *
     * @return array<string, string> Export settings
     */
    private function getExportSettings($idShop)
    {
        $exporter = new GscDataExporter((int) $idShop, (int) $this->context->language->id, $this->context->link);
        $format = $exporter->normalizeFormat((string) Configuration::get('TEC_GSC_EXPORT_FORMAT', null, null, (int) $idShop));
        $period = $exporter->normalizePeriod((string) Configuration::get('TEC_GSC_EXPORT_PERIOD', null, null, (int) $idShop));

        return [
            'format' => $format !== '' ? $format : 'json',
            'period' => $period !== '' ? $period : '28d',
        ];
    }

    /**
     * Build a Google Search Console URL for the configured property.
     *
     * @param array<string, mixed> $config Configuration row
     *
     * @return string Search Console URL
     */
    private function getSearchConsoleUrl(array $config)
    {
        $siteUrl = isset($config['site_url']) ? trim((string) $config['site_url']) : '';
        if ($siteUrl === '') {
            return '';
        }

        return 'https://search.google.com/search-console?resource_id=' . rawurlencode($siteUrl);
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
        $seoZoomDb = isset($config['seozoom_db']) ? (string) $config['seozoom_db'] : 'it';
        $seoZoomCacheHours = isset($config['seozoom_cache_hours']) ? (int) $config['seozoom_cache_hours'] : 24;

        return [
            'client_id' => isset($config['client_id']) ? (string) $config['client_id'] : '',
            'client_secret' => $repository->maskSecret(isset($config['client_secret']) ? (string) $config['client_secret'] : ''),
            'site_url' => isset($config['site_url']) ? (string) $config['site_url'] : '',
            'is_connected' => !empty($config['is_connected']),
            'last_sync' => isset($config['last_sync']) ? (string) $config['last_sync'] : '',
            'seozoom_api_key' => $repository->maskSecret(isset($config['seozoom_api_key']) ? (string) $config['seozoom_api_key'] : ''),
            'seozoom_db' => $repository->normalizeSeoZoomDb($seoZoomDb),
            'seozoom_cache_hours' => $repository->normalizeSeoZoomCacheHours($seoZoomCacheHours),
        ];
    }

    /**
     * Load SEOZoom domain metrics for the dashboard.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Configuration row
     *
     * @return array<string, mixed> SEOZoom domain metrics
     */
    private function getSeoZoomDomainMetrics($idShop, array $config)
    {
        return (new GscSeoZoomService())->getDomainMetrics((int) $idShop, $config);
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
            'top_queries' => (new GscSeoZoomService())->enrichQueryRowsWithSearchVolume(
                (int) $idShop,
                $config,
                $this->getSearchConsoleTopQueries($idShop, $config, $periodStart, $periodEnd)
            ),
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
     * Validate a submitted retention value.
     *
     * @param mixed $value Submitted value
     * @param int[] $allowedValues Allowed retention values
     *
     * @return int|null Validated integer or null when invalid
     */
    private function getValidatedRetentionValue($value, array $allowedValues)
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            return null;
        }

        $intValue = (int) $value;

        return in_array($intValue, $allowedValues, true) ? $intValue : null;
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
