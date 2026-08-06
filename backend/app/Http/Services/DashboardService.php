<?php

namespace App\Http\Services;

use App\Models\MonthlyWorkingDays;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardService
{
    private ERPService $erp;
    private LocalSalesRulesService $localRules;

    /** @var array<string, int|null> */
    private array $workingDaysOverrideCache = [];

    public function __construct(?ERPService $erp = null, ?LocalSalesRulesService $localRules = null)
    {
        $this->erp = $erp ?? new ERPService();
        $this->localRules = $localRules ?? new LocalSalesRulesService();
    }

    /**
     * Saved working-days override for a calendar month, or null if none.
     */
    public function getWorkingDaysOverride(int $year, int $month): ?int
    {
        $key = $year . '-' . $month;
        if (!array_key_exists($key, $this->workingDaysOverrideCache)) {
            $row = MonthlyWorkingDays::query()
                ->where('year', $year)
                ->where('month', $month)
                ->first();
            $this->workingDaysOverrideCache[$key] = $row ? (int) $row->working_days : null;
        }

        return $this->workingDaysOverrideCache[$key];
    }

    public function setWorkingDaysOverride(int $year, int $month, int $workingDays): MonthlyWorkingDays
    {
        $row = MonthlyWorkingDays::query()->updateOrCreate(
            ['year' => $year, 'month' => $month],
            ['working_days' => $workingDays]
        );
        $this->workingDaysOverrideCache[$year . '-' . $month] = $workingDays;

        return $row;
    }

    public function clearWorkingDaysOverride(int $year, int $month): void
    {
        MonthlyWorkingDays::query()
            ->where('year', $year)
            ->where('month', $month)
            ->delete();
        $this->workingDaysOverrideCache[$year . '-' . $month] = null;
    }

    /**
     * Working days in the month (saved override if present, else Fridays excluded).
     *
     * @return array{total: int, gone: int, auto_total: int, is_override: bool}
     */
    public function workingDaysInMonth(?Carbon $date = null, ?Carbon $asOf = null): array
    {
        $ref = $date ?? Carbon::now();

        return $this->workingDaysInRange(
            $ref->copy()->startOfMonth(),
            $ref->copy()->endOfMonth(),
            $asOf ?? Carbon::now()
        );
    }

    /**
     * Auto working days (Fridays excluded) — ignores saved overrides.
     *
     * @return array{total: int, gone: int}
     */
    public function autoWorkingDaysInRange(Carbon $from, Carbon $to, ?Carbon $asOf = null): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $asOf    = ($asOf ?? Carbon::now())->copy()->startOfDay();
        $goneEnd = $asOf->lt($to) ? $asOf : $to;

        $total = 0;
        $gone  = 0;

        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if ($d->isFriday()) {
                continue;
            }
            $total++;
            if ($asOf->gte($from) && $d->lte($goneEnd)) {
                $gone++;
            }
        }

        return [
            'total' => max(1, $total),
            'gone'  => max(1, $gone),
        ];
    }

    /**
     * Working days in an inclusive date range.
     * Uses saved monthly overrides when present; scales partial-month / multi-month ranges.
     *
     * @return array{total: int, gone: int, auto_total: int, is_override: bool}
     */
    public function workingDaysInRange(Carbon $from, Carbon $to, ?Carbon $asOf = null): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }
        $asOf = ($asOf ?? Carbon::now())->copy()->startOfDay();

        $total      = 0;
        $gone       = 0;
        $autoTotal  = 0;
        $isOverride = false;
        $cursor     = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $monthStart   = $cursor->copy()->startOfMonth();
            $monthEnd     = $cursor->copy()->endOfMonth()->startOfDay();
            $overlapStart = $from->greaterThan($monthStart) ? $from : $monthStart;
            $overlapEnd   = $to->lessThan($monthEnd) ? $to : $monthEnd;

            $autoMonth   = $this->autoWorkingDaysInRange($monthStart, $monthEnd, $asOf);
            $autoOverlap = $this->autoWorkingDaysInRange($overlapStart, $overlapEnd, $asOf);
            $autoTotal  += $autoOverlap['total'];

            $override = $this->getWorkingDaysOverride((int) $monthStart->year, (int) $monthStart->month);
            if ($override !== null) {
                $isOverride = true;
                $monthTotal = max(1, $override);
                $ratio      = $autoMonth['total'] > 0 ? ($monthTotal / $autoMonth['total']) : 1;
                $overlapTotal = max(1, (int) round($autoOverlap['total'] * $ratio));
                $overlapGone  = max(1, (int) round($autoOverlap['gone'] * $ratio));
                $overlapGone  = min($overlapGone, $overlapTotal);
            } else {
                $overlapTotal = $autoOverlap['total'];
                $overlapGone  = $autoOverlap['gone'];
            }

            $total += $overlapTotal;
            $gone  += $overlapGone;
            $cursor->addMonth();
        }

        return [
            'total'       => max(1, $total),
            'gone'        => max(1, $gone),
            'auto_total'  => max(1, $autoTotal),
            'is_override' => $isOverride,
        ];
    }

    /**
     * Target covering a date range: the whole monthly target of every month the range
     * touches. A monthly target is a fixed figure, so a partial month still carries its
     * full target rather than a day-weighted slice of it.
     */
    public function rangeTarget(?string $repName, ?int $erpId, Carbon $from, Carbon $to): float
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $total  = 0.0;
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $total += $this->localRules->repTarget($repName, $erpId, $cursor->copy());
            $cursor->addMonth();
        }

        return round($total, 2);
    }

    /**
     * Working days of every whole month the range touches — the denominator that matches
     * rangeTarget(), so the daily target stays a stable monthly figure.
     *
     * @return array{total: int, gone: int, auto_total: int, is_override: bool}
     */
    public function workingDaysInCoveredMonths(Carbon $from, Carbon $to, ?Carbon $asOf = null): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        return $this->workingDaysInRange(
            $from->copy()->startOfMonth(),
            $to->copy()->endOfMonth()->startOfDay(),
            $asOf
        );
    }

    /** Average daily sales using working days elapsed (excludes Fridays). */
    public function avgDailySales(float $actual, ?Carbon $date = null, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $gone = ($from && $to)
            ? $this->workingDaysInRange($from, $to, $date)['gone']
            : $this->workingDaysInMonth($date)['gone'];

        return round($actual / $gone, 2);
    }

    /** Target spread across working days in the month or custom range. */
    public function monthlyDailyTarget(float $monthlyTarget, ?Carbon $date = null, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $total = ($from && $to)
            ? $this->workingDaysInRange($from, $to, $date)['total']
            : $this->workingDaysInMonth($date)['total'];

        return $total > 0 ? round($monthlyTarget / $total, 2) : 0.0;
    }

    /**
     * Metrics for an ERP salesperson OID over a date range (defaults to month-to-date).
     */
    public function getOidCurrentMonth(
        string $oid,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $repName = null,
        ?int $erpId = null
    ): array
    {
        $asOf  = Carbon::now()->startOfDay();
        $end   = $toDate ? Carbon::parse($toDate)->startOfDay() : $asOf->copy();
        $start = $fromDate
            ? Carbon::parse($fromDate)->startOfDay()
            : $end->copy()->startOfMonth();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $salesEnd = $end->gt($asOf) ? $asOf : $end;
        $workingDays = $this->workingDaysInRange($start, $end, $asOf);
        $workingDaysTotal = $workingDays['total'];
        $workingDaysGone  = $workingDays['gone'];

        $actual      = $this->erp->getTotalSalesValue(
            $start->format('Y-m-d'),
            $salesEnd->format('Y-m-d'),
            $oid
        );
        $avgDaily    = $actual / $workingDaysGone;
        $forecast    = $avgDaily * $workingDaysTotal;
        $target      = $this->rangeTarget($repName, $erpId, $start, $end);
        $targetDays  = $this->workingDaysInCoveredMonths($start, $end, $asOf)['total'];
        $dailyTarget = $targetDays > 0 ? round($target / $targetDays, 2) : 0.0;
        $achievement = $target > 0 ? round(($forecast / $target) * 100, 2) : 0.0;
        $actualAch   = $target > 0 ? round(($actual / $target) * 100, 2) : 0.0;
        $rate        = $this->localRules->commissionRateForAchievement('rep', $achievement);
        $commission  = round(($actual * $rate) / 100, 2);

        return [
            'actual'               => round($actual, 2),
            'forecast'             => round($forecast, 2),
            'target'               => round($target, 2),
            'daily_target'         => $dailyTarget,
            'commission_amount'    => $target > 0 ? $commission : 0.0,
            'commission_pct'       => $target > 0 ? $rate : 0.0,
            'achievement'          => $target > 0 ? $achievement : 0.0,
            'actual_achievement'   => $target > 0 ? $actualAch : 0.0,
            'working_days_total'   => $workingDaysTotal,
            'working_days_gone'    => $workingDaysGone,
        ];
    }

    /**
     * @deprecated Prefer getOidCurrentMonth — kept for callers that still pass a User.
     */
    public function getSalesRepCurrentMonth(User $rep, ?string $fromDate = null, ?string $toDate = null): array
    {
        if (!$rep->oid) {
            return [
                'actual' => 0.0, 'forecast' => 0.0, 'target' => 0.0, 'daily_target' => 0.0,
                'commission_amount' => 0.0, 'commission_pct' => 0.0, 'achievement' => 0.0,
                'actual_achievement' => 0.0, 'working_days_total' => 0, 'working_days_gone' => 0,
            ];
        }

        return $this->getOidCurrentMonth(
            $rep->oid,
            $fromDate,
            $toDate,
            $rep->name,
            $rep->erp_id ? (int) $rep->erp_id : null
        );
    }

    /**
     * Prefetch totals for many OIDs over a date range (concurrent).
     */
    public function prefetchCurrentMonthTotals(array $oids, ?string $fromDate = null, ?string $toDate = null): void
    {
        $asOf      = Carbon::now()->startOfDay();
        $ref       = $toDate ? Carbon::parse($toDate)->startOfDay() : $asOf->copy();
        $startDate = $fromDate ?? $ref->copy()->startOfMonth()->format('Y-m-d');
        $endDate   = ($ref->gt($asOf) ? $asOf : $ref)->format('Y-m-d');
        $queries   = [];
        foreach ($oids as $oid) {
            if (!$oid) {
                continue;
            }
            $queries[] = ['oid' => $oid, 'start' => $startDate, 'end' => $endDate];
        }
        $this->erp->getTotalSalesValuesBatch($queries);
    }

    /**
     * Returns daily sales for the last N days for a single rep OID.
     */
    public function getDailySales(string $oid, int $days = 15, ?string $toDate = null): array
    {
        $ref = $toDate ? Carbon::parse($toDate) : Carbon::now();
        $this->erp->getTotalSalesValuesBatch($this->erp->buildDailySalesQueries([$oid], $days, $toDate));

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date   = $ref->copy()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $sales  = 0;
            if (!$date->isFriday()) {
                $sales = $this->erp->getDailySalesTotal($dateStr, $oid);
            }
            $result[] = [
                'date'  => $date->format('M d'),
                'sales' => round($sales, 2),
            ];
        }
        return $result;
    }

    /**
     * Aggregates current-month actual sales for a list of OIDs.
     */
    public function getAggregatedSales(array $oids, ?string $fromDate = null, ?string $toDate = null): float
    {
        $this->prefetchCurrentMonthTotals($oids, $fromDate, $toDate);
        $ref       = $toDate ? Carbon::parse($toDate) : Carbon::now();
        $startDate = $fromDate ?? $ref->copy()->startOfMonth()->format('Y-m-d');
        $today     = $ref->format('Y-m-d');
        $total     = 0;
        foreach ($oids as $oid) {
            if (!$oid) continue;
            $total += $this->erp->getTotalSalesValue($startDate, $today, $oid);
        }
        return round($total, 2);
    }

    /**
     * Returns aggregated sales for a list of OIDs for a specific date range.
     */
    public function getAggregatedSalesByRange(array $oids, string $startDate, string $endDate): float
    {
        $queries = [];
        foreach ($oids as $oid) {
            if (!$oid) continue;
            $queries[] = ['oid' => $oid, 'start' => $startDate, 'end' => $endDate];
        }
        $this->erp->getTotalSalesValuesBatch($queries);

        $total = 0;
        foreach ($oids as $oid) {
            if (!$oid) continue;
            $total += $this->erp->getTotalSalesValue($startDate, $endDate, $oid);
        }
        return round($total, 2);
    }

    /**
     * Returns daily aggregated sales for a list of OIDs for the last N days.
     */
    public function getAggregatedDailySales(array $oids, int $days = 30, ?string $toDate = null): array
    {
        $ref = $toDate ? Carbon::parse($toDate) : Carbon::now();
        $from = $ref->copy()->subDays($days - 1)->format('Y-m-d');

        return $this->getAggregatedDailySalesByRange($oids, $from, $ref->format('Y-m-d'));
    }

    /**
     * Returns daily aggregated sales for a list of OIDs for an inclusive date range.
     * Future days are omitted; Fridays are included as 0.
     */
    public function getAggregatedDailySalesByRange(array $oids, string $fromDate, string $toDate): array
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $asOf = Carbon::now()->startOfDay();
        $chartEnd = $to->gt($asOf) ? $asOf : $to;

        $this->erp->getTotalSalesValuesBatch(
            $this->erp->buildDailySalesQueriesForRange($oids, $from->format('Y-m-d'), $chartEnd->format('Y-m-d'))
        );

        $result = [];
        for ($d = $from->copy(); $d->lte($chartEnd); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');
            $sales   = 0;
            if (!$d->isFriday()) {
                foreach ($oids as $oid) {
                    if (!$oid) {
                        continue;
                    }
                    $sales += $this->erp->getDailySalesTotal($dateStr, $oid);
                }
            }
            $result[] = [
                'date'  => $d->format('M d'),
                'sales' => round($sales, 2),
            ];
        }

        return $result;
    }

    public function erp(): ERPService
    {
        return $this->erp;
    }

    /**
     * Build rep metrics for all ERP salespersons that have an OID.
     * @return array{people: array<int, array>, reps: array<int, array>, oids: array<int, string>, total_sales: float, total_forecast: float}
     */
    public function getErpOrgRepMetrics(?string $fromDate = null, ?string $toDate = null): array
    {
        $people = $this->erp->getSalespersons(1, true);
        $oids   = array_values(array_filter(array_column($people, 'oid')));

        $this->prefetchCurrentMonthTotals($oids, $fromDate, $toDate);

        $reps             = [];
        $totalSales       = 0.0;
        $totalForecast    = 0.0;
        $totalTarget      = 0.0;
        $totalCommission  = 0.0;

        foreach ($people as $person) {
            $calc = $this->getOidCurrentMonth($person['oid'], $fromDate, $toDate, $person['name'], (int) $person['id']);
            $totalSales      += $calc['actual'];
            $totalForecast   += $calc['forecast'];
            $totalTarget     += $calc['target'];
            $totalCommission += $calc['commission_amount'];

            $reps[] = [
                'id'           => $person['id'],
                'name'         => $person['name'],
                'email'        => (string) $person['id'],
                'oid'          => $person['oid'],
                'supervisor'   => $this->localRules->supervisorNameForRep($person['name']),
                'target'       => $calc['target'],
                'sales'        => $calc['actual'],
                'actual'       => $calc['actual'],
                'actual_sales' => $calc['actual'],
                'forecast'     => $calc['forecast'],
                'achievement'  => $calc['achievement'],
                'actual_achievement' => $calc['actual_achievement'],
                'commission'   => $calc['commission_amount'],
                'percent'      => $calc['achievement'],
            ];
        }

        return [
            'people'            => $people,
            'reps'              => $reps,
            'oids'              => $oids,
            'total_sales'       => round($totalSales, 2),
            'total_forecast'    => round($totalForecast, 2),
            'total_target'      => round($totalTarget, 2),
            'total_commission'  => round($totalCommission, 2),
            // Forecast/projected achievement (commission pace).
            'achievement'       => $totalTarget > 0 ? round(($totalForecast / $totalTarget) * 100, 2) : 0.0,
            'forecast_achievement' => $totalTarget > 0 ? round(($totalForecast / $totalTarget) * 100, 2) : 0.0,
            'actual_achievement'   => $totalTarget > 0 ? round(($totalSales / $totalTarget) * 100, 2) : 0.0,
        ];
    }

    /**
     * ERP people whose name looks like a supervisor (مشرف).
     * @return array<int, array{id:int,name:string}>
     */
    public function getErpSupervisorList(): array
    {
        return collect($this->localRules->supervisorGroups())
            ->map(fn($group) => ['id' => $group['id'], 'name' => $group['name']])
            ->values()
            ->all();
    }

    public function localRules(): LocalSalesRulesService
    {
        return $this->localRules;
    }

    /**
     * Gets all descendant OIDs (sales reps) under a manager of any role.
     */
    public function getDescendantReps(User $manager): array
    {
        return match ($manager->role_id) {
            4 => $this->getSalesManagerReps($manager),
            3 => $this->getAreaManagerReps($manager),
            2 => $this->getSupervisorReps($manager),
            1 => [$manager->oid],
            default => [],
        };
    }

    private function getSalesManagerReps(User $manager): array
    {
        $areaManagerIds  = $manager->children()->pluck('id')->toArray();
        $supervisorIds   = User::whereIn('parent_id', $areaManagerIds)->pluck('id')->toArray();
        return User::whereNotNull('oid')
            ->whereIn('parent_id', $supervisorIds)
            ->pluck('oid')->toArray();
    }

    private function getAreaManagerReps(User $manager): array
    {
        $supervisorIds = $manager->children()->pluck('id')->toArray();
        return User::whereNotNull('oid')
            ->whereIn('parent_id', $supervisorIds)
            ->pluck('oid')->toArray();
    }

    private function getSupervisorReps(User $supervisor): array
    {
        return User::whereNotNull('oid')
            ->where('parent_id', $supervisor->id)
            ->pluck('oid')->toArray();
    }

    public function fmt(float $value): string
    {
        return '$' . number_format($value, 0, '.', ',');
    }

    public function fmtPct(float $value): string
    {
        return round($value, 1) . '%';
    }
}
