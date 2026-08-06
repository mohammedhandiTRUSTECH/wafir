<?php

namespace App\Http\Services;

use App\Integrations\Zatca\ZatcaClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Domain service for ERP / ZATCA data.
 *
 * All raw HTTP calls are delegated to ZatcaClient; this class owns caching,
 * date math, and higher-level helpers consumed by controllers and jobs.
 */
class ERPService
{
    private ZatcaClient $zatca;
    private int $cacheMinutes = 10;
    private int $concurrency  = 15;

    public function __construct(?ZatcaClient $zatca = null)
    {
        $this->zatca = $zatca ?? new ZatcaClient();
    }

    // -------------------------------------------------------------------------
    // Cache key helpers
    // -------------------------------------------------------------------------

    private function salesCacheKey(string $oid, string $startDate, string $endDate, int $company = 1): string
    {
        return implode(':', ['erp-total-sales-v2', $company, $oid, $startDate, $endDate]);
    }

    // -------------------------------------------------------------------------
    // Date normalisation helpers (delegated to ZatcaClient but kept here as
    // pass-throughs so existing callers that call them on ERPService still work)
    // -------------------------------------------------------------------------

    public function erpFromDate(string $date): string
    {
        return $this->zatca->erpFromDate($date);
    }

    public function erpToDate(string $date): string
    {
        return $this->zatca->erpToDate($date);
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    public function getToken(): ?string
    {
        $token = DB::table('erp_tokens')->first();
        if ($token && $token->expires_at > now()->format('Y-m-d H:i')) {
            return $token->token;
        }

        $resp = $this->authenticate();
        if ($resp['success']) {
            DB::table('erp_tokens')->delete();
            $tokenObj = $this->decodeToken($resp['token']);
            if (isset($tokenObj->exp)) {
                $exp = Carbon::createFromTimestamp($tokenObj->exp)->addHours(2)->format('Y-m-d H:i');
                DB::table('erp_tokens')->insert([
                    'token'      => $resp['token'],
                    'expires_at' => $exp,
                ]);
            }

            return $resp['token'];
        }

        return null;
    }

    public function authenticate(): array
    {
        return $this->zatca->authenticate();
    }

    private function decodeToken(string $token): mixed
    {
        return json_decode(
            base64_decode(
                str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))
            )
        );
    }

    // -------------------------------------------------------------------------
    // Users (GetSalespersons)
    // -------------------------------------------------------------------------

    /**
     * Raw GetSalespersons call – returns decoded JSON array.
     */
    public function getUsers(int $companyId): array
    {
        return $this->zatca->fetchUsers($companyId);
    }

    /**
     * Normalised salespersons list with caching (5-minute TTL).
     *
     * @return array<int, array{id:int,name:string,oid:?string,whsid:?string}>
     */
    public function getSalespersons(int $companyId = 1, bool $onlyWithOid = false): array
    {
        $cacheKey = "erp-salespersons:{$companyId}:" . ($onlyWithOid ? 'oid' : 'all');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($companyId, $onlyWithOid) {
            $raw      = $this->getUsers($companyId);
            $out      = [];
            $seenOids = [];

            foreach ($raw as $user) {
                $oid   = isset($user->Oid) ? trim((string) $user->Oid) : '';
                $oid   = $oid !== '' ? $oid : null;
                $whsid = isset($user->WHSid) ? trim((string) $user->WHSid) : '';
                $whsid = $whsid !== '' ? $whsid : null;

                if ($onlyWithOid && !$oid) {
                    continue;
                }

                // Prefer the ERP row that has an OID when duplicates share a name
                if ($oid && isset($seenOids[$oid])) {
                    continue;
                }
                if ($oid) {
                    $seenOids[$oid] = true;
                }

                $out[] = [
                    'id'    => (int) $user->ID,
                    'name'  => trim((string) ($user->Name ?? '')),
                    'oid'   => $oid,
                    'whsid' => $whsid,
                ];
            }

            return $out;
        });
    }

    public function findSalespersonById(int $erpId, int $companyId = 1): ?array
    {
        foreach ($this->getSalespersons($companyId, false) as $person) {
            if ($person['id'] === $erpId) {
                return $person;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Sales (GetNetSales)
    // -------------------------------------------------------------------------

    /**
     * Single-request GetNetSales.
     * Signature kept identical to the original for backward compatibility.
     */
    public function totalSalesByDate(
        string $startDate,
        string $endDate,
        ?string $oid,
        int $company = 1,
        bool $onlyTotal = true
    ): array {
        return $this->zatca->fetchNetSales((string) $oid, $startDate, $endDate, $company);
    }

    /**
     * Cached MTD total for a single salesperson.
     */
    public function getTotalSalesValue(string $startDate, string $endDate, string $oid, int $company = 1): float
    {
        $cacheKey = $this->salesCacheKey($oid, $startDate, $endDate, $company);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($startDate, $endDate, $oid, $company) {
            $resp = $this->totalSalesByDate($startDate, $endDate, $oid, $company);

            if (!$resp['success'] || !isset($resp['data'][0]->TotalSales)) {
                return 0.0;
            }

            return (float) $resp['data'][0]->TotalSales;
        });
    }

    /**
     * Prefetch many MTD totals concurrently (writes to the same cache keys as
     * getTotalSalesValue so subsequent calls are served from cache).
     *
     * @param array<int, array{oid:string,start:string,end:string,company?:int}> $queries
     */
    public function getTotalSalesValuesBatch(array $queries): void
    {
        $pending = [];

        foreach ($queries as $q) {
            if (empty($q['oid'])) {
                continue;
            }
            $company  = (int) ($q['company'] ?? 1);
            $cacheKey = $this->salesCacheKey($q['oid'], $q['start'], $q['end'], $company);

            if (Cache::has($cacheKey) || isset($pending[$cacheKey])) {
                continue;
            }

            $pending[$cacheKey] = [
                'oid' => $q['oid'],
                'url' => $this->zatca->netSalesUrl($q['oid'], $q['start'], $q['end'], $company),
            ];
        }

        if (!$pending) {
            return;
        }

        $results = $this->zatca->fetchNetSalesBatch($pending, $this->concurrency);

        foreach ($results as $cacheKey => $value) {
            if ($value === null) {
                // Hard failure – short TTL to avoid stampede
                Cache::put($cacheKey, 0.0, now()->addSeconds(30));
            } else {
                Cache::put($cacheKey, $value, now()->addMinutes($this->cacheMinutes));
            }
        }
    }

    public function getDailySalesTotal(string $date, string $oid, int $company = 1): float
    {
        $cacheKey = implode(':', ['erp-daily-sales-v2', $company, $oid, $date]);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($date, $oid, $company) {
            $day        = Carbon::parse($date);
            $monthStart = $day->copy()->startOfMonth()->format('Y-m-d');
            $todayTotal = $this->getTotalSalesValue($monthStart, $day->format('Y-m-d'), $oid, $company);

            if ($day->isSameDay($day->copy()->startOfMonth())) {
                return round($todayTotal, 2);
            }

            $previousDay   = $day->copy()->subDay();
            $previousTotal = $this->getTotalSalesValue(
                $previousDay->copy()->startOfMonth()->format('Y-m-d'),
                $previousDay->format('Y-m-d'),
                $oid,
                $company
            );

            return round(max(0, $todayTotal - $previousTotal), 2);
        });
    }

    /**
     * Build MTD query list needed to derive daily totals for oids over the last N days.
     *
     * @param  array<int, string|null> $oids
     * @return array<int, array{oid:string,start:string,end:string}>
     */
    public function buildDailySalesQueries(array $oids, int $days, ?string $toDate = null): array
    {
        $ref  = $toDate ? Carbon::parse($toDate) : Carbon::now();
        $from = $ref->copy()->subDays($days - 1)->format('Y-m-d');

        return $this->buildDailySalesQueriesForRange($oids, $from, $ref->format('Y-m-d'));
    }

    /**
     * Build MTD query list needed to derive daily totals for oids over an inclusive range.
     *
     * @param  array<int, string|null> $oids
     * @return array<int, array{oid:string,start:string,end:string}>
     */
    public function buildDailySalesQueriesForRange(array $oids, string $fromDate, string $toDate): array
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $queries = [];

        foreach ($oids as $oid) {
            if (!$oid) {
                continue;
            }
            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                if ($date->isFriday()) {
                    continue;
                }
                $monthStart = $date->copy()->startOfMonth()->format('Y-m-d');
                $queries[]  = ['oid' => $oid, 'start' => $monthStart, 'end' => $date->format('Y-m-d')];

                if (!$date->isSameDay($date->copy()->startOfMonth())) {
                    $prev      = $date->copy()->subDay();
                    $queries[] = [
                        'oid'   => $oid,
                        'start' => $prev->copy()->startOfMonth()->format('Y-m-d'),
                        'end'   => $prev->format('Y-m-d'),
                    ];
                }
            }
        }

        return $queries;
    }

    // -------------------------------------------------------------------------
    // Legacy helpers kept for backward compatibility
    // -------------------------------------------------------------------------

    /**
     * @deprecated Use getTotalSalesValue() which caches the result.
     */
    public function salesPerMonthYear(string $oid, int $month, int $year, int $company = 1): array
    {
        $url = rtrim(config('erp.sales_per_month_url'), '/') . '/'
            . '?SalesPersonID=' . $oid
            . '&Month=' . $month
            . '&Year=' . $year
            . '&CompanyID=' . $company;

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10, 'connect_timeout' => 5]);
            $resp   = $client->get($url);

            return ['success' => true, 'data' => json_decode($resp->getBody()->getContents())];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
