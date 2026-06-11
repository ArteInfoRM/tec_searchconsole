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

declare(strict_types=1);

namespace Tecnoacquisti\SearchConsole;

use Context;
use Db;
use Product;

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
     * @param int $idShop Shop identifier
     * @param int $idLang Language identifier
     */
    public function __construct(int $idShop, int $idLang)
    {
        $this->idShop = $idShop;
        $this->idLang = $idLang;
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
            return array();
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

        return is_array($row) ? $row : array();
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
            return array();
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

        return is_array($rows) ? $rows : array();
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

        $product = new Product($idProduct, false, $this->idLang, $this->idShop);
        if (!isset($product->id) || (int) $product->id <= 0) {
            return '';
        }

        $pageUrl = Context::getContext()->link->getProductLink($product, null, null, null, $this->idLang, $this->idShop);
        $path = parse_url($pageUrl, PHP_URL_PATH);

        return is_string($path) ? rtrim($path, '/') : '';
    }
}
