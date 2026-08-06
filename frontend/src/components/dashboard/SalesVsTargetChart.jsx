import { Line } from "react-chartjs-2";

const SalesVsTargetChart = ({ labels = [], actual = [], dailyTarget = [], expanded = false }) => {
  const data = {
    labels,
    datasets: [
      {
        label: "Actual Sales",
        data: actual,
        borderColor: "#3b82f6",
        backgroundColor: "#3b82f6",
        tension: 0.4,
        pointRadius: expanded ? 4 : 3,
      },
      {
        label: "Daily Target",
        data: dailyTarget,
        borderColor: "#10b981",
        borderDash: [6, 6],
        pointRadius: 0,
      },
    ],
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: "bottom" },
      tooltip: { mode: "index", intersect: false },
    },
    scales: {
      x: {
        ticks: {
          autoSkip: !expanded,
          maxRotation: expanded ? 45 : 0,
          minRotation: expanded ? 45 : 0,
          font: { size: expanded ? 11 : 10 },
        },
      },
      y: { ticks: { callback: (v) => `${Number(v).toLocaleString()} \u00EA` } },
    },
  };

  return <Line data={data} options={options} />;
};

export default SalesVsTargetChart;
