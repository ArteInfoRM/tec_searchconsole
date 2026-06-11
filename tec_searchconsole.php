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
 * @version   1.0.1
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use Tecnoacquisti\SearchConsole\GscConfigRepository;
use Tecnoacquisti\SearchConsole\GscApiClient;
use Tecnoacquisti\SearchConsole\GscOAuthHandler;
use Tecnoacquisti\SearchConsole\GscProductLinker;

/**
 * Google Search Console integration module.
 */
class Tec_searchconsole extends Module
{
    /**
     * Module constructor.
     */
    public function __construct()
    {
        $this->name = 'tec_searchconsole';
        $this->tab = 'analytics_stats';
        $this->version = '1.0.1';
        $this->author = 'Tecnoacquisti.com';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => '9.99.99');

        $this->loadModuleAutoloader();

        parent::__construct();

        $this->displayName = $this->l('Search Console SEO Dashboard');
        $this->description = $this->l('Google Search Console API integration with OAuth 2.0, local SEO history, dashboard, and alerts.');
    }

    /**
     * Install module resources.
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->installSql()
            && $this->installTab()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayHeader')
            && $this->registerHook('dashboardZoneTwo')
            && $this->initializeConfiguration();
    }

    /**
     * Uninstall module resources.
     *
     * @return bool
     */
    public function uninstall()
    {
        return $this->uninstallTab()
            && $this->uninstallSql()
            && $this->deleteConfiguration()
            && parent::uninstall();
    }

    /**
     * Redirect module configuration to the dashboard controller.
     *
     * @return string
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminTecGsc'));

        return '';
    }

    /**
     * Render product SEO widget in the product back-office page.
     *
     * @param array<string, mixed> $params Hook parameters
     *
     * @return string Rendered widget
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : (int) Tools::getValue('id_product');
        if ($idProduct <= 0 || !class_exists(GscProductLinker::class)) {
            return '';
        }

        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;
        $linker = new GscProductLinker($idShop, $idLang);

        $this->context->smarty->assign(array(
            'gsc_seo_data' => $linker->getProductSeoData($idProduct, 30),
            'gsc_top_keys' => $linker->getProductTopKeywords($idProduct, 10, 30),
        ));

        return $this->display(__FILE__, 'views/templates/admin/product_seo.tpl');
    }

    /**
     * Load back-office assets.
     *
     * @param array<string, mixed> $params Hook parameters
     *
     * @return void
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        $this->ensureStatsTabRemainsClickable();

        $controllerName = isset($this->context->controller->controller_name)
            ? (string) $this->context->controller->controller_name
            : (string) Tools::getValue('controller');
        if (!in_array($controllerName, array('AdminTecGsc', 'AdminDashboard'), true)) {
            return;
        }

        $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        $this->context->controller->addJS($this->_path . 'views/js/tec_gsc_dashboard.js');
    }

    /**
     * Render Google Search Console verification meta tag.
     *
     * @param array<string, mixed> $params Hook parameters
     *
     * @return string Rendered verification tag
     */
    public function hookDisplayHeader($params)
    {
        $idShop = isset($this->context->shop->id) ? (int) $this->context->shop->id : null;
        $token = trim((string) Configuration::get('TEC_GSC_VERIFICATION_TOKEN', null, null, $idShop));
        if ($token === '') {
            return '';
        }

        $this->context->smarty->assign(array(
            'gsc_verification_token' => $token,
        ));

        return $this->display(__FILE__, 'views/templates/hook/search_console_verification.tpl');
    }

    /**
     * Render Search Console metrics in the main back-office dashboard.
     *
     * @param array<string, mixed> $params Hook parameters
     *
     * @return string Dashboard widget
     */
    public function hookDashboardZoneTwo($params)
    {
        $idShop = isset($this->context->shop->id) ? (int) $this->context->shop->id : 1;
        $config = $this->getSearchConsoleConfig($idShop);
        $metrics = array(
            'clicks' => 0,
            'impressions' => 0,
            'ctr' => 0,
            'position' => 0,
        );
        $sitemaps = array();
        $topQueries = array();

        $apiClient = $this->getSearchConsoleApiClient($idShop, $config);
        if ($apiClient instanceof GscApiClient) {
            $periodStart = date('Y-m-d', strtotime('-29 days'));
            $periodEnd = date('Y-m-d', strtotime('-2 days'));
            $metrics = $apiClient->getDailyTotals($periodStart, $periodEnd);
            $topQueries = $apiClient->getTopQueries($periodStart, $periodEnd, 5);
            $sitemaps = $apiClient->listSitemaps();
        }

        $this->context->smarty->assign(array(
            'tec_gsc_dashboard_url' => $this->context->link->getAdminLink('AdminTecGsc'),
            'tec_gsc_is_connected' => !empty($config['is_connected']),
            'tec_gsc_metrics' => $metrics,
            'tec_gsc_top_queries' => $topQueries,
            'tec_gsc_sitemaps' => array_slice($sitemaps, 0, 5),
            'tec_gsc_sitemap_count' => count($sitemaps),
        ));

        return $this->display(__FILE__, 'views/templates/hook/dashboard.tpl');
    }

    /**
     * Load Composer or fallback class autoloader.
     *
     * @return void
     */
    public function loadModuleAutoloader()
    {
        spl_autoload_register(function ($class) {
            $prefix = 'Tecnoacquisti\\SearchConsole\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = __DIR__ . '/classes/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
    }

    /**
     * Execute install SQL file.
     *
     * @return bool
     */
    private function installSql()
    {
        return $this->executeSqlFile(__DIR__ . '/sql/install.sql');
    }

    /**
     * Execute uninstall SQL file.
     *
     * @return bool
     */
    private function uninstallSql()
    {
        return $this->executeSqlFile(__DIR__ . '/sql/uninstall.sql');
    }

    /**
     * Execute SQL statements from a file.
     *
     * @param string $filePath SQL file path
     *
     * @return bool
     */
    private function executeSqlFile($filePath)
    {
        if (!is_file($filePath)) {
            return false;
        }

        $sql = file_get_contents($filePath);
        if ($sql === false) {
            return false;
        }

        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if ($statement !== '' && !Db::getInstance()->execute($statement)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Install the back-office tab.
     *
     * @return bool
     */
    private function installTab()
    {
        $idParent = $this->getStatsSiblingParentId();

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminTecGsc';
        $tab->module = $this->name;
        $tab->id_parent = $idParent;

        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = 'Search Console SEO';
        }

        return (bool) $tab->add();
    }

    /**
     * Keep Search Console as a sibling of the native stats page.
     *
     * @return bool
     */
    private function ensureStatsTabRemainsClickable()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminTecGsc');
        if ($idTab <= 0) {
            return true;
        }

        $idParent = $this->getStatsSiblingParentId();
        $tab = new Tab($idTab);
        if ((int) $tab->id_parent === $idParent) {
            return true;
        }

        $tab->id_parent = $idParent;

        return (bool) $tab->update();
    }

    /**
     * Get the parent used by the native stats page.
     *
     * @return int
     */
    private function getStatsSiblingParentId()
    {
        $idStats = (int) Tab::getIdFromClassName('AdminStats');
        if ($idStats <= 0) {
            return 0;
        }

        $statsTab = new Tab($idStats);

        return (int) $statsTab->id_parent;
    }

    /**
     * Uninstall the back-office tab.
     *
     * @return bool
     */
    private function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminTecGsc');
        if ($idTab <= 0) {
            return true;
        }

        $tab = new Tab($idTab);

        return (bool) $tab->delete();
    }

    /**
     * Initialize module configuration.
     *
     * @return bool
     */
    private function initializeConfiguration()
    {
        if (class_exists(GscConfigRepository::class)) {
            $repository = new GscConfigRepository();
            $repository->ensureAllShopRows();
            $repository->getCronToken();
        }

        return true;
    }

    /**
     * Delete module configuration keys.
     *
     * @return bool
     */
    private function deleteConfiguration()
    {
        Configuration::deleteByName('TEC_GSC_CRON_TOKEN');
        Configuration::deleteByName('TEC_GSC_VERIFICATION_TOKEN');

        return true;
    }

    /**
     * Read Search Console configuration for one shop.
     *
     * @param int $idShop Shop identifier
     *
     * @return array<string, mixed> Configuration row
     */
    private function getSearchConsoleConfig($idShop)
    {
        $row = Db::getInstance()->getRow(
            'SELECT *
            FROM `' . _DB_PREFIX_ . 'tec_gsc_config`
            WHERE id_shop = ' . (int) $idShop
        );

        return is_array($row) ? $row : array();
    }

    /**
     * Create a Search Console API client when the module is connected.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Configuration row
     *
     * @return GscApiClient|null API client
     */
    private function getSearchConsoleApiClient($idShop, array $config)
    {
        $siteUrl = isset($config['site_url']) ? (string) $config['site_url'] : '';
        if ($siteUrl === '' || empty($config['is_connected'])) {
            return null;
        }

        try {
            $oauth = new GscOAuthHandler((int) $idShop);

            return new GscApiClient($oauth, $siteUrl);
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC dashboard widget error: ' . $exception->getMessage(), 2);

            return null;
        }
    }
}
