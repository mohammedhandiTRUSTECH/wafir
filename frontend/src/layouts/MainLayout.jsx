import { Bars3Icon } from "@heroicons/react/24/outline";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { Outlet } from "react-router-dom";

import AdminSidebar from "../components/AdminSidebar";

const AdminLayout = ({ children, onLogout }) => {
  const [collapsed, setCollapsed] = useState(true);
  const toggleSidebar = () => setCollapsed(!collapsed);
  const { i18n } = useTranslation();

  return (
    <div className="bg-secondary flex h-screen" dir={i18n.language === "ar" ? "rtl" : "ltr"}>
      {/* Sidebar */}
      <AdminSidebar collapsed={collapsed} toggleSidebar={toggleSidebar} onLogout={onLogout} />

      {/* Mobile backdrop */}
      {!collapsed && <div className="fixed inset-0 z-30 bg-black/40 md:hidden" onClick={toggleSidebar} />}

      {/* Main content */}
      <div className="flex flex-1 flex-col overflow-auto">
        <main className="flex-1 px-6 py-6">
          {/* Mobile hamburger */}
          <button onClick={toggleSidebar} aria-label="Toggle sidebar">
            <Bars3Icon className="h-6 w-6 text-gray-700" />
          </button>
          <div className="container mx-auto max-w-7xl">{children ? children : <Outlet />}</div>
        </main>
      </div>
    </div>
  );
};

export default AdminLayout;
