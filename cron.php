<?php
/**
 * 2009-2026 Arte e Informatica
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license.
 *
 * @author    Arte e Informatica <helpdesk@tecnoacquisti.com>
 * @copyright 2009-2026 Arte e Informatica
 * @license   Commercial license
 */

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/tec_searchconsole.php';

use Tecnoacquisti\SearchConsole\GscAlertEngine;
use Tecnoacquisti\SearchConsole\GscApiClient;
use Tecnoacquisti\SearchConsole\GscConfigRepository;
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
    $options = getopt('', array('token:'));
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
        echo '[Shop ' . $idShop . '] Sync completed: ' . $processedRows . ' rows, ' . $createdAlerts . " alerts\n";
    } catch (Exception $exception) {
        PrestaShopLogger::addLog('GSC cron error shop ' . $idShop . ': ' . $exception->getMessage(), 3);
        echo '[Shop ' . $idShop . '] Error: ' . $exception->getMessage() . "\n";
    }
}
