import "./charts/chartConfig";
import "./index.scss";

import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { Route, Routes, useLocation, useNavigate } from "react-router-dom";

import MainLayout from "./layouts/MainLayout";
import AdminPanel from "./pages/AdminPanel";
import AreaManagers from "./pages/AreaManagers";
import Dashboard from "./pages/Dashboard";
import Login from "./pages/Login";
import NotFound from "./pages/NotFound";
import SalesManagers from "./pages/SalesManagers";
import SalesReps from "./pages/SalesReps";
import Supervisors from "./pages/Supervisors";
import { isAuthenticated } from "./services/authService";

export default function App() {
  const { i18n } = useTranslation();
  const location = useLocation();
  const navigate = useNavigate();

  const [authenticated, setAuthenticated] = useState(isAuthenticated());

  // Listen for auth changes (e.g. after login/logout in other tabs)
  useEffect(() => {
    const check = () => setAuthenticated(isAuthenticated());
    window.addEventListener("storage", check);
    return () => window.removeEventListener("storage", check);
  }, []);

  // Sync lang query param with i18n
  useEffect(() => {
    const searchParams = new URLSearchParams(location.search);
    let lang = searchParams.get("lang");
    if (!lang) {
      lang = i18n.language || "en";
      searchParams.set("lang", lang);
      navigate(`${location.pathname}?${searchParams.toString()}`, { replace: true });
    }
    if (lang !== i18n.language) i18n.changeLanguage(lang);
  }, [location.search, i18n, navigate, location.pathname]);

  // Redirect unauthenticated users to /login
  useEffect(() => {
    if (!authenticated && location.pathname !== "/login") {
      navigate("/login", { replace: true });
    }
  }, [authenticated, location.pathname, navigate]);

  // Redirect authenticated users away from /login
  useEffect(() => {
    if (authenticated && location.pathname === "/login") {
      navigate("/dashboard", { replace: true });
    }
  }, [authenticated, location.pathname, navigate]);

  if (!authenticated) {
    return (
      <Routes>
        <Route path="/login" element={<Login onLogin={() => setAuthenticated(true)} />} />
        <Route path="*" element={<Login onLogin={() => setAuthenticated(true)} />} />
      </Routes>
    );
  }

  return (
    <MainLayout onLogout={() => setAuthenticated(false)}>
      <Routes>
        <Route path="/"               element={<Dashboard />} />
        <Route path="/dashboard"      element={<Dashboard />} />
        <Route path="/admin-panel"    element={<AdminPanel />} />
        <Route path="/sales-managers" element={<SalesManagers />} />
        <Route path="/area-managers"  element={<AreaManagers />} />
        <Route path="/supervisors"    element={<Supervisors />} />
        <Route path="/sales-reps"     element={<SalesReps />} />
        <Route path="*"               element={<NotFound />} />
      </Routes>
    </MainLayout>
  );
}
