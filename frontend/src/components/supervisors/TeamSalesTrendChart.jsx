import { Line } from "react-chartjs-2";

const TeamSalesTrendChart = ({ labels = [], actual = [], dailyTarget = [] }) => {
  const data = {
    labels,
    datasets: [
      { label: "Actual Sales", data: actual, borderColor: "#3b82f6", backgroundColor: "#3b82f6", tension: 0.4, pointRadius: 4 },
      { label: "Daily Target", data: dailyTarget, borderColor: "#10b981", borderDash: [6, 6], pointRadius: 0 },
    ],
  };
  const options = {
    responsive: true,
    plugins: { legend: { position: "bottom" }, tooltip: { mode: "index", intersect: false } },
    scales: { y: { ticks: { callback: (v) => `${Number(v).toLocaleString()} \u00EA` } } },
  };
  return <Line data={data} options={options} />;
};

export default TeamSalesTrendChart;
