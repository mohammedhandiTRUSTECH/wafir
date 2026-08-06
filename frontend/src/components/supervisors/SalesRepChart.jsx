import { Bar } from "react-chartjs-2";

export const SalesRepChart = ({ labels = [], actual = [], target = [] }) => {
  const data = {
    labels,
    datasets: [
      { label: "Actual Sales", data: actual, backgroundColor: "#3b82f6", borderRadius: 8 },
      { label: "Target",       data: target, backgroundColor: "#e5e7eb", borderRadius: 8 },
    ],
  };
  const options = {
    responsive: true,
    scales: { y: { ticks: { callback: (v) => `${Number(v).toLocaleString()} \u00EA` } } },
  };
  return <Bar data={data} options={options} />;
};
