import axios from "axios";

const resolveApiBaseUrl = () => {
  return process.env.REACT_APP_API_BASE_URL || "http://localhost:8000/api/admin";
};

const api = axios.create({
  baseURL: resolveApiBaseUrl(),
  headers: { "Content-Type": "application/json", Accept: "application/json" },
});

// Attach JWT token to every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("authToken");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Redirect to login on 401 — but never on the login request itself
api.interceptors.response.use(
  (res) => res,
  (err) => {
    const url = err.config?.url || "";
    const isLoginRequest = url.includes("/auth/login");
    if (err.response?.status === 401 && !isLoginRequest) {
      localStorage.removeItem("authToken");
      localStorage.removeItem("adminUser");
      if (window.location.pathname !== "/login") {
        window.location.href = "/login";
      }
    }
    return Promise.reject(err);
  }
);

export default api;
