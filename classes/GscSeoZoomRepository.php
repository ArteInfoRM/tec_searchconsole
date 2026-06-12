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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Stores cached SEOZoom domain metrics.
 */
class GscSeoZoomRepository
{
    /**
     * Ensure SEOZoom cache tables exist.
     *
     * @return void
     */
    public function ensureTables(): void
    {
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tec_gsc_seozoom_domain_metrics` (
                `id_metric` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
                `db` VARCHAR(5) NOT NULL DEFAULT \'it\',
                `domain` VARCHAR(255) NOT NULL DEFAULT \'\',
                `main_domain` VARCHAR(255) NOT NULL DEFAULT \'\',
                `zoom_authority` DECIMAL(10,2) DEFAULT NULL,
                `zoom_trust` DECIMAL(10,2) DEFAULT NULL,
                `organic_traffic` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `organic_keywords` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `units_used` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `units_remaining` INT(10) UNSIGNED DEFAULT NULL,
                `raw_payload` MEDIUMTEXT,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_metric`),
                UNIQUE KEY `idx_shop_db_domain` (`id_shop`, `db`, `domain`),
                KEY `idx_date_upd` (`date_upd`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tec_gsc_seozoom_keyword_metrics` (
                `id_metric` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_shop` INT(10) UNSIGNED NOT NULL DEFAULT 1,
                `db` VARCHAR(5) NOT NULL DEFAULT \'it\',
                `keyword` VARCHAR(255) NOT NULL DEFAULT \'\',
                `search_volume` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `units_used` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                `units_remaining` INT(10) UNSIGNED DEFAULT NULL,
                `raw_payload` MEDIUMTEXT,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_metric`),
                UNIQUE KEY `idx_shop_db_keyword` (`id_shop`, `db`, `keyword`),
                KEY `idx_date_upd` (`date_upd`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * Read cached metrics.
     *
     * @param int $idShop Shop identifier
     * @param string $db SEOZoom database code
     * @param string $domain Domain name
     *
     * @return array<string, mixed> Cached row
     */
    public function getCachedDomainMetrics(int $idShop, string $db, string $domain): array
    {
        $this->ensureTables();
        $row = Db::getInstance()->getRow(
            'SELECT *
            FROM `' . _DB_PREFIX_ . 'tec_gsc_seozoom_domain_metrics`
            WHERE id_shop = ' . (int) $idShop . '
                AND db = \'' . pSQL($db) . '\'
                AND domain = \'' . pSQL($domain) . '\''
        );

        return is_array($row) ? $row : [];
    }

    /**
     * Save domain metrics.
     *
     * @param int $idShop Shop identifier
     * @param string $db SEOZoom database code
     * @param string $domain Requested domain
     * @param array<string, mixed> $metrics Normalized metrics
     *
     * @return void
     */
    public function saveDomainMetrics(int $idShop, string $db, string $domain, array $metrics): void
    {
        $this->ensureTables();
        $now = date('Y-m-d H:i:s');
        $row = [
            'id_shop' => (int) $idShop,
            'db' => pSQL($db),
            'domain' => pSQL($domain),
            'main_domain' => pSQL(isset($metrics['main_domain']) ? (string) $metrics['main_domain'] : $domain),
            'zoom_authority' => isset($metrics['zoom_authority']) ? (float) $metrics['zoom_authority'] : null,
            'zoom_trust' => isset($metrics['zoom_trust']) ? (float) $metrics['zoom_trust'] : null,
            'organic_traffic' => isset($metrics['organic_traffic']) ? (int) $metrics['organic_traffic'] : 0,
            'organic_keywords' => isset($metrics['organic_keywords']) ? (int) $metrics['organic_keywords'] : 0,
            'units_used' => isset($metrics['units_used']) ? (int) $metrics['units_used'] : 0,
            'units_remaining' => isset($metrics['units_remaining']) ? (int) $metrics['units_remaining'] : null,
            'raw_payload' => pSQL(isset($metrics['raw_payload']) ? (string) $metrics['raw_payload'] : ''),
            'date_add' => $now,
            'date_upd' => $now,
        ];

        Db::getInstance()->insert('tec_gsc_seozoom_domain_metrics', $row, false, true, Db::REPLACE);
    }

    /**
     * Check whether a cached row is still fresh.
     *
     * @param array<string, mixed> $row Cached row
     * @param int $cacheHours Cache duration in hours
     *
     * @return bool True when cache can be reused
     */
    public function isFresh(array $row, int $cacheHours): bool
    {
        if (empty($row['date_upd'])) {
            return false;
        }

        return strtotime((string) $row['date_upd']) >= strtotime('-' . max(1, $cacheHours) . ' hours');
    }

    /**
     * Read cached keyword metrics.
     *
     * @param int $idShop Shop identifier
     * @param string $db SEOZoom database code
     * @param array<int, string> $keywords Keywords
     *
     * @return array<string, array<string, mixed>> Rows indexed by normalized keyword
     */
    public function getCachedKeywordMetrics(int $idShop, string $db, array $keywords): array
    {
        $this->ensureTables();
        $keywords = $this->normalizeKeywords($keywords);
        if (empty($keywords)) {
            return [];
        }

        $quotedKeywords = [];
        foreach ($keywords as $keyword) {
            $quotedKeywords[] = '\'' . pSQL($keyword) . '\'';
        }

        $rows = Db::getInstance()->executeS(
            'SELECT *
            FROM `' . _DB_PREFIX_ . 'tec_gsc_seozoom_keyword_metrics`
            WHERE id_shop = ' . (int) $idShop . '
                AND db = \'' . pSQL($db) . '\'
                AND keyword IN (' . implode(',', $quotedKeywords) . ')'
        );

        $indexedRows = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (isset($row['keyword'])) {
                    $indexedRows[(string) $row['keyword']] = $row;
                }
            }
        }

        return $indexedRows;
    }

    /**
     * Save keyword metrics.
     *
     * @param int $idShop Shop identifier
     * @param string $db SEOZoom database code
     * @param array<string, array<string, mixed>> $metrics Keyword metrics
     *
     * @return void
     */
    public function saveKeywordMetrics(int $idShop, string $db, array $metrics): void
    {
        $this->ensureTables();
        $now = date('Y-m-d H:i:s');
        foreach ($metrics as $keyword => $row) {
            $keyword = $this->normalizeKeyword((string) $keyword);
            if ($keyword === '') {
                continue;
            }

            Db::getInstance()->insert('tec_gsc_seozoom_keyword_metrics', [
                'id_shop' => (int) $idShop,
                'db' => pSQL($db),
                'keyword' => pSQL($keyword),
                'search_volume' => isset($row['search_volume']) ? (int) $row['search_volume'] : 0,
                'units_used' => isset($row['units_used']) ? (int) $row['units_used'] : 0,
                'units_remaining' => isset($row['units_remaining']) ? (int) $row['units_remaining'] : null,
                'raw_payload' => pSQL(isset($row['raw_payload']) ? (string) $row['raw_payload'] : ''),
                'date_add' => $now,
                'date_upd' => $now,
            ], false, true, Db::REPLACE);
        }
    }

    /**
     * Normalize keywords and remove duplicates.
     *
     * @param array<int, string> $keywords Keywords
     *
     * @return array<int, string> Normalized keywords
     */
    private function normalizeKeywords(array $keywords): array
    {
        $normalized = [];
        foreach ($keywords as $keyword) {
            $keyword = $this->normalizeKeyword((string) $keyword);
            if ($keyword !== '') {
                $normalized[$keyword] = $keyword;
            }
        }

        return array_values($normalized);
    }

    /**
     * Normalize one keyword for cache matching.
     *
     * @param string $keyword Keyword
     *
     * @return string Normalized keyword
     */
    private function normalizeKeyword(string $keyword): string
    {
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword));

        return mb_strtolower($keyword, 'UTF-8');
    }

}
