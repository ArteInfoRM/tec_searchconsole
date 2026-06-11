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

use Exception;
use Google\Client;
use Google\Service\Webmasters;
use Google\Service\Webmasters\SearchAnalyticsQueryRequest;
use PrestaShopLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Wraps the Google Search Console API.
 */
class GscApiClient
{
    /**
     * @var Webmasters Search Console service
     */
    private $service;

    /**
     * @var string Search Console property URL
     */
    private $siteUrl;

    /**
     * @param GscOAuthHandler $oauthHandler OAuth handler
     * @param string $siteUrl Search Console property URL
     */
    public function __construct(GscOAuthHandler $oauthHandler, string $siteUrl)
    {
        $accessToken = $oauthHandler->getValidAccessToken();
        if ($accessToken === null) {
            throw new Exception('Google Search Console account is not connected.');
        }

        $client = new Client();
        $client->setAccessToken($accessToken);

        $this->service = new Webmasters($client);
        $this->siteUrl = $siteUrl;
    }

    /**
     * Fetch Search Analytics rows.
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param string[] $dimensions Search Console dimensions
     * @param int $rowLimit Row limit
     * @param int $startRow Start row
     *
     * @return array<int, mixed> API rows
     */
    public function getSearchAnalytics(
        string $startDate,
        string $endDate,
        array $dimensions = ['query', 'page'],
        int $rowLimit = 5000,
        int $startRow = 0
    ): array {
        $request = new SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions($dimensions);
        $request->setRowLimit($rowLimit);
        $request->setStartRow($startRow);

        try {
            $response = $this->service->searchanalytics->query($this->siteUrl, $request);
            $rows = $response->getRows();

            return is_array($rows) ? $rows : [];
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC API error: ' . $exception->getMessage(), 3);

            return [];
        }
    }

    /**
     * Fetch all Search Analytics rows using API pagination.
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param string[] $dimensions Search Console dimensions
     *
     * @return array<int, mixed> API rows
     */
    public function getAllSearchAnalytics(
        string $startDate,
        string $endDate,
        array $dimensions = ['query', 'page', 'date']
    ): array {
        $allRows = [];
        $startRow = 0;
        $pageSize = 25000;

        do {
            $rows = $this->getSearchAnalytics($startDate, $endDate, $dimensions, $pageSize, $startRow);
            $allRows = array_merge($allRows, $rows);
            $startRow += $pageSize;
        } while (count($rows) === $pageSize);

        return $allRows;
    }

    /**
     * Fetch daily aggregate Search Analytics totals.
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     *
     * @return array<string, float|int> Aggregated totals
     */
    public function getDailyTotals(string $startDate, string $endDate): array
    {
        $rows = $this->getSearchAnalytics($startDate, $endDate, ['date'], 25000, 0);
        $clicks = 0;
        $impressions = 0;
        $weightedPosition = 0.0;

        foreach ($rows as $row) {
            if (!is_object($row)) {
                continue;
            }

            $rowClicks = method_exists($row, 'getClicks') ? (int) $row->getClicks() : 0;
            $rowImpressions = method_exists($row, 'getImpressions') ? (int) $row->getImpressions() : 0;
            $rowPosition = method_exists($row, 'getPosition') ? (float) $row->getPosition() : 0.0;
            $clicks += $rowClicks;
            $impressions += $rowImpressions;
            $weightedPosition += $rowPosition * $rowImpressions;
        }

        return [
            'clicks' => $clicks,
            'impressions' => $impressions,
            'ctr' => $impressions > 0 ? $clicks / $impressions : 0.0,
            'position' => $impressions > 0 ? $weightedPosition / $impressions : 0.0,
        ];
    }

    /**
     * Fetch top pages ordered by clicks.
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param int $limit Result limit
     *
     * @return array<int, array<string, mixed>> Top page rows
     */
    public function getTopPages(string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->getTopDimensionRows('page', $startDate, $endDate, $limit);
    }

    /**
     * Fetch top queries ordered by clicks.
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param int $limit Result limit
     *
     * @return array<int, array<string, mixed>> Top query rows
     */
    public function getTopQueries(string $startDate, string $endDate, int $limit = 20): array
    {
        return $this->getTopDimensionRows('query', $startDate, $endDate, $limit);
    }

