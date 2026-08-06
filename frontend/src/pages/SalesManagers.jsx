import { ArrowTrendingUpIcon, ChevronDownIcon, ExclamationCircleIcon } from "@heroicons/react/24/outline";
import {
  flexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table";
import { useEffect, useState } from "react";
import { FaBullseye, FaChartLine, FaDollarSign, FaWaveSquare } from "react-icons/fa";

import AchievementTierChart from "../components/salesManager/AchievementTierChart";
import { AreaManagerChart } from "../components/salesManager/AreaManagerChart";
import { SalesDistributionChart } from "../components/salesManager/SalesDistributionChart";
import { SalesTrendChart } from "../components/salesManager/SalesTrendChart";
import { WeeklyPerformanceChart } from "../components/salesManager/WeeklyPerformanceChart";
import api from "../services/api";

const fmt    = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;
const fmtPct = (n) => Number(n || 0).toFixed(1) + "%";

const supervisorsColumns = [
  { accessorKey: "supervisor",   header: "Supervisor" },
  { accessorKey: "area_manager", header: "Area Manager" },
  { accessorKey: "team_size",    header: "Team Size" },
  { accessorKey: "target",       header: "Target",      cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "sales",        header: "Sales",       cell: ({ getValue }) => fmt(getValue()) },
  {
    accessorKey: "achievement", header: "Forecast Ach.",
    cell: ({ getValue }) => (
      <span className={`font-medium ${getValue() >= 100 ? "text-green-600" : "text-blue-600"}`}>
        {fmtPct(getValue())}
      </span>
    ),
  },
];

const salesRepsColumns = [
  { accessorKey: "name",        header: "Name" },
  { accessorKey: "supervisor",  header: "Supervisor" },
  { accessorKey: "target",      header: "Target",   cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "sales",       header: "Sales",    cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "forecast",    header: "Forecast", cell: ({ getValue }) => fmt(getValue()) },
  {
    accessorKey: "achievement", header: "Forecast Ach.",
    cell: ({ getValue }) => (
      <span className={`font-medium ${getValue() >= 100 ? "text-green-600" : "text-blue-600"}`}>
        {fmtPct(getValue())}
      </span>
    ),
  },
  { accessorKey: "commission",  header: "Commission", cell: ({ getValue }) => fmt(getValue()) },
];

const tabs = [
  { label: "Area Managers",      key: "area-managers" },
  { label: "All Supervisors",    key: "supervisors"   },
  { label: "All Sales Reps",     key: "reps"          },
  { label: "Advanced Analytics", key: "analytics"     },
];

export default function SalesManagers() {
  const [activeTab,  setActiveTab]  = useState("area-managers");
  const [managers,   setManagers]   = useState([]);
  const [selectedId, setSelectedId] = useState("");
  const [data,       setData]       = useState(null);
  const [loading,    setLoading]    = useState(false);

  // Load list of sales managers
  useEffect(() => {
    api.get("/sales-managers/list").then((res) => {
      const list = res.data.data || [];
      setManagers(list);
      if (list.length) setSelectedId(String(list[0].id));
    });
  }, []);

  // Load data when selected manager changes
  useEffect(() => {
    if (!selectedId) return;
    setLoading(true);
    api.get(`/sales-managers/${selectedId}`)
      .then((res) => setData(res.data.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [selectedId]);

  const stats         = data?.stats || {};
  const areaManagers  = data?.area_managers || [];
  const supervisors   = data?.supervisors || [];
  const salesReps     = data?.sales_reps || [];
  const topPerformers = data?.top_performers || [];
  const needsAttn     = data?.needs_attention || [];
  const charts        = data?.charts || {};

  const supervisorsTable = useReactTable({
    data: supervisors, columns: supervisorsColumns,
    getCoreRowModel: getCoreRowModel(), getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(), initialState: { pagination: { pageSize: 5 } },
  });
  const salesRepsTable = useReactTable({
    data: salesReps, columns: salesRepsColumns,
    getCoreRowModel: getCoreRowModel(), getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(), initialState: { pagination: { pageSize: 5 } },
  });

  return (
    <div className="min-h-screen space-y-4 bg-gray-100">
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Sales Manager Dashboard</h1>
          <p className="text-sm text-gray-500">Complete overview across all levels</p>
        </div>
        <div className="relative inline-block w-[200px]">
          <select value={selectedId} onChange={(e) => setSelectedId(e.target.value)}
            className="w-full cursor-pointer appearance-none rounded-lg border px-4 py-2 pr-10 text-sm">
            {managers.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
          </select>
          <ChevronDownIcon className="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" />
        </div>
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center text-gray-400">Loading…</div>
      ) : data ? (
        <>
          {/* Manager Summary */}
          <div className="rounded-2xl border bg-white p-6">
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
              <div>
                <h2 className="font-semibold">{data.manager?.name}</h2>
                <p className="text-sm text-gray-500">{data.manager?.email}</p>
              </div>
              <div className="flex gap-8 text-sm">
                <div><p className="text-gray-500">Area Managers</p><p className="font-medium">{stats.area_managers}</p></div>
                <div><p className="text-gray-500">Supervisors</p><p className="font-medium">{stats.supervisors}</p></div>
                <div><p className="text-gray-500">Sales Reps</p><p className="font-medium">{stats.sales_reps}</p></div>
              </div>
            </div>
          </div>

          {/* KPI Cards */}
          <div className="grid grid-cols-1 gap-4 md:grid-cols-5">
            <TargetCard target={stats.total_target} achievement={stats.actual_achievement ?? stats.achievement} />
            <StatCard title="Total Sales"      value={fmt(stats.total_sales)}    change={<span className="text-green-600">{fmtPct(stats.actual_achievement ?? stats.achievement)} of target</span>} icon={<FaDollarSign />} />
            <StatCard title="Forecast"         value={fmt(stats.total_forecast)} change={<span className="text-green-600">{fmtPct(stats.forecast_achievement ?? stats.achievement)} of target (projected)</span>} icon={<FaChartLine />} />
            <StatCard title="Commission Pool"  value={fmt(stats.commission_pool)} change={<span className="text-green-600">Org-wide</span>} icon={<FaWaveSquare />} />
            <StatCard title="Avg Daily Sales"  value={fmt(stats.avg_daily_sales)} change={<span className="text-green-600">Organization-wide</span>} icon={<FaWaveSquare />} />
          </div>

          {/* Charts row 1 */}
          <div className="grid gap-6 md:grid-cols-2">
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Sales Trend - Last 30 Days</h3>
              <SalesTrendChart
                labels={charts.sales_trend?.labels || []}
                actual={charts.sales_trend?.actual || []}
                dailyTarget={charts.sales_trend?.daily_target || []}
              />
            </div>
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Area Manager Performance</h3>
              <AreaManagerChart labels={charts.area_manager_performance?.labels || []} data={charts.area_manager_performance?.data || []} />
            </div>
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Sales Distribution by Area</h3>
              <div className="mx-auto h-[260px] w-[260px]">
                <SalesDistributionChart labels={charts.sales_distribution?.labels || []} data={charts.sales_distribution?.data || []} />
              </div>
            </div>
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Weekly Performance Trend</h3>
              <WeeklyPerformanceChart labels={charts.weekly_performance?.labels || []} data={charts.weekly_performance?.data || []} />
            </div>
          </div>

          {/* Top / Needs attention */}
          <div className="flex flex-col gap-6 md:flex-row">
            <div className="flex-1 rounded-lg border bg-white p-4">
              <div className="mb-4 flex items-center gap-2 text-green-600">
                <ArrowTrendingUpIcon className="h-5 w-5" />
                <h2 className="text-sm font-semibold">Top 5 Performers</h2>
              </div>
              {topPerformers.map((p, i) => (
                <PerformerCard key={p.id || i} person={p} index={i} />
              ))}
            </div>
            <div className="flex-1 rounded-lg border bg-white p-4">
              <div className="mb-4 flex items-center gap-2 text-red-600">
                <ExclamationCircleIcon className="h-5 w-5" />
                <h2 className="text-sm font-semibold">Needs Attention</h2>
              </div>
              {needsAttn.map((p, i) => (
                <AttentionCard key={p.id || i} person={p} />
              ))}
            </div>
          </div>

          {/* Tabs */}
          <div className="mb-8 flex w-fit gap-2 rounded-full bg-gray-200 p-1">
            {tabs.map((tab) => (
              <button key={tab.key} onClick={() => setActiveTab(tab.key)}
                className={`rounded-full px-4 py-2 text-sm font-medium transition ${activeTab === tab.key ? "bg-white text-black" : "text-gray-700 hover:bg-gray-100"}`}>
                {tab.label}
              </button>
            ))}
          </div>

          {activeTab === "area-managers" && (
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-semibold">Area Managers Detailed Performance</h3>
              <div className="space-y-6">
                {areaManagers.map((am) => (
                  <div key={am.id} className="rounded-2xl border p-5">
                    <div className="mb-3 flex items-start justify-between">
                      <div><h4 className="font-medium">{am.name}</h4><p className="text-sm text-gray-500">{am.email}</p></div>
                      <span className={`rounded-full px-3 py-1 text-sm font-medium ${am.progress >= 100 ? "bg-green-100 text-green-700" : "bg-blue-100 text-blue-700"}`}>
                        {fmtPct(am.progress)}
                      </span>
                    </div>
                    <div className="mb-3 grid grid-cols-5 gap-4 text-sm">
                      <Info label="Supervisors" value={am.supervisors} />
                      <Info label="Reps"        value={am.reps} />
                      <Info label="Target"      value={fmt(am.target)} />
                      <Info label="Sales"       value={fmt(am.sales)} />
                      <Info label="Commission"  value={fmt(am.commission)} />
                    </div>
                    <div className="h-2 w-full rounded-full bg-gray-200">
                      <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(am.progress, 100)}%` }} />
                    </div>
                    <p className="mt-2 text-sm text-gray-500">Supervisors: {am.supervisors_list}</p>
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === "supervisors" && (
            <TableView table={supervisorsTable} title="All Supervisors Performance" />
          )}

          {activeTab === "reps" && (
            <TableView table={salesRepsTable} title="All Sales Representatives Performance" />
          )}

          {activeTab === "analytics" && (
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-6 font-semibold">Achievement Distribution</h3>
              <AchievementTierChart
                labels={charts.achievement_tiers?.labels || []}
                data={charts.achievement_tiers?.data || []}
              />
            </div>
          )}
        </>
      ) : null}
    </div>
  );
}

function TableView({ table, title }) {
  return (
    <div className="rounded-2xl border bg-white p-6">
      <h3 className="mb-4 font-semibold">{title}</h3>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="border-b text-left text-gray-500">
            {table.getHeaderGroups().map((hg) => (
              <tr key={hg.id}>
                {hg.headers.map((h) => (
                  <th key={h.id} className="cursor-pointer py-3" onClick={h.column.getToggleSortingHandler()}>
                    <div className="flex items-center gap-1">
                      {flexRender(h.column.columnDef.header, h.getContext())}
                      {{ asc: "▲", desc: "▼" }[h.column.getIsSorted()] ?? null}
                    </div>
                  </th>
                ))}
              </tr>
            ))}
          </thead>
          <tbody>
            {table.getRowModel().rows.map((row) => (
              <tr key={row.id} className="border-b last:border-0">
                {row.getVisibleCells().map((cell) => (
                  <td key={cell.id} className="py-3">{flexRender(cell.column.columnDef.cell, cell.getContext())}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="mt-4 flex items-center justify-between text-sm">
        <span className="text-gray-500">Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}</span>
        <div className="flex gap-2">
          <button onClick={() => table.previousPage()} disabled={!table.getCanPreviousPage()} className="rounded-md border px-3 py-1 disabled:opacity-50">Previous</button>
          <button onClick={() => table.nextPage()}     disabled={!table.getCanNextPage()}     className="rounded-md border px-3 py-1 disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>
  );
}

const Info = ({ label, value }) => (
  <div><p className="text-gray-500">{label}</p><p className="font-medium">{value}</p></div>
);

const StatCard = ({ title, value, change, icon }) => (
  <div className="rounded-2xl border bg-white p-6">
    <div className="mb-3 flex items-center justify-between">
      <p className="text-sm text-gray-500">{title}</p>
      <div className="rounded-lg bg-blue-50 p-2 text-blue-600">{icon}</div>
    </div>
    <p className="mb-1 text-xl font-semibold">{value}</p>
    <div className="text-sm">{change}</div>
  </div>
);

const TargetCard = ({ target, achievement }) => (
  <div className="rounded-2xl border bg-white p-6">
    <div className="mb-3 flex items-center justify-between">
      <p className="text-sm text-gray-500">Monthly Target</p>
      <div className="rounded-lg bg-blue-50 p-2 text-blue-600"><FaBullseye /></div>
    </div>
    <p className="mb-3 text-xl font-semibold">{Number(target || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></p>
    <div className="mb-2 h-2 rounded-full bg-gray-200">
      <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(achievement || 0, 100)}%` }} />
    </div>
    <p className="text-sm text-gray-500">{Number(achievement || 0).toFixed(1)}% achieved</p>
  </div>
);

function PerformerCard({ person, index }) {
  return (
    <div className="mb-2 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-2">
      <div className="flex items-center gap-4">
        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-green-600 text-xs font-semibold text-white">{index + 1}</div>
        <div>
          <div className="text-sm font-medium">{person.name}</div>
          <div className="text-xs text-gray-500">{Number(person.sales || person.actual || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span> / {Number(person.target || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></div>
        </div>
      </div>
      <div className="rounded-full bg-green-600 px-3 py-0.5 text-xs font-semibold text-white">{Number(person.achievement || person.percent || 0).toFixed(1)}%</div>
    </div>
  );
}

function AttentionCard({ person }) {
  return (
    <div className="mb-2 flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-2">
      <div className="flex items-center gap-4">
        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-xs font-semibold text-white">
          <ExclamationCircleIcon className="h-4 w-4" />
        </div>
        <div>
          <div className="text-sm font-medium">{person.name}</div>
          <div className="text-xs text-gray-500">{Number(person.sales || person.actual || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span> / {Number(person.target || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></div>
        </div>
      </div>
      <div className="rounded-full bg-red-600 px-3 py-0.5 text-xs font-semibold text-white">{Number(person.achievement || person.percent || 0).toFixed(1)}%</div>
    </div>
  );
}
