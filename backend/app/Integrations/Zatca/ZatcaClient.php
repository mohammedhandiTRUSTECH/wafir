<?php

namespace App\Integrations\Zatca;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Carbon;

/**
 * Low-level HTTP client for the ZATCA ERP Integration API.
 *
 * This class is the single place that knows about ZATCA endpoints, URL
 * construction, and how to fire HTTP requests. All caching and domain logic
 * lives one layer up in ERPService.
 */
class ZatcaClient
{
    private Client $http;
    private string $baseUrl;
    private string $salesUrl;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('erp.base_url'), '/') . '/';
        $this->salesUrl = rtrim(config('erp.sales_per_month_url'), '/') . '/';

        $this->http = new Client([
            'timeout'         => 10,
            'connect_timeout' => 5,
            'http_errors'     => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // URL builders
    // -------------------------------------------------------------------------

    public function authUrl(): string
    {
        return $this->baseUrl . ltrim(config('erp.authentication_route'), '/');
    }

    public function usersUrl(int $companyId): string
    {
        return $this->baseUrl . config('erp.users_list_route') . $companyId;
    }

    /**
     * Build the GetNetSales URL for a salesperson over a date range.
     *
     * The ERP treats date-only ToDate as midnight (exclusive of that calendar
     * day). erpFromDate/erpToDate normalize to inclusive day bounds so that
     * GetNetSales results match TransactionDate as expected.
     */
    public function netSalesUrl(string $oid, string $startDate, string $endDate, int $company = 1): string
    {
        return $this->salesUrl
            . '?SalesPersonID=' . urlencode($oid)
            . '&FromDate=' . urlencode($this->erpFromDate($startDate))
            . '&ToDate=' . urlencode($this->erpToDate($endDate))
            . '&CompanyID=' . $company;
    }

    public function erpFromDate(string $date): string
    {
        return Carbon::parse($date)->format('Y-m-d') . 'T00:00:00';
    }

    public function erpToDate(string $date): string
    {
        return Carbon::parse($date)->format('Y-m-d') . 'T23:59:59';
    }

    // -------------------------------------------------------------------------
    // HTTP calls
    // -------------------------------------------------------------------------

    /**
     * POST to /Authentication/Authenticate and return raw response body string.
     * Returns ['success' => bool, 'token' => string] | ['success' => false, 'message' => string].
     */
    public function authenticate(): array
    {
        try {
            $resp = $this->http->post($this->authUrl(), [
                'json' => [
                    'userName' => config('erp.authentication_username'),
                    'password' => config('erp.authentication_password'),
                ],
            ]);

            return ['success' => true, 'token' => $resp->getBody()->getContents()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * GET GetSalespersons – returns raw decoded JSON array (stdClass objects).
     */
    public function fetchUsers(int $companyId): array
    {
        try {
            $resp = $this->http->get($this->usersUrl($companyId));

            return json_decode($resp->getBody()->getContents()) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * GET GetNetSales for a single salesperson / date range.
     * Returns ['success' => bool, 'data' => array] | ['success' => false, 'message' => string].
     */
    public function fetchNetSales(string $oid, string $startDate, string $endDate, int $company = 1): array
    {
        $url = $this->netSalesUrl($oid, $startDate, $endDate, $company);

        try {
            $resp = $this->http->get($url);

            return ['success' => true, 'data' => json_decode($resp->getBody()->getContents())];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Concurrent GET GetNetSales for many queries via a Guzzle Pool.
     *
     * @param array<string, array{oid:string,url:string}> $pending  keyed by cache key
     * @param int                                         $concurrency
     * @return array<string, float>  cache key => TotalSales value (0.0 on error)
     */
    public function fetchNetSalesBatch(array $pending, int $concurrency = 15): array
    {
        if (!$pending) {
            return [];
        }

        $results = [];

        $requests = function () use ($pending) {
            foreach ($pending as $cacheKey => $meta) {
                yield $cacheKey => new Request('GET', $meta['url']);
            }
        };

        $pool = new Pool($this->http, $requests(), [
            'concurrency' => $concurrency,
            'fulfilled'   => function ($response, $cacheKey) use (&$results) {
                $body  = json_decode($response->getBody()->getContents());
                $results[$cacheKey] = isset($body[0]->TotalSales) ? (float) $body[0]->TotalSales : 0.0;
            },
            'rejected'    => function ($reason, $cacheKey) use (&$results) {
                $results[$cacheKey] = null; // null signals a hard failure (short-TTL cache)
            },
        ]);

        $pool->promise()->wait();

        return $results;
    }
}
