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

use Tecnoacquisti\SearchConsole\GscOAuthHandler;

function tecGscAppendCallbackStatus($url, $parameter)
{
    if ($url === '') {
        $url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__;
    }

    return $url . (strpos($url, '?') === false ? '?' : '&') . ltrim($parameter, '&?');
}

$module = Module::getInstanceByName('tec_searchconsole');
if ($module && method_exists($module, 'loadModuleAutoloader')) {
    $module->loadModuleAutoloader();
}

$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
if ($idShop <= 0) {
    $idShop = 1;
}
$state = (string) Tools::getValue('state');
$code = (string) Tools::getValue('code');
$matchedShopId = GscOAuthHandler::findShopIdByState($state);
if ($matchedShopId > 0) {
    $idShop = $matchedShopId;
}
$adminUrl = (string) Configuration::get('TEC_GSC_ADMIN_RETURN_URL_' . (int) $idShop);

if ($matchedShopId <= 0) {
    Tools::redirectAdmin(tecGscAppendCallbackStatus($adminUrl, 'gsc_error=csrf'));
    exit;
}

if ($code === '') {
    GscOAuthHandler::clearState($idShop);
    Configuration::deleteByName('TEC_GSC_ADMIN_RETURN_URL_' . (int) $idShop);
    Tools::redirectAdmin(tecGscAppendCallbackStatus($adminUrl, 'gsc_error=no_code'));
    exit;
}

try {
    $oauthHandler = new GscOAuthHandler($idShop);
    $success = $oauthHandler->exchangeCodeForTokens($code);
} catch (Exception $exception) {
    PrestaShopLogger::addLog('GSC callback failed: ' . $exception->getMessage(), 3);
    $success = false;
}

GscOAuthHandler::clearState($idShop);
Configuration::deleteByName('TEC_GSC_ADMIN_RETURN_URL_' . (int) $idShop);
Tools::redirectAdmin(tecGscAppendCallbackStatus($adminUrl, $success ? 'gsc_success=connected' : 'gsc_error=token_failed'));
exit;
