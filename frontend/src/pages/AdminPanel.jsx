import React, { useState } from "react";

import CommissionSchemes from "../components/adminPanel/CommissionSchemes";
import SalesReps from "../components/adminPanel/SalesReps";
import Supervisors from "../components/adminPanel/Supervisors";
import TargetsAndForecasts from "../components/adminPanel/TargetsAndForecasts";

const tabs = [
  { label: "Targets & Forecasts", key: "targets"    },
  { label: "Sales Reps",          key: "reps"        },
  { label: "Supervisors",         key: "supervisors" },
  { label: "Commission Schemes",  key: "commission"  },
];

export default function AdminPanel() {
  const [activeTab, setActiveTab] = useState("targets");

  return (
    <div className="min-h-screen space-y-4 bg-gray-100">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold">Admin Panel</h1>
        <p className="text-sm text-gray-500">Manage sales structure, targets, and commission schemes</p>
      </div>

      <div className="mb-8 flex w-fit gap-2 rounded-full bg-gray-200 p-1">
        {tabs.map((tab) => (
          <button key={tab.key} onClick={() => setActiveTab(tab.key)}
            className={`rounded-full border px-4 py-2 text-sm font-medium transition ${activeTab === tab.key ? "bg-white text-black" : "text-gray-700 hover:bg-gray-100"}`}>
            {tab.label}
          </button>
        ))}
      </div>

      {activeTab === "targets"    && <TargetsAndForecasts />}
      {activeTab === "reps"       && <SalesReps />}
      {activeTab === "supervisors"&& <Supervisors />}
      {activeTab === "commission" && <CommissionSchemes />}
    </div>
  );
}
