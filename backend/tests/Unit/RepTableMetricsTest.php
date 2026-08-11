<?php

namespace Tests\Unit;

use App\Http\Services\DashboardService;
use Tests\TestCase;

class RepTableMetricsTest extends TestCase
{
    private DashboardService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(DashboardService::class);
    }

    public function test_avg_daily_times_working_days_equals_forecast(): void
    {
        $m = $this->svc->computeRepTableMetrics(39634.1, 39634.1, 125000, 125000, 10, 27);

        $this->assertSame(3963.41, $m['avg_daily_sales']);
        $this->assertSame(107012.07, $m['forecast']);
        $this->assertEqualsWithDelta($m['avg_daily_sales'] * 27, $m['forecast'], 0.001);
    }

    public function test_radwan_ahmed_row(): void
    {
        $m = $this->svc->computeRepTableMetrics(39634.1, 39634.1, 125000, 125000, 10, 27);

        $this->assertSame(3963.41, $m['avg_daily_sales']);
        $this->assertSame(107012.07, $m['forecast']);
        $this->assertSame(85.61, $m['achievement']);
        $this->assertSame(1.0, $m['commission_pct']);
        $this->assertSame(396.34, $m['commission_amount']);
        $this->assertSame(31.71, $m['actual_achievement']);
    }

    public function test_imran_farman_row_uses_rounded_avg(): void
    {
        $m = $this->svc->computeRepTableMetrics(43136.66, 43136.66, 123000, 123000, 10, 27);

        $this->assertSame(4313.67, $m['avg_daily_sales']);
        $this->assertSame(116469.09, $m['forecast']);
        $this->assertEqualsWithDelta($m['avg_daily_sales'] * 27, $m['forecast'], 0.001);
        $this->assertSame(94.69, $m['achievement']);
        $this->assertSame(1.5, $m['commission_pct']);
        $this->assertSame(647.05, $m['commission_amount']);
    }

    public function test_mumtaz_and_islam_rows(): void
    {
        $mumtaz = $this->svc->computeRepTableMetrics(52878.52, 52878.52, 160000, 160000, 10, 27);
        $this->assertSame(5287.85, $mumtaz['avg_daily_sales']);
        $this->assertSame(142771.95, $mumtaz['forecast']);
        $this->assertEqualsWithDelta($mumtaz['avg_daily_sales'] * 27, $mumtaz['forecast'], 0.001);
        $this->assertSame(89.23, $mumtaz['achievement']);
        $this->assertSame(1.0, $mumtaz['commission_pct']);

        $islam = $this->svc->computeRepTableMetrics(42535.85, 42535.85, 125000, 125000, 10, 27);
        $this->assertSame(4253.59, $islam['avg_daily_sales']);
        $this->assertSame(114846.93, $islam['forecast']);
        $this->assertEqualsWithDelta($islam['avg_daily_sales'] * 27, $islam['forecast'], 0.001);
        $this->assertSame(91.88, $islam['achievement']);
        $this->assertSame(1.5, $islam['commission_pct']);
    }

    public function test_zero_sales_stay_zero(): void
    {
        $m = $this->svc->computeRepTableMetrics(0, 0, 125000, 125000, 10, 27);

        $this->assertSame(0.0, $m['avg_daily_sales']);
        $this->assertSame(0.0, $m['forecast']);
        $this->assertSame(0.0, $m['achievement']);
        $this->assertSame(0.0, $m['commission_pct']);
        $this->assertSame(0.0, $m['commission_amount']);
    }

    public function test_no_target_disables_commission(): void
    {
        $m = $this->svc->computeRepTableMetrics(39634.1, 39634.1, 0, 0, 10, 27);

        $this->assertSame(3963.41, $m['avg_daily_sales']);
        $this->assertSame(107012.07, $m['forecast']);
        $this->assertSame(0.0, $m['achievement']);
        $this->assertSame(0.0, $m['commission_pct']);
        $this->assertSame(0.0, $m['commission_amount']);
    }

    public function test_commission_tier_boundaries(): void
    {
        $below = $this->svc->computeRepTableMetrics(79400, 79400, 270000, 270000, 10, 27);
        $this->assertLessThan(79.5, $below['achievement']);
        $this->assertSame(0.0, $below['commission_pct']);

        $onePct = $this->svc->computeRepTableMetrics(80000, 80000, 270000, 270000, 10, 27);
        $this->assertGreaterThanOrEqual(79.5, $onePct['achievement']);
        $this->assertLessThan(90.5, $onePct['achievement']);
        $this->assertSame(1.0, $onePct['commission_pct']);

        $top = $this->svc->computeRepTableMetrics(96000, 96000, 270000, 270000, 10, 27);
        $this->assertGreaterThanOrEqual(95.5, $top['achievement']);
        $this->assertSame(2.0, $top['commission_pct']);
        $this->assertSame(1920.0, $top['commission_amount']);
    }
}
