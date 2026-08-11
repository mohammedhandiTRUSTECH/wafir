import { Doughnut } from "react-chartjs-2";

const ForecastPieChart = ({ forecast = 0, target = 0, compact = false, hideLabel = false }) => {
  const forecastValue = Number(forecast) || 0;
  const targetValue = Number(target) || 0;
  const overTarget = forecastValue > targetValue && targetValue > 0;

  const labels = overTarget
    ? ["Target met", "Above target"]
    : ["Forecast", "Remaining to target"];

  const values = overTarget
    ? [targetValue, forecastValue - targetValue]
    : [
        Math.max(forecastValue, 0),
        Math.max(targetValue - forecastValue, 0),
      ];

  const colors = overTarget
    ? ["#10b981", "#34d399"]
    : ["#3b82f6", "#e5e7eb"];

  const hasData = values.some((v) => v > 0);

  const data = {
    labels,
    datasets: [
      {
        data: hasData ? values : [1],
        backgroundColor: hasData ? colors : ["#e5e7eb"],
        borderWidth: 0,
        hoverOffset: compact ? 0 : 4,
      },
    ],
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: "62%",
    plugins: {
      legend: {
        position: "bottom",
        display: !compact,
        labels: { boxWidth: 12, padding: 16 },
      },
      tooltip: {
        callbacks: {
          label: (ctx) => {
            if (!hasData) return " No data";
            const value = Number(ctx.raw || 0);
            return ` ${ctx.label}: ${value.toLocaleString()} \u00EA`;
          },
        },
      },
    },
  };

  const achievement =
    targetValue > 0 ? (forecastValue / targetValue) * 100 : 0;

  return (
    <div className={`relative ${compact ? "h-16 w-16" : "h-56"}`}>
      <Doughnut data={data} options={options} />
      {!hideLabel && (
        <div className={`pointer-events-none absolute inset-0 flex items-center justify-center ${compact ? "" : "pb-8"}`}>
          <div className="text-center">
            <p className={`${compact ? "text-lg" : "text-2xl"} font-semibold text-gray-900`}>
              {targetValue > 0 ? `${achievement.toFixed(1)}%` : "—"}
            </p>
            {!compact && (
              <p className="text-xs text-gray-500">forecast achievement</p>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default ForecastPieChart;
