import React, { useEffect, useState } from "react";
import api from "../../services/api";

const fmt = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;

const TargetsAndForecasts = () => {
  const [data,    setData]    = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving,  setSaving]  = useState({});
  const [newTargets, setNewTargets] = useState({});

  useEffect(() => {
    api.get("/panel/targets")
      .then((res) => setData(res.data.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const handleUpdate = async (rep) => {
    const newVal = newTargets[rep.id];
    if (!newVal || !rep.user_target_id || data?.read_only) return;
    setSaving((s) => ({ ...s, [rep.id]: true }));
    try {
      await api.put(`/panel/targets/${rep.id}`, { target: Number(newVal) });
      setData((d) => ({
        ...d,
        reps: d.reps.map((r) => r.id === rep.id ? { ...r, current_target: Number(newVal) } : r),
      }));
      setNewTargets((t) => { const c = { ...t }; delete c[rep.id]; return c; });
    } catch (e) {
      alert("Failed to update target");
    } finally {
      setSaving((s) => ({ ...s, [rep.id]: false }));
    }
  };

  if (loading) return <div className="flex h-40 items-center justify-center text-gray-400">Loading…</div>;
  if (!data)   return null;

  return (
    <div className="min-h-screen rounded-2xl border bg-white p-6">
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-lg font-medium">Monthly Targets</h2>
      </div>

      

      <div className="mb-8 grid grid-cols-2 gap-6">
        <div>
          <label className="mb-1 block text-sm font-medium">Company-wide Target</label>
          <input className="w-full rounded-lg bg-gray-100 px-4 py-2" value={data.company_target} readOnly />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium">Company-wide Forecast</label>
          <input className="w-full rounded-lg bg-gray-100 px-4 py-2" value={Math.round(data.company_forecast)} readOnly />
        </div>
      </div>

      <h3 className="text-md mb-4 font-medium">Individual Targets</h3>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b text-left text-gray-500">
              <th className="py-3">Sales Rep</th>
              <th>Supervisor</th>
              <th>Current Target</th>
              <th>Forecast</th>
              {!data.read_only && <th>New Target</th>}
              {!data.read_only && <th className="text-right">Actions</th>}
            </tr>
          </thead>
          <tbody>
            {data.reps.map((rep) => (
              <tr key={rep.id} className="border-b last:border-none">
                <td className="py-4 font-medium">{rep.name}</td>
                <td className="text-gray-600">{rep.supervisor}</td>
                <td>{fmt(rep.current_target)}</td>
                <td>{fmt(rep.forecast)}</td>
                {!data.read_only && (
                  <td>
                    <input
                      className="w-28 rounded-lg bg-gray-100 px-3 py-1"
                      defaultValue={rep.current_target}
                      onChange={(e) => setNewTargets((t) => ({ ...t, [rep.id]: e.target.value }))}
                    />
                  </td>
                )}
                {!data.read_only && (
                  <td className="text-right">
                    <button
                      onClick={() => handleUpdate(rep)}
                      disabled={saving[rep.id]}
                      className="rounded bg-black px-3 py-1 text-xs text-white disabled:opacity-50"
                    >
                      {saving[rep.id] ? "Saving…" : "Save"}
                    </button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default TargetsAndForecasts;
