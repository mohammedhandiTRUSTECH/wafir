"use client";

import { ArrowsPointingOutIcon, ChevronDownIcon, LockClosedIcon, XMarkIcon } from "@heroicons/react/24/outline";
import {
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table";
import React, { useEffect, useMemo, useRef, useState } from "react";
import {
  FaBullseye,
  FaCalendarDay,
  FaChartLine,
  FaDollarSign,
  FaSort,
  FaSortDown,
  FaSortUp,
  FaWaveSquare,
} from "react-icons/fa";
import { useSearchParams } from "react-router-dom";

import CommissionChart from "../components/dashboard/CommissionChart";
import SalesVsTargetChart from "../components/dashboard/SalesVsTargetChart";
import api from "../services/api";

const fmt = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;
const fmtPct = (n) => Number(n || 0).toFixed(1) + "%";
const fmtSigned = (n) => {
  const v = Number(n || 0);
  const sign = v > 0 ? "+" : v < 0 ? "−" : "";
  return (
    <>
      {sign}
      {Math.abs(v).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span>
    </>
  );
};

function paceStatus(paceDelta) {
  if (!Number.isFinite(paceDelta)) return { label: "—", tone: "neutral" };
  if (paceDelta >= 2) return { label: "Ahead", tone: "good" };
  if (paceDelta <= -2) return { label: "Behind", tone: "bad" };
  return { label: "On pace", tone: "ok" };
}

function forecastStatus(pct, hasTarget) {
  if (!hasTarget) return { label: "No target", tone: "neutral" };
  if (pct >= 100) return { label: "Hit", tone: "good" };
  if (pct >= 90) return { label: "At risk", tone: "warn" };
  return { label: "Miss", tone: "bad" };
}

const chipTone = {
  good: "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200",
  ok: "bg-sky-50 text-sky-700 ring-1 ring-sky-200",
  warn: "bg-amber-50 text-amber-800 ring-1 ring-amber-200",
  bad: "bg-rose-50 text-rose-700 ring-1 ring-rose-200",
  neutral: "bg-gray-100 text-gray-600 ring-1 ring-gray-200",
};

const StatusChip = ({ label, tone = "neutral" }) => (
  <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ${chipTone[tone] || chipTone.neutral}`}>
    {label}
  </span>
);

function CompareBar({ valuePct, left, right }) {
  const width = Math.min(Math.max(Number(valuePct) || 0, 0), 100);
  return (
    <div className="mt-3">
      <div className="h-2 overflow-hidden rounded-full bg-gray-100">
        <div className="h-full rounded-full bg-zinc-900 transition-[width] duration-500" style={{ width: `${width}%` }} />
      </div>
      <div className="mt-1.5 flex justify-between gap-3 text-[11px] tabular-nums text-gray-500">
        <span>{left}</span>
        <span className="text-right">{right}</span>
      </div>
    </div>
  );
}

const salesColumns = [
  {
    accessorKey: "name",
    header: "Name",
    cell: ({ row }) => (
      <div>
        <p className="font-medium">{row.original.name}</p>
        <p className="text-xs text-gray-500">{row.original.email}</p>
      </div>
    ),
  },
  { accessorKey: "supervisor", header: "Supervisor" },
  {
    accessorKey: "target",
    header: ({ column }) => (
      <button onClick={() => column.toggleSorting(column.getIsSorted() === "asc")} className="flex items-center gap-2">
        Target
        {column.getIsSorted() === "asc" && <FaSortUp />}
        {column.getIsSorted() === "desc" && <FaSortDown />}
        {!column.getIsSorted() && <FaSort className="opacity-40" />}
      </button>
    ),
    cell: ({ getValue }) => fmt(getValue()),
    sortingFn: (a, b) => a.original.target - b.original.target,
  },
  { accessorKey: "actual",          header: "Actual Sales",    cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "avg_daily_sales", header: "Avg Daily Sales", cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "forecast",        header: "Forecast",        cell: ({ getValue }) => fmt(getValue()) },
  {
    accessorKey: "achievement",
    header: "Forecast Ach.",
    cell: ({ row }) => (
      <span className={`rounded-md px-2 py-1 text-xs font-medium ${row.original.success ? "bg-green-100 text-green-700" : "bg-blue-100 text-blue-700"}`}>
        {fmtPct(row.original.achievement)}
      </span>
    ),
  },
  {
    accessorKey: "commission_pct",
    header: "Rate",
    cell: ({ getValue }) => (
      <span className="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
        {fmtPct(getValue())}
      </span>
    ),
  },
  {
    accessorKey: "commission",
    header: () => <div className="text-right">Commission</div>,
    cell: ({ getValue }) => <div className="text-right font-medium">{fmt(getValue())}</div>,
  },
];

function pad(n) {
  return String(n).padStart(2, "0");
}

function toYmd(date) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function monthBounds(ym) {
  const [y, m] = ym.split("-").map(Number);
  const from = new Date(y, m - 1, 1);
  const to = new Date(y, m, 0);
  return { from: toYmd(from), to: toYmd(to) };
}

function currentMonthValue() {
  const now = new Date();
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}`;
}

function formatRangeLabel(from, to) {
  const opts = { month: "short", day: "numeric", year: "numeric" };
  const a = new Date(from + "T00:00:00").toLocaleDateString(undefined, opts);
  const b = new Date(to + "T00:00:00").toLocaleDateString(undefined, opts);
  return `${a} – ${b}`;
}

function formatDayLabel(ymd) {
  return new Date(ymd + "T00:00:00").toLocaleDateString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function formatMonthLabel(ym) {
  const [y, m] = (ym || "").split("-").map(Number);
  if (!y || !m) return ym || "";
  return new Date(y, m - 1, 1).toLocaleDateString(undefined, { month: "long", year: "numeric" });
}

const isYm = (v) => /^\d{4}-\d{2}$/.test(v || "");
const isYmd = (v) => /^\d{4}-\d{2}-\d{2}$/.test(v || "");

function readSelectionFromSearch(search) {
  const params = new URLSearchParams(search);
  const month = isYm(params.get("month")) ? params.get("month") : currentMonthValue();
  const bounds = monthBounds(month);

  let from = isYmd(params.get("from")) ? params.get("from") : bounds.from;
  let to = isYmd(params.get("to")) ? params.get("to") : bounds.to;
  if (from > to) {
    [from, to] = [to, from];
  }

  return {
    mode: params.get("mode") === "range" ? "range" : "month",
    month,
    from,
    to,
  };
}

export default function Dashboard() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [initialSelection] = useState(() => readSelectionFromSearch(window.location.search));

  const [mode, setMode] = useState(initialSelection.mode); // "month" | "range"
  const [month, setMonth] = useState(initialSelection.month);
  const [fromDate, setFromDate] = useState(initialSelection.from);
  const [toDate, setToDate] = useState(initialSelection.to);
  const [applied, setApplied] = useState({ from: initialSelection.from, to: initialSelection.to });
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [workingDaysInput, setWorkingDaysInput] = useState("");
  const [savingDays, setSavingDays] = useState(false);
  const [reloadKey, setReloadKey] = useState(0);
  const [expandedChart, setExpandedChart] = useState(null); // "sales" | "commission" | "commission-forecast" | null
  const [daysOpen, setDaysOpen] = useState(false);
  const [monthOpen, setMonthOpen] = useState(false);
  const [pickerYear, setPickerYear] = useState(() => Number(initialSelection.month.split("-")[0]));
  const daysPopoverRef = useRef(null);
  const monthPopoverRef = useRef(null);

  const queryRange = useMemo(() => {
    if (mode === "month") {
      return monthBounds(month);
    }
    return applied;
  }, [mode, month, applied]);

  useEffect(() => {
    const next = new URLSearchParams(searchParams);
    next.set("mode", mode);
    next.set("month", month);
    if (mode === "range") {
      next.set("from", applied.from);
      next.set("to", applied.to);
    } else {
      next.delete("from");
      next.delete("to");
    }

    if (next.toString() !== searchParams.toString()) {
      setSearchParams(next, { replace: true });
    }
  }, [mode, month, applied, searchParams, setSearchParams]);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);

    const params = new URLSearchParams({
      from_date: queryRange.from,
      to_date: queryRange.to,
    });

    api.get(`/dashboard?${params}`, { signal: controller.signal })
      .then((res) => {
        const payload = res.data.data;
        setData(payload);
        if (payload?.stats?.working_days_total != null) {
          setWorkingDaysInput(String(payload.stats.working_days_total));
        }
      })
      .catch((err) => {
        if (err.name !== "CanceledError" && err.message !== "canceled") {
          console.error(err);
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setLoading(false);
        }
      });

    return () => controller.abort();
  }, [queryRange.from, queryRange.to, reloadKey]);

  useEffect(() => {
    if (!daysOpen && !monthOpen) return undefined;
    const onPointerDown = (event) => {
      if (daysOpen && !daysPopoverRef.current?.contains(event.target)) {
        setDaysOpen(false);
      }
      if (monthOpen && !monthPopoverRef.current?.contains(event.target)) {
        setMonthOpen(false);
      }
    };
    document.addEventListener("mousedown", onPointerDown);
    return () => document.removeEventListener("mousedown", onPointerDown);
  }, [daysOpen, monthOpen]);

  const stats = data?.stats || {};
  const teamPerf = data?.team_performance || [];
  const salesReps = data?.sales_reps || [];
  const chartSales = data?.chart_sales_vs_target || { labels: [], actual: [], daily_target: [] };
  const chartComm = data?.chart_commission || { labels: [], data: [] };
  const chartCommForecast = data?.chart_commission_forecast || { labels: [], data: [] };

  const workingDaysLabel = stats.working_days_total
    ? `${stats.working_days_gone || 0} / ${stats.working_days_total} working days`
    : "Working days";

  const monthlyTarget = Number(stats.monthly_target || 0);
  const totalSales = Number(stats.total_sales || 0);
  const totalForecast = Number(stats.total_forecast || 0);
  const forecastMonth = stats.forecast_month || month;
  const forecastMonthTarget = Number(
    stats.forecast_month_target != null ? stats.forecast_month_target : 0
  );

  const dailyTarget = stats.daily_target != null
    ? Number(stats.daily_target)
    : (stats.working_days_total > 0
      ? monthlyTarget / Number(stats.working_days_total)
      : 0);

  // Actual = sales/target; forecast = projected end-of-period/target.
  const actualAchievement = stats.actual_achievement != null
    ? Number(stats.actual_achievement)
    : (monthlyTarget > 0 ? (totalSales / monthlyTarget) * 100 : 0);
  const forecastAchievement = forecastMonthTarget > 0
    ? (totalForecast / forecastMonthTarget) * 100
    : (stats.forecast_achievement != null
      ? Number(stats.forecast_achievement)
      : 0);

  const rangeLabel = formatRangeLabel(queryRange.from, queryRange.to);
  const todayYmd = toYmd(new Date());
  const untilDate = queryRange.to < todayYmd ? queryRange.to : todayYmd;
  const untilDayLabel = formatDayLabel(untilDate);
  const daysGone = Number(stats.working_days_gone || 0);
  const daysTotal = Number(stats.working_days_total || 0);
  const daysRemain = Math.max(0, daysTotal - daysGone);
  const daysPct = daysTotal > 0 ? Math.min(100, (daysGone / daysTotal) * 100) : 0;
  const daysHint = stats.working_days_override
    ? "saved override"
    : "Fridays excluded";

  const avgDailySales = Number(stats.avg_daily_sales || 0);
  const salesGap = Math.max(0, monthlyTarget - totalSales);
  const forecastGap = Math.max(0, forecastMonthTarget - totalForecast);
  const forecastOver = Math.max(0, totalForecast - forecastMonthTarget);
  const dailyVariance = avgDailySales - dailyTarget;
  const dailyHitPct = dailyTarget > 0 ? (avgDailySales / dailyTarget) * 100 : 0;
  const paceDelta = actualAchievement - daysPct;
  const monthPace = paceStatus(paceDelta);
  const closeOutlook = forecastStatus(forecastAchievement, forecastMonthTarget > 0);

  const enterRangeMode = () => {
    if (mode !== "range") {
      const bounds = monthBounds(month);
      setFromDate(bounds.from);
      setToDate(bounds.to);
      setApplied(bounds);
    }
    setDaysOpen(false);
    setMonthOpen(false);
    setMode("range");
  };

  const pickMonth = (year, monthIndex) => {
    setMonth(`${year}-${pad(monthIndex)}`);
    setMonthOpen(false);
  };

  const applyCustomRange = () => {
    if (!fromDate || !toDate) return;
    const from = fromDate <= toDate ? fromDate : toDate;
    const to = fromDate <= toDate ? toDate : fromDate;
    setFromDate(from);
    setToDate(to);
    setApplied({ from, to });
  };

  const saveWorkingDays = async () => {
    const days = parseInt(workingDaysInput, 10);
    if (!Number.isFinite(days) || days < 1 || days > 31) {
      alert("Working days must be between 1 and 31");
      return;
    }
    const [y, m] = month.split("-").map(Number);
    setSavingDays(true);
    try {
      await api.put("/dashboard/working-days", {
        year: y,
        month: m,
        working_days: days,
      });
      setReloadKey((k) => k + 1);
      setDaysOpen(false);
    } catch (e) {
      console.error(e);
      alert("Failed to save working days");
    } finally {
      setSavingDays(false);
    }
  };

  const resetWorkingDays = async () => {
    const [y, m] = month.split("-").map(Number);
    setSavingDays(true);
    try {
      await api.delete("/dashboard/working-days", {
        params: { year: y, month: m },
      });
      setReloadKey((k) => k + 1);
      setDaysOpen(false);
    } catch (e) {
      console.error(e);
      alert("Failed to reset working days");
    } finally {
      setSavingDays(false);
    }
  };

  return (
    <div className="min-h-screen space-y-4 bg-gray-100">
      {/* Header */}
      <div className="mb-8">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h1 className="text-2xl font-semibold">Sales Dashboard</h1>
            <p className="text-sm text-gray-500">Overview of sales performance and targets</p>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            {mode === "month" ? (
              <button
                type="button"
                onClick={enterRangeMode}
                className="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-600 shadow-sm hover:border-gray-300 hover:text-gray-900"
              >
                Range
              </button>
            ) : (
              <button
                type="button"
                onClick={() => setMode("month")}
                className="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-600 shadow-sm hover:border-gray-300 hover:text-gray-900"
              >
                Back to month
              </button>
            )}

            {mode === "month" && (
              <div className="relative" ref={daysPopoverRef}>
                <button
                  type="button"
                  onClick={() => {
                    setDaysOpen((open) => !open);
                    setMonthOpen(false);
                  }}
                  className={`h-9 rounded-lg border px-3 text-sm shadow-sm ${daysOpen ? "border-gray-900 bg-gray-900 text-white" : "border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900"}`}
                >
                  Days
                </button>
                {daysOpen && (
                  <div className="absolute right-0 z-20 mt-2 w-64 rounded-xl border bg-white p-3 shadow-lg">
                    <p className="text-sm font-medium text-gray-900">Working days</p>
                    <p className="mt-0.5 text-xs text-gray-500">{daysHint}</p>
                    <div className="mt-3 flex items-center gap-2">
                      <input
                        type="number"
                        min={1}
                        max={31}
                        value={workingDaysInput}
                        onChange={(e) => setWorkingDaysInput(e.target.value)}
                        className="h-9 w-20 rounded-lg border px-3 text-sm text-gray-900"
                      />
                      <button
                        type="button"
                        onClick={saveWorkingDays}
                        disabled={savingDays || loading}
                        className="h-9 rounded-lg bg-black px-3 text-sm text-white hover:bg-gray-800 disabled:opacity-50"
                      >
                        {savingDays ? "Saving…" : "Save"}
                      </button>
                      {stats.working_days_override && (
                        <button
                          type="button"
                          onClick={resetWorkingDays}
                          disabled={savingDays || loading}
                          className="h-9 rounded-lg border px-3 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                        >
                          Use auto
                        </button>
                      )}
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        <div className="mt-5 rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm sm:p-5">
          {mode === "month" ? (
            <div className="relative inline-block" ref={monthPopoverRef}>
              <button
                type="button"
                onClick={() => {
                  setPickerYear(Number(month.split("-")[0]));
                  setMonthOpen((open) => !open);
                  setDaysOpen(false);
                }}
                className="inline-flex items-center gap-1.5 rounded-lg px-1 py-0.5 text-left hover:bg-gray-50"
                aria-expanded={monthOpen}
                aria-haspopup="dialog"
              >
                <span className="text-xl font-semibold tracking-tight text-gray-900">{formatMonthLabel(month)}</span>
                <ChevronDownIcon className={`h-5 w-5 text-gray-400 transition ${monthOpen ? "rotate-180" : ""}`} />
              </button>
              {monthOpen && (
                <div className="absolute left-0 z-20 mt-2 w-72 rounded-xl border bg-white p-3 shadow-lg">
                  <div className="mb-2 flex items-center justify-between">
                    <button
                      type="button"
                      onClick={() => setPickerYear((year) => year - 1)}
                      className="h-8 w-8 rounded-lg text-gray-600 hover:bg-gray-100"
                      aria-label="Previous year"
                    >
                      ‹
                    </button>
                    <p className="text-sm font-medium text-gray-900">{pickerYear}</p>
                    <button
                      type="button"
                      onClick={() => setPickerYear((year) => year + 1)}
                      className="h-8 w-8 rounded-lg text-gray-600 hover:bg-gray-100"
                      aria-label="Next year"
                    >
                      ›
                    </button>
                  </div>
                  <div className="grid grid-cols-3 gap-1">
                    {Array.from({ length: 12 }, (_, index) => {
                      const monthIndex = index + 1;
                      const selected = month === `${pickerYear}-${pad(monthIndex)}`;
                      const label = new Date(pickerYear, index, 1).toLocaleDateString(undefined, { month: "short" });
                      return (
                        <button
                          key={monthIndex}
                          type="button"
                          onClick={() => pickMonth(pickerYear, monthIndex)}
                          className={`h-9 rounded-lg text-sm ${selected ? "bg-black text-white" : "text-gray-700 hover:bg-gray-100"}`}
                        >
                          {label}
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div className="flex flex-wrap items-end gap-2">
              <label className="flex flex-col gap-1 text-xs text-gray-500">
                From
                <input
                  type="date"
                  value={fromDate}
                  onChange={(e) => setFromDate(e.target.value)}
                  className="h-9 rounded-lg border bg-white px-3 text-sm text-gray-900"
                />
              </label>
              <label className="flex flex-col gap-1 text-xs text-gray-500">
                To
                <input
                  type="date"
                  value={toDate}
                  onChange={(e) => setToDate(e.target.value)}
                  className="h-9 rounded-lg border bg-white px-3 text-sm text-gray-900"
                />
              </label>
              <button
                type="button"
                onClick={applyCustomRange}
                className="h-9 rounded-lg bg-black px-4 text-sm text-white hover:bg-gray-800"
              >
                Apply
              </button>
            </div>
          )}

          {daysTotal > 0 && (
            <div className="mt-4 flex flex-col gap-4 border-t border-gray-100 pt-4 sm:flex-row sm:items-stretch sm:gap-8">
              <div className="min-w-0 flex-1">
                <div className="flex items-baseline justify-between gap-3">
                  <span className="text-sm font-medium text-gray-900">
                    {daysGone} / {daysTotal} working days
                  </span>
                </div>
                <div className="mt-2.5 h-2 overflow-hidden rounded-full bg-gray-100">
                  <div
                    className="h-full rounded-full bg-gradient-to-r from-zinc-800 to-black transition-[width] duration-500"
                    style={{ width: `${daysPct}%` }}
                  />
                </div>
                <dl className="mt-3 grid gap-1.5 sm:grid-cols-2">
                  {[
                    {
                      label: "Month",
                      value: formatMonthLabel(mode === "range" ? forecastMonth : month),
                      swatch: "bg-zinc-500",
                    },
                    {
                      label: "Range",
                      value: rangeLabel,
                      swatch: "bg-zinc-400",
                    },
                  ].map((row) => (
                    <div
                      key={row.label}
                      className="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-3 py-2"
                    >
                      <dt className="flex items-center gap-2 text-[11px] font-medium uppercase tracking-wide text-gray-500">
                        <span className={`h-1.5 w-1.5 rounded-full ${row.swatch}`} />
                        {row.label}
                      </dt>
                      <dd className="text-right text-sm font-semibold text-gray-900">{row.value}</dd>
                    </div>
                  ))}
                </dl>
              </div>

              <dl className="grid grid-cols-3 gap-2 sm:flex sm:w-56 sm:shrink-0 sm:flex-col sm:gap-1.5">
                {[
                  { label: "Working days", value: daysTotal, swatch: "bg-zinc-800" },
                  { label: "Remain days", value: daysRemain, swatch: "bg-zinc-300" },
                  { label: "Worked days", value: daysGone, swatch: "bg-black" },
                ].map((row) => (
                  <div
                    key={row.label}
                    className="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-3 py-2"
                  >
                    <dt className="flex items-center gap-2 text-[11px] font-medium uppercase tracking-wide text-gray-500">
                      <span className={`h-1.5 w-1.5 rounded-full ${row.swatch}`} />
                      {row.label}
                    </dt>
                    <dd className="text-sm font-semibold tabular-nums text-gray-900">{row.value}</dd>
                  </div>
                ))}
              </dl>
            </div>
          )}
        </div>
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center text-gray-400">Loading…</div>
      ) : (
        <>
          {/* Stats Cards */}
          <div className="mb-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <TargetCard
              target={monthlyTarget}
              achievement={actualAchievement}
              timePct={daysPct}
              gap={salesGap}
              status={monthPace}
            />
            <MetricCard
              title="Total Sales"
              icon={<FaDollarSign />}
              value={fmt(totalSales)}
              chip={<StatusChip label={fmtPct(actualAchievement)} tone={monthPace.tone} />}
              bar={
                <CompareBar
                  valuePct={actualAchievement}
                  left={`Booked ${fmtPct(actualAchievement)}`}
                  right="of target 100%"
                />
              }
              rows={[{ label: "Gap to target", value: fmt(salesGap) }]}
            />
            <MetricCard
              title="Daily Target"
              icon={<FaCalendarDay />}
              value={fmt(dailyTarget)}
              chip={<StatusChip label={daysRemain > 0 ? `${daysRemain} days left` : "Closed"} tone="neutral" />}
              bar={
                <CompareBar
                  valuePct={dailyHitPct}
                  left={<>Avg {fmt(avgDailySales)}</>}
                  right={<>Plan {fmt(dailyTarget)}</>}
                />
              }
              rows={[
                daysRemain > 0
                  ? { label: "Working days", value: workingDaysLabel }
                  : { label: "Month is closed", value: "" },
              ]}
            />
            <MetricCard
              title="Avg Daily Sales"
              icon={<FaWaveSquare />}
              value={fmt(avgDailySales)}
              chip={
                <StatusChip
                  label={
                    dailyTarget > 0
                      ? `${dailyHitPct >= 100 ? "+" : ""}${fmtPct(dailyHitPct - 100)} vs tgt`
                      : "No daily tgt"
                  }
                  tone={dailyTarget <= 0 ? "neutral" : dailyVariance >= 0 ? "good" : "bad"}
                />
              }
              bar={
                <CompareBar
                  valuePct={dailyHitPct}
                  left={`${fmtPct(dailyHitPct)} of daily target`}
                  right={fmt(dailyTarget)}
                />
              }
              rows={[
                { label: "Vs daily target", value: fmtSigned(dailyVariance), className: dailyVariance >= 0 ? "text-emerald-700" : "text-rose-700" },
              ]}
            />
            <MetricCard
              title="Total Forecast"
              icon={<FaChartLine />}
              value={fmt(totalForecast)}
              chip={<StatusChip label={`${fmtPct(forecastAchievement)} of target`} tone={closeOutlook.tone} />}
              bar={
                <CompareBar
                  valuePct={forecastAchievement}
                  left={`Forecast ${fmtPct(forecastAchievement)}`}
                  right="of target 100%"
                />
              }
              rows={[
                {
                  label: forecastOver > 0 ? "Over target" : "Left to target",
                  value: fmt(forecastOver > 0 ? forecastOver : forecastGap),
                },
              ]}
            />
            <ForecastAchievementCard
              forecast={totalForecast}
              target={forecastMonthTarget}
              monthLabel={formatMonthLabel(forecastMonth)}
              achievement={forecastAchievement}
              outlook={closeOutlook}
            />
          </div>

          {/* Charts: earned + forecast side by side, sales vs target full width */}
          <div className="mb-6 grid gap-6 lg:grid-cols-2">
            <div className="rounded-2xl border bg-white p-6">
              <div className="mb-4 flex items-start justify-between gap-2">
                <div>
                  <h3 className="font-medium">Commission Earned by Sales Rep</h3>
                  <p className="mt-0.5 text-xs text-gray-500">Until {untilDayLabel}</p>
                </div>
                <button
                  type="button"
                  onClick={() => setExpandedChart("commission")}
                  className="rounded-lg border p-1.5 text-gray-500 hover:bg-gray-50 hover:text-gray-800"
                  title="Expand chart"
                >
                  <ArrowsPointingOutIcon className="h-4 w-4" />
                </button>
              </div>
              <div className="h-72">
                <CommissionChart
                  labels={chartComm.labels}
                  data={chartComm.data}
                  datasetLabel="Commission earned"
                />
              </div>
            </div>
            <div className="rounded-2xl border bg-white p-6">
              <div className="mb-4 flex items-start justify-between gap-2">
                <div>
                  <h3 className="font-medium">Forecast Commission by Sales Rep</h3>
                  <p className="mt-0.5 text-xs text-gray-500">{formatMonthLabel(forecastMonth)} projection</p>
                </div>
                <button
                  type="button"
                  onClick={() => setExpandedChart("commission-forecast")}
                  className="rounded-lg border p-1.5 text-gray-500 hover:bg-gray-50 hover:text-gray-800"
                  title="Expand chart"
                >
                  <ArrowsPointingOutIcon className="h-4 w-4" />
                </button>
              </div>
              <div className="h-72">
                <CommissionChart
                  labels={chartCommForecast.labels}
                  data={chartCommForecast.data}
                  datasetLabel="Forecast commission"
                  color="#10b981"
                />
              </div>
            </div>
          </div>

          <div className="mb-10 rounded-2xl border bg-white p-6">
            <div className="mb-4 flex items-center justify-between gap-2">
              <h3 className="font-medium">Sales vs Target ({rangeLabel})</h3>
              <button
                type="button"
                onClick={() => setExpandedChart("sales")}
                className="rounded-lg border p-1.5 text-gray-500 hover:bg-gray-50 hover:text-gray-800"
                title="Expand chart"
              >
                <ArrowsPointingOutIcon className="h-4 w-4" />
              </button>
            </div>
            <div className="h-72">
              <SalesVsTargetChart
                labels={chartSales.labels}
                actual={chartSales.actual}
                dailyTarget={chartSales.daily_target}
              />
            </div>
          </div>

          {expandedChart === "sales" && (
            <ChartExpandModal
              title={`Sales vs Target (${rangeLabel})`}
              onClose={() => setExpandedChart(null)}
            >
              <SalesVsTargetChart
                labels={chartSales.labels}
                actual={chartSales.actual}
                dailyTarget={chartSales.daily_target}
                expanded
              />
            </ChartExpandModal>
          )}

          {expandedChart === "commission" && (
            <ChartExpandModal
              title={`Commission Earned by Sales Rep (until ${untilDayLabel})`}
              onClose={() => setExpandedChart(null)}
            >
              <CommissionChart
                labels={chartComm.labels}
                data={chartComm.data}
                datasetLabel="Commission earned"
                expanded
              />
            </ChartExpandModal>
          )}

          {expandedChart === "commission-forecast" && (
            <ChartExpandModal
              title={`Forecast Commission by Sales Rep (${formatMonthLabel(forecastMonth)})`}
              onClose={() => setExpandedChart(null)}
            >
              <CommissionChart
                labels={chartCommForecast.labels}
                data={chartCommForecast.data}
                datasetLabel="Forecast commission"
                color="#10b981"
                expanded
              />
            </ChartExpandModal>
          )}


          {/* Sales Reps Table */}
          <div className="rounded-2xl border bg-white p-6">
            <h3 className="mb-4 font-medium">Sales Representatives Performance</h3>
            <SalesRepsTable data={salesReps} />
          </div>
        </>
      )}
    </div>
  );
}

function SalesRepsTable({ data }) {
  const [sorting,       setSorting]       = React.useState([]);
  const [pagination,    setPagination]    = React.useState({ pageIndex: 0, pageSize: 10 });
  const [columnFilters, setColumnFilters] = React.useState([]);

  const table = useReactTable({
    data,
    columns: salesColumns,
    state: { sorting, pagination, columnFilters },
    onSortingChange:       setSorting,
    onPaginationChange:    setPagination,
    onColumnFiltersChange: setColumnFilters,
    getCoreRowModel:       getCoreRowModel(),
    getSortedRowModel:     getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getFilteredRowModel:   getFilteredRowModel(),
  });

  return (
    <>
      <div className="mb-4">
        <input
          type="text"
          placeholder="Search by name…"
          value={table.getColumn("name")?.getFilterValue() || ""}
          onChange={(e) => table.getColumn("name")?.setFilterValue(e.target.value)}
          className="w-full max-w-sm rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-black"
        />
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="border-b text-left text-gray-500">
            {table.getHeaderGroups().map((hg) => (
              <tr key={hg.id}>
                {hg.headers.map((h) => (
                  <th key={h.id} className="py-3">{flexRender(h.column.columnDef.header, h.getContext())}</th>
                ))}
              </tr>
            ))}
          </thead>
          <tbody>
            {table.getRowModel().rows.map((row) => (
              <tr key={row.id} className="border-b last:border-none">
                {row.getVisibleCells().map((cell) => (
                  <td key={cell.id} className="py-4">{flexRender(cell.column.columnDef.cell, cell.getContext())}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="mt-4 flex items-center justify-between">
        <p className="text-sm text-gray-500">Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}</p>
        <div className="flex gap-2">
          <button onClick={() => table.previousPage()} disabled={!table.getCanPreviousPage()} className="rounded border px-3 py-1 text-sm disabled:opacity-50">Previous</button>
          <button onClick={() => table.nextPage()}     disabled={!table.getCanNextPage()}     className="rounded border px-3 py-1 text-sm disabled:opacity-50">Next</button>
        </div>
      </div>
    </>
  );
}

const MetricCard = ({ title, icon, value, chip, bar, rows = [] }) => (
  <div className="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <div className="mb-3 flex items-start justify-between gap-2">
      <div className="flex min-w-0 items-center gap-2">
        <div className="rounded-md bg-gray-100 p-1.5 text-gray-500">{icon}</div>
        <p className="text-lg font-semibold text-gray-800">{title}</p>
      </div>
      {chip}
    </div>
    <p className="text-2xl font-semibold tracking-tight text-gray-900 tabular-nums">{value}</p>
    {bar}
    <div className="min-h-3 flex-1" />
    {rows.length > 0 && (
      <dl className="mt-3 space-y-1.5 border-t border-gray-100 pt-3">
        {rows.map((row) => (
          <div key={row.label} className="flex items-center justify-between gap-3 text-sm">
            <dt className="text-gray-500">{row.label}</dt>
            {row.value !== "" && row.value != null && (
              <dd className={`text-right font-medium tabular-nums ${row.className || "text-gray-900"}`}>{row.value}</dd>
            )}
          </div>
        ))}
      </dl>
    )}
  </div>
);

const ForecastAchievementCard = ({ forecast, target, monthLabel, achievement, outlook }) => {
  const pct = Math.max(0, Number(achievement) || 0);
  const hasTarget = Number(target) > 0;
  const over = hasTarget && pct >= 100;
  const remaining = Math.max(0, Number(target) - Number(forecast));
  const ringPct = Math.min(pct, 100);
  const radius = 58;
  const circ = 2 * Math.PI * radius;
  const dash = (ringPct / 100) * circ;
  const shortMonth = (monthLabel || "").replace(/\s+\d{4}$/, "") || monthLabel;
  const tone = outlook?.tone === "good"
    ? { text: "text-emerald-700", ring: "#059669", wash: "from-emerald-50 via-white to-teal-50", bar: "bg-emerald-500", glow: "bg-emerald-300/50" }
    : outlook?.tone === "warn"
      ? { text: "text-amber-700", ring: "#d97706", wash: "from-amber-50 via-white to-orange-50", bar: "bg-amber-500", glow: "bg-amber-300/50" }
      : outlook?.tone === "bad"
        ? { text: "text-rose-700", ring: "#e11d48", wash: "from-rose-50 via-white to-orange-50", bar: "bg-rose-500", glow: "bg-rose-300/50" }
        : { text: "text-sky-700", ring: "#0284c7", wash: "from-sky-50 via-white to-indigo-50", bar: "bg-sky-500", glow: "bg-sky-300/50" };

  return (
    <div className={`relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br ${tone.wash} p-6 shadow-sm`}>
      <div className={`pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full ${tone.glow} blur-2xl`} />
      <div className="mb-3 flex items-center justify-between gap-2">
        <p className="text-lg font-semibold text-gray-800">Forecast Achievement</p>
        <div className="flex items-center gap-1.5">
          <StatusChip label={outlook?.label || "—"} tone={outlook?.tone || "neutral"} />
          <span className="inline-flex items-center gap-1 rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-semibold text-gray-600 ring-1 ring-gray-200">
            <LockClosedIcon className="h-3 w-3" />
            {shortMonth}
          </span>
        </div>
      </div>
      <div className="flex flex-1 items-center justify-center py-1">
        <div className="relative h-[168px] w-[168px]">
          <svg viewBox="0 0 144 144" className="-rotate-90 h-full w-full">
            <circle cx="72" cy="72" r={radius} fill="none" stroke="rgba(15,23,42,0.08)" strokeWidth="14" />
            <circle
              cx="72"
              cy="72"
              r={radius}
              fill="none"
              stroke={tone.ring}
              strokeWidth="14"
              strokeLinecap="round"
              strokeDasharray={`${dash} ${circ}`}
              className="transition-[stroke-dasharray] duration-700 ease-out"
            />
          </svg>
          <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
            <span className={`text-2xl font-semibold tabular-nums ${tone.text}`}>
              {hasTarget ? `${pct.toFixed(1)}%` : "—"}
            </span>
            <span className="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-gray-500">
             
            </span>
          </div>
        </div>
      </div>
      <div className="mt-auto flex items-center justify-between pt-2 text-xs tabular-nums text-gray-600">
        <span>{fmt(forecast)}</span>
        <span className="text-gray-400">{over ? "over target" : <>{fmt(remaining)} left</>}</span>
        <span>{fmt(target)}</span>
      </div>
    </div>
  );
};

const TargetCard = ({ target, achievement, timePct, gap, status }) => (
  <div className="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <div className="mb-3 flex items-start justify-between gap-2">
      <div className="flex items-center gap-2">
        <div className="rounded-md bg-gray-100 p-1.5 text-gray-500"><FaBullseye /></div>
        <p className="text-lg font-semibold text-gray-800">Target</p>
      </div>
      <StatusChip label={status?.label || "—"} tone={status?.tone || "neutral"} />
    </div>
    <p className="text-2xl font-semibold tracking-tight text-gray-900 tabular-nums">{fmt(target)}</p>
    <div className="mt-3 space-y-1.5">
      <div className="h-2 overflow-hidden rounded-full bg-gray-100">
        <div className="h-full rounded-full bg-zinc-900" style={{ width: `${Math.min(achievement || 0, 100)}%` }} />
      </div>
      <div className="h-1.5 overflow-hidden rounded-full bg-gray-100">
        <div className="h-full rounded-full bg-zinc-400" style={{ width: `${Math.min(timePct || 0, 100)}%` }} />
      </div>
      <div className="flex justify-between text-[11px] tabular-nums text-gray-500">
        <span>Sales {fmtPct(achievement)}</span>
        <span>Time {fmtPct(timePct)}</span>
      </div>
    </div>
    <div className="min-h-3 flex-1" />
    <dl className="mt-3 border-t border-gray-100 pt-3">
      <div className="flex items-center justify-between gap-3 text-sm">
        <dt className="text-gray-500">Remaining</dt>
        <dd className="font-medium tabular-nums text-gray-900">{fmt(gap)}</dd>
      </div>
    </dl>
  </div>
);

function ChartExpandModal({ title, onClose, children }) {
  useEffect(() => {
    const onKey = (e) => {
      if (e.key === "Escape") onClose();
    };
    document.addEventListener("keydown", onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = prev;
    };
  }, [onClose]);

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      onClick={onClose}
      role="presentation"
    >
      <div
        className="flex max-h-[90vh] w-full max-w-5xl flex-col rounded-2xl bg-white shadow-xl"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-label={title}
      >
        <div className="flex items-center justify-between border-b px-6 py-4">
          <h3 className="font-medium">{title}</h3>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg border p-1.5 text-gray-500 hover:bg-gray-50 hover:text-gray-800"
            title="Close"
          >
            <XMarkIcon className="h-5 w-5" />
          </button>
        </div>
        <div className="min-h-[420px] flex-1 p-6">{children}</div>
      </div>
    </div>
  );
}

const TeamRow = ({ name, reps, members = [], value, percent, warning }) => {
  const [open, setOpen] = useState(false);

  return (
    <div className="mb-6">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="mb-2 flex w-full items-center justify-between gap-3 text-left"
      >
        <div className="flex min-w-0 items-start gap-2">
          <ChevronDownIcon
            className={`mt-1 h-4 w-4 shrink-0 text-gray-400 transition-transform ${open ? "rotate-0" : "-rotate-90"}`}
          />
          <div className="min-w-0">
            <p className="font-medium">{name}</p>
            <p className="text-sm text-gray-500">{reps} Sales Reps</p>
          </div>
        </div>
        <div className="shrink-0 text-right">
          <p className="text-sm">{value}</p>
          <p className={`text-sm ${warning ? "text-orange-500" : "text-green-600"}`}>
            {Number(percent).toFixed(1)}% projected
          </p>
        </div>
      </button>
      <div className="h-2 rounded-full bg-gray-200">
        <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(percent, 100)}%` }} />
      </div>

      {open && members.length > 0 && (
        <div className="mt-4 space-y-3 border-l-2 border-gray-100 pl-4">
          {members.map((m) => (
            <div key={m.id ?? m.name} className="flex items-center justify-between gap-3">
              <p className="min-w-0 truncate text-sm font-medium text-gray-800">{m.name}</p>
              <div className="shrink-0 text-right">
                <p className="text-xs text-gray-600">
                  {fmt(m.sales)} / {fmt(m.target)}
                </p>
                <p className={`text-xs ${Number(m.percent) < 100 ? "text-orange-500" : "text-green-600"}`}>
                  {Number(m.percent || 0).toFixed(1)}%
                </p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
