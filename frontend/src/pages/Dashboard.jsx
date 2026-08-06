"use client";

import { ArrowsPointingOutIcon, ChevronDownIcon, XMarkIcon } from "@heroicons/react/24/outline";
import {
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table";
import React, { useEffect, useMemo, useState } from "react";
import {
  FaBullseye,
  FaCalendarDay,
  FaChartLine,
  FaChartPie,
  FaDollarSign,
  FaSort,
  FaSortDown,
  FaSortUp,
  FaWaveSquare,
} from "react-icons/fa";
import { useSearchParams } from "react-router-dom";

import CommissionChart from "../components/dashboard/CommissionChart";
import ForecastPieChart from "../components/dashboard/ForecastPieChart";
import SalesVsTargetChart from "../components/dashboard/SalesVsTargetChart";
import api from "../services/api";

const fmt = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;
const fmtPct = (n) => Number(n || 0).toFixed(1) + "%";

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
  { accessorKey: "actual",   header: "Actual Sales", cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "forecast", header: "Forecast",     cell: ({ getValue }) => fmt(getValue()) },
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
  const [expandedChart, setExpandedChart] = useState(null); // "sales" | "commission" | null

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

  const stats = data?.stats || {};
  const teamPerf = data?.team_performance || [];
  const salesReps = data?.sales_reps || [];
  const chartSales = data?.chart_sales_vs_target || { labels: [], actual: [], daily_target: [] };
  const chartComm = data?.chart_commission || { labels: [], data: [] };

  const workingDaysLabel = stats.working_days_total
    ? `${stats.working_days_gone || 0} / ${stats.working_days_total} working days`
    : "Working days";

  const monthlyTarget = Number(stats.monthly_target || 0);
  const totalSales = Number(stats.total_sales || 0);
  const totalForecast = Number(stats.total_forecast || 0);

  const dailyTarget = stats.daily_target != null
    ? Number(stats.daily_target)
    : (stats.working_days_total > 0
      ? monthlyTarget / Number(stats.working_days_total)
      : 0);

  // Actual = sales/target; forecast = projected end-of-period/target.
  const actualAchievement = stats.actual_achievement != null
    ? Number(stats.actual_achievement)
    : (monthlyTarget > 0 ? (totalSales / monthlyTarget) * 100 : 0);
  const forecastAchievement = stats.forecast_achievement != null
    ? Number(stats.forecast_achievement)
    : (stats.achievement != null
      ? Number(stats.achievement)
      : (monthlyTarget > 0 ? (totalForecast / monthlyTarget) * 100 : 0));

  const rangeLabel = formatRangeLabel(queryRange.from, queryRange.to);

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
      <div className="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Sales Dashboard</h1>
          <p className="text-sm text-gray-500">Overview of sales performance and targets</p>
        </div>

        <div className="flex flex-col items-stretch gap-3 sm:items-end">
          <div className="inline-flex rounded-lg border bg-white p-1 text-sm">
            <button
              type="button"
              onClick={() => setMode("month")}
              className={`rounded-md px-3 py-1.5 ${mode === "month" ? "bg-black text-white" : "text-gray-600 hover:bg-gray-50"}`}
            >
              Month
            </button>
            <button
              type="button"
              onClick={() => {
                if (mode !== "range") {
                  const bounds = monthBounds(month);
                  setFromDate(bounds.from);
                  setToDate(bounds.to);
                  setApplied(bounds);
                }
                setMode("range");
              }}
              className={`rounded-md px-3 py-1.5 ${mode === "range" ? "bg-black text-white" : "text-gray-600 hover:bg-gray-50"}`}
            >
              Custom range
            </button>
          </div>

          {mode === "month" ? (
            <div className="relative inline-block w-full min-w-[200px] sm:w-[220px]">
              <input
                type="month"
                value={month}
                onChange={(e) => setMonth(e.target.value)}
                className="w-full cursor-pointer appearance-none rounded-lg border bg-white px-4 py-2 pr-10 text-sm"
              />
              <ChevronDownIcon className="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" />
            </div>
          ) : (
            <div className="flex flex-wrap items-end gap-2">
              <label className="flex flex-col gap-1 text-xs text-gray-500">
                From
                <input
                  type="date"
                  value={fromDate}
                  onChange={(e) => setFromDate(e.target.value)}
                  className="rounded-lg border bg-white px-3 py-2 text-sm text-gray-900"
                />
              </label>
              <label className="flex flex-col gap-1 text-xs text-gray-500">
                To
                <input
                  type="date"
                  value={toDate}
                  onChange={(e) => setToDate(e.target.value)}
                  className="rounded-lg border bg-white px-3 py-2 text-sm text-gray-900"
                />
              </label>
              <button
                type="button"
                onClick={applyCustomRange}
                className="rounded-lg bg-black px-4 py-2 text-sm text-white hover:bg-gray-800"
              >
                Apply
              </button>
            </div>
          )}

          {mode === "month" && (
            <div className="flex flex-wrap items-end gap-2 rounded-lg border bg-white px-3 py-2">
              <label className="flex flex-col gap-1 text-xs text-gray-500">
                Working days
                <input
                  type="number"
                  min={1}
                  max={31}
                  value={workingDaysInput}
                  onChange={(e) => setWorkingDaysInput(e.target.value)}
                  className="w-20 rounded-lg border px-3 py-1.5 text-sm text-gray-900"
                />
              </label>
              <button
                type="button"
                onClick={saveWorkingDays}
                disabled={savingDays || loading}
                className="rounded-lg bg-black px-3 py-1.5 text-sm text-white hover:bg-gray-800 disabled:opacity-50"
              >
                {savingDays ? "Saving…" : "Save"}
              </button>
              {stats.working_days_override && (
                <button
                  type="button"
                  onClick={resetWorkingDays}
                  disabled={savingDays || loading}
                  className="rounded-lg border px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                  Use auto
                </button>
              )}
            </div>
          )}

          <p className="text-xs text-gray-500">
            {rangeLabel} · {workingDaysLabel}
            {stats.working_days_override
              ? " (saved)"
              : stats.working_days_auto
                ? ` (auto ${stats.working_days_auto}, Fridays excluded)`
                : " (Fridays excluded)"}
          </p>
        </div>
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center text-gray-400">Loading…</div>
      ) : (
        <>
          {/* Stats Cards */}
          <div className="mb-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <TargetCard target={stats.monthly_target} achievement={actualAchievement} />
            <StatCard
              title="Total Sales"
              value={fmt(stats.total_sales)}
              change={`${fmtPct(actualAchievement)} of target`}
              icon={<FaDollarSign />}
            />
            <StatCard
              title="Daily Target"
              value={fmt(dailyTarget)}
              icon={<FaCalendarDay />}
            />
            <StatCard title="Avg Daily Sales" value={fmt(stats.avg_daily_sales)} change={workingDaysLabel} icon={<FaWaveSquare />} />
            <StatCard
              title="Total Forecast"
              value={fmt(stats.total_forecast)}
              change={`${fmtPct(forecastAchievement)} of target (achievement)`}
              icon={<FaChartLine />}
            />
            <div className="rounded-2xl border bg-white p-6">
              <div className="mb-3 flex items-center justify-between">
                <p className="text-sm text-gray-500">Forecast Achievement</p>
                <div className="rounded-lg bg-blue-50 p-2 text-blue-600">
                  <FaChartPie />
                </div>
              </div>
              <ForecastPieChart
                forecast={totalForecast}
                target={monthlyTarget}
                compact
              />
            </div>
          </div>

          {/* Charts */}
          <div className="mb-10 grid gap-6 md:grid-cols-2">
            <div className="rounded-2xl border bg-white p-6">
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
              <div className="h-56">
                <SalesVsTargetChart
                  labels={chartSales.labels}
                  actual={chartSales.actual}
                  dailyTarget={chartSales.daily_target}
                />
              </div>
            </div>
            <div className="rounded-2xl border bg-white p-6">
              <div className="mb-4 flex items-center justify-between gap-2">
                <h3 className="font-medium">Commission Earned by Sales Rep</h3>
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
                <CommissionChart labels={chartComm.labels} data={chartComm.data} />
              </div>
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
              title="Commission Earned by Sales Rep"
              onClose={() => setExpandedChart(null)}
            >
              <CommissionChart labels={chartComm.labels} data={chartComm.data} expanded />
            </ChartExpandModal>
          )}

          {/* Team Performance */}
          <div className="mb-10 rounded-2xl border bg-white p-6">
            <h3 className="mb-6 font-medium">Team Performance Overview</h3>
            {teamPerf.map((t) => (
              <TeamRow
                key={t.name}
                name={t.name}
                reps={t.reps}
                members={t.members || []}
                value={<>{fmt(t.sales)} / {fmt(t.target)}</>}
                percent={t.percent}
                warning={t.percent < 100}
              />
            ))}
          </div>

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

const StatCard = ({ title, value, change, icon, changeClassName = "text-green-600" }) => (
  <div className="rounded-2xl border bg-white p-6">
    <div className="mb-3 flex items-center justify-between">
      <p className="text-sm text-gray-500">{title}</p>
      <div className="rounded-lg bg-blue-50 p-2 text-blue-600">{icon}</div>
    </div>
    <p className="mb-1 text-xl font-semibold">{value}</p>
    {change != null && change !== "" && (
      <p className={`text-sm ${changeClassName}`}>{change}</p>
    )}
  </div>
);

const TargetCard = ({ target, achievement }) => (
  <div className="rounded-2xl border bg-white p-6">
    <div className="mb-3 flex items-center justify-between">
      <p className="text-sm text-gray-500">Target</p>
      <div className="rounded-lg bg-blue-50 p-2 text-blue-600"><FaBullseye /></div>
    </div>
    <p className="mb-3 text-xl font-semibold">{fmt(target)}</p>
    <div className="mb-2 h-2 rounded-full bg-gray-200">
      <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(achievement || 0, 100)}%` }} />
    </div>
    <p className="text-sm text-gray-500">{Number(achievement || 0).toFixed(1)}% achieved</p>
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
