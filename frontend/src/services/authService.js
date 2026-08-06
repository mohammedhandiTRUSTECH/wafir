import api from "./api";

export const login = async (email, password) => {
  const { data } = await api.post("/auth/login", { email, password });
  if (data.status) {
    localStorage.setItem("authToken", data.token);
    localStorage.setItem("adminUser", JSON.stringify(data.admin));
  }
  return data;
};

export const logout = async () => {
  try { await api.post("/auth/logout"); } catch (_) {}
  localStorage.removeItem("authToken");
  localStorage.removeItem("adminUser");
};

export const getMe = () => api.get("/auth/me");

export const isAuthenticated = () => !!localStorage.getItem("authToken");

export const getCurrentUser = () => {
  const raw = localStorage.getItem("adminUser");
  try { return raw ? JSON.parse(raw) : null; } catch { return null; }
};
