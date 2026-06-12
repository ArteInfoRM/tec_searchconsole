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

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/tec_searchconsole.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

use Tecnoacquisti\SearchConsole\GscAlertEngine;
use Tecnoacquisti\SearchConsole\GscApiClient;
use Tecnoacquisti\SearchConsole\GscConfigRepository;
use Tecnoacquisti\SearchConsole\GscDataRetention;
use Tecnoacquisti\SearchConsole\GscDataSync;
use Tecnoacquisti\SearchConsole\GscOAuthHandler;

$module = Module::getInstanceByName('tec_searchconsole');
if ($module && method_exists($module, 'loadModuleAutoloader')) {
    $module->loadModuleAutoloader();
}

$repository = new GscConfigRepository();
$expectedToken = $repository->getCronToken();
$providedToken = (string) Tools::getValue('token');

if ($providedToken === '' && PHP_SAPI === 'cli') {
    $options = getopt('', ['token:']);
    $providedToken = isset($options['token']) ? (string) $options['token'] : '';
}

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit(1);
}

$shops = Shop::getShops(false);
foreach ($shops as $shop) {
    $idShop = isset($shop['id_shop']) ? (int) $shop['id_shop'] : 0;
    if ($idShop <= 0) {
        continue;
    }

    try {
        $config = $repository->getConfig($idShop);
        $siteUrl = isset($config['site_url']) ? (string) $config['site_url'] : '';
        if ($siteUrl === '' || empty($config['is_connected'])) {
            echo '[Shop ' . $idShop . "] Not connected\n";
            continue;
        }

        $oauthHandler = new GscOAuthHandler($idShop);
        $apiClient = new GscApiClient($oauthHandler, $siteUrl);
        $processedRows = (new GscDataSync($apiClient, $idShop))->syncRecentDays(3);
        $createdAlerts = (new GscAlertEngine($idShop))->analyzeAndGenerateAlerts();
        $retentionSettings = $repository->getRetentionSettings($config);
        $retention = new GscDataRetention();
        $deletedDataRows = $retention->cleanupData($idShop, $retentionSettings['data_retention_months']);
        $deletedAlertRows = $retention->cleanupAlerts($idShop, $retentionSettings['alert_retention_days']);
        echo '[Shop ' . $idShop . '] Sync completed: ' . $processedRows . ' rows, ' . $createdAlerts . ' alerts, ' . $deletedDataRows . ' old data rows removed, ' . $deletedAlertRows . " old alert rows removed\n";
    } catch (Exception $exception) {
        PrestaShopLogger::addLog('GSC cron error shop ' . $idShop . ': ' . $exception->getMessage(), 3);
        echo '[Shop ' . $idShop . '] Error: ' . $exception->getMessage() . "\n";
    }
}
