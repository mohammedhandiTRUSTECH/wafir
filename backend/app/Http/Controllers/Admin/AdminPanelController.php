<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPanelController extends Controller
{
    private DashboardService $svc;

    public function __construct(DashboardService $svc)
    {
        $this->svc = $svc;
    }

    /**
     * GET /api/admin/panel/targets
     * Returns company-wide target/forecast + individual rep targets.
     */
    public function targets(): JsonResponse
    {
        $reps = $this->svc->erp()->getSalespersons(1, true);
        $this->svc->prefetchCurrentMonthTotals(array_column($reps, 'oid'));

        $companyForecast = 0;
        $companyTarget   = 0;
        $repRows         = [];

        foreach ($reps as $person) {
            $erpId    = (int) $person['id'];
            $calc     = $this->svc->getOidCurrentMonth($person['oid'], null, null, $person['name'], $erpId);
            // Full monthly target for editing — not the MTD-prorated value used on dashboards.
            $monthlyTarget = $this->svc->localRules()->repTarget($person['name'], $erpId);
            $forecast = $calc['forecast'];
            $companyForecast += $forecast;
            $companyTarget += $monthlyTarget;

            $repRows[] = [
                'id'               => $erpId,
                'name'             => $person['name'],
                'email'            => (string) $person['id'],
                'supervisor'       => $this->svc->localRules()->supervisorNameForRep($person['name']),
                'current_target'   => round($monthlyTarget, 2),
                'forecast'         => round($forecast, 2),
                'user_target_id'   => $erpId,
                'sales_target_id'  => null,
                'editable_target'  => true,
            ];
        }

        return response()->json([
            'status' => true,
            'source' => 'erp_api_with_local_rules',
            'data'   => [
                'company_target'   => round($companyTarget, 2),
                'company_forecast' => round($companyForecast, 2),
                'reps'             => $repRows,
                'read_only'        => false,
            ],
        ]);
    }

    /**
     * PUT /api/admin/panel/targets/{userId}
     * Upserts the current-month target override for an ERP salesperson.
     */
    public function updateTarget(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'target' => 'required|numeric|min:0',
        ]);

        $person = $this->svc->erp()->findSalespersonById($userId);
        if (!$person) {
            return response()->json([
                'status'  => false,
                'message' => 'Salesperson not found',
            ], 404);
        }

        $target = (float) $request->input('target');
        $row = $this->svc->localRules()->setMonthlyTarget(
            $userId,
            (string) $person['name'],
            $target
        );

        return response()->json([
            'status'  => true,
            'message' => 'Target updated successfully',
            'data'    => [
                'erp_id'   => $row->erp_id,
                'rep_name' => $row->rep_name,
                'year'     => $row->year,
                'month'    => $row->month,
                'target'   => $row->target,
            ],
        ]);
    }

    /**
     * GET /api/admin/panel/commission-schemes
     * Returns commission tiers + per-rep commission summary.
     */
    public function commissionSchemes(): JsonResponse
    {
        $tiers = [
            'sales_rep'      => $this->svc->localRules()->repTiers(),
            'supervisor'     => $this->svc->localRules()->supervisorTiers(),
            'sales_manager'  => $this->svc->localRules()->salesManagerTiers(),
        ];

        $reps = $this->svc->erp()->getSalespersons(1, true);
        $this->svc->prefetchCurrentMonthTotals(array_column($reps, 'oid'));

        $repRows = [];
        foreach ($reps as $person) {
            $calc = $this->svc->getOidCurrentMonth($person['oid'], null, null, $person['name'], (int) $person['id']);

            $repRows[] = [
                'id'                => $person['id'],
                'name'              => $person['name'],
                'email'             => (string) $person['id'],
                'supervisor'        => $this->svc->localRules()->supervisorNameForRep($person['name']),
                'target'            => $calc['target'],
                'total_earned'      => $calc['commission_amount'],
                'achievement'       => $calc['achievement'],
                'current_tier_rate' => $calc['commission_pct'],
                'commission_amount' => $calc['commission_amount'],
            ];
        }

        return response()->json([
            'status' => true,
            'source' => 'erp_api_with_local_rules',
            'data'   => [
                'tiers' => $tiers,
                'reps'  => $repRows,
            ],
        ]);
    }

    /**
     * GET /api/admin/panel/sales-reps
     * Returns all sales reps with their supervisors list for the admin panel.
     */
    public function salesReps(): JsonResponse
    {
        $reps = collect($this->svc->erp()->getSalespersons(1, true))
            ->map(function ($person) {
                $calc = $this->svc->getOidCurrentMonth($person['oid'], null, null, $person['name'], (int) $person['id']);

                return [
                    'id'         => $person['id'],
                    'name'       => $person['name'],
                    'email'      => (string) $person['id'],
                    'supervisor' => $this->svc->localRules()->supervisorNameForRep($person['name']),
                    'target'     => $calc['target'],
                    'commission' => $calc['commission_pct'] . '%',
                    'is_active'  => true,
                ];
            })
            ->values();

        $supervisors = collect($this->svc->localRules()->supervisorGroups())
            ->map(fn($group) => ['id' => $group['id'], 'name' => $group['name']])
            ->values();

        return response()->json([
            'status' => true,
            'source' => 'erp_api_with_local_rules',
            'data'   => [
                'reps'        => $reps,
                'supervisors' => $supervisors,
            ],
        ]);
    }

    /**
     * GET /api/admin/panel/supervisors
     * Returns all supervisors with their team data.
     */
    public function supervisors(): JsonResponse
    {
        $peopleByName = collect($this->svc->erp()->getSalespersons(1, true))->keyBy('name');

        $supervisors = collect($this->svc->localRules()->supervisorGroups())
            ->map(function ($group) use ($peopleByName) {
                $sales = 0.0;

                foreach ($group['members'] as $member) {
                    $person = $peopleByName->get($member['name']);
                    if (!$person || empty($person['oid'])) {
                        continue;
                    }

                    $sales += $this->svc->getOidCurrentMonth(
                        $person['oid'],
                        null,
                        null,
                        $person['name'],
                        (int) $person['id']
                    )['actual'];
                }

                return [
                    'id'        => $group['id'],
                    'name'      => $group['name'],
                    'email'     => $group['area'] ? strtolower($group['area']) . '@local' : 'local@rules',
                    'team_size' => count($group['members']),
                    'target'    => round($group['target'], 2),
                    'sales'     => round($sales, 2),
                    'members'   => array_column($group['members'], 'name'),
                ];
            })
            ->values();

        return response()->json(['status' => true, 'source' => 'erp_api_with_local_rules', 'data' => $supervisors]);
    }
}
