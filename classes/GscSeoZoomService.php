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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Loads SEOZoom metrics using a local cache.
 */
class GscSeoZoomService
{
    /**
     * Load dashboard domain metrics.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Module configuration
     * @param bool $forceRefresh Force an API call
     *
     * @return array<string, mixed> Domain metrics status
     */
    public function getDomainMetrics(int $idShop, array $config, bool $forceRefresh = false): array
    {
        $domain = $this->normalizeDomain(isset($config['site_url']) ? (string) $config['site_url'] : '');
        $apiKey = isset($config['seozoom_api_key']) ? trim((string) $config['seozoom_api_key']) : '';
        $db = isset($config['seozoom_db']) ? (string) $config['seozoom_db'] : 'it';
        $cacheHours = isset($config['seozoom_cache_hours']) ? (int) $config['seozoom_cache_hours'] : 24;

        $result = [
            'enabled' => $apiKey !== '',
            'domain' => $domain,
            'db' => $db,
            'has_data' => false,
            'is_cached' => false,
            'error' => '',
            'seozoom_url' => $this->buildDomainSeoZoomUrl($domain),
            'metrics' => [],
        ];

        if ($apiKey === '' || $domain === '') {
            return $result;
        }

        $repository = new GscSeoZoomRepository();
        $cached = $repository->getCachedDomainMetrics($idShop, $db, $domain);
        if (!$forceRefresh && $repository->isFresh($cached, $cacheHours)) {
            $result['has_data'] = true;
            $result['is_cached'] = true;
            $result['metrics'] = $this->normalizeCachedRow($cached);

            return $result;
        }

        try {
            $client = new GscSeoZoomClient();
            $metrics = $client->getDomainMetrics($apiKey, $db, $domain);
            $repository->saveDomainMetrics($idShop, $db, $domain, $metrics);
            $result['has_data'] = true;
            $result['metrics'] = $this->normalizeCachedRow($repository->getCachedDomainMetrics($idShop, $db, $domain));
        } catch (\Exception $exception) {
            if (!empty($cached)) {
                $result['has_data'] = true;
                $result['is_cached'] = true;
                $result['metrics'] = $this->normalizeCachedRow($cached);
            }

            $result['error'] = $exception->getMessage();
        }

        return $result;
    }

    /**
     * Add cached SEOZoom search volume to query rows.
     *
     * @param int $idShop Shop identifier
     * @param array<string, mixed> $config Module configuration
     * @param array<int, array<string, mixed>> $rows Search Console query rows
     *
     * @return array<int, array<string, mixed>> Rows with optional search volume
     */
    public function enrichQueryRowsWithSearchVolume(int $idShop, array $config, array $rows): array
    {
        $apiKey = isset($config['seozoom_api_key']) ? trim((string) $config['seozoom_api_key']) : '';
        if ($apiKey === '' || empty($rows)) {
            return $rows;
        }

        $db = isset($config['seozoom_db']) ? (string) $config['seozoom_db'] : 'it';
        $cacheHours = isset($config['seozoom_cache_hours']) ? (int) $config['seozoom_cache_hours'] : 24;
        $keywords = [];
        foreach ($rows as $row) {
            if (!empty($row['query'])) {
                $keywords[] = (string) $row['query'];
            }
        }

        if (empty($keywords)) {
            return $rows;
        }

        $repository = new GscSeoZoomRepository();
        $cachedRows = $repository->getCachedKeywordMetrics($idShop, $db, $keywords);
        $missingKeywords = [];

        foreach ($keywords as $keyword) {
            $normalizedKeyword = $this->normalizeKeyword($keyword);
            if ($normalizedKeyword === '') {
                continue;
            }

            if (empty($cachedRows[$normalizedKeyword]) || !$repository->isFresh($cachedRows[$normalizedKeyword], $cacheHours)) {
                $missingKeywords[$normalizedKeyword] = $normalizedKeyword;
            }
        }

        if (!empty($missingKeywords)) {
            try {
                $metrics = (new GscSeoZoomClient())->getKeywordMetrics($apiKey, $db, array_values($missingKeywords));
                if (!empty($metrics)) {
                    $repository->saveKeywordMetrics($idShop, $db, $metrics);
                    $cachedRows = $repository->getCachedKeywordMetrics($idShop, $db, $keywords);
                }
            } catch (\Exception $exception) {
                // Keep Search Console data visible even when SEOZoom is temporarily unavailable.
            }
        }

        foreach ($rows as &$row) {
            if (empty($row['query'])) {
                continue;
            }

            $normalizedKeyword = $this->normalizeKeyword((string) $row['query']);
            if (isset($cachedRows[$normalizedKeyword]['search_volume'])) {
                $row['search_volume'] = (int) $cachedRows[$normalizedKeyword]['search_volume'];
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Build a SEOZoom suite URL.
     *
     * @param string $domain Domain
     *
     * @return string SEOZoom URL
     */
    public function buildDomainSeoZoomUrl(string $domain): string
    {
        $domain = trim($domain);
        if ($domain === '') {
            return '';
        }

        return 'https://sznew.seozoom.it/analyze/websitezoom?q=' . rawurlencode($domain);
    }

    /**
     * Build a SEOZoom page analysis URL.
     *
     * @param string $url Page URL
     *
     * @return string SEOZoom URL
     */
    public function buildPageSeoZoomUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return 'https://sznew.seozoom.it/analyze/pagezoom?q=' . rawurlencode(rawurlencode($url));
    }

    /**
     * Normalize Search Console property URL to a domain.
     *
     * @param string $siteUrl Search Console property URL
     *
     * @return string Domain name
     */
    public function normalizeDomain(string $siteUrl): string
    {
        $siteUrl = trim($siteUrl);
        if ($siteUrl === '') {
            return '';
        }

        if (strpos($siteUrl, 'sc-domain:') === 0) {
            return strtolower(trim(substr($siteUrl, 10)));
        }

        $host = parse_url($siteUrl, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return '';
        }

        return strtolower($host);
    }

    /**
     * Normalize a cached database row for templates.
     *
     * @param array<string, mixed> $row Cached row
     *
     * @return array<string, mixed> Template metrics
     */
    private function normalizeCachedRow(array $row): array
    {
        return [
            'main_domain' => isset($row['main_domain']) ? (string) $row['main_domain'] : '',
            'zoom_authority' => isset($row['zoom_authority']) ? (float) $row['zoom_authority'] : null,
            'zoom_trust' => isset($row['zoom_trust']) ? (float) $row['zoom_trust'] : null,
            'organic_traffic' => isset($row['organic_traffic']) ? (int) $row['organic_traffic'] : 0,
            'organic_keywords' => isset($row['organic_keywords']) ? (int) $row['organic_keywords'] : 0,
            'units_used' => isset($row['units_used']) ? (int) $row['units_used'] : 0,
            'units_remaining' => isset($row['units_remaining']) ? (int) $row['units_remaining'] : null,
            'date_upd' => isset($row['date_upd']) ? (string) $row['date_upd'] : '',
        ];
    }

    /**
     * Normalize a keyword for cache matching.
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
