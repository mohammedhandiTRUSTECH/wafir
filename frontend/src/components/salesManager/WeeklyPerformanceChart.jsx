import { Line } from "react-chartjs-2";

export const WeeklyPerformanceChart = ({ labels = [], data: values = [] }) => {
  const data = {
    labels,
    datasets: [{
      label: "Weekly Sales",
      data: values,
      borderColor: "#3b82f6",
      backgroundColor: "rgba(59,130,246,0.25)",
      fill: true,
      tension: 0.4,
    }],
  };
  return <Line data={data} />;
};
