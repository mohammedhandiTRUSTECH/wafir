"use client";

import React, { useEffect, useState } from "react";
import api from "../../services/api";

const fmt = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;

export default function SalesReps() {
  const [reps,        setReps]        = useState([]);
  const [supervisors, setSupervisors] = useState([]);
  const [loading,     setLoading]     = useState(true);
  const [open,        setOpen]        = useState(false);
  const [form,        setForm]        = useState({ name: "", username: "", supervisor_id: "", target: "", password: "" });
  const [saving,      setSaving]      = useState(false);
  const [error,       setError]       = useState("");

  const load = () => {
    setLoading(true);
    api.get("/panel/sales-reps")
      .then((res) => {
        setReps(res.data.data?.reps || []);
        setSupervisors(res.data.data?.supervisors || []);
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const handleAdd = async () => {
    if (!form.name || !form.username || !form.password) { setError("Name, username, and password are required."); return; }
    setSaving(true); setError("");
    try {
      await api.post("/users", {
        name:      form.name,
        username:  form.username,
        password:  form.password,
        role_id:   1,
        parent_id: form.supervisor_id || undefined,
      });
      setOpen(false);
      setForm({ name: "", username: "", supervisor_id: "", target: "", password: "" });
      load();
    } catch (e) {
      setError(e.response?.data?.message || "Failed to add sales rep.");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm("Delete this sales rep?")) return;
    try {
      await api.delete(`/users/${id}`);
      setReps((r) => r.filter((rep) => rep.id !== id));
    } catch { alert("Failed to delete."); }
  };

  if (loading) return <div className="flex h-40 items-center justify-center text-gray-400">Loading…</div>;

  return (
    <div className="min-h-screen rounded-2xl border bg-white p-6">
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-lg font-medium">Sales Representatives</h2>
        <button onClick={() => setOpen(true)} className="flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-sm text-white">
          <span className="text-lg">+</span> Add Sales Rep
        </button>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b text-left text-gray-500">
              <th className="py-3">Name</th>
              <th>Username</th>
              <th>Supervisor</th>
              <th>Target</th>
              <th>Commission Rate</th>
              <th className="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {reps.map((rep) => (
              <tr key={rep.id} className="border-b last:border-none">
                <td className="py-4 font-medium">{rep.name}</td>
                <td className="text-gray-600">{rep.email}</td>
                <td>{rep.supervisor}</td>
                <td>{fmt(rep.target)}</td>
                <td>{rep.commission}</td>
                <td className="space-x-3 text-right">
                  <button onClick={() => handleDelete(rep.id)} className="text-red-500 hover:text-red-700">🗑</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {open && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="w-full max-w-md rounded-2xl bg-white p-6">
            <h3 className="mb-4 text-lg font-medium">Add Sales Representative</h3>
            {error && <p className="mb-3 text-sm text-red-500">{error}</p>}
            <div className="space-y-4">
              <input placeholder="Full Name"     value={form.name}      onChange={(e) => setForm({ ...form, name: e.target.value })}      className="w-full rounded-lg border px-4 py-2 text-sm" />
              <input placeholder="Username"      value={form.username}  onChange={(e) => setForm({ ...form, username: e.target.value })}  className="w-full rounded-lg border px-4 py-2 text-sm" />
              <input placeholder="Password"      value={form.password}  onChange={(e) => setForm({ ...form, password: e.target.value })}  type="password" className="w-full rounded-lg border px-4 py-2 text-sm" />
              <select value={form.supervisor_id} onChange={(e) => setForm({ ...form, supervisor_id: e.target.value })} className="w-full rounded-lg border px-4 py-2 text-sm">
                <option value="">Select Supervisor (optional)</option>
                {supervisors.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
              </select>
            </div>
            <div className="mt-6 flex justify-end gap-3">
              <button onClick={() => setOpen(false)} className="rounded-lg border px-4 py-2 text-sm">Cancel</button>
              <button onClick={handleAdd} disabled={saving} className="rounded-lg bg-black px-4 py-2 text-sm text-white disabled:opacity-50">
                {saving ? "Adding…" : "Add Sales Rep"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
