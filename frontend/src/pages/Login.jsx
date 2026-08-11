import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { login } from "../services/authService";
import api from "../services/api";

export default function Login({ onLogin }) {
  const navigate  = useNavigate();
  const [email,    setEmail]    = useState("");
  const [password, setPassword] = useState("");
  const [error,    setError]    = useState("");
  const [loading,  setLoading]  = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const data = await login(email, password);
      if (data.status) {
        onLogin?.();
        navigate("/dashboard", { replace: true });
      } else {
        setError(data.message || "Invalid credentials");
      }
    } catch (err) {
      if (!err.response) {
        setError(`Cannot reach the API server at ${api.defaults.baseURL}. Start the backend or fix REACT_APP_API_BASE_URL.`);
      } else {
        setError(err.response?.data?.message || "Login failed. Please try again.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-100">
      <form onSubmit={handleSubmit} className="w-full max-w-sm rounded bg-white p-6 shadow">
        <h2 className="mb-6 text-center text-2xl font-bold">Admin Login</h2>

        {error && (
          <div className="mb-4 rounded border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-600">
            {error}
          </div>
        )}

        <label className="mb-2 block font-semibold" htmlFor="email">Email</label>
        <input
          id="email"
          type="email"
          className="mb-4 w-full rounded border px-3 py-2"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          autoComplete="email"
        />

        <label className="mb-2 block font-semibold" htmlFor="password">Password</label>
        <input
          id="password"
          type="password"
          className="mb-6 w-full rounded border px-3 py-2"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
          autoComplete="current-password"
        />

        <button
          type="submit"
          disabled={loading}
          className="w-full rounded bg-blue-600 py-2 text-white hover:bg-blue-700 disabled:opacity-60"
        >
          {loading ? "Logging in…" : "Log In"}
        </button>
      </form>
    </div>
  );
}
