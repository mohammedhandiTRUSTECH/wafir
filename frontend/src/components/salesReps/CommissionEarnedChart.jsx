import { Line } from "react-chartjs-2";

export const CommissionEarnedChart = ({ labels = [], data: values = [] }) => {
  const data = {
    labels,
    datasets: [{
      label: "Commission",
      data: values,
      borderColor: "#7a40ff",
      backgroundColor: "rgba(122,64,255,0.15)",
      fill: true,
      tension: 0.4,
    }],
  };
  const options = {
    responsive: true,
    scales: { y: { ticks: { callback: (v) => `${Number(v).toLocaleString()} \u00EA` } } },
  };
  return <Line data={data} options={options} />;
};
