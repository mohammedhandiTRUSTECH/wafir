"use client";

import React, { useEffect, useState } from "react";
import api from "../../services/api";

const fmt = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;

export default function Supervisors() {
  const [supervisors, setSupervisors] = useState([]);
  const [loading,     setLoading]     = useState(true);
  const [open,        setOpen]        = useState(false);
  const [form,        setForm]        = useState({ name: "", username: "", password: "" });
  const [saving,      setSaving]      = useState(false);
  const [error,       setError]       = useState("");

  const load = () => {
    setLoading(true);
    api.get("/panel/supervisors")
      .then((res) => setSupervisors(res.data.data || []))
      .catch(console.error)
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const handleAdd = async () => {
    if (!form.name || !form.username || !form.password) { setError("All fields are required."); return; }
    setSaving(true); setError("");
    try {
      await api.post("/users", { name: form.name, username: form.username, password: form.password, role_id: 2 });
      setOpen(false);
      setForm({ name: "", username: "", password: "" });
      load();
    } catch (e) {
      setError(e.response?.data?.message || "Failed to add supervisor.");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm("Delete this supervisor?")) return;
    try {
      await api.delete(`/users/${id}`);
      setSupervisors((s) => s.filter((sup) => sup.id !== id));
    } catch { alert("Failed to delete."); }
  };

  if (loading) return <div className="flex h-40 items-center justify-center text-gray-400">Loading…</div>;

  return (
    <div className="min-h-screen rounded-2xl border bg-white p-6">
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-lg font-medium">Supervisors</h2>
        <button onClick={() => setOpen(true)} className="flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-sm text-white">
          <span className="text-lg">+</span> Add Supervisor
        </button>
      </div>

      <div className="space-y-6">
        {supervisors.map((sup) => (
          <div key={sup.id} className="rounded-2xl border p-6">
            <div className="mb-4 flex items-start justify-between">
              <div>
                <h3 className="font-semibold">{sup.name}</h3>
                <p className="text-sm text-gray-500">{sup.email}</p>
              </div>
              <div className="space-x-3">
                <button onClick={() => handleDelete(sup.id)} className="text-red-500 hover:text-red-700">🗑</button>
              </div>
            </div>
            <div className="mb-4 grid grid-cols-3 gap-6 text-sm">
              <div><p className="text-gray-500">Team Size</p><p className="font-medium">{sup.team_size} reps</p></div>
              <div><p className="text-gray-500">Team Target</p><p className="font-medium">{fmt(sup.target)}</p></div>
              <div><p className="text-gray-500">Team Sales</p><p className="font-medium">{fmt(sup.sales)}</p></div>
            </div>
            {sup.members?.length > 0 && (
              <div>
                <p className="mb-2 text-sm text-gray-500">Team Members:</p>
                <div className="flex flex-wrap gap-2">
                  {sup.members.map((m) => (
                    <span key={m} className="rounded-full bg-blue-50 px-3 py-1 text-sm text-blue-600">{m}</span>
                  ))}
                </div>
              </div>
            )}
          </div>
        ))}
      </div>

      {open && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="w-full max-w-md rounded-2xl bg-white p-6">
            <h3 className="mb-4 text-lg font-medium">Add Supervisor</h3>
            {error && <p className="mb-3 text-sm text-red-500">{error}</p>}
            <div className="space-y-4">
              <input placeholder="Full Name" value={form.name}     onChange={(e) => setForm({ ...form, name: e.target.value })}     className="w-full rounded-lg border px-4 py-2 text-sm" />
              <input placeholder="Username"  value={form.username} onChange={(e) => setForm({ ...form, username: e.target.value })} className="w-full rounded-lg border px-4 py-2 text-sm" />
              <input placeholder="Password"  value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} type="password" className="w-full rounded-lg border px-4 py-2 text-sm" />
            </div>
            <div className="mt-6 flex justify-end gap-3">
              <button onClick={() => setOpen(false)} className="rounded-lg border px-4 py-2 text-sm">Cancel</button>
              <button onClick={handleAdd} disabled={saving} className="rounded-lg bg-black px-4 py-2 text-sm text-white disabled:opacity-50">
                {saving ? "Adding…" : "Add Supervisor"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
