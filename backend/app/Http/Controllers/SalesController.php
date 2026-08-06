<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesRequest;
use App\Http\Requests\TeamSalesRequest;
use App\Http\Resources\SalesResource;
use App\Http\Resources\UserTargetResource;
use App\Http\Services\ERPService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SalesController extends Controller
{

    private ERPService $erpService;

    public function __construct(ERPService $erpService)
    {
        $this->erpService = $erpService;
    }

    public function mySales(SalesRequest $request, $oid = null): JsonResponse
    {
        $user = auth('api')->user();
        $startDate = $request->get('start_date') ?? now()->format('Y-m-') . '01';
        $endDate = $request->get('end_date') ?? Carbon::make($startDate)->endOfMonth()->format('Y-m-d');
        $oid = $oid ?? $user->getOid();
        if (!$oid) {
            return response()->json(['status' => true, 'total_sales' => 0, 'data' => null]);
        }
        $totalSales = 0;
        $getTotalSales = $this->erpService->totalSalesByDate($startDate, $endDate, $oid);
        if ($getTotalSales['success'] and isset($getTotalSales['data'][0]->TotalSales)) {
            $totalSales = number_format($getTotalSales['data'][0]->TotalSales);
        }
        return response()->json(['status' => true, 'total_sales' => $totalSales, 'data' => SalesResource::collection($getTotalSales['data'])]);
    }

    public function myTarget(): JsonResponse
    {
        $user = auth('api')->user();
        if ($user->target) {
            return response()->json(['status' => true, 'data' => new UserTargetResource($user->target), 'message' => 'Target returned']);
        }
        return response()->json(['status' => true, 'data' => null, 'message' => 'Target not assigned'], 404);
    }

    public function currentSales($id = null): JsonResponse
    {
        $data = [];
        $user = auth('api')->user();
        if ($user->role_id == 1) {
            $data = $this->getSalesRep($user);
        } elseif ($user->role_id == 2) {
            $data = [
                'actual_sales_amount' => 0,
                'forecast_sales_amount' => 0,
                'current_commission_amount' => 0,
                'commission_percentage' => 0,
            ];
            foreach ($user->children as $child) {
                $sub = $this->getSalesRep($child, false);
                $data['actual_sales_amount'] += $sub['actual_sales_amount'];
                $data['forecast_sales_amount'] += $sub['forecast_sales_amount'];
            }
            $data = array_merge($data, $this->getAnotherRoleCommission($user, $data['forecast_sales_amount'], $data['actual_sales_amount']));
        } elseif ($user->role_id == 3) {
            $data = [
                'actual_sales_amount' => 0,
                'forecast_sales_amount' => 0,
                'current_commission_amount' => 0,
                'commission_percentage' => 0,
            ];
            $superVisors = $user->children()->get()->pluck('id')->toArray();

            $users = array_merge($superVisors, [$user->id]);
            $salesReps = User::query()
                ->whereNotNull('oid')
                ->whereIn('parent_id', $users)
                ->get();
            foreach ($salesReps as $child) {
                $sub = $this->getSalesRep($child, false);
                $data['actual_sales_amount'] += $sub['actual_sales_amount'];
                $data['forecast_sales_amount'] += $sub['forecast_sales_amount'];
                $data['current_commission_amount'] += $sub['current_commission_amount'];
                $data['commission_percentage'] += $sub['commission_percentage'];
            }
            $data = array_merge($data, $this->getAnotherRoleCommission($user, $data['forecast_sales_amount'], $data['actual_sales_amount']));
        } elseif ($user->role_id == 4) {
            $data = [
                'actual_sales_amount' => 0,
                'forecast_sales_amount' => 0,
                'current_commission_amount' => 0,
                'commission_percentage' => 0,
            ];
            $areaManagers = $user->children()->get()->pluck('id')->toArray();
            $superVisors = User::query()->select(['id'])->whereIn('parent_id', $areaManagers)->get()->pluck('id')->toArray();
            $users = array_merge($superVisors, $areaManagers);
            $salesReps = User::query()->select(['id', 'oid'])
                ->whereNotNull('oid')
                ->whereIn('parent_id', $users)->get();
            foreach ($salesReps as $child) {
                $sub = $this->getSalesRep($child, false);
                $data['actual_sales_amount'] += isset($sub['actual_sales_amount']) ? $sub['actual_sales_amount'] : 0;
                $data['forecast_sales_amount'] += isset($sub['forecast_sales_amount']) ? $sub['forecast_sales_amount'] : 0;
                $data['current_commission_amount'] += isset($sub['current_commission_amount']) ? $sub['current_commission_amount'] : 0;
                $data['commission_percentage'] += isset($sub['commission_percentage']) ? $sub['commission_percentage'] : 0;
            }
            $data = array_merge($data, $this->getAnotherRoleCommission($user, $data['forecast_sales_amount'], $data['actual_sales_amount']));
        }
        $data = [
            'actual_sales_amount' => round($data['actual_sales_amount'], 3),
            'forecast_sales_amount' => round($data['forecast_sales_amount'], 3),
            'current_commission_amount' => $data['current_commission_amount'],
            'commission_percentage' => $data['commission_percentage']
        ];
        return response()->json(['statue' => true, 'data' => $data, 'message' => 'Sales returned']);
    }

    private function getAnotherRoleCommission($user, $forecast, $totalCurrentSales): array
    {
        $data = [];
        if ($user->target) {
            $currentSalesPercentage = (($forecast * 100) / $user->target->target->target);
            $commissionRule = $user->target->target->details()
                ->where('sale_percentage', '<=', $currentSalesPercentage)
                ->orderByDesc('sale_percentage')->first();
            if ($commissionRule) {
                $data['current_commission_amount'] = ceil((($totalCurrentSales * $commissionRule->commission_percentage) / 100));
                $data['commission_percentage'] = $commissionRule->commission_percentage;
            }
        }
        return $data;
    }

    public function getSalesRep($user, $withCommission = true)
    {
        $fridays = Carbon::now()->startOfMonth()->daysUntil(
            Carbon::now()->endOfMonth()
        )->filter(fn ($d) => $d->isFriday())->count();
        $fridaysGone = Carbon::now()->startOfMonth()->daysUntil(
            Carbon::now()
        )->filter(fn ($d) => $d->isFriday())->count();
        $endOfMonth = Carbon::now()->endOfMonth()->format('d');
        $totalDaysWithoutFridays = $endOfMonth - $fridays;
        $monthYear = now()->format('Y-m');
        $startDate = $monthYear . '-01';
        $dayNumber = (int)now()->format('d');
        $endDate = $monthYear . '-' . $dayNumber;
        $totalCurrentSales = 0;
        $currentSalesAmount = $this->erpService->totalSalesByDate($startDate, $endDate, $user->oid);
        if ($currentSalesAmount['success'] and isset($currentSalesAmount['data'][0])) {
            $totalCurrentSales = $currentSalesAmount['data'][0]->TotalSales;
        }
        $avgDailySales = ($totalCurrentSales / ($dayNumber - $fridaysGone));
        $forecast = $avgDailySales * $totalDaysWithoutFridays;
        $data = [
            'actual_sales_amount' => $totalCurrentSales,
            'forecast_sales_amount' => $forecast,
            'current_commission_amount' => 0,
            'commission_percentage' => 0,
        ];
        if ($withCommission) {
            if ($user->target) {
                $currentSalesPercentage = (($forecast * 100) / $user->target->target->target);
                $commissionRule = $user->target->target->details()
                    ->where('sale_percentage', '<=', $currentSalesPercentage)
                    ->orderByDesc('sale_percentage')->first();
                if ($commissionRule) {
                    $data['current_commission_amount'] = ceil((($totalCurrentSales * $commissionRule->commission_percentage) / 100));
                    $data['commission_percentage'] = $commissionRule->commission_percentage;
                }
            }
        }
        return $data;
    }

    public function teamSales(TeamSalesRequest $request, $oid = null): JsonResponse
    {
        $user = auth('api')->user();
        $data = [];
        if ($user->hasChildren()) {
            if (!$oid) {
                $users = $user->children;
            } else {
                $users = User::query()->where('oid', $oid)->get();
                if (!count($users)) {
                    return response()->json(['status' => false, 'data' => null, 'message' => 'User not found'], 404);
                }
            }
            foreach ($users as $user) {
                $startMonth = $request->get('year') . '-' . $request->get('month') . '-01';
                $endMonth = Carbon::make($startMonth)->endOfMonth()->format('Y-m-d');
                $resp = $this->erpService->totalSalesByDate($startMonth, $endMonth, $user->oid);
                if ($resp['success'] and is_array($resp['data']) and isset($resp['data'][0])) {
                    $data[] = [
                        'name' => $user->name,
                        'total_sales' => $resp['data'][0]->TotalSales,
                        'oid' => $user->oid
                    ];
                } else {
                    $data[] = [
                        'name' => $user->name,
                        'total_sales' => 0,
                        'oid' => $user->oid
                    ];
                }
            }
            return response()->json(['status' => true, 'data' => $data, 'message' => 'Sales returned']);
        }
        return response()->json(['status' => false, 'data' => null, 'message' => 'You are not have any team'], 400);
    }

    public function yearSales(Request $request, $oid = null): JsonResponse
    {
        $request->validate(['year' => 'required|numeric|min:2010|max:' . date('Y')]);
        $user = auth('api')->user();
        $data = [];
        $totalSales = 0;
        $totalTarget = 0;
        if ($user->hasChildren()) {
            if ($oid) {
                $children = User::query()->where('oid', $oid)->get();
                if (!count($children)) {
                    return response()->json(['status' => false, 'data' => null, 'message' => 'User not found'], 404);
                }
            } else {
                $children = $user->children;
            }
            foreach ($children as $child) {
                $sub = [];
                if (isset($child->target->target)) {
                    $totalTarget += $child->target->target->target;
                }
                $sub['name'] = $child->name;
                $sub['oid'] = $child->oid;
                for ($i = 1; $i <= 12; $i++) {
                    $subMonth = [
                        'name' => Carbon::create()->month($i)->format('F'),
                        'number' => $i,
                        'total_sales' => 0
                    ];
                    $startOfMonth = Carbon::createFromDate($request->get('year'), $i, 1)->startOfDay()->format('Y-m-d');
                    $endOfMonth = Carbon::createFromDate($request->get('year'), $i, 1)->endOfMonth()->endOfDay()->format('Y-m-d');
                    if ($child->hasChildren()) {
                        $oids = [];
                        if ($child->role_id == 2) {
                            $oids = User::query()->select(['oid'])->where('parent_id', $child->id)->pluck('oid')->toArray();
                        } elseif ($child->role_id == 3) {
                            $oids = User::query()->select(['oid'])->where('parent_id', $child->children->pluck('id')->toArray())->pluck('oid')->toArray();
                        }
                        $childrenTotalSales = 0;
                        foreach ($oids as $oid) {
                            $resp = $this->erpService->totalSalesByDate($startOfMonth, $endOfMonth, $oid);
                            if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
                                $childrenTotalSales = $childrenTotalSales + $resp['data'][0]->TotalSales;
                                $totalSales += $resp['data'][0]->TotalSales;
                            }
                        }
                        $subMonth['total_sales'] = $childrenTotalSales;
                    } else {
                        $resp = $this->erpService->totalSalesByDate($startOfMonth, $endOfMonth, $child->oid);
                        if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
                            $subMonth['total_sales'] = $resp['data'][0]->TotalSales;
                            $totalSales += $resp['data'][0]->TotalSales;
                        }
                    }

                    $sub['months'][] = $subMonth;
                }
                $data[] = $sub;
            }
            return response()->json(['status' => true, 'data' => ['sales' => $data, 'total_actual_sales' => $totalSales, 'total_team_target' => $totalTarget], 'message' => 'Sales returned']);
        }
        return response()->json(['status' => false, 'data' => null, 'message' => 'You are not have any team'], 400);
    }

    public function weekSales($oid = null): JsonResponse
    {
        $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
        $data = [];
        $user = auth('api')->user();
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::make($sunday)->addDays($i);
            $dateFormatted = $date->format('Y-m-d');
            $data[] = [
                'name' => $date->format('l'),
                'date' => $date->format('Y-m-d'),
                'sales' => $this->getSales($dateFormatted, $user),
            ];
        }
        return response()->json(['status' => true, 'data' => $data, 'message' => 'Sales returned']);
    }

    private function getSales($date, $user)
    {
        $sales = 0;
        $erp = $this->erpService;
        if ($user->role_id == 1) {
            $total = $erp->totalSalesByDate($date, $date, $user->getOid());
            if ($total['success'] and isset($total['data'][0]->TotalSales)) {
                $sales = $total['data'][0]->TotalSales;
            }
        } elseif ($user->role_id == 2) {
            $salesReps = User::query()->whereNotNull('oid')->where('parent_id', $user->id)->get();
            foreach ($salesReps as $salesRep) {
                $total = $erp->totalSalesByDate($date, $date, $salesRep->getOid());
                if ($total['success'] and isset($total['data'][0]->TotalSales)) {
                    $sales = $sales + $total['data'][0]->TotalSales;
                }
            }
        } elseif ($user->role_id == 3) {
            $salesReps = User::query()->whereNotNull('oid')
                ->where('parent_id', array_merge($user->children()->get()->pluck('id')->toArray(), [$user->id]))
                ->get();
            foreach ($salesReps as $salesRep) {
                $total = $erp->totalSalesByDate($date, $date, $salesRep->getOid());
                if ($total['success'] and isset($total['data'][0]->TotalSales)) {
                    $sales = $sales + $total['data'][0]->TotalSales;
                }
            }
        } elseif ($user->role_id == 4) {
            $areaManagers = $user->children()->get()->pluck('id')->toArray();
            $salesSupervisors = User::query()->select(['id'])->where('role_id', 3)->whereIn('parent_id', $areaManagers)->get()->pluck('id')->toArray();
            $salesReps = User::query()->whereNotNull('oid')->where('parent_id', array_merge($salesSupervisors, $areaManagers))->get();
            foreach ($salesReps as $salesRep) {
                $total = $erp->totalSalesByDate($date, $date, $salesRep->getOid());
                if ($total['success'] and isset($total['data'][0]->TotalSales)) {
                    $sales = $sales + $total['data'][0]->TotalSales;
                }
            }
        }
        return $sales;
    }

    public function yearTotalSales(Request $request): JsonResponse
    {
        $year = $request->get('year') ?? date('Y');
        $user = auth('api')->user();
        $data = [];
        switch ($user->role_id) {
            case 1:
                $data = $this->salesRep($year, $user);
                break;
            case 2:
                $data = $this->superVisor($year, $user);
                break;
            case 3:
                $data = $this->areaManager($year, $user);
                break;
            case 4:
                $data = $this->salesManager($year, $user);
                break;

        }
        return response()->json(['status' => true, 'data' => $data, 'message' => 'Sales returned']);
    }

    private function salesRep($year, $user): array
    {
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
        $total = 0;
        $resp = $this->erpService->totalSalesByDate($startDate, $endDate, $user->getOid());
        if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
            $total = $resp['data'][0]->TotalSales;
        }
        return ['total' => $total];
    }

    private function superVisor($year, $user): array
    {
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
        $total = 0;
        foreach ($user->children as $child) {
            $resp = $this->erpService->totalSalesByDate($startDate, $endDate, $child->getOid());
            if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
                $total = $total + $resp['data'][0]->TotalSales;
            }
        }
        return ['total' => $total];
    }

    private function areaManager($year, $user): array
    {
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
        $total = 0;
        $superVisors = $user->children()->get()->pluck('id')->toArray();
        $salesReps = User::query()->whereIn('parent_id', $superVisors)->get();
        foreach ($salesReps as $salesRep) {
            $resp = $this->erpService->totalSalesByDate($startDate, $endDate, $salesRep->getOid());
            if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
                $total = $total + $resp['data'][0]->TotalSales;
            }
        }
        return ['total' => $total];
    }

    private function salesManager($year, $user): array
    {
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
        $total = 0;
        $areaManagers = $user->children()->get()->pluck('id')->toArray();
        $superVisors = User::query()->select(['id'])->whereIn('parent_id', $areaManagers)->get()->pluck('id')->toArray();
        $salesReps = User::query()->select(['id', 'oid'])->whereIn('parent_id', $superVisors)->get();
        foreach ($salesReps as $salesRep) {
            $resp = $this->erpService->totalSalesByDate($startDate, $endDate, $salesRep->getOid());
            if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
                $total = $total + $resp['data'][0]->TotalSales;
            }
        }
        return ['total' => $total];
    }

    public function commissionPercentage(): JsonResponse
    {
        $user = auth('api')->user();
        if ($user->role_id != 1) {
            $commission = $user->role->commission ? $user->role->commission->commission_percentage : null;
            return response()->json(['status' => true, 'data' => ['commission' => $commission], 'message' => 'Commission percentage returned']);
        }
        return response()->json(['status' => false, 'data' => null, 'message' => 'No commission percentage'], 403);
    }
}
