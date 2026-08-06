import { ChevronDownIcon, ChevronUpIcon } from "@heroicons/react/24/outline";
import { useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";

import { getCurrentUser, logout } from "../services/authService";
import { ADMIN_MENU } from "./menu";

const AdminSidebar = ({ collapsed, toggleSidebar, onLogout }) => {
  const [openMenu, setOpenMenu] = useState(null);
  const [openSubMenu, setOpenSubMenu] = useState(null);
  const [hovered, setHovered] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();

  const adminUser = getCurrentUser();
  const user = {
    name:   adminUser?.name  || "Admin",
    email:  adminUser?.email || "",
    avatar: `https://ui-avatars.com/api/?name=${encodeURIComponent(adminUser?.name || "Admin")}&background=3b82f6&color=fff&size=40`,
  };

  const handleNavigate = (path) => navigate(path);

  const handleLogout = async () => {
    await logout();
    onLogout?.();
    navigate("/login");
  };

  const isActive = (item) => {
    if (item.subMenu) {
      return item.subMenu.some(
        (sub) =>
          location.pathname.startsWith(sub.path) ||
          (sub.subMenu && sub.subMenu.some((ss) => location.pathname.startsWith(ss.path)))
      );
    }
    return location.pathname.startsWith(item.path);
  };

  const toggleSubmenu = (title) => {
    setOpenMenu((prev) => (prev === title ? null : title));
    setOpenSubMenu(null);
  };

  const toggleSubSubmenu = (title) => setOpenSubMenu((prev) => (prev === title ? null : title));

  const isCollapsed = collapsed && !hovered; // <-- Collapsed only if not hovered

  return (
    <div
      onMouseEnter={() => setHovered(true)}
      onMouseLeave={() => setHovered(false)}
      className={`fixed inset-y-0 left-0 z-40 flex h-screen flex-col border-r bg-white transition-all duration-300 md:relative md:translate-x-0 ${isCollapsed ? "w-20" : "w-72"} ${collapsed ? "-translate-x-full md:translate-x-0" : "translate-x-0"} `}
    >
      {/* Top bar */}
      <div
        className={`border-b-secondary/20 flex flex-shrink-0 items-center justify-between border-b px-4 py-[4px] ${isCollapsed ? "hidden" : "block"}`}
      >
        <span className={`py-4 text-2xl font-semibold text-black`}>Sales Tracking System</span>
      </div>

      {/* Scrollable menu */}
      <nav className="custom-1 mx-1 mt-4 flex-1 space-y-1 overflow-y-auto px-2">
        {ADMIN_MENU.map((item) => {
          const active = isActive(item);
          const isSubmenuOpen = openMenu === item.title;

          return (
            <div key={item.title}>
              <div
                onClick={() => {
                  if (item.subMenu) toggleSubmenu(item.title);
                  else handleNavigate(item.path);
                }}
                className={`flex cursor-pointer items-center hover:bg-gray-100 ${
                  isCollapsed ? "justify-center" : "justify-between"
                } rounded-lg px-3 py-3 transition ${
                  active || isSubmenuOpen
                    ? "bg-secondary text-primary bg-blue-50 font-semibold text-blue-500"
                    : "font-semibold text-black"
                }`}
              >
                <div className="flex items-center">
                  <item.icon className={` ${isCollapsed ? "mr-0" : "mr-[5px]"} h-[21px] w-[21px] stroke-2`} />
                  {!isCollapsed && <span>{item.title}</span>}
                </div>
                {!isCollapsed && item.subMenu && (
                  <span>
                    {isSubmenuOpen ? (
                      <ChevronUpIcon className="h-3 w-3 stroke-[3]" />
                    ) : (
                      <ChevronDownIcon className="h-3 w-3 stroke-[3]" />
                    )}
                  </span>
                )}
              </div>

              {item.subMenu && isSubmenuOpen && !isCollapsed && (
                <div className="ml-2 mt-1">
                  {item.subMenu.map((sub) => {
                    const isSubSubmenuOpen = openSubMenu === sub.title;
                    const hasSubSubmenu = sub.subMenu && sub.subMenu.length > 0;

                    return (
                      <div key={sub.title}>
                        <div
                          onClick={() => {
                            if (hasSubSubmenu) toggleSubSubmenu(sub.title);
                            else handleNavigate(sub.path);
                          }}
                          className={`relative flex cursor-pointer items-center px-4 py-2 transition ${
                            location.pathname.startsWith(sub.path)
                              ? "text-secondary font-semibold"
                              : "text-black"
                          }`}
                        >
                          {location.pathname.startsWith(sub.path) && (
                            <span className="bg-secondary absolute left-0 h-7 w-1 rounded"></span>
                          )}
                          <span>{sub.title}</span>
                          {hasSubSubmenu && (
                            <span className="ml-auto">
                              {isSubSubmenuOpen ? (
                                <ChevronUpIcon className="h-3 w-3 stroke-[3]" />
                              ) : (
                                <ChevronDownIcon className="h-3 w-3 stroke-[3]" />
                              )}
                            </span>
                          )}
                        </div>

                        {hasSubSubmenu && isSubSubmenuOpen && (
                          <div className="ml-6 mt-1">
                            {sub.subMenu.map((ss) => (
                              <div
                                key={ss.title}
                                onClick={() => handleNavigate(ss.path)}
                                className={`relative flex cursor-pointer items-center px-4 py-2 transition ${
                                  location.pathname === ss.path
                                    ? "text-secondary font-semibold"
                                    : "text-black"
                                }`}
                              >
                                {location.pathname === ss.path && (
                                  <span className="ring-secondary absolute left-0 h-1 w-1 rotate-45 ring-2"></span>
                                )}
                                <span>{ss.title}</span>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          );
        })}
      </nav>
      {/* User profile & logout */}
      <div className="flex items-center space-x-3 border-t border-gray-200 px-4 pt-4 transition-all duration-300 ease-in-out">
        {/* Avatar always visible */}
        <img
          src={user.avatar}
          alt={`${user.name} avatar`}
          className="h-10 w-10 flex-shrink-0 rounded-full object-cover"
        />

        {/* Show details only if sidebar expanded */}
        {!isCollapsed && (
          <div className="flex flex-col overflow-hidden">
            <p className="whitespace-nowrap text-sm font-semibold text-gray-900">{user.name}</p>
            <p className="w-40 truncate text-xs text-gray-500">{user.email}</p>
          </div>
        )}
      </div>
      <div className="flex items-center border-gray-200 px-4 pb-4 transition-all duration-300 ease-in-out">
        {!isCollapsed && (
          <button
            onClick={handleLogout}
            aria-label="Logout"
            className="mt-3 w-full rounded-md border border-red-600 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500"
          >
            Logout
          </button>
        )}
      </div>
    </div>
  );
};

export default AdminSidebar;
