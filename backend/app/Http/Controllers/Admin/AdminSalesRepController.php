<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AdminSalesRepController extends Controller
{
    private DashboardService $svc;

    public function __construct(DashboardService $svc)
    {
        $this->svc = $svc;
    }

    /** GET /api/admin/sales-reps/list — live from ERP GetSalespersons */
    public function list(): JsonResponse
    {
        $reps = collect($this->svc->erp()->getSalespersons(1, true))
            ->map(fn($p) => [
                'id'   => $p['id'],
                'name' => $p['name'],
            ])
            ->values();

        return response()->json(['status' => true, 'data' => $reps, 'source' => 'erp_api']);
    }

    /** GET /api/admin/sales-reps/{id} — {id} is ERP salesperson ID */
    public function show(int $id): JsonResponse
    {
        $person = $this->svc->erp()->findSalespersonById($id);

        if (!$person) {
            return response()->json(['status' => false, 'message' => 'Salesperson not found in ERP'], 404);
        }

        if (!$person['oid']) {
            return response()->json([
                'status'  => false,
                'message' => 'This salesperson has no OID in ERP — GetNetSales cannot be queried',
            ], 422);
        }

        $oid  = $person['oid'];
        $calc = $this->svc->getOidCurrentMonth($oid, null, null, $person['name'], (int) $person['id']);

        $erp = $this->svc->erp();
        $prefetch = $erp->buildDailySalesQueries([$oid], 15);
        $prefetch[] = [
            'oid'   => $oid,
            'start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end'   => Carbon::now()->format('Y-m-d'),
        ];
        for ($i = 7; $i >= 0; $i--) {
            $prefetch[] = [
                'oid'   => $oid,
                'start' => Carbon::now()->subWeeks($i)->startOfWeek(Carbon::SUNDAY)->format('Y-m-d'),
                'end'   => Carbon::now()->subWeeks($i)->endOfWeek(Carbon::SATURDAY)->format('Y-m-d'),
            ];
        }
        $erp->getTotalSalesValuesBatch($prefetch);

        $dailyRows    = [];
        $bestDaySales = 0;
        $daysAbove    = 0;
        for ($i = 9; $i >= 0; $i--) {
            $date    = Carbon::now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $sales   = 0;
            if (!$date->isFriday()) {
                $sales = $erp->getDailySalesTotal($dateStr, $oid);
            }
            $achievement = $calc['daily_target'] > 0
                ? round(($sales / $calc['daily_target']) * 100, 1) : 0;
            $commission = round(($sales * $calc['commission_pct']) / 100, 2);

            if ($sales > $bestDaySales) {
                $bestDaySales = $sales;
            }
            if ($calc['daily_target'] > 0 && $sales >= $calc['daily_target']) {
                $daysAbove++;
            }

            $dailyRows[] = [
                'date'         => $date->format('M d'),
                'sales'        => round($sales, 2),
                'daily_target' => $calc['daily_target'],
                'achievement'  => $achievement,
                'commission'   => $commission,
            ];
        }

        $weeklyLabels     = [];
        $weeklyCommission = [];
        for ($i = 7; $i >= 0; $i--) {
            $start  = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $end    = Carbon::now()->subWeeks($i)->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
            $wSales = $erp->getTotalSalesValue($start, $end, $oid);
            $weeklyLabels[]     = 'Week ' . Carbon::parse($start)->format('M d');
            $weeklyCommission[] = round(($wSales * $calc['commission_pct']) / 100, 2);
        }

        $dailyChart = $this->svc->getDailySales($oid, 15);

        return response()->json([
            'status' => true,
            'source' => 'erp_api',
            'data'   => [
                'rep' => [
                    'id'         => $person['id'],
                    'name'       => $person['name'],
                    'email'      => (string) $person['id'],
                    'oid'        => $person['oid'],
                    'supervisor' => $this->svc->localRules()->supervisorNameForRep($person['name']),
                ],
                'stats' => [
                    'total_sales'       => $calc['actual'],
                    'monthly_target'    => $calc['target'],
                    'total_forecast'    => $calc['forecast'],
                    'commission_earned' => $calc['commission_amount'],
                    'avg_daily_sales'   => $this->svc->avgDailySales($calc['actual']),
                    'achievement'       => $calc['achievement'],
                    'forecast_achievement' => $calc['achievement'],
                    'actual_achievement'   => $calc['actual_achievement'],
                ],
                'daily_performance' => $dailyRows,
                'insights' => [
                    'target_achievement'     => $calc['actual_achievement'],
                    'forecast_achievement'   => $calc['achievement'],
                    'best_day_sales'         => round($bestDaySales, 2),
                    'days_above_target'      => $daysAbove . ' / 10',
                    'projected_end_of_month' => round($calc['forecast'], 2),
                ],
                'charts' => [
                    'daily_performance' => [
                        'labels'       => array_column($dailyChart, 'date'),
                        'actual'       => array_column($dailyChart, 'sales'),
                        'daily_target' => array_fill(0, count($dailyChart), $calc['daily_target']),
                    ],
                    'commission_weekly' => [
                        'labels' => $weeklyLabels,
                        'data'   => $weeklyCommission,
                    ],
                ],
            ],
        ]);
    }
}
