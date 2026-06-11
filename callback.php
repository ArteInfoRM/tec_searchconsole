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

use Tecnoacquisti\SearchConsole\GscOAuthHandler;

$module = Module::getInstanceByName('tec_searchconsole');
if ($module && method_exists($module, 'loadModuleAutoloader')) {
    $module->loadModuleAutoloader();
}

$context = Context::getContext();
$idShop = isset($context->shop->id) ? (int) $context->shop->id : 1;
$state = (string) Tools::getValue('state');
$code = (string) Tools::getValue('code');
$matchedShopId = GscOAuthHandler::findShopIdByState($state);
if ($matchedShopId > 0) {
    $idShop = $matchedShopId;
}
$adminUrl = (string) Configuration::get('TEC_GSC_ADMIN_RETURN_URL_' . (int) $idShop);
if ($adminUrl === '') {
    $adminUrl = $context->link->getAdminLink('AdminTecGsc');
}

if ($matchedShopId <= 0) {
    Tools::redirectAdmin($adminUrl . '&gsc_error=csrf');
    exit;
}

if ($code === '') {
    GscOAuthHandler::clearState($idShop);
    Configuration::deleteByName('TEC_GSC_ADMIN_RETURN_URL_' . (int) $idShop);
    Tools::redirectAdmin($adminUrl . '&gsc_error=no_code');
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
Tools::redirectAdmin($adminUrl . ($success ? '&gsc_success=connected' : '&gsc_error=token_failed'));
exit;
