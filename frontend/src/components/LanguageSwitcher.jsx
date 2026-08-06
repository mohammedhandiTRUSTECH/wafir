import React, { useEffect, useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import { FaCheck, FaGlobe } from "react-icons/fa";
import { useLocation, useNavigate } from "react-router-dom";

export default function LanguageSwitcher() {
  const { i18n } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [selectedLang, setSelectedLang] = useState(i18n.language);
  const dropdownRef = useRef(null);

  const changeLanguage = (lang) => {
    i18n.changeLanguage(lang).then(() => setSelectedLang(lang));
    const searchParams = new URLSearchParams(location.search);
    searchParams.set("lang", lang);
    navigate(`${location.pathname}?${searchParams.toString()}`);
    setOpen(false);
  };

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={() => setOpen(!open)}
        className={`flex items-center justify-center rounded-full p-1 px-2 text-white transition hover:bg-white/30`}
      >
        <div className="flex items-center gap-2">
          <span className="text-sm text-black">{selectedLang === "ar" ? "العربية" : "English"}</span>
        </div>
      </button>

      {open && (
        <div className="absolute z-50 mt-0 w-32 rounded-lg border border-blue-200 bg-white backdrop-blur-md ltr:right-0 rtl:left-0">
          <button
            onClick={() => changeLanguage("en")}
            className={`flex w-full items-center justify-between px-4 py-2 text-left font-medium transition-colors duration-200 ${
              selectedLang === "en" ? "rounded-t-lg text-black" : "rounded-t-lg text-black"
            }`}
          >
            English
            {selectedLang === "en" && <FaCheck className="ml-2 text-green-500" />}
          </button>
          <button
            onClick={() => changeLanguage("ar")}
            className={`flex w-full items-center justify-between px-4 py-2 text-left font-medium transition-colors duration-200 ${
              selectedLang === "ar" ? "rounded-b-lg text-black" : "rounded-b-lg text-black"
            }`}
          >
            العربية
            {selectedLang === "ar" && <FaCheck className="ml-2 text-green-500" />}
          </button>
        </div>
      )}
    </div>
  );
}
