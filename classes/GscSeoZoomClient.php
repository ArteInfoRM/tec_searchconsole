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
 * Minimal SEOZoom API v2 client.
 */
class GscSeoZoomClient
{
    private const DOMAINS_ENDPOINT = 'https://apiv2.seozoom.com/api/v2/domains/';
    private const KEYWORDS_ENDPOINT = 'https://apiv2.seozoom.com/api/v2/keywords/';

    /**
     * Load domain metrics from SEOZoom.
     *
     * @param string $apiKey SEOZoom API key
     * @param string $db SEOZoom database code
     * @param string $domain Domain to analyze
     *
     * @return array<string, mixed> Normalized metrics with the raw row
     *
     * @throws \Exception When the API call fails
     */
    public function getDomainMetrics(string $apiKey, string $db, string $domain): array
    {
        if ($apiKey === '' || $domain === '') {
            throw new \Exception('SEOZoom API key and domain are required.');
        }

        $url = self::DOMAINS_ENDPOINT . '?' . http_build_query([
            'api_key' => $apiKey,
            'action' => 'metrics',
            'domain' => $domain,
            'db' => $db,
        ], '', '&');

        $response = $this->request($url);
        $payload = json_decode($response, true);
        if (!is_array($payload)) {
            throw new \Exception('SEOZoom returned an invalid response.');
        }

        if (!empty($payload['error'])) {
            throw new \Exception('SEOZoom error: ' . (string) $payload['error']);
        }

        if (empty($payload['response'][0]) || !is_array($payload['response'][0])) {
            throw new \Exception('SEOZoom did not return domain metrics.');
        }

        $row = $payload['response'][0];

        return [
            'domain' => isset($row['domain']) ? (string) $row['domain'] : $domain,
            'main_domain' => isset($row['maindomain']) ? (string) $row['maindomain'] : $domain,
            'zoom_authority' => isset($row['za']) ? (float) $row['za'] : null,
            'zoom_trust' => isset($row['trust']) ? (float) $row['trust'] : null,
            'organic_traffic' => isset($row['extimated_traffic']) ? (int) $row['extimated_traffic'] : 0,
            'organic_keywords' => isset($row['total_keywords']) ? (int) $row['total_keywords'] : 0,
            'units_used' => isset($payload['UnitsUsed']) ? (int) $payload['UnitsUsed'] : 0,
            'units_remaining' => isset($payload['UnitsRemaining']) ? (int) $payload['UnitsRemaining'] : null,
            'raw_payload' => json_encode($row),
        ];
    }

    /**
     * Load keyword metrics from SEOZoom.
     *
     * @param string $apiKey SEOZoom API key
     * @param string $db SEOZoom database code
     * @param array<int, string> $keywords Keywords to analyze
     *
     * @return array<string, array<string, mixed>> Metrics indexed by normalized keyword
     *
     * @throws \Exception When the API call fails
     */
    public function getKeywordMetrics(string $apiKey, string $db, array $keywords): array
    {
        $keywords = $this->normalizeKeywords($keywords);
        if ($apiKey === '' || empty($keywords)) {
            return [];
        }

        $encodedKeywords = array_map('rawurlencode', array_slice($keywords, 0, 100));
        $url = self::KEYWORDS_ENDPOINT . '?' . http_build_query([
            'api_key' => $apiKey,
            'action' => 'metrics',
            'db' => $db,
        ], '', '&') . '&keyword=' . implode('|', $encodedKeywords);

        $response = $this->request($url);
        $payload = json_decode($response, true);
        if (!is_array($payload)) {
            throw new \Exception('SEOZoom returned an invalid response.');
        }

        if (!empty($payload['error'])) {
            throw new \Exception('SEOZoom error: ' . (string) $payload['error']);
        }

        if (empty($payload['response']) || !is_array($payload['response'])) {
            return [];
        }

        $metrics = [];
        foreach ($payload['response'] as $row) {
            if (!is_array($row) || empty($row['keyword'])) {
                continue;
            }

            $keyword = $this->normalizeKeyword((string) $row['keyword']);
            $metrics[$keyword] = [
                'keyword' => (string) $row['keyword'],
                'search_volume' => isset($row['search_volume']) ? (int) $row['search_volume'] : 0,
                'units_used' => isset($payload['UnitsUsed']) ? (int) $payload['UnitsUsed'] : 0,
                'units_remaining' => isset($payload['UnitsRemaining']) ? (int) $payload['UnitsRemaining'] : null,
                'raw_payload' => json_encode($row),
            ];
        }

        return $metrics;
    }

    /**
     * Execute a GET request.
     *
     * @param string $url Request URL
     *
     * @return string Response body
     *
     * @throws \Exception When the HTTP request fails
     */
    private function request(string $url): string
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new \Exception('Unable to initialize the SEOZoom API request.');
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_USERAGENT, 'tec_searchconsole/1.0.4');

        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            throw new \Exception('SEOZoom API request failed. ' . $error);
        }

        return (string) $response;
    }

    /**
     * Normalize keywords and remove duplicates.
     *
     * @param array<int, string> $keywords Submitted keywords
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
