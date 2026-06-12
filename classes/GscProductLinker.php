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

use Db;
use Link;
use Product;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Links stored GSC data to PrestaShop products.
 */
class GscProductLinker
{
    /**
     * @var int Shop identifier
     */
    private $idShop;

    /**
     * @var int Language identifier
     */
    private $idLang;

    /**
     * @var Link URL builder
     */
    private $link;

    /**
     * @param int $idShop Shop identifier
     * @param int $idLang Language identifier
     * @param Link $link URL builder
     */
    public function __construct(int $idShop, int $idLang, Link $link)
    {
        $this->idShop = $idShop;
        $this->idLang = $idLang;
        $this->link = $link;
    }

    /**
     * Get SEO metrics for one product.
     *
     * @param int $idProduct Product identifier
     * @param int $days Period length in days
     *
     * @return array<string, mixed> Aggregated metrics
     */
    public function getProductSeoData(int $idProduct, int $days = 30): array
    {
        $pagePath = $this->getProductPagePath($idProduct);
        if ($pagePath === '') {
            return [];
        }

        $days = max(1, min(365, $days));
        $row = Db::getInstance()->getRow(
            'SELECT
                SUM(clicks) AS total_clicks,
                SUM(impressions) AS total_impressions,
                AVG(ctr) AS avg_ctr,
                AVG(position) AS avg_position,
                COUNT(DISTINCT query) AS keyword_count
            FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE id_shop = ' . (int) $this->idShop . '
                AND data_date >= \'' . pSQL(date('Y-m-d', strtotime('-' . $days . ' days'))) . '\'
                AND page LIKE \'%' . pSQL($pagePath) . '%\''
        );

        return is_array($row) ? $row : [];
    }

    /**
     * Get top keywords for one product.
     *
     * @param int $idProduct Product identifier
     * @param int $limit Result limit
     * @param int $days Period length in days
     *
     * @return array<int, array<string, mixed>> Keyword rows
     */
    public function getProductTopKeywords(int $idProduct, int $limit = 20, int $days = 30): array
    {
        $pagePath = $this->getProductPagePath($idProduct);
        if ($pagePath === '') {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $days = max(1, min(365, $days));
        $rows = Db::getInstance()->executeS(
            'SELECT
                query,
                SUM(clicks) AS clicks,
                SUM(impressions) AS impressions,
                AVG(ctr) AS ctr,
                AVG(position) AS position
            FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE id_shop = ' . (int) $this->idShop . '
                AND data_date >= \'' . pSQL(date('Y-m-d', strtotime('-' . $days . ' days'))) . '\'
                AND page LIKE \'%' . pSQL($pagePath) . '%\'
                AND query IS NOT NULL
                AND query != \'\'
            GROUP BY query
            ORDER BY impressions DESC
            LIMIT ' . (int) $limit
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * Build the canonical product URL.
     *
     * @param int $idProduct Product identifier
     *
     * @return string Product URL
     */
    public function getProductPageUrl(int $idProduct): string
    {
        if ($idProduct <= 0) {
            return '';
        }

        $product = new Product($idProduct, false, $this->idLang, $this->idShop);
        if (!isset($product->id) || (int) $product->id <= 0) {
            return '';
        }

        return $this->link->getProductLink($product, null, null, null, $this->idLang, $this->idShop);
    }

    /**
     * Build the canonical product path.
     *
     * @param int $idProduct Product identifier
     *
     * @return string URL path
     */
    private function getProductPagePath(int $idProduct): string
    {
        if ($idProduct <= 0) {
            return '';
        }

        $pageUrl = $this->getProductPageUrl($idProduct);
        $path = parse_url($pageUrl, PHP_URL_PATH);

        return is_string($path) ? rtrim($path, '/') : '';
    }
}
