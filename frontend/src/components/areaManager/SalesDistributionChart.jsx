import { Pie } from "react-chartjs-2";

const COLORS = ["#3b82f6","#10b981","#f59e0b","#ef4444","#8b5cf6","#06b6d4"];

export const SalesDistributionChart = ({ labels = [], data: values = [] }) => {
  const data = {
    labels,
    datasets: [{ data: values, backgroundColor: COLORS.slice(0, labels.length), borderWidth: 4 }],
  };
  return <Pie data={data} options={{ plugins: { legend: { position: "bottom" } } }} />;
};
