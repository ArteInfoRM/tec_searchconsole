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
 * Handles Search Console data retention and cleanup.
 */
class GscDataRetention
{
    /**
     * Disable automatic cleanup.
     */
    public const RETENTION_DISABLED = 0;

    /**
     * Default Search Console data retention in months.
     */
    public const DEFAULT_DATA_RETENTION_MONTHS = 16;

    /**
     * Default alert retention in days.
     */
    public const DEFAULT_ALERT_RETENTION_DAYS = 180;

    /**
     * Allowed Search Console data retention values.
     *
     * @var int[]
     */
    private $dataRetentionOptions = [0, 3, 6, 12, 16];

    /**
     * Allowed alert retention values.
     *
     * @var int[]
     */
    private $alertRetentionOptions = [0, 90, 180, 365];

    /**
     * Normalize a Search Console data retention value.
     *
     * @param int $months Submitted retention in months
     *
     * @return int Normalized retention in months
     */
    public function normalizeDataRetentionMonths(int $months): int
    {
        return in_array($months, $this->dataRetentionOptions, true)
            ? $months
            : self::DEFAULT_DATA_RETENTION_MONTHS;
    }

    /**
     * Normalize an alert retention value.
     *
     * @param int $days Submitted retention in days
     *
     * @return int Normalized retention in days
     */
    public function normalizeAlertRetentionDays(int $days): int
    {
        return in_array($days, $this->alertRetentionOptions, true)
            ? $days
            : self::DEFAULT_ALERT_RETENTION_DAYS;
    }

    /**
     * Get allowed Search Console data retention values.
     *
     * @return int[] Retention values in months
     */
    public function getDataRetentionOptions(): array
    {
        return $this->dataRetentionOptions;
    }

    /**
     * Get allowed alert retention values.
     *
     * @return int[] Retention values in days
     */
    public function getAlertRetentionOptions(): array
    {
        return $this->alertRetentionOptions;
    }

    /**
     * Remove Search Console data older than the selected retention.
     *
     * @param int $idShop Shop identifier
     * @param int $months Retention in months, 0 disables cleanup
     *
     * @return int Number of rows selected for deletion
     */
    public function cleanupData(int $idShop, int $months): int
    {
        $months = $this->normalizeDataRetentionMonths($months);
        if ($idShop <= 0 || $months === self::RETENTION_DISABLED) {
            return 0;
        }

        $cutoffDate = date('Y-m-d', strtotime('-' . $months . ' months'));
        $count = $this->countOldDataRows($idShop, $cutoffDate);
        if ($count <= 0) {
            return 0;
        }

        $deleted = Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE id_shop = ' . (int) $idShop . '
                AND data_date < \'' . pSQL($cutoffDate) . '\''
        );
        if (!$deleted) {
            throw new \RuntimeException('Unable to clean old Search Console data rows.');
        }

        return $count;
    }

    /**
     * Remove alerts older than the selected retention.
     *
     * @param int $idShop Shop identifier
     * @param int $days Retention in days, 0 disables cleanup
     *
     * @return int Number of rows selected for deletion
     */
    public function cleanupAlerts(int $idShop, int $days): int
    {
        $days = $this->normalizeAlertRetentionDays($days);
        if ($idShop <= 0 || $days === self::RETENTION_DISABLED) {
            return 0;
        }

        $cutoffDateTime = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        $count = $this->countOldAlertRows($idShop, $cutoffDateTime);
        if ($count <= 0) {
            return 0;
        }

        $deleted = Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'tec_gsc_alerts`
            WHERE id_shop = ' . (int) $idShop . '
                AND date_add < \'' . pSQL($cutoffDateTime) . '\''
        );
        if (!$deleted) {
            throw new \RuntimeException('Unable to clean old Search Console alert rows.');
        }

        return $count;
    }

    /**
     * Get storage statistics for the current shop.
     *
     * @param int $idShop Shop identifier
     * @param int $dataRetentionMonths Retention in months
     * @param int $alertRetentionDays Retention in days
     *
     * @return array<string, mixed> Storage statistics
     */
    public function getStats(int $idShop, int $dataRetentionMonths, int $alertRetentionDays): array
    {
        $dataRetentionMonths = $this->normalizeDataRetentionMonths($dataRetentionMonths);
        $alertRetentionDays = $this->normalizeAlertRetentionDays($alertRetentionDays);
        $dataCutoffDate = $dataRetentionMonths === self::RETENTION_DISABLED
            ? ''
            : date('Y-m-d', strtotime('-' . $dataRetentionMonths . ' months'));
        $alertCutoffDateTime = $alertRetentionDays === self::RETENTION_DISABLED
            ? ''
            : date('Y-m-d H:i:s', strtotime('-' . $alertRetentionDays . ' days'));

        $dataRow = Db::getInstance()->getRow(
            'SELECT COUNT(*) AS total_rows, MIN(data_date) AS oldest_date, MAX(data_date) AS newest_date
            FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE id_shop = ' . (int) $idShop
        );
        $alertRow = Db::getInstance()->getRow(
            'SELECT COUNT(*) AS total_rows, MIN(date_add) AS oldest_date, MAX(date_add) AS newest_date
            FROM `' . _DB_PREFIX_ . 'tec_gsc_alerts`
            WHERE id_shop = ' . (int) $idShop
        );

        return [
            'data_total_rows' => isset($dataRow['total_rows']) ? (int) $dataRow['total_rows'] : 0,
            'data_oldest_date' => isset($dataRow['oldest_date']) ? (string) $dataRow['oldest_date'] : '',
            'data_newest_date' => isset($dataRow['newest_date']) ? (string) $dataRow['newest_date'] : '',
            'data_deletable_rows' => $dataCutoffDate === '' ? 0 : $this->countOldDataRows($idShop, $dataCutoffDate),
            'data_cutoff_date' => $dataCutoffDate,
            'alert_total_rows' => isset($alertRow['total_rows']) ? (int) $alertRow['total_rows'] : 0,
            'alert_oldest_date' => isset($alertRow['oldest_date']) ? (string) $alertRow['oldest_date'] : '',
            'alert_newest_date' => isset($alertRow['newest_date']) ? (string) $alertRow['newest_date'] : '',
            'alert_deletable_rows' => $alertCutoffDateTime === '' ? 0 : $this->countOldAlertRows($idShop, $alertCutoffDateTime),
            'alert_cutoff_date' => $alertCutoffDateTime,
        ];
    }

    /**
     * Count old Search Console data rows.
     *
     * @param int $idShop Shop identifier
     * @param string $cutoffDate Cutoff date in YYYY-MM-DD format
     *
     * @return int Number of rows older than the cutoff
     */
    private function countOldDataRows(int $idShop, string $cutoffDate): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE id_shop = ' . (int) $idShop . '
                AND data_date < \'' . pSQL($cutoffDate) . '\''
        );
    }

    /**
     * Count old alert rows.
     *
     * @param int $idShop Shop identifier
     * @param string $cutoffDateTime Cutoff date and time
     *
     * @return int Number of rows older than the cutoff
     */
    private function countOldAlertRows(int $idShop, string $cutoffDateTime): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'tec_gsc_alerts`
            WHERE id_shop = ' . (int) $idShop . '
                AND date_add < \'' . pSQL($cutoffDateTime) . '\''
        );
    }
}
