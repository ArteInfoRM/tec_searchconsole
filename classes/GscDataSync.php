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

use Db;
use DateTime;

/**
 * Synchronizes Search Console data into local storage.
 */
class GscDataSync
{
    /**
     * @var GscApiClient API client
     */
    private $apiClient;

    /**
     * @var int Shop identifier
     */
    private $idShop;

    /**
     * @param GscApiClient $apiClient API client
     * @param int $idShop Shop identifier
     */
    public function __construct(GscApiClient $apiClient, int $idShop)
    {
        $this->apiClient = $apiClient;
        $this->idShop = $idShop;
    }

    /**
     * Synchronize recent Search Console data.
     *
     * @param int $daysBack Number of days to fetch
     *
     * @return int Number of processed rows
     */
    public function syncRecentDays(int $daysBack = 3): int
    {
        $daysBack = max(3, min(30, $daysBack));
        $endDate = date('Y-m-d', strtotime('-2 days'));
        $startDate = date('Y-m-d', strtotime('-' . $daysBack . ' days'));

        $rows = $this->apiClient->getAllSearchAnalytics(
            $startDate,
            $endDate,
            array('query', 'page', 'device', 'country', 'date')
        );

        $processedRows = $this->upsertRows($rows);
        (new GscConfigRepository())->updateLastSync($this->idShop);

        return $processedRows;
    }

    /**
     * Synchronize historical Search Console data.
     *
     * @param int $months Number of months to fetch
     *
     * @return int Number of processed rows
     */
    public function syncHistorical(int $months = 16): int
    {
        $months = max(1, min(16, $months));
        $endDate = date('Y-m-d', strtotime('-2 days'));
        $current = new DateTime(date('Y-m-d', strtotime('-' . $months . ' months')));
        $end = new DateTime($endDate);
        $processedRows = 0;

        while ($current < $end) {
            $chunkStart = $current->format('Y-m-d');
            $current->modify('+30 days');
            $chunkEnd = $current > $end ? $endDate : $current->format('Y-m-d');

            $rows = $this->apiClient->getAllSearchAnalytics(
                $chunkStart,
                $chunkEnd,
                array('query', 'page', 'device', 'country', 'date')
            );
            $processedRows += $this->upsertRows($rows);
            sleep(1);
        }

        (new GscConfigRepository())->updateLastSync($this->idShop);

        return $processedRows;
    }

    /**
     * Upsert API rows into local storage.
     *
     * @param array<int, mixed> $rows Search Console API rows
     *
     * @return int Number of processed rows
     */
    private function upsertRows(array $rows): int
    {
        $processedRows = 0;

        foreach ($rows as $row) {
            if (!is_object($row) || !method_exists($row, 'getKeys')) {
                continue;
            }

            $keys = $row->getKeys();
            if (!is_array($keys)) {
                $keys = array();
            }

            $query = $this->truncate((string) ($keys[0] ?? ''), 499);
            $page = $this->truncate((string) ($keys[1] ?? ''), 999);
            $device = $this->normalizeDevice((string) ($keys[2] ?? 'ALL'));
            $country = $this->truncate(strtoupper((string) ($keys[3] ?? '')), 3);
            $date = $this->normalizeDate((string) ($keys[4] ?? date('Y-m-d')));
            $isAnonymized = $query === '' ? 1 : 0;

            $clicks = method_exists($row, 'getClicks') ? (int) $row->getClicks() : 0;
            $impressions = method_exists($row, 'getImpressions') ? (int) $row->getImpressions() : 0;
            $ctr = method_exists($row, 'getCtr') ? (float) $row->getCtr() : 0.0;
            $position = method_exists($row, 'getPosition') ? (float) $row->getPosition() : 0.0;

            Db::getInstance()->execute(
                'INSERT INTO `' . _DB_PREFIX_ . 'tec_gsc_data`
                (id_shop, data_date, query, page, device, country, clicks, impressions, ctr, position, is_anonymized)
                VALUES (
                    ' . (int) $this->idShop . ',
                    \'' . pSQL($date) . '\',
                    \'' . pSQL($query) . '\',
                    \'' . pSQL($page) . '\',
                    \'' . pSQL($device) . '\',
                    \'' . pSQL($country) . '\',
                    ' . (int) $clicks . ',
                    ' . (int) $impressions . ',
                    ' . (float) $ctr . ',
                    ' . (float) $position . ',
                    ' . (int) $isAnonymized . '
                )
                ON DUPLICATE KEY UPDATE
                    clicks = VALUES(clicks),
                    impressions = VALUES(impressions),
                    ctr = VALUES(ctr),
                    position = VALUES(position),
                    is_anonymized = VALUES(is_anonymized)'
            );
            ++$processedRows;
        }

        return $processedRows;
    }

    /**
     * Normalize a Search Console device value.
     *
     * @param string $device Device value
     *
     * @return string Normalized device
     */
    private function normalizeDevice(string $device): string
    {
        $device = strtoupper($device);
        $allowedDevices = array('DESKTOP', 'MOBILE', 'TABLET', 'ALL');

        return in_array($device, $allowedDevices, true) ? $device : 'ALL';
    }

    /**
     * Normalize a date value.
     *
     * @param string $date Date value
     *
     * @return string Date in YYYY-MM-DD format
     */
    private function normalizeDate(string $date): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d');
    }

    /**
     * Truncate a string using a multibyte-safe function when available.
     *
     * @param string $value Input value
     * @param int $length Maximum length
     *
     * @return string Truncated value
     */
    private function truncate(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length, 'UTF-8');
        }

        return substr($value, 0, $length);
    }
}
