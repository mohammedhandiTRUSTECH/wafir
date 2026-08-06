import { Bar } from "react-chartjs-2";

const AchievementTierChart = ({ labels = [], data: values = [] }) => {
  const data = {
    labels,
    datasets: [{
      data: values,
      backgroundColor: "#3b82f6",
      borderRadius: 6,
      barThickness: 18,
    }],
  };
  const options = {
    indexAxis: "y",
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [4, 4] } },
      y: { grid: { display: false } },
    },
  };
  return (
    <div className="h-[260px]">
      <h4 className="mb-4 font-medium">Reps by Achievement Tier</h4>
      <Bar data={data} options={options} />
    </div>
  );
};

export default AchievementTierChart;
