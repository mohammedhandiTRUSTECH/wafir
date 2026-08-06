import { Bar } from "react-chartjs-2";

export const AreaManagerChart = ({ labels = [], data: values = [] }) => {
  const data = {
    labels,
    datasets: [
      { label: "Achievement %", data: values, backgroundColor: "#3b82f6", borderRadius: 8 },
    ],
  };
  const options = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { ticks: { callback: (v) => v + "%" } } },
  };
  return <Bar data={data} options={options} />;
};
