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
 * Generates SEO alerts from stored Search Console data.
 */
class GscAlertEngine
{
    /**
     * @var int Shop identifier
     */
    private $idShop;

    /**
     * @var float Position drop threshold
     */
    private $positionDropThreshold = 3.0;

    /**
     * @var float CTR drop threshold percentage
     */
    private $ctrDropPctThreshold = 20.0;

    /**
     * @param int $idShop Shop identifier
     */
    public function __construct(int $idShop)
    {
        $this->idShop = $idShop;
    }

    /**
     * Compare recent periods and generate alerts.
     *
     * @return int Number of generated alerts
     */
    public function analyzeAndGenerateAlerts(): int
    {
        $period2End = date('Y-m-d', strtotime('-2 days'));
        $period2Start = date('Y-m-d', strtotime('-8 days'));
        $period1End = date('Y-m-d', strtotime('-9 days'));
        $period1Start = date('Y-m-d', strtotime('-15 days'));

        $rows = Db::getInstance()->executeS(
            'SELECT
                p.page,
                p.position AS pos_prev,
                p.ctr AS ctr_prev,
                c.position AS pos_curr,
                c.ctr AS ctr_curr
            FROM (
                SELECT page, AVG(position) AS position, AVG(ctr) AS ctr
                FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
                WHERE id_shop = ' . (int) $this->idShop . '
                    AND data_date BETWEEN \'' . pSQL($period1Start) . '\' AND \'' . pSQL($period1End) . '\'
                    AND query IS NOT NULL
                    AND query != \'\'
                GROUP BY page
            ) p
            INNER JOIN (
                SELECT page, AVG(position) AS position, AVG(ctr) AS ctr
                FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
                WHERE id_shop = ' . (int) $this->idShop . '
                    AND data_date BETWEEN \'' . pSQL($period2Start) . '\' AND \'' . pSQL($period2End) . '\'
                    AND query IS NOT NULL
                    AND query != \'\'
                GROUP BY page
            ) c ON p.page = c.page'
        );

        if (!is_array($rows)) {
            return 0;
        }

        $createdAlerts = 0;
        foreach ($rows as $row) {
            $positionBefore = (float) $row['pos_prev'];
            $positionAfter = (float) $row['pos_curr'];
            $positionDelta = $positionAfter - $positionBefore;
            if ($positionDelta >= $this->positionDropThreshold) {
                $createdAlerts += $this->createAlert(
                    'POSITION_DROP',
                    (string) $row['page'],
                    '',
                    $positionBefore,
                    $positionAfter,
                    ($positionDelta / max($positionBefore, 0.1)) * 100
                );
            }

            $ctrBefore = (float) $row['ctr_prev'];
            $ctrAfter = (float) $row['ctr_curr'];
            if ($ctrBefore > 0) {
                $ctrDrop = (($ctrBefore - $ctrAfter) / $ctrBefore) * 100;
                if ($ctrDrop >= $this->ctrDropPctThreshold) {
                    $createdAlerts += $this->createAlert('CTR_DROP', (string) $row['page'], '', $ctrBefore, $ctrAfter, $ctrDrop);
                }
            }
        }

        return $createdAlerts;
    }

    /**
     * Create an alert if it does not exist in the last 24 hours.
     *
     * @param string $type Alert type
     * @param string $page Page URL
     * @param string $query Query
     * @param float $before Previous value
     * @param float $after Current value
     * @param float $deltaPct Delta percentage
     *
     * @return int One when created, zero when skipped
     */
    private function createAlert(string $type, string $page, string $query, float $before, float $after, float $deltaPct): int
    {
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $exists = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'tec_gsc_alerts`
            WHERE id_shop = ' . (int) $this->idShop . '
                AND alert_type = \'' . pSQL($type) . '\'
                AND page = \'' . pSQL($page) . '\'
                AND date_add > \'' . pSQL($since) . '\''
        );

        if ($exists > 0) {
            return 0;
        }

        Db::getInstance()->insert('tec_gsc_alerts', [
            'id_shop' => (int) $this->idShop,
            'alert_type' => pSQL($type),
            'page' => pSQL($page),
            'query' => pSQL($query),
            'value_before' => (float) $before,
            'value_after' => (float) $after,
            'delta_pct' => (float) $deltaPct,
            'is_read' => 0,
            'date_add' => date('Y-m-d H:i:s'),
        ]);

        return 1;
    }
}
