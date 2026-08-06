<?php

namespace App\Http\Resources;

use App\Http\Services\ERPService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyChildrenResource extends JsonResource
{

    protected static $roleId;
    public static function withExtra($collection, $roleId)
    {
        self::$roleId = $roleId;
        return parent::collection($collection);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'employee_id' => $this->employee_id,
            'email' => $this->email,
            'has_children' => (bool)count($this->children),
            'role_id' => $this->role_id,
            'role_name' => $this->role->name,
            'oid' => $this->oid,
            'sales_target' => $this->getTotalSalesAndTarget(),
        ];
    }

    private function getTotalSalesAndTarget(): array
    {
        $roleId = self::$roleId;
        $firstDay = Carbon::now()->startOfMonth()->format('Y-m-d');
        $lastDay = Carbon::now()->endOfMonth()->format('Y-m-d');

        switch ($roleId) {
            case 2:
                return $this->getForSuperVisor($firstDay, $lastDay);
            case 3:
                return $this->getForAreaManager($firstDay, $lastDay);
            case 4:
                return $this->getForSalesManager($firstDay, $lastDay);
        }
    }

    private function erpService(): ERPService
    {
        return app(ERPService::class);
    }

    private function getForSuperVisor($firstDay, $lastDay): array
    {
        $totalSales = ['total_sales' => 0, 'target' => 0];
        $resp = $this->erpService()->totalSalesByDate($firstDay, $lastDay, $this->oid);
        if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
            $totalSales['total_sales'] = $resp['data'][0]->TotalSales;
        }
        if (isset($this->target->target)) {
            $totalSales['target'] = $this->target->target->target;
        }
        return $totalSales;
    }

    private function getForAreaManager($firstDay, $lastDay): array
    {
        $totalSales = ['total_sales' => 0, 'target' => 0];
        $erp = $this->erpService();
        foreach ($this->children as $child) {
            if (isset($child->target->target)) {
                $totalSales['target'] = $totalSales['target'] + $child->target->target->target;
            }
            $resp = $erp->totalSalesByDate($firstDay, $lastDay, $child->oid);
            if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
                $totalSales['total_sales'] = $totalSales['total_sales'] + $resp['data'][0]->TotalSales;
            }
        }
        return $totalSales;
    }

    private function getForSalesManager($firstDay, $lastDay): array
    {
        $totalSales = ['total_sales' => 0, 'target' => 0];
        if ($this->role_id == 2) {
            $salesReps = User::query()->whereIn('id', $this->children()->select(['id'])->get()->pluck('id')->toArray())->get();
        } else {
            $salesReps = User::query()->whereIn('parent_id', $this->children()->select(['id'])->get()->pluck('id')->toArray())->get();
        }
        $erp = $this->erpService();
        foreach ($salesReps as $salesRep) {
            if (isset($salesRep->target->target)) {
                $totalSales['target'] = $totalSales['target'] + $salesRep->target->target->target;
            }
            $resp = $erp->totalSalesByDate($firstDay, $lastDay, $salesRep->oid);
            if ($resp['success'] and isset($resp['data'][0]->TotalSales)) {
                $totalSales['total_sales'] = $totalSales['total_sales'] + $resp['data'][0]->TotalSales;
            }
        }
        return $totalSales;
    }
}
