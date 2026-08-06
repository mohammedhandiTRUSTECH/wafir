<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class AdminAreaManagerController extends Controller
{
    private DashboardService $svc;

    public function __construct(DashboardService $svc)
    {
        $this->svc = $svc;
    }

    /** GET /api/admin/area-managers/list — ERP has no AM role; org-wide view */
    public function list(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'source' => 'erp_api',
            'data'   => [
                ['id' => 1, 'name' => 'Organization (ERP)'],
            ],
        ]);
    }

    /** GET /api/admin/area-managers/{id} — org-wide ERP sales */
    public function show(int $id): JsonResponse
    {
        $metrics     = $this->svc->getErpOrgRepMetrics();
        $repRows     = $metrics['reps'];
        $repsByName  = collect($repRows)->keyBy('name');

        $totalSales      = $metrics['total_sales'];
        $totalForecast   = $metrics['total_forecast'];
        $totalTarget     = $metrics['total_target'];
        $totalCommission = $metrics['total_commission'];
        $achievement     = $metrics['achievement'];
        $actualAchievement = $metrics['actual_achievement'];
        $avgDaily        = $this->svc->avgDailySales($totalSales);
        $dailyTarget     = $this->svc->monthlyDailyTarget($totalTarget);

        $supRows = collect($this->svc->localRules()->supervisorGroups())
            ->map(function ($group) use ($repsByName) {
                $sales = 0.0;
                $forecast = 0.0;
                $commission = 0.0;
                $matched = 0;

                foreach ($group['members'] as $member) {
                    $rep = $repsByName->get($member['name']);
                    if (!$rep) {
                        continue;
                    }
                    $matched++;
                    $sales += $rep['sales'];
                    $forecast += $rep['forecast'];
                    $commission += $rep['commission'];
                }

                $target = (float) $group['target'];

                return [
                    'supervisor'   => $group['name'],
                    'area_manager' => 'Organization (ERP)',
                    'team_size'    => $matched ?: count($group['members']),
                    'target'       => round($target, 2),
                    'sales'        => round($sales, 2),
                    'forecast'     => round($forecast, 2),
                    'commission'   => round($commission, 2),
                    'achievement'  => $target > 0 ? round(($forecast / $target) * 100, 2) : 0.0,
                ];
            })
            ->values()
            ->all();

        if (!$supRows) {
            $supRows = [[
                'supervisor'   => 'All Reps (ERP)',
                'area_manager' => 'Organization (ERP)',
                'team_size'    => count($repRows),
                'target'       => $totalTarget,
                'sales'        => $totalSales,
                'forecast'     => $totalForecast,
                'commission'   => $totalCommission,
                'achievement'  => $achievement,
            ]];
        }

        $dailySales = $this->svc->getAggregatedDailySales($metrics['oids'], 15);

        return response()->json([
            'status' => true,
            'source' => 'erp_api_with_local_rules',
            'data'   => [
                'manager' => [
                    'id'         => 1,
                    'name'       => 'Organization (ERP)',
                    'email'      => 'GetSalespersons + GetNetSales',
                    'reports_to' => '—',
                ],
                'stats' => [
                    'total_sales'       => $totalSales,
                    'total_target'      => $totalTarget,
                    'total_forecast'    => $totalForecast,
                    'commission_earned' => $totalCommission,
                    'avg_daily_sales'   => $avgDaily,
                    'achievement'       => $achievement,
                    'forecast_achievement' => $achievement,
                    'actual_achievement'   => $actualAchievement,
                    'team_structure'    => count($supRows) . ' Supervisors, ' . count($repRows) . ' Reps',
                ],
                'supervisors' => $supRows,
                'sales_reps'  => $repRows,
                'charts' => [
                    'sales_trend' => [
                        'labels'       => array_column($dailySales, 'date'),
                        'actual'       => array_column($dailySales, 'sales'),
                        'daily_target' => array_fill(0, count($dailySales), $dailyTarget),
                    ],
                    'sales_distribution' => [
                        'labels' => array_column($supRows, 'supervisor') ?: ['All'],
                        'data'   => array_column($supRows, 'sales') ?: [$totalSales],
                    ],
                    'supervisor_comparison' => [
                        'labels' => array_column($supRows, 'supervisor'),
                        'actual' => array_column($supRows, 'sales'),
                        'target' => array_column($supRows, 'target'),
                    ],
                ],
            ],
        ]);
    }
}
