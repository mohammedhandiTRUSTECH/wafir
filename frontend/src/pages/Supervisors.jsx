import { ChevronDownIcon } from "@heroicons/react/24/outline";
import { useEffect, useState } from "react";
import { FaBullseye, FaChartLine, FaDollarSign, FaWaveSquare } from "react-icons/fa";

import { SalesRepChart } from "../components/supervisors/SalesRepChart";
import TeamSalesTrendChart from "../components/supervisors/TeamSalesTrendChart";
import api from "../services/api";

const fmt    = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;
const fmtPct = (n) => Number(n || 0).toFixed(1) + "%";

export default function SupervisorsPage() {
  const [supervisors, setSupervisors] = useState([]);
  const [selectedId,  setSelectedId]  = useState("");
  const [data,        setData]        = useState(null);
  const [loading,     setLoading]     = useState(false);

  useEffect(() => {
    api.get("/supervisors/list").then((res) => {
      const list = res.data.data || [];
      setSupervisors(list);
      if (list.length) setSelectedId(String(list[0].id));
    });
  }, []);

  useEffect(() => {
    if (!selectedId) return;
    setLoading(true);
    api.get(`/supervisors/${selectedId}`)
      .then((res) => setData(res.data.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, [selectedId]);

  const sup     = data?.supervisor || {};
  const stats   = data?.stats || {};
  const members = data?.team_members || [];
  const charts  = data?.charts || {};

  return (
    <div className="min-h-screen space-y-4 bg-gray-100">
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Supervisor Dashboard</h1>
          <p className="text-sm text-gray-500">Monitor your team's performance and targets</p>
        </div>
        <div className="relative inline-block w-[200px]">
          <select value={selectedId} onChange={(e) => setSelectedId(e.target.value)}
            className="w-full cursor-pointer appearance-none rounded-lg border px-4 py-2 pr-10 text-sm">
            {supervisors.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
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
                <h2 className="font-semibold">{sup.name}</h2>
                <p className="text-sm text-gray-500">{sup.email}</p>
              </div>
              <div className="flex gap-8 text-sm">
                <div><p className="text-gray-500">Team Size</p><p className="font-medium">{sup.team_size} Sales Representatives</p></div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
            <TargetCard target={stats.total_target} achievement={stats.actual_achievement ?? stats.achievement} />
            <StatCard title="Total Sales"    value={fmt(stats.total_sales)}     change={<span className="text-green-600">{fmtPct(stats.actual_achievement ?? stats.achievement)} of target</span>} icon={<FaDollarSign />} />
            <StatCard title="Team Forecast"  value={fmt(stats.team_forecast)}   change={<span className="text-green-600">{fmtPct(stats.forecast_achievement ?? stats.achievement)} of target (projected)</span>} icon={<FaChartLine />} />
            <StatCard title="Avg Daily Sales" value={fmt(stats.avg_daily_sales)} change={<span className="text-green-600">Current month</span>} icon={<FaWaveSquare />} />
          </div>

          <div className="grid gap-6 md:grid-cols-2">
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Team Sales Trend (Last 15 Days)</h3>
              <TeamSalesTrendChart
                labels={charts.team_sales_trend?.labels || []}
                actual={charts.team_sales_trend?.actual || []}
                dailyTarget={charts.team_sales_trend?.daily_target || []}
              />
            </div>
            <div className="rounded-2xl border bg-white p-6">
              <h3 className="mb-4 font-medium">Sales vs Target by Rep</h3>
              <SalesRepChart
                labels={charts.sales_rep_performance?.labels || []}
                actual={charts.sales_rep_performance?.actual || []}
                target={charts.sales_rep_performance?.target || []}
              />
            </div>
          </div>

          <div className="rounded-2xl border bg-white p-6">
            <h3 className="mb-4 font-semibold">Team Members Performance</h3>
            {members.map((m) => <TeamRow key={m.id} member={m} />)}
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
      <p className="text-sm text-gray-500">Team Target</p>
      <div className="rounded-lg bg-blue-50 p-2 text-blue-600"><FaBullseye /></div>
    </div>
    <p className="mb-3 text-xl font-semibold">{Number(target || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></p>
    <div className="mb-2 h-2 rounded-full bg-gray-200">
      <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(achievement || 0, 100)}%` }} />
    </div>
    <p className="text-sm text-gray-500">{Number(achievement || 0).toFixed(1)}% achieved</p>
  </div>
);

const TeamRow = ({ member }) => {
  const progress = member.actual_achievement ?? member.achievement;
  return (
  <div className="mb-6 rounded-lg border border-gray-200 p-4">
    <div className="flex justify-between">
      <div>
        <p className="font-medium">{member.name}</p>
        <p className="mb-4 text-sm text-gray-500">{member.email}</p>
      </div>
      <p className="font-medium">{Number(progress || 0).toFixed(1)}%</p>
    </div>
    <div className="flex justify-between gap-4 text-sm text-gray-500">
      <div><p>Target</p><p>{fmt(member.target)}</p></div>
      <div><p>Actual Sales</p><p>{fmt(member.actual_sales)}</p></div>
      <div><p>Forecast</p><p>{fmt(member.forecast)}</p></div>
      <div><p>Commission</p><p>{fmt(member.commission)}</p></div>
    </div>
    <div className="mt-3 h-2 rounded-full bg-gray-200">
      <div className="h-2 rounded-full bg-black" style={{ width: `${Math.min(progress || 0, 100)}%` }} />
    </div>
  </div>
  );
};
