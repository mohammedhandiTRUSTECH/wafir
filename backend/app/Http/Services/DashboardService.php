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
     * Calendar month used for forecast. Day-to-day range tweaks inside a month
     * do not change the projection — it always uses the full month.
     * A range that crosses months uses the month of the end date.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function forecastMonthBounds(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->startOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $month = ($from->year === $to->year && $from->month === $to->month)
            ? $from
            : $to;

        return [
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth()->startOfDay(),
        ];
    }

    /**
     * Sales-rep table columns. Avg is rounded first so:
     *   Avg Daily  = month actual / working days gone
     *   Forecast   = Avg Daily × working days in the month
     *   Forecast % = Forecast / month target
     *   Commission = range actual × rate(forecast %)
     *
     * @return array{
     *   avg_daily_sales: float,
     *   forecast: float,
     *   achievement: float,
     *   actual_achievement: float,
     *   commission_pct: float,
     *   commission_amount: float,
     *   forecast_commission: float
     * }
     */
    public function computeRepTableMetrics(
        float $monthActual,
        float $rangeActual,
        float $monthTarget,
        float $rangeTarget,
        int $monthDaysGone,
        int $monthDaysTotal,
        string $role = 'rep'
    ): array {
        $avgDaily = $monthDaysGone > 0
            ? round($monthActual / $monthDaysGone, 2)
            : 0.0;
        $forecast = round($avgDaily * max(0, $monthDaysTotal), 2);
        $achievement = $monthTarget > 0
            ? round(($forecast / $monthTarget) * 100, 2)
            : 0.0;
        $actualAch = $rangeTarget > 0
            ? round(($rangeActual / $rangeTarget) * 100, 2)
            : 0.0;
        $rate = $this->localRules->commissionRateForAchievement($role, $achievement);
        $hasTarget = $rangeTarget > 0;

        return [
            'avg_daily_sales'     => $avgDaily,
            'forecast'            => $forecast,
            'achievement'         => $achievement,
            'actual_achievement'  => $hasTarget ? $actualAch : 0.0,
            'commission_pct'      => $hasTarget ? $rate : 0.0,
            'commission_amount'   => $hasTarget ? round(($rangeActual * $rate) / 100, 2) : 0.0,
            'forecast_commission' => $hasTarget ? round(($forecast * $rate) / 100, 2) : 0.0,
        ];
    }

    /**
     * Metrics for an ERP salesperson OID over a date range (defaults to month-to-date).
     * Actual sales follow the selected range; forecast is always the calendar-month projection.
     */
    public function getOidCurrentMonth(
        string $oid,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $repName = null,
        ?int $erpId = null
    ): array
    {
        $today = Carbon::now()->startOfDay();
        $end   = $toDate ? Carbon::parse($toDate)->startOfDay() : $today->copy();
        $start = $fromDate
            ? Carbon::parse($fromDate)->startOfDay()
            : $end->copy()->startOfMonth();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        // Actual follows the picked range. Forecast/avg daily always use the
        // full calendar month through today (or through month-end if the month is over).
        $rangeAsOf = $end->lt($today) ? $end->copy() : $today->copy();

        $salesEnd = $end->gt($rangeAsOf) ? $rangeAsOf : $end;
        $workingDays = $this->workingDaysInRange($start, $end, $rangeAsOf);
        $workingDaysTotal = $workingDays['total'];
        $workingDaysGone  = $workingDays['gone'];

        $actual      = $this->erp->getTotalSalesValue(
            $start->format('Y-m-d'),
            $salesEnd->format('Y-m-d'),
            $oid
        );

        [$monthStart, $monthEnd] = $this->forecastMonthBounds($start, $end);
        $forecastAsOf = $monthEnd->lt($today) ? $monthEnd->copy() : $today->copy();
        $monthSalesEnd = $monthEnd->gt($forecastAsOf) ? $forecastAsOf : $monthEnd;
        $monthWorking  = $this->workingDaysInRange($monthStart, $monthEnd, $forecastAsOf);
        $monthActual   = $this->erp->getTotalSalesValue(
            $monthStart->format('Y-m-d'),
            $monthSalesEnd->format('Y-m-d'),
            $oid
        );
        $target      = $this->rangeTarget($repName, $erpId, $start, $end);
        $monthTarget = $this->rangeTarget($repName, $erpId, $monthStart, $monthEnd);
        $targetDays  = $this->workingDaysInCoveredMonths($start, $end, $rangeAsOf)['total'];
        $dailyTarget = $targetDays > 0 ? round($target / $targetDays, 2) : 0.0;
        $metrics     = $this->computeRepTableMetrics(
            $monthActual,
            $actual,
            $monthTarget,
            $target,
            (int) $monthWorking['gone'],
            (int) $monthWorking['total']
        );

        return [
            'actual'               => round($actual, 2),
            'forecast'             => $metrics['forecast'],
            'avg_daily_sales'      => $metrics['avg_daily_sales'],
            'target'               => round($target, 2),
            'daily_target'         => $dailyTarget,
            'commission_amount'    => $metrics['commission_amount'],
            'forecast_commission'  => $metrics['forecast_commission'],
            'commission_pct'       => $metrics['commission_pct'],
            'achievement'          => $metrics['achievement'],
            'actual_achievement'   => $metrics['actual_achievement'],
            'working_days_total'   => $workingDaysTotal,
            'working_days_gone'    => $workingDaysGone,
            'forecast_month'       => $monthStart->format('Y-m'),
            'month_target'         => round($monthTarget, 2),
            'month_actual'         => round($monthActual, 2),
            'month_working_days_total' => $monthWorking['total'],
            'month_working_days_gone'  => $monthWorking['gone'],
        ];
    }

    /**
     * @deprecated Prefer getOidCurrentMonth — kept for callers that still pass a User.
     */
    public function getSalesRepCurrentMonth(User $rep, ?string $fromDate = null, ?string $toDate = null): array
    {
        if (!$rep->oid) {
            return [
                'actual' => 0.0, 'forecast' => 0.0, 'avg_daily_sales' => 0.0, 'target' => 0.0, 'daily_target' => 0.0,
                'commission_amount' => 0.0, 'forecast_commission' => 0.0, 'commission_pct' => 0.0, 'achievement' => 0.0,
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
        $today = Carbon::now()->startOfDay();
        $end   = $toDate ? Carbon::parse($toDate)->startOfDay() : $today->copy();
        $start = $fromDate
            ? Carbon::parse($fromDate)->startOfDay()
            : $end->copy()->startOfMonth();
        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }
        $rangeAsOf = $end->lt($today) ? $end->copy() : $today->copy();

        $rangeEnd = ($end->gt($rangeAsOf) ? $rangeAsOf : $end)->format('Y-m-d');
        $rangeStart = $start->format('Y-m-d');
        [$monthStart, $monthEnd] = $this->forecastMonthBounds($start, $end);
        $forecastAsOf = $monthEnd->lt($today) ? $monthEnd->copy() : $today->copy();
        $monthEndClamped = ($monthEnd->gt($forecastAsOf) ? $forecastAsOf : $monthEnd)->format('Y-m-d');
        $monthStartDate  = $monthStart->format('Y-m-d');

        $queries = [];
        $seen    = [];
        foreach ($oids as $oid) {
            if (!$oid) {
                continue;
            }
            foreach ([
                [$rangeStart, $rangeEnd],
                [$monthStartDate, $monthEndClamped],
            ] as [$from, $to]) {
                $key = $oid . '|' . $from . '|' . $to;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $queries[] = ['oid' => $oid, 'start' => $from, 'end' => $to];
            }
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
                'avg_daily_sales' => $calc['avg_daily_sales'] ?? 0.0,
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
