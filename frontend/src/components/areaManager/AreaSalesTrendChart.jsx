import { Line } from "react-chartjs-2";

const AreaSalesTrendChart = () => {
  const data = {
    labels: [
      "Dec 29",
      "Dec 31",
      "Jan 2",
      "Jan 4",
      "Jan 6",
      "Jan 8",
      "Jan 10",
      "Jan 12",
      "Jan 14",
      "Jan 16",
      "Jan 18",
      "Jan 20",
      "Jan 22",
      "Jan 24",
      "Jan 26",
    ],
    datasets: [
      {
        label: "Actual Sales",
        data: [
          38000, 42000, 39500, 45000, 42900, 47000, 44000, 41000, 43000, 40500, 45500, 42500, 46500, 44000,
          42000,
        ],
        borderColor: "#3b82f6",
        backgroundColor: "#3b82f6",
        tension: 0.4,
        pointRadius: 4,
      },
      {
        label: "Daily Target",
        data: new Array(15).fill(35000),
        borderColor: "#10b981",
        borderDash: [6, 6],
        pointRadius: 0,
      },
    ],
  };

  const options = {
    responsive: true,
    plugins: {
      legend: { position: "bottom" },
      tooltip: { mode: "index", intersect: false },
    },
    scales: {
      y: {
        ticks: {
          callback: (value) => `${value.toLocaleString()} \u00EA`,
        },
      },
    },
  };

  return <Line data={data} options={options} />;
};

export default AreaSalesTrendChart;
