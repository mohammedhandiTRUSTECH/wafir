<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    private DashboardService $svc;

    public function __construct(DashboardService $svc)
    {
        $this->svc = $svc;
    }

    /**
     * Main dashboard – salespeople + sales from ERP API (GetSalespersons / GetNetSales).
     * GET /api/admin/dashboard?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD
     */
    public function index(Request $request): JsonResponse
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->startOfDay();
        $asOf = Carbon::now()->startOfDay();
        $workingDays = $this->svc->workingDaysInRange($from, $to, $asOf);

        $erp       = $this->svc->erp();
        $salesReps = $erp->getSalespersons(1, true); // only people with OID (can fetch sales)

        $allOids = array_values(array_filter(array_column($salesReps, 'oid')));
        $chartEnd = $to->gt($asOf) ? $asOf->format('Y-m-d') : $toDate;
        $erp->getTotalSalesValuesBatch(
            $erp->buildDailySalesQueriesForRange($allOids, $fromDate, $chartEnd)
        );
        $this->svc->prefetchCurrentMonthTotals($allOids, $fromDate, $toDate);

        $repRows       = [];
        $totalSales    = 0;
        $totalForecast = 0;

        foreach ($salesReps as $person) {
            $calc = $this->svc->getOidCurrentMonth($person['oid'], $fromDate, $toDate, $person['name'], (int) $person['id']);
            $totalSales    += $calc['actual'];
            $totalForecast += $calc['forecast'];

            $repRows[] = [
                'id'          => $person['id'],
                'name'        => $person['name'],
                'email'       => (string) $person['id'],
                'supervisor'  => $this->svc->localRules()->supervisorNameForRep($person['name']),
                'target'      => $calc['target'],
                'actual'      => $calc['actual'],
                'forecast'    => $calc['forecast'],
                'achievement' => $calc['achievement'],
                'actual_achievement' => $calc['actual_achievement'],
                'commission_pct' => $calc['commission_pct'],
                'commission'  => $calc['commission_amount'],
                'success'     => $calc['achievement'] >= 100,
            ];
        }

        $teamPerf = collect($repRows)
            ->groupBy('supervisor')
            ->map(fn($rows, $supervisor) => [
                'name' => $supervisor,
                'reps' => count($rows),
                'sales' => round(collect($rows)->sum('actual'), 2),
                'target' => round(collect($rows)->sum('target'), 2),
                'percent' => collect($rows)->sum('target') > 0
                    ? round((collect($rows)->sum('forecast') / collect($rows)->sum('target')) * 100, 2)
                    : 0,
                'members' => collect($rows)->map(fn($r) => [
                    'id'     => $r['id'],
                    'name'   => $this->displayName($r['name']),
                    'sales'  => $r['actual'],
                    'target' => $r['target'],
                    'percent'=> $r['achievement'],
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $dailySales    = $this->svc->getAggregatedDailySalesByRange($allOids, $fromDate, $toDate);
        $periodTarget  = round(collect($repRows)->sum('target'), 2);
        // The target is a whole-month figure, so spread it over the months it belongs to
        // rather than over the (possibly partial) selected range.
        $targetDays     = $this->svc->workingDaysInCoveredMonths($from, $to, $asOf)['total'];
        $avgDailyTarget = $targetDays > 0
            ? round($periodTarget / $targetDays, 2)
            : 0.0;

        $commissionChart = collect($repRows)->map(fn($r) => [
            'name'       => $this->displayName($r['name']),
            'commission' => $r['commission'],
        ])->toArray();

        $avgDaily = $workingDays['gone'] > 0
            ? round($totalSales / $workingDays['gone'], 2)
            : 0.0;

        // Forecast achievement (projected) — used for commission / pace.
        $forecastAchievement = $periodTarget > 0
            ? round(($totalForecast / $periodTarget) * 100, 2)
            : 0.0;
        // Actual achievement — sales so far vs target.
        $actualAchievement = $periodTarget > 0
            ? round(($totalSales / $periodTarget) * 100, 2)
            : 0.0;

        return response()->json([
            'status' => true,
            'data'   => [
                'stats' => [
                    'total_sales'         => round($totalSales, 2),
                    'monthly_target'      => $periodTarget,
                    'daily_target'        => $avgDailyTarget,
                    // Keep `achievement` as forecast % for backward compatibility.
                    'achievement'         => $forecastAchievement,
                    'forecast_achievement'=> $forecastAchievement,
                    'actual_achievement'  => $actualAchievement,
                    'avg_daily_sales'     => $avgDaily,
                    'total_forecast'      => round($totalForecast, 2),
                    'working_days_total'  => $workingDays['total'],
                    'working_days_gone'   => $workingDays['gone'],
                    'working_days_auto'   => $workingDays['auto_total'],
                    'working_days_override' => $workingDays['is_override'],
                    'from_date'           => $fromDate,
                    'to_date'             => $toDate,
                ],
                'team_performance' => $teamPerf,
                'sales_reps'       => $repRows,
                'chart_sales_vs_target' => [
                    'labels'       => array_column($dailySales, 'date'),
                    'actual'       => array_column($dailySales, 'sales'),
                    'daily_target' => array_fill(0, count($dailySales), $avgDailyTarget),
                ],
                'chart_commission' => [
                    'labels' => array_column($commissionChart, 'name'),
                    'data'   => array_column($commissionChart, 'commission'),
                ],
                'source' => 'erp_api_with_local_rules',
            ],
        ]);
    }

    /**
     * First + last name for chart/list labels (falls back to full name when short).
     */
    private function displayName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) <= 1) {
            return $name;
        }
        if (count($parts) === 2) {
            return $parts[0] . ' ' . $parts[1];
        }

        return $parts[0] . ' ' . $parts[count($parts) - 1];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(Request $request): array
    {
        $fromDate = $request->get('from_date');
        $toDate   = $request->get('to_date');

        if ($fromDate && $toDate) {
            try {
                $from = Carbon::parse($fromDate)->startOfDay();
                $to   = Carbon::parse($toDate)->startOfDay();
                if ($from->gt($to)) {
                    [$from, $to] = [$to->copy(), $from->copy()];
                }

                return [$from->format('Y-m-d'), $to->format('Y-m-d')];
            } catch (\Throwable) {
                // fall through to default
            }
        }

        $now = Carbon::now();

        return [
            $now->copy()->startOfMonth()->format('Y-m-d'),
            $now->copy()->endOfMonth()->format('Y-m-d'),
        ];
    }

    /**
     * Get saved / auto working days for a calendar month.
     * GET /api/admin/dashboard/working-days?year=2026&month=7
     */
    public function getWorkingDays(Request $request): JsonResponse
    {
        $year  = (int) $request->get('year', Carbon::now()->year);
        $month = (int) $request->get('month', Carbon::now()->month);

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return response()->json(['status' => false, 'message' => 'Invalid year or month'], 422);
        }

        $ref  = Carbon::create($year, $month, 1)->startOfDay();
        $auto = $this->svc->autoWorkingDaysInRange(
            $ref->copy()->startOfMonth(),
            $ref->copy()->endOfMonth(),
            Carbon::now()
        );
        $override = $this->svc->getWorkingDaysOverride($year, $month);
        $resolved = $this->svc->workingDaysInMonth($ref);

        return response()->json([
            'status' => true,
            'data'   => [
                'year'          => $year,
                'month'         => $month,
                'working_days'  => $resolved['total'],
                'working_days_gone' => $resolved['gone'],
                'auto_total'    => $auto['total'],
                'is_override'   => $override !== null,
                'override'      => $override,
            ],
        ]);
    }

    /**
     * Save working days for a calendar month.
     * PUT /api/admin/dashboard/working-days  { year, month, working_days }
     */
    public function updateWorkingDays(Request $request): JsonResponse
    {
        $year  = (int) $request->input('year');
        $month = (int) $request->input('month');
        $days  = (int) $request->input('working_days');

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return response()->json(['status' => false, 'message' => 'Invalid year or month'], 422);
        }
        if ($days < 1 || $days > 31) {
            return response()->json(['status' => false, 'message' => 'working_days must be between 1 and 31'], 422);
        }

        $row = $this->svc->setWorkingDaysOverride($year, $month, $days);
        $ref = Carbon::create($year, $month, 1);
        $resolved = $this->svc->workingDaysInMonth($ref);

        return response()->json([
            'status'  => true,
            'message' => 'Working days saved',
            'data'    => [
                'year'          => (int) $row->year,
                'month'         => (int) $row->month,
                'working_days'  => (int) $row->working_days,
                'working_days_gone' => $resolved['gone'],
                'is_override'   => true,
            ],
        ]);
    }

    /**
     * Clear saved working days and fall back to auto (Fridays excluded).
     * DELETE /api/admin/dashboard/working-days?year=&month=
     */
    public function clearWorkingDays(Request $request): JsonResponse
    {
        $year  = (int) $request->get('year', Carbon::now()->year);
        $month = (int) $request->get('month', Carbon::now()->month);

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return response()->json(['status' => false, 'message' => 'Invalid year or month'], 422);
        }

        $this->svc->clearWorkingDaysOverride($year, $month);
        $ref = Carbon::create($year, $month, 1);
        $resolved = $this->svc->workingDaysInMonth($ref);

        return response()->json([
            'status'  => true,
            'message' => 'Working days reset to auto',
            'data'    => [
                'year'         => $year,
                'month'        => $month,
                'working_days' => $resolved['total'],
                'working_days_gone' => $resolved['gone'],
                'is_override'  => false,
            ],
        ]);
    }

    /**
     * Overview stats from ERP GetSalespersons.
     * GET /api/admin/dashboard/overview
     */
    public function overview(): JsonResponse
    {
        $all       = $this->svc->erp()->getSalespersons(1, false);
        $withOid   = array_values(array_filter($all, fn($p) => !empty($p['oid'])));
        $withoutOid = count($all) - count($withOid);

        return response()->json([
            'status' => true,
            'data'   => [
                'total_users'      => count($all),
                'active_users'     => count($withOid),
                'inactive_users'   => $withoutOid,
                'total_targets'    => count($this->svc->localRules()->repRows()),
                'assigned_targets' => count($this->svc->localRules()->repRows()),
                'users_by_role'    => [
                    ['role' => 'With OID (sales fetchable)', 'count' => count($withOid)],
                    ['role' => 'Without OID', 'count' => $withoutOid],
                ],
                'source' => 'erp_api_with_local_rules',
            ],
        ]);
    }
}
