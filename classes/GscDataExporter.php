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
use DOMDocument;
use Link;
use Product;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Exports locally stored Search Console rows.
 */
class GscDataExporter
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
     * Get supported export formats.
     *
     * @return array<string, string> Format labels indexed by value
     */
    public function getFormats(): array
    {
        return [
            'json' => 'JSON',
            'csv' => 'CSV',
            'xml' => 'XML',
        ];
    }

    /**
     * Get supported export periods.
     *
     * @return array<string, string> Period labels indexed by value
     */
    public function getPeriods(): array
    {
        return [
            '24h' => 'Last 24 hours',
            '7d' => 'Last 7 days',
            '28d' => 'Last 28 days',
            '3m' => 'Last 3 months',
            '6m' => 'Last 6 months',
            '12m' => 'Last 12 months',
            '16m' => 'Last 16 months',
            'all' => 'All data',
        ];
    }

    /**
     * Normalize a submitted export format.
     *
     * @param string $format Submitted format
     *
     * @return string Normalized format
     */
    public function normalizeFormat(string $format): string
    {
        $format = strtolower(trim($format));
        if ($format === 'css') {
            $format = 'csv';
        }

        return array_key_exists($format, $this->getFormats()) ? $format : '';
    }

    /**
     * Normalize a submitted export period.
     *
     * @param string $period Submitted period
     *
     * @return string Normalized period
     */
    public function normalizePeriod(string $period): string
    {
        $period = strtolower(trim($period));

        return array_key_exists($period, $this->getPeriods()) ? $period : '';
    }

    /**
     * Build export filename.
     *
     * @param string $format Export format
     * @param string $period Export period
     * @param int $idProduct Product identifier, 0 for global export
     *
     * @return string Filename
     */
    public function getFilename(string $format, string $period, int $idProduct = 0): string
    {
        $scope = $idProduct > 0 ? 'product-' . (int) $idProduct : 'all';

        return 'tec-searchconsole-' . $scope . '-' . $period . '-' . date('Ymd-His') . '.' . $format;
    }

    /**
     * Get the MIME type for a format.
     *
     * @param string $format Export format
     *
     * @return string MIME type
     */
    public function getContentType(string $format): string
    {
        if ($format === 'json') {
            return 'application/json; charset=utf-8';
        }

        if ($format === 'xml') {
            return 'application/xml; charset=utf-8';
        }

        return 'text/csv; charset=utf-8';
    }

    /**
     * Load stored rows for the requested export.
     *
     * @param string $period Export period
     * @param int $idProduct Product identifier, 0 for global export
     *
     * @return array<int, array<string, mixed>> Export rows
     */
    public function getRows(string $period, int $idProduct = 0): array
    {
        $period = $this->normalizePeriod($period);
        if ($period === '') {
            return [];
        }

        $conditions = ['id_shop = ' . (int) $this->idShop];
        $startDate = $this->getPeriodStartDate($period);
        if ($startDate !== '') {
            $conditions[] = 'data_date >= \'' . pSQL($startDate) . '\'';
        }

        if ($idProduct > 0) {
            $pagePath = $this->getProductPagePath($idProduct);
            if ($pagePath === '') {
                return [];
            }

            $conditions[] = 'page LIKE \'%' . pSQL($this->escapeLikeValue($pagePath)) . '%\' ESCAPE \'\\\\\'';
        }

        $rows = Db::getInstance()->executeS(
            'SELECT
                data_date,
                query,
                page,
                device,
                country,
                clicks,
                impressions,
                ctr,
                position,
                is_anonymized
            FROM `' . _DB_PREFIX_ . 'tec_gsc_data`
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY data_date DESC, clicks DESC, impressions DESC, id DESC'
        );

        return is_array($rows) ? $this->normalizeRows($rows) : [];
    }

    /**
     * Render export rows in the requested format.
     *
     * @param array<int, array<string, mixed>> $rows Export rows
     * @param string $format Export format
     * @param string $period Export period
     * @param int $idProduct Product identifier, 0 for global export
     *
     * @return string Export content
     */
    public function render(array $rows, string $format, string $period, int $idProduct = 0): string
    {
        if ($format === 'json') {
            return $this->renderJson($rows, $period, $idProduct);
        }

        if ($format === 'xml') {
            return $this->renderXml($rows, $period, $idProduct);
        }

        return $this->renderCsv($rows);
    }

    /**
     * Get the start date for a period.
     *
     * @param string $period Export period
     *
     * @return string Start date in YYYY-MM-DD format or empty for all data
     */
    private function getPeriodStartDate(string $period): string
    {
        $modifiers = [
            '24h' => '-1 day',
            '7d' => '-7 days',
            '28d' => '-28 days',
            '3m' => '-3 months',
            '6m' => '-6 months',
            '12m' => '-12 months',
            '16m' => '-16 months',
        ];

        return isset($modifiers[$period]) ? date('Y-m-d', strtotime($modifiers[$period])) : '';
    }

    /**
     * Normalize database values for export.
     *
     * @param array<int, array<string, mixed>> $rows Database rows
     *
     * @return array<int, array<string, mixed>> Normalized rows
     */
    private function normalizeRows(array $rows): array
    {
        $normalizedRows = [];
        foreach ($rows as $row) {
            $normalizedRows[] = [
                'date' => isset($row['data_date']) ? (string) $row['data_date'] : '',
                'query' => isset($row['query']) ? (string) $row['query'] : '',
                'page' => isset($row['page']) ? (string) $row['page'] : '',
                'device' => isset($row['device']) ? (string) $row['device'] : '',
                'country' => isset($row['country']) ? (string) $row['country'] : '',
                'clicks' => isset($row['clicks']) ? (int) $row['clicks'] : 0,
                'impressions' => isset($row['impressions']) ? (int) $row['impressions'] : 0,
                'ctr' => isset($row['ctr']) ? (float) $row['ctr'] : 0.0,
                'position' => isset($row['position']) ? (float) $row['position'] : 0.0,
                'is_anonymized' => !empty($row['is_anonymized']) ? 1 : 0,
            ];
        }

        return $normalizedRows;
    }

    /**
     * Render rows as JSON.
     *
     * @param array<int, array<string, mixed>> $rows Export rows
     * @param string $period Export period
     * @param int $idProduct Product identifier, 0 for global export
     *
     * @return string JSON content
     */
    private function renderJson(array $rows, string $period, int $idProduct): string
    {
        $payload = [
            'meta' => [
                'module' => 'tec_searchconsole',
                'scope' => $idProduct > 0 ? 'product' : 'global',
                'id_product' => $idProduct,
                'period' => $period,
                'generated_at' => date('c'),
                'row_count' => count($rows),
            ],
            'rows' => $rows,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($json) ? $json . "\n" : "{}\n";
    }

    /**
     * Render rows as XML.
     *
     * @param array<int, array<string, mixed>> $rows Export rows
     * @param string $period Export period
     * @param int $idProduct Product identifier, 0 for global export
     *
     * @return string XML content
     */
    private function renderXml(array $rows, string $period, int $idProduct): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement('search_console_export');
        $root->setAttribute('module', 'tec_searchconsole');
        $root->setAttribute('scope', $idProduct > 0 ? 'product' : 'global');
        $root->setAttribute('id_product', (string) $idProduct);
        $root->setAttribute('period', $period);
        $root->setAttribute('generated_at', date('c'));
        $root->setAttribute('row_count', (string) count($rows));
        $document->appendChild($root);

        foreach ($rows as $row) {
            $rowNode = $document->createElement('row');
            foreach ($row as $field => $value) {
                $node = $document->createElement((string) $field);
                $node->appendChild($document->createTextNode((string) $value));
                $rowNode->appendChild($node);
            }
            $root->appendChild($rowNode);
        }

        $xml = $document->saveXML();

        return is_string($xml) ? $xml : '';
    }

    /**
     * Render rows as CSV.
     *
     * @param array<int, array<string, mixed>> $rows Export rows
     *
     * @return string CSV content
     */
    private function renderCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        if (!is_resource($handle)) {
            return '';
        }

        $headers = ['date', 'query', 'page', 'device', 'country', 'clicks', 'impressions', 'ctr', 'position', 'is_anonymized'];
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $this->sanitizeCsvValue(isset($row[$header]) ? $row[$header] : '');
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return is_string($content) ? $content : '';
    }

    /**
     * Escape a value used inside a SQL LIKE expression.
     *
     * @param string $value Raw value
     *
     * @return string Escaped value
     */
    private function escapeLikeValue(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }

    /**
     * Sanitize values that could be interpreted as spreadsheet formulas.
     *
     * @param mixed $value CSV cell value
     *
     * @return mixed Sanitized CSV cell value
     */
    private function sanitizeCsvValue($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? '\'' . $value : $value;
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

        $pageUrl = $this->link->getProductLink($product, null, null, null, $this->idLang, $this->idShop);
        $path = parse_url($pageUrl, PHP_URL_PATH);

        return is_string($path) ? rtrim($path, '/') : '';
    }
}
