<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AdminSalesManagerController extends Controller
{
    private DashboardService $svc;

    public function __construct(DashboardService $svc)
    {
        $this->svc = $svc;
    }

    /** GET /api/admin/sales-managers/list — ERP has no SM role; expose org-wide view */
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

    /** GET /api/admin/sales-managers/{id} — org-wide sales from GetNetSales */
    public function show(int $id): JsonResponse
    {
        $metrics = $this->svc->getErpOrgRepMetrics();
        $repRows = $metrics['reps'];
        $allOids = $metrics['oids'];
        $repsByName = collect($repRows)->keyBy('name');

        $totalSales      = $metrics['total_sales'];
        $totalForecast   = $metrics['total_forecast'];
        $totalTarget     = $metrics['total_target'];
        $totalCommission = $metrics['total_commission'];
        $achievement     = $metrics['achievement'];
        $actualAchievement = $metrics['actual_achievement'];
        $avgDaily        = $this->svc->avgDailySales($totalSales);
        $dailyTarget     = $this->svc->monthlyDailyTarget($totalTarget);

        $sorted  = collect($repRows)->sortByDesc('sales')->values()->toArray();
        $top5    = array_slice($sorted, 0, 5);
        $bottom5 = collect($repRows)->sortBy('sales')->values()->take(5)->toArray();

        $dailySales = $this->svc->getAggregatedDailySales($allOids, 30);

        $tiers = ['<80%' => 0, '80-90%' => 0, '90-100%' => 0, '100-110%' => 0, '110%+' => 0];
        foreach ($repRows as $r) {
            $pct = (float) ($r['achievement'] ?? 0);
            if ($pct < 80) {
                $tiers['<80%']++;
            } elseif ($pct < 90) {
                $tiers['80-90%']++;
            } elseif ($pct < 100) {
                $tiers['90-100%']++;
            } elseif ($pct < 110) {
                $tiers['100-110%']++;
            } else {
                $tiers['110%+']++;
            }
        }

        $weeklyLabels = [];
        $weeklyData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $end   = Carbon::now()->subWeeks($i)->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
            $weeklyLabels[] = 'Week ' . Carbon::parse($start)->format('M d');
            $weeklyData[]   = $this->svc->getAggregatedSalesByRange($allOids, $start, $end);
        }

        $supGroups = $this->svc->localRules()->supervisorGroups();

        $amRows = [[
            'id'               => 1,
            'name'             => 'All Areas (ERP)',
            'email'            => 'erp',
            'supervisors'      => count($supGroups),
            'reps'             => count($repRows),
            'target'           => $totalTarget,
            'sales'            => $totalSales,
            'commission'       => $totalCommission,
            'progress'         => $achievement,
            'supervisors_list' => collect($supGroups)->pluck('name')->implode(', ') ?: '—',
        ]];

        $supRows = collect($supGroups)
            ->map(function ($group) use ($repsByName) {
                $sales = 0.0;
                $forecast = 0.0;
                $matched = 0;

                foreach ($group['members'] as $member) {
                    $rep = $repsByName->get($member['name']);
                    if (!$rep) {
                        continue;
                    }
                    $matched++;
                    $sales += $rep['sales'];
                    $forecast += $rep['forecast'];
                }

                $target = (float) $group['target'];

                return [
                    'supervisor'   => $group['name'],
                    'area_manager' => '—',
                    'team_size'    => $matched ?: count($group['members']),
                    'target'       => round($target, 2),
                    'sales'        => round($sales, 2),
                    'achievement'  => $target > 0 ? round(($forecast / $target) * 100, 2) : 0.0,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'status' => true,
            'source' => 'erp_api_with_local_rules',
            'data'   => [
                'manager' => [
                    'id'    => 1,
                    'name'  => 'Organization (ERP)',
                    'email' => 'GetSalespersons + GetNetSales',
                ],
                'stats' => [
                    'area_managers'   => 1,
                    'supervisors'     => count($supGroups),
                    'sales_reps'      => count($repRows),
                    'total_sales'     => $totalSales,
                    'total_target'    => $totalTarget,
                    'total_forecast'  => $totalForecast,
                    'commission_pool' => $totalCommission,
                    'avg_daily_sales' => $avgDaily,
                    'achievement'     => $achievement,
                    'forecast_achievement' => $achievement,
                    'actual_achievement'   => $actualAchievement,
                ],
                'area_managers'   => $amRows,
                'supervisors'     => $supRows,
                'sales_reps'      => $repRows,
                'top_performers'  => $top5,
                'needs_attention' => $bottom5,
                'charts' => [
                    'sales_trend' => [
                        'labels'       => array_column($dailySales, 'date'),
                        'actual'       => array_column($dailySales, 'sales'),
                        'daily_target' => array_fill(0, count($dailySales), $dailyTarget),
                    ],
                    'sales_distribution' => [
                        'labels' => ['All Areas (ERP)'],
                        'data'   => [$totalSales],
                    ],
                    'area_manager_performance' => [
                        'labels' => ['All'],
                        'data'   => [$achievement],
                    ],
                    'achievement_tiers' => [
                        'labels' => array_keys($tiers),
                        'data'   => array_values($tiers),
                    ],
                    'weekly_performance' => [
                        'labels' => $weeklyLabels,
                        'data'   => $weeklyData,
                    ],
                ],
            ],
        ]);
    }
}