    /**
     * Fetch top rows for one Search Console dimension.
     *
     * @param string $dimension Search Console dimension
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param int $limit Result limit
     *
     * @return array<int, array<string, mixed>> Dimension rows
     */
    private function getTopDimensionRows(string $dimension, string $startDate, string $endDate, int $limit): array
    {
        if (!in_array($dimension, ['page', 'query'], true)) {
            return [];
        }

        $rows = $this->getSearchAnalytics($startDate, $endDate, [$dimension], max(1, min(100, $limit)), 0);
        $dimensionRows = [];

        foreach ($rows as $row) {
            if (!is_object($row) || !method_exists($row, 'getKeys')) {
                continue;
            }

            $keys = $row->getKeys();
            $clicks = method_exists($row, 'getClicks') ? (int) $row->getClicks() : 0;
            $impressions = method_exists($row, 'getImpressions') ? (int) $row->getImpressions() : 0;
            $dimensionRows[] = [
                $dimension => isset($keys[0]) ? (string) $keys[0] : '',
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $impressions > 0 ? $clicks / $impressions : 0.0,
                'position' => method_exists($row, 'getPosition') ? (float) $row->getPosition() : 0.0,
            ];
        }

        usort($dimensionRows, function (array $left, array $right) {
            return (int) $right['clicks'] <=> (int) $left['clicks'];
        });

        return $dimensionRows;
    }

    /**
     * List Search Console sites available to the connected account.
     *
     * @return array<int, mixed> Site entries
     */
    public function listSites(): array
    {
        try {
            $response = $this->service->sites->listSites();
            $entries = $response->getSiteEntry();

            return is_array($entries) ? $entries : [];
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC sites list error: ' . $exception->getMessage(), 2);

            return [];
        }
    }

    /**
     * List submitted sitemaps for the configured Search Console property.
     *
     * @return array<int, array<string, mixed>> Sitemap rows
     */
    public function listSitemaps(): array
    {
        try {
            $response = $this->service->sitemaps->listSitemaps($this->siteUrl);
            $sitemaps = $response->getSitemap();
        } catch (Exception $exception) {
            PrestaShopLogger::addLog('GSC sitemaps list error: ' . $exception->getMessage(), 2);

            return [];
        }

        if (!is_array($sitemaps)) {
            return [];
        }

        $rows = [];
        foreach ($sitemaps as $sitemap) {
            if (!is_object($sitemap)) {
                continue;
            }

            $rows[] = [
                'path' => method_exists($sitemap, 'getPath') ? (string) $sitemap->getPath() : '',
                'type' => method_exists($sitemap, 'getType') ? (string) $sitemap->getType() : '',
                'is_pending' => method_exists($sitemap, 'getIsPending') ? (bool) $sitemap->getIsPending() : false,
                'is_sitemaps_index' => method_exists($sitemap, 'getIsSitemapsIndex') ? (bool) $sitemap->getIsSitemapsIndex() : false,
                'last_submitted' => method_exists($sitemap, 'getLastSubmitted') ? (string) $sitemap->getLastSubmitted() : '',
                'last_downloaded' => method_exists($sitemap, 'getLastDownloaded') ? (string) $sitemap->getLastDownloaded() : '',
                'warnings' => method_exists($sitemap, 'getWarnings') ? (int) $sitemap->getWarnings() : 0,
                'errors' => method_exists($sitemap, 'getErrors') ? (int) $sitemap->getErrors() : 0,
                'submitted_urls' => $this->getSubmittedUrlCount($sitemap),
            ];
        }

        return $rows;
    }

    /**
     * Extract submitted URL count from sitemap contents.
     *
     * @param object $sitemap Sitemap object
     *
     * @return int Submitted URLs
     */
    private function getSubmittedUrlCount($sitemap): int
    {
        if (!method_exists($sitemap, 'getContents')) {
            return 0;
        }

        $contents = $sitemap->getContents();
        if (!is_array($contents)) {
            return 0;
        }

        $submittedUrls = 0;
        foreach ($contents as $content) {
            if (is_object($content) && method_exists($content, 'getSubmitted')) {
                $submittedUrls += (int) $content->getSubmitted();
            }
        }

        return $submittedUrls;
    }
}
