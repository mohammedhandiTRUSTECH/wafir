import { Line } from "react-chartjs-2";

export const SalesTrendChart = ({ labels = [], actual = [], dailyTarget = [] }) => {
  const data = {
    labels,
    datasets: [
      {
        label: "Actual Sales",
        data: actual,
        borderColor: "#3b82f6",
        backgroundColor: "rgba(59,130,246,0.25)",
        fill: true,
        tension: 0.4,
        pointRadius: 3,
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

  return <Line data={data} />;
};
