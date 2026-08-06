import React from "react";
import { FaExclamationTriangle } from "react-icons/fa";
import { useNavigate } from "react-router-dom";

const NotFound = () => {
  const navigate = useNavigate();

  return (
    <div
      className="flex flex-col items-center justify-center bg-blue-50 px-6 text-center"
      style={{ minHeight: "calc(100vh - 140px)" }} // deduct header height
    >
      <FaExclamationTriangle className="mb-6 text-6xl text-blue-600" />
      <h1 className="text-5xl font-extrabold text-blue-600">404</h1>
      <p className="mt-4 text-lg text-gray-700">Oops! The page you are looking for does not exist.</p>
      <p className="mt-2 text-gray-500">It might have been removed, renamed, or never existed.</p>
      <button
        className="mt-6 rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700"
        onClick={() => navigate("/")}
      >
        Go Back Home
      </button>
    </div>
  );
};

export default NotFound;
