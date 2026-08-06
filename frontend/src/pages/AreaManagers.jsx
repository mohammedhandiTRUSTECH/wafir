import { ChevronDownIcon } from "@heroicons/react/24/outline";
import { flexRender, getCoreRowModel, getPaginationRowModel, getSortedRowModel, useReactTable } from "@tanstack/react-table";
import { useEffect, useState } from "react";
import { FaBullseye, FaChartLine, FaDollarSign, FaWaveSquare } from "react-icons/fa";

import { SalesDistributionChart } from "../components/areaManager/SalesDistributionChart";
import { SupervisorComparisonChart } from "../components/areaManager/SupervisorComparisonChart";
import SalesVsTargetChart from "../components/dashboard/SalesVsTargetChart";
import api from "../services/api";

const fmt    = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;
const fmtPct = (n) => Number(n || 0).toFixed(1) + "%";

const supervisorsColumns = [
  { accessorKey: "supervisor",   header: "Supervisor"   },
  { accessorKey: "area_manager", header: "Area Manager" },
  { accessorKey: "team_size",    header: "Team Size"    },
  { accessorKey: "target",       header: "Target",  cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "sales",        header: "Sales",   cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "achievement",  header: "Forecast Ach.",
    cell: ({ getValue }) => <span className={`font-medium ${getValue() >= 100 ? "text-green-600" : "text-blue-600"}`}>{fmtPct(getValue())}</span> },
];

const salesRepsColumns = [
  { accessorKey: "name",        header: "Name"        },
  { accessorKey: "supervisor",  header: "Supervisor"  },
  { accessorKey: "target",      header: "Target",    cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "sales",       header: "Sales",     cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "forecast",    header: "Forecast",  cell: ({ getValue }) => fmt(getValue()) },
  { accessorKey: "achievement", header: "Forecast Ach.",
    cell: ({ getValue }) => <span className={`font-medium ${getValue() >= 100 ? "text-green-600" : "text-blue-600"}`}>{fmtPct(getValue())}</span> },
  { accessorKey: "commission",  header: "Commission", cell: ({ getValue }) => fmt(getValue()) },
];

const tabs = [
  { label: "Supervisors Overview", key: "supervisors" },
  { label: "All Sales Reps",       key: "reps"        },
];

export default function AreaManagers() {
  const [activeTab,  setActiveTab]  = useState("supervisors");
  const [managers,   setManagers]   = useState([]);
  const [selectedId, setSelectedId] = useState("");
  const [data,       setData]       = useState(null);
  const [loading,    setLoading]    = useState(false);

  useEffect(() => {
    api.get("/area-managers/list").then((res) => {
      const list = res.data.data || [];
      setManagers(list);
      if (list.length) setSelectedId(String(list[0].id));
    });
  }, []);

  useEffect(() => {
    if (!selectedId) return;
    setLoading(true);
    api.get(`/area-managers/${selectedId}`)
      .then((res) => setData(res.data.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [selectedId]);

  const stats       = data?.stats || {};
  const supervisors = data?.supervisors || [];
  const salesReps   = data?.sales_reps || [];
  const charts      = data?.charts || {};

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
          <h1 className="text-2xl font-semibold">Area Manager Dashboard</h1>
          <p className="text-sm text-gray-500">Monitor your area's supervisors and sales teams</p>
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
            <div className="flex items-center justify-between">
              <div>
                <h2 className="font-semibold">{data.manager?.name}</h2>
                <p className="text-sm text-gray-500">{data.manager?.email}</p>
              </div>
              <div className="flex gap-8 text-sm">
                <div><p className="text-gray-500">Reports To</p><p className="font-medium">{data.manager?.reports_to}</p></div>
              </div>
            </div>
          </div>

          {/* KPI Cards */}
          <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
            <TargetCard target={stats.total_target} achievement={stats.actual_achievement ?? stats.achievement} />
            <StatCard title="Total Sales"       value={fmt(stats.total_sales)}      change={<span className="text-green-600">{fmtPct(stats.actual_achievement ?? stats.achievement)} of target</span>} icon={<FaDollarSign />} />
            <StatCard title="Commission Earned" value={fmt(stats.commission_earned)} change={<span className="text-green-600">Current month</span>} icon={<FaChartLine />} />
            <StatCard title="Team Structure"    value={stats.team_structure}         change={<span className="text-green-600">Active</span>} icon={<FaWaveSquare />} />
          </div>

          {/* Charts */}
          <div className="mb-10 grid gap-6 md:grid-cols-2">
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Area Sales Trend (Last 15 Days)</h3>
              <SalesVsTargetChart
                labels={charts.sales_trend?.labels || []}
                actual={charts.sales_trend?.actual || []}
                dailyTarget={charts.sales_trend?.daily_target || []}
              />
            </div>
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Sales Distribution by Supervisor</h3>
              <div className="mx-auto h-[260px] w-[260px]">
                <SalesDistributionChart
                  labels={charts.sales_distribution?.labels || []}
                  data={charts.sales_distribution?.data || []}
                />
              </div>
            </div>
          </div>

          <div className="rounded-2xl border bg-white p-6">
            <h3 className="mb-4 font-medium">Supervisor Sales Comparison</h3>
            <SupervisorComparisonChart
              labels={charts.supervisor_comparison?.labels || []}
              actual={charts.supervisor_comparison?.actual || []}
              target={charts.supervisor_comparison?.target || []}
            />
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

          {activeTab === "supervisors" && <TableView table={supervisorsTable} title="Supervisors Performance" />}
          {activeTab === "reps"        && <TableView table={salesRepsTable}    title="All Sales Representatives Performance" />}
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
      <p className="text-sm text-gray-500">Area Target</p>
      <div className="rounded-lg bg-blue-50 p-2 text-blue-600"><FaBullseye /></div>
    </div>
    <p className="mb-3 text-xl font-semibold">{fmt(target)}</p>
    <div className="mb-2 h-2 rounded-full bg-gray-200">
      <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(achievement || 0, 100)}%` }} />
    </div>
    <p className="text-sm text-gray-500">{Number(achievement || 0).toFixed(1)}% achieved</p>
  </div>
);
