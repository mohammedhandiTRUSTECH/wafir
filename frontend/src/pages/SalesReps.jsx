import { ChevronDownIcon } from "@heroicons/react/24/outline";
import { flexRender, getCoreRowModel, getPaginationRowModel, getSortedRowModel, useReactTable } from "@tanstack/react-table";
import { useEffect, useState } from "react";
import { FaBullseye, FaChartLine, FaDollarSign, FaWaveSquare } from "react-icons/fa";

import { CommissionEarnedChart } from "../components/salesReps/CommissionEarnedChart";
import { DailySalesPerformanceChart } from "../components/salesReps/DailySalesPerformanceChart";
import api from "../services/api";

const fmt    = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;
const fmtPct = (n) => Number(n || 0).toFixed(1) + "%";

const dailyColumns = [
  { accessorKey: "date",         header: "Date"         },
  { accessorKey: "sales",        header: "Sales",        cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "daily_target", header: "Daily Target", cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "achievement",  header: "Achievement",
    cell: ({ getValue }) => <span className={`font-medium ${getValue() >= 100 ? "text-green-600" : "text-blue-600"}`}>{fmtPct(getValue())}</span> },
  { accessorKey: "commission",   header: "Commission",   cell: ({ getValue }) => <>{Number(getValue()).toFixed(2)} <span className="icon-saudi_riyal">&#xea;</span></> },
];

export default function SalesReps() {
  const [reps,       setReps]       = useState([]);
  const [selectedId, setSelectedId] = useState("");
  const [data,       setData]       = useState(null);
  const [loading,    setLoading]    = useState(false);

  useEffect(() => {
    api.get("/sales-reps/list").then((res) => {
      const list = res.data.data || [];
      setReps(list);
      if (list.length) setSelectedId(String(list[0].id));
    });
  }, []);

  useEffect(() => {
    if (!selectedId) return;
    setLoading(true);
    api.get(`/sales-reps/${selectedId}`)
      .then((res) => setData(res.data.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [selectedId]);

  const rep     = data?.rep || {};
  const stats   = data?.stats || {};
  const daily   = data?.daily_performance || [];
  const insights= data?.insights || {};
  const charts  = data?.charts || {};

  const dailyTable = useReactTable({
    data: daily, columns: dailyColumns,
    getCoreRowModel: getCoreRowModel(), getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(), initialState: { pagination: { pageSize: 5 } },
  });

  return (
    <div className="min-h-screen space-y-4 bg-gray-100">
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Sales Representative Dashboard</h1>
          <p className="text-sm text-gray-500">Track your performance and earnings</p>
        </div>
        <div className="relative inline-block w-[200px]">
          <select value={selectedId} onChange={(e) => setSelectedId(e.target.value)}
            className="w-full cursor-pointer appearance-none rounded-lg border px-4 py-2 pr-10 text-sm">
            {reps.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
          </select>
          <ChevronDownIcon className="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" />
        </div>
      </div>

      {loading ? (
        <div className="flex h-64 items-center justify-center text-gray-400">Loading…</div>
      ) : data ? (
        <>
          <div className="rounded-2xl border bg-white p-6">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="font-semibold">{rep.name}</h2>
                <p className="text-sm text-gray-500">{rep.email}</p>
              </div>
              <div className="flex gap-8 text-sm">
                <div><p className="text-gray-500">Supervisor</p><p className="font-medium">{rep.supervisor}</p></div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
            <TargetCard target={stats.monthly_target} achievement={stats.actual_achievement ?? stats.achievement} />
            <StatCard title="Total Sales"       value={fmt(stats.total_sales)}       change={<span className="text-green-600">{fmtPct(stats.actual_achievement ?? stats.achievement)} of target</span>} icon={<FaDollarSign />} />
            <StatCard title="Commission Earned" value={fmt(stats.commission_earned)}  change={<span className="text-green-600">Current month</span>} icon={<FaChartLine />} />
            <StatCard title="Avg Daily Sales"   value={fmt(stats.avg_daily_sales)}    change={<span className="text-green-600">Current month</span>} icon={<FaWaveSquare />} />
          </div>

          <div className="grid gap-6 md:grid-cols-2">
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Daily Sales Performance</h3>
              <DailySalesPerformanceChart
                labels={charts.daily_performance?.labels || []}
                actual={charts.daily_performance?.actual || []}
                dailyTarget={charts.daily_performance?.daily_target || []}
              />
            </div>
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Commission Earned (Weekly Breakdown)</h3>
              <CommissionEarnedChart
                labels={charts.commission_weekly?.labels || []}
                data={charts.commission_weekly?.data || []}
              />
            </div>
          </div>

          {/* Daily Performance Table */}
          <div className="rounded-2xl border bg-white p-6">
            <h3 className="mb-4 font-semibold">Recent Daily Performance</h3>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="border-b text-left text-gray-500">
                  {dailyTable.getHeaderGroups().map((hg) => (
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
                  {dailyTable.getRowModel().rows.map((row) => (
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
              <span className="text-gray-500">Page {dailyTable.getState().pagination.pageIndex + 1} of {dailyTable.getPageCount()}</span>
              <div className="flex gap-2">
                <button onClick={() => dailyTable.previousPage()} disabled={!dailyTable.getCanPreviousPage()} className="rounded-md border px-3 py-1 disabled:opacity-50">Previous</button>
                <button onClick={() => dailyTable.nextPage()}     disabled={!dailyTable.getCanNextPage()}     className="rounded-md border px-3 py-1 disabled:opacity-50">Next</button>
              </div>
            </div>
          </div>

          {/* Performance Insights */}
          <div className="rounded-2xl border bg-white p-6">
            <h3 className="mb-6 font-semibold">Performance Insights</h3>
            <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
              <div>
                <h4 className="mb-4 font-medium">This Month's Progress</h4>
                <ProgressBar label="Target Achievement"   value={insights.target_achievement || 0} />
                <ProgressBar label="Forecast Achievement" value={insights.forecast_achievement || 0} />
              </div>
              <div>
                <h4 className="mb-4 font-medium">Performance Metrics</h4>
                <div className="space-y-3 text-sm">
                  <Metric label="Best Day Sales"          value={fmt(insights.best_day_sales)} />
                  <Metric label="Days Above Target"       value={insights.days_above_target || "0 / 10"} />
                  <Metric label="Projected End of Month"  value={fmt(insights.projected_end_of_month)} color="text-green-600" />
                </div>
              </div>
            </div>
          </div>
        </>
      ) : null}
    </div>
  );
}

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
    <p className="mb-3 text-xl font-semibold">{fmt(target)}</p>
    <div className="mb-2 h-2 rounded-full bg-gray-200">
      <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(achievement || 0, 100)}%` }} />
    </div>
    <p className="text-sm text-gray-500">{Number(achievement || 0).toFixed(1)}% achieved</p>
  </div>
);

const ProgressBar = ({ label, value }) => (
  <div className="mb-4">
    <div className="mb-1 flex justify-between text-sm">
      <span className="text-gray-500">{label}</span>
      <span className="font-medium">{Number(value).toFixed(1)}%</span>
    </div>
    <div className="h-2 rounded-full bg-gray-200">
      <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(value, 100)}%` }} />
    </div>
  </div>
);

const Metric = ({ label, value, color = "font-medium" }) => (
  <div className="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
    <span className="text-gray-500">{label}</span>
    <span className={`font-medium ${color}`}>{value}</span>
  </div>
);
