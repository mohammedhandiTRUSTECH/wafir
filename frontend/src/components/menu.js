import {
  BanknotesIcon,
  BriefcaseIcon,
  ClipboardDocumentCheckIcon,
  HomeIcon,
  RectangleGroupIcon,
  UserGroupIcon,
  UsersIcon,
} from "@heroicons/react/24/outline";

export const ADMIN_MENU = [
  {
    title: "Dashboard",
    icon: RectangleGroupIcon,
    path: "/dashboard",
  },
  {
    title: "Admin Panel",
    icon: UserGroupIcon,
    path: "/admin-panel",
  },
  {
    title: "Sales Manager",
    icon: UsersIcon,
    path: "/sales-managers",
  },
  {
    title: "Area Manager",
    icon: BriefcaseIcon,
    path: "/area-managers",
  },
  {
    title: "Supervisor View",
    icon: ClipboardDocumentCheckIcon,
    path: "/supervisors",
  },
  {
    title: "Sales Rep View",
    icon: BanknotesIcon,
    path: "/sales-reps",
  },
  //   {
  //     title: "Attendance & Leaves",
  //     icon: CalendarDaysIcon,
  //     subMenu: [
  //       { title: "Student Attendance", path: "/admin/attendance-students" },
  //       { title: "Employee Attendance", path: "/admin/attendance-employees" },
  //       { title: "Student Leaves", path: "/admin/leaves-students" },
  //       { title: "Employee leaves", path: "/admin/leaves-employees" },
  //     ],
  //   },
  //   {
  //     title: "Communication",
  //     icon: ChatBubbleLeftRightIcon,
  //     path: "/admin/communication",
  //     subMenu: [
  //       { title: "Messages", path: "/admin/communication/messages" },
  //       { title: "Notifications", path: "/admin/communication/notifications" },
  //       { title: "Announcements", path: "/admin/communication/announcements" },
  //     ],
  //   },
  //   {
  //     title: "Finance",
  //     icon: BanknotesIcon,
  //     path: "/admin/finance",
  //     subMenu: [
  //       { title: "Invoices", path: "/admin/finance/invoices" },
  //       { title: "Products & Services", path: "/admin/finance/products-services" },
  //       { title: "Payment Requests", path: "/admin/finance/payment-requests" },
  //       { title: "Payments", path: "/admin/finance/payments" },
  //     ],
  //   },
  //   {
  //     title: "Reports",
  //     icon: ChartBarIcon,
  //     path: "/admin/reports",
  //   },
  //   {
  //     title: "Configurations",
  //     icon: Cog6ToothIcon,
  //     path: "/admin/configurations",
  //     subMenu: [
  //       { title: "General Configurations", path: "/admin/configurations/general-configurations" },
  //       { title: "Student Configurations", path: "/admin/configurations/student-configurations" },
  //       {
  //         title: "Attendance Configurations",
  //         path: "/admin/configurations/attendance-configurations",
  //         subMenu: [
  //           { title: "kiosk", path: "/admin/configurations/attendance-configurations/kiosk" },
  //           {
  //             title: "Ratio Management",
  //             path: "/admin/configurations/attendance-configurations/ratio-management",
  //           },
  //           {
  //             title: "Student Attendance",
  //             path: "/admin/configurations/attendance-configurations/student-attendance",
  //           },
  //           {
  //             title: "Employee Attendance",
  //             path: "/admin/configurations/attendance-configurations/employee-attendance",
  //           },
  //         ],
  //       },

  //       { title: "Employee Configurations", path: "/admin/configurations/employee-configurations" },
  //       {
  //         title: "Finance Configurations",
  //         path: "/admin/configurations/finance-configurations",
  //         subMenu: [
  //           { title: "General Finance", path: "/admin/configurations/finance-configurations/general-finance" },
  //           {
  //             title: "Invoice and payment requests",
  //             path: "/admin/configurations/finance-configurations/invoice-and-payment-requests",
  //           },
  //           { title: "Templates", path: "/admin/configurations/finance-configurations/templates" },
  //         ],
  //       },
  //       { title: "Roles & Permissions", path: "/admin/configurations/roles-permissions" },
  //     ],
  //   },
];
