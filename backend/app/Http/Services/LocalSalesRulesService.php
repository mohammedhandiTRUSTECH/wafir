<?php

namespace App\Http\Services;

use App\Models\MonthlyRepTarget;
use Illuminate\Support\Carbon;

class LocalSalesRulesService
{
    /** @var array{by_erp: array<int, float>, by_name: array<string, float>}|null */
    private ?array $overrideCache = null;

    public function repRows(): array
    {
        return config('local_sales_rules.reps', []);
    }

    public function repTiers(): array
    {
        return config('local_sales_rules.rep_tiers', []);
    }

    public function supervisorTiers(): array
    {
        return config('local_sales_rules.supervisor_tiers', []);
    }

    public function salesManagerTiers(): array
    {
        return config('local_sales_rules.sales_manager_tiers', []);
    }

    public function repMeta(?string $name): ?array
    {
        $needle = $this->normalize($name);
        if ($needle === '') {
            return null;
        }

        foreach ($this->repRows() as $row) {
            if ($this->normalize($row['name'] ?? null) === $needle) {
                return $row;
            }
        }

        return null;
    }

    public function repTarget(?string $name, ?int $erpId = null, ?Carbon $date = null): float
    {
        $date = $date ?? Carbon::now();
        $overrides = $this->monthOverrides($date);

        if ($erpId !== null && array_key_exists($erpId, $overrides['by_erp'])) {
            return (float) $overrides['by_erp'][$erpId];
        }

        $needle = $this->normalize($name);
        if ($needle !== '' && array_key_exists($needle, $overrides['by_name'])) {
            return (float) $overrides['by_name'][$needle];
        }

        return (float) ($this->repMeta($name)['target'] ?? 0);
    }

    public function setMonthlyTarget(int $erpId, string $repName, float $target, ?Carbon $date = null): MonthlyRepTarget
    {
        $date = $date ?? Carbon::now();

        $row = MonthlyRepTarget::query()->updateOrCreate(
            [
                'erp_id' => $erpId,
                'year'   => (int) $date->year,
                'month'  => (int) $date->month,
            ],
            [
                'rep_name' => $repName,
                'target'   => $target,
            ]
        );

        $this->overrideCache = null;

        return $row;
    }

    public function supervisorNameForRep(?string $name): string
    {
        $meta = $this->repMeta($name);
        if (!$meta) {
            return '—';
        }

        return (string) ($meta['supervisor'] ?: (($meta['area'] ?? 'Unknown') . ' Team'));
    }

    public function supervisorGroups(): array
    {
        $groups = [];

        foreach ($this->repRows() as $row) {
            $name = (string) ($row['supervisor'] ?: (($row['area'] ?? 'Unknown') . ' Team'));

            if (!isset($groups[$name])) {
                $groups[$name] = [
                    'id' => count($groups) + 1,
                    'name' => $name,
                    'area' => $row['area'] ?? null,
                    'target' => 0.0,
                    'members' => [],
                ];
            }

            $groups[$name]['target'] += $this->repTarget($row['name'] ?? null);
            $groups[$name]['members'][] = $row;
        }

        return array_values($groups);
    }

    public function supervisorById(int $id): ?array
    {
        foreach ($this->supervisorGroups() as $group) {
            if ((int) $group['id'] === $id) {
                return $group;
            }
        }

        return null;
    }

    public function commissionRateForAchievement(string $role, float $achievement): float
    {
        $tiers = match ($role) {
            'supervisor'    => $this->supervisorTiers(),
            'sales_manager' => $this->salesManagerTiers(),
            default         => $this->repTiers(),
        };

        foreach (array_reverse($tiers) as $tier) {
            $min = (float) ($tier['min'] ?? 0);
            if ($achievement >= $min) {
                return (float) ($tier['rate'] ?? 0);
            }
        }

        return 0.0;
    }

    /**
     * @return array{by_erp: array<int, float>, by_name: array<string, float>}
     */
    private function monthOverrides(Carbon $date): array
    {
        if ($this->overrideCache !== null) {
            return $this->overrideCache;
        }

        $byErp  = [];
        $byName = [];

        $rows = MonthlyRepTarget::query()
            ->where('year', (int) $date->year)
            ->where('month', (int) $date->month)
            ->get(['erp_id', 'rep_name', 'target']);

        foreach ($rows as $row) {
            $byErp[(int) $row->erp_id] = (float) $row->target;
            $needle = $this->normalize($row->rep_name);
            if ($needle !== '') {
                $byName[$needle] = (float) $row->target;
            }
        }

        return $this->overrideCache = [
            'by_erp'  => $byErp,
            'by_name' => $byName,
        ];
    }

    private function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', trim($value));

        return $value ?: '';
    }
}
