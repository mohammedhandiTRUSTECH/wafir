import { Line } from "react-chartjs-2";

export const DailySalesPerformanceChart = ({ labels = [], actual = [], dailyTarget = [] }) => {
  const data = {
    labels,
    datasets: [
      { label: "Sales",        data: actual,      borderColor: "#3b82f6", backgroundColor: "rgba(59,130,246,0.25)", fill: true, tension: 0.4 },
      { label: "Daily Target", data: dailyTarget, borderColor: "#10b981", borderDash: [6, 6], pointRadius: 0 },
    ],
  };
  return <Line data={data} />;
};
