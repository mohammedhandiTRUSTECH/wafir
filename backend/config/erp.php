<?php

return [
    'base_url' => env('ERP_BASE_URL', 'http://78.46.35.180/ZatcaIntegrationApi/api/Zatca/'),
    'authentication_route' => env('ERP_AUTHENTICATION_ROUTE', '/Authentication/Authenticate'),
    'users_list_route' => env('ERP_USERS_LIST_ROUTE', 'GetSalespersons?CompanyID='),
    'authentication_username' => env('ERP_AUTHENTICATION_USERNAME', 'API_User'),
    'authentication_password' => env('ERP_AUTHENTICATION_PASSWORD', 'Commate123!@#'),
    'sales_per_month_url' => env('ERP_SALES_PER_MONTH_URL', 'http://78.46.35.180/ZatcaIntegrationApi/api/Zatca/GetNetSales/'),
];
