// src/i18n/index.js

import i18n from "i18next";
import { initReactI18next } from "react-i18next";

import ar from "./localization/ar.json";
import en from "./localization/en.json";

// Import JSON files

const fallbackLang = "en";

// Get language from query param
const getLangFromUrl = () => {
  const searchParams = new URLSearchParams(window.location.search);
  return searchParams.get("lang") || fallbackLang;
};

i18n.use(initReactI18next).init({
  lng: getLangFromUrl(),
  fallbackLng: fallbackLang,
  interpolation: { escapeValue: false },
  resources: {
    en: { translation: en },
    ar: { translation: ar },
  },
});

export default i18n;
