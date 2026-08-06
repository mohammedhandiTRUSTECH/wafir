<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class AdminSupervisorController extends Controller
{
    private DashboardService $svc;

    public function __construct(DashboardService $svc)
    {
        $this->svc = $svc;
    }

    /** GET /api/admin/supervisors/list — ERP names containing مشرف */
    public function list(): JsonResponse
    {
        $supervisors = $this->svc->getErpSupervisorList();

        return response()->json([
            'status' => true,
            'source' => 'erp_api_with_local_rules',
            'data'   => $supervisors,
        ]);
    }

    /**
     * GET /api/admin/supervisors/{id}
     * Uses local Excel-based supervisor/rep mapping and ERP sales totals.
     */
    public function show(int $id): JsonResponse
    {
        $selected = $this->svc->localRules()->supervisorById($id);
        if (!$selected) {
            return response()->json(['status' => false, 'message' => 'Supervisor not found'], 404);
        }

        $peopleByName = collect($this->svc->erp()->getSalespersons(1, true))->keyBy('name');
        $repRows = [];
        $oids = [];
        $totalSales = 0.0;
        $totalForecast = 0.0;

        foreach ($selected['members'] as $member) {
            $person = $peopleByName->get($member['name']);
            if (!$person || empty($person['oid'])) {
                continue;
            }

            $calc = $this->svc->getOidCurrentMonth($person['oid'], null, null, $person['name'], (int) $person['id']);
            $totalSales += $calc['actual'];
            $totalForecast += $calc['forecast'];
            $oids[] = $person['oid'];

            $repRows[] = [
                'id'           => $person['id'],
                'name'         => $person['name'],
                'email'        => (string) $person['id'],
                'target'       => $calc['target'],
                'actual_sales' => $calc['actual'],
                'forecast'     => $calc['forecast'],
                'commission'   => $calc['commission_amount'],
                'achievement'  => $calc['achievement'],
                'actual_achievement' => $calc['actual_achievement'],
            ];
        }

        $dailySales     = $this->svc->getAggregatedDailySales($oids, 15);
        $target         = (float) $selected['target'];
        $forecastAchievement = $target > 0 ? round(($totalForecast / $target) * 100, 2) : 0.0;
        $actualAchievement   = $target > 0 ? round(($totalSales / $target) * 100, 2) : 0.0;
        $avgDaily       = $this->svc->avgDailySales($totalSales);
        $dailyTarget    = $this->svc->monthlyDailyTarget($target);

        $repCompLabels = array_map(fn($r) => explode(' ', $r['name'])[0], $repRows);
        $repActual     = array_column($repRows, 'actual_sales');
        $repTarget     = array_column($repRows, 'target');

        return response()->json([
            'status' => true,
            'source' => 'erp_api_with_local_rules',
            'data'   => [
                'supervisor' => [
                    'id'         => $selected['id'],
                    'name'       => $selected['name'],
                    'email'      => $selected['area'] ? strtolower($selected['area']) . '@local' : 'local@rules',
                    'team_size'  => count($repRows),
                    'reports_to' => '—',
                ],
                'stats' => [
                    'total_sales'     => $totalSales,
                    'total_target'    => round($target, 2),
                    'team_forecast'   => $totalForecast,
                    'avg_daily_sales' => $avgDaily,
                    'achievement'     => $forecastAchievement,
                    'forecast_achievement' => $forecastAchievement,
                    'actual_achievement'   => $actualAchievement,
                ],
                'team_members' => $repRows,
                'charts' => [
                    'team_sales_trend' => [
                        'labels'       => array_column($dailySales, 'date'),
                        'actual'       => array_column($dailySales, 'sales'),
                        'daily_target' => array_fill(0, count($dailySales), $dailyTarget),
                    ],
                    'sales_rep_performance' => [
                        'labels' => $repCompLabels,
                        'actual' => $repActual,
                        'target' => $repTarget,
                    ],
                ],
            ],
        ]);
    }
}
