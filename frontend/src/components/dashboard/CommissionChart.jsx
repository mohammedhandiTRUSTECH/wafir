import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/react/24/outline";
import { useEffect, useMemo, useState } from "react";
import { Bar } from "react-chartjs-2";

const PAGE_SIZE = 10;

const CommissionChart = ({
  labels = [],
  data: values = [],
  expanded = false,
  datasetLabel = "Commission",
  color = "#3b82f6",
}) => {
  const [page, setPage] = useState(0);

  const ranked = useMemo(() => {
    return labels
      .map((label, i) => ({ label, value: Number(values[i]) || 0 }))
      .sort((a, b) => b.value - a.value);
  }, [labels, values]);

  const totalPages = Math.max(1, Math.ceil(ranked.length / PAGE_SIZE));

  useEffect(() => {
    setPage(0);
  }, [labels, values]);

  useEffect(() => {
    if (page > totalPages - 1) setPage(Math.max(0, totalPages - 1));
  }, [page, totalPages]);

  const pageItems = ranked.slice(page * PAGE_SIZE, page * PAGE_SIZE + PAGE_SIZE);
  const pageLabels = pageItems.map((item) => item.label);
  const pageValues = pageItems.map((item) => item.value);

  const data = {
    labels: pageLabels,
    datasets: [
      {
        label: datasetLabel,
        data: pageValues,
        backgroundColor: color,
        borderRadius: 8,
      },
    ],
  };

  const options = {
    indexAxis: "y",
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (ctx) =>
            `${Number(ctx.raw || 0).toLocaleString()} \u00EA`,
        },
      },
    },
    scales: {
      x: {
        beginAtZero: true,
        ticks: {
          callback: (v) => `${Number(v).toLocaleString()} \u00EA`,
        },
      },
      y: {
        grid: { display: false },
        ticks: {
          autoSkip: false,
          font: { size: expanded ? 12 : 11 },
        },
      },
    },
  };

  const from = ranked.length === 0 ? 0 : page * PAGE_SIZE + 1;
  const to = Math.min((page + 1) * PAGE_SIZE, ranked.length);

  return (
    <div className="flex h-full flex-col">
      <div className="min-h-0 flex-1">
        <Bar data={data} options={options} />
      </div>
      {ranked.length > PAGE_SIZE && (
        <div className="mt-3 flex items-center justify-between gap-2 border-t pt-3">
          <button
            type="button"
            onClick={() => setPage((p) => Math.max(0, p - 1))}
            disabled={page === 0}
            className="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
          >
            <ChevronLeftIcon className="h-4 w-4" />
            Prev
          </button>
          <p className="text-xs text-gray-500">
            Top {from}–{to} of {ranked.length}
          </p>
          <button
            type="button"
            onClick={() => setPage((p) => Math.min(totalPages - 1, p + 1))}
            disabled={page >= totalPages - 1}
            className="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
          >
            Next
            <ChevronRightIcon className="h-4 w-4" />
          </button>
        </div>
      )}
    </div>
  );
};

export default CommissionChart;
