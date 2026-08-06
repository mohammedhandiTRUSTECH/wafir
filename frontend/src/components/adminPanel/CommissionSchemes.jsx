"use client";

import { flexRender, getCoreRowModel, getFilteredRowModel, getPaginationRowModel, getSortedRowModel, useReactTable } from "@tanstack/react-table";
import React, { useEffect, useState } from "react";
import { FaSort, FaSortDown, FaSortUp } from "react-icons/fa";
import api from "../../services/api";

const fmt    = (n) => <>{Number(n || 0).toLocaleString()} <span className="icon-saudi_riyal">&#xea;</span></>;
const fmtPct = (n) => Number(n || 0).toFixed(1) + "%";

const salesColumns = [
  {
    accessorKey: "salesrep",
    header: "Sales Rep",
    cell: ({ row }) => (
      <div>
        <p className="font-medium">{row.original.name}</p>
        <p className="text-xs text-gray-500">{row.original.email}</p>
      </div>
    ),
  },
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
  {
    accessorKey: "achievement",
    header: "Achievement",
    cell: ({ row }) => (
      <span className={`rounded-md px-2 py-1 text-xs font-medium ${row.original.achievement >= 100 ? "bg-green-100 text-green-700" : "bg-blue-100 text-blue-700"}`}>
        {fmtPct(row.original.achievement)}
      </span>
    ),
  },
  {
    accessorKey: "current_tier_rate",
    header: "Current Tier Rate",
    cell: ({ getValue }) => <span className="rounded-md px-2 py-1 text-xs font-medium">{fmtPct(getValue())}</span>,
  },
  {
    accessorKey: "total_earned",
    header: () => <div className="text-right">Total Earned</div>,
    cell: ({ getValue }) => <div className="text-right font-medium">{fmt(getValue())}</div>,
  },
];

export default function CommissionSchemes() {
  const [data,    setData]    = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get("/panel/commission-schemes")
      .then((res) => setData(res.data.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const tiers = data?.tiers || {};
  const repsRaw = (data?.reps || []).map((r) => ({
    ...r,
    total_earned: r.commission_amount ?? r.total_earned,
  }));

  const [sorting,       setSorting]       = React.useState([]);
  const [pagination,    setPagination]    = React.useState({ pageIndex: 0, pageSize: 5 });
  const [columnFilters, setColumnFilters] = React.useState([]);

  const table = useReactTable({
    data: repsRaw, columns: salesColumns,
    state: { sorting, pagination, columnFilters },
    onSortingChange: setSorting, onPaginationChange: setPagination, onColumnFiltersChange: setColumnFilters,
    getCoreRowModel: getCoreRowModel(), getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(), getFilteredRowModel: getFilteredRowModel(),
  });

  if (loading) return <div className="flex h-40 items-center justify-center text-gray-400">Loading…</div>;

  return (
    <div className="min-h-screen space-y-6">
      <div className="rounded-2xl border bg-white p-6">
        <h2 className="mb-6 text-lg font-medium">Commission Schema</h2>
        <div className="grid gap-6 md:grid-cols-3">
          <CommissionSchemaTable title="Sales manager" tiers={tiers.sales_manager || []} />
          <CommissionSchemaTable title="Supervisor" tiers={tiers.supervisor || []} />
          <CommissionSchemaTable title="Salesman" tiers={tiers.sales_rep || []} />
        </div>
      </div>

      <div className="rounded-2xl border bg-white p-6">
        <h3 className="mb-4 font-medium">Individual Commission Rates</h3>
        <div className="mb-4">
          <input
            type="text" placeholder="Search by name…"
            value={table.getColumn("salesrep")?.getFilterValue() || ""}
            onChange={(e) => table.getColumn("salesrep")?.setFilterValue(e.target.value)}
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
      </div>
    </div>
  );
}

function formatTierFrom(tier) {
  const min = Number(tier.min ?? 0);
  if (min <= 0) return "0.00";
  return `≥${min.toFixed(2)}%`;
}

function formatTierTo(tier) {
  if (tier.max == null) return ">100%";
  return `<${Number(tier.max).toFixed(2)}%`;
}

function CommissionSchemaTable({ title, tiers = [] }) {
  return (
    <div className="overflow-hidden rounded-xl border">
      <div className="border-b bg-gray-50 px-4 py-3">
        <h4 className="text-sm font-medium text-gray-800">{title}</h4>
      </div>
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b text-left text-gray-500">
            <th className="px-4 py-2 font-medium">From</th>
            <th className="px-4 py-2 font-medium">To</th>
            <th className="px-4 py-2 font-medium">Get</th>
          </tr>
        </thead>
        <tbody>
          {tiers.length === 0 ? (
            <tr>
              <td colSpan={3} className="px-4 py-4 text-center text-gray-400">No tiers</td>
            </tr>
          ) : (
            tiers.map((tier, i) => (
              <tr key={i} className="border-b last:border-none">
                <td className="px-4 py-2.5 text-gray-800">{formatTierFrom(tier)}</td>
                <td className="px-4 py-2.5 text-gray-800">{formatTierTo(tier)}</td>
                <td className="px-4 py-2.5 font-medium text-gray-900">{Number(tier.rate || 0).toFixed(2)}%</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}
