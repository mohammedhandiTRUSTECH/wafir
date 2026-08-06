<?php

return [
    'rep_tiers' => [
        ['min' => 0.0,  'max' => 79.5,  'rate' => 0.0, 'label' => 'Below 79.5%'],
        ['min' => 79.5, 'max' => 90.5,  'rate' => 1.0, 'label' => '79.5% - 90.5%'],
        ['min' => 90.5, 'max' => 95.5,  'rate' => 1.5, 'label' => '90.5% - 95.5%'],
        ['min' => 95.5, 'max' => null,  'rate' => 2.0, 'label' => '95.5% and above'],
    ],

    'supervisor_tiers' => [
        ['min' => 0.0,  'max' => 79.5,  'rate' => 0.0,  'label' => 'Below 79.5%'],
        ['min' => 79.5, 'max' => 90.5,  'rate' => 0.2,  'label' => '79.5% - 90.5%'],
        ['min' => 90.5, 'max' => 95.5,  'rate' => 0.35, 'label' => '90.5% - 95.5%'],
        ['min' => 95.5, 'max' => null,  'rate' => 0.5,  'label' => '95.5% and above'],
    ],

    'sales_manager_tiers' => [
        ['min' => 0.0,  'max' => 79.5,  'rate' => 0.0,  'label' => 'Below 79.5%'],
        ['min' => 79.5, 'max' => 90.5,  'rate' => 0.1,  'label' => '79.5% - 90.5%'],
        ['min' => 90.5, 'max' => 95.5,  'rate' => 0.2,  'label' => '90.5% - 95.5%'],
        ['min' => 95.5, 'max' => null,  'rate' => 0.25, 'label' => '95.5% and above'],
    ],

    'reps' => [
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'منصور اشرف',                 'target' => 125000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'عبدالتواب سلامة + كريم رماح', 'target' => 125000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'طاهر هريسة',                 'target' => 139000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'خورم',                       'target' => 115000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'توفيل زامان',                'target' => 100000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'عمران فرمان',                'target' => 123000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'ممتاز احمد',                 'target' => 160000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'اسلام عوض',                  'target' => 125000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'سرجاب محمد',                 'target' => 100000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'رضوان احمد',                 'target' => 125000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'قل زيب بوستان',             'target' => 105000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'محمد اشفاق',                 'target' => 115000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'اشرف الخيرات',              'target' => 101000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'صدام المرهبي',               'target' => 101000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'ضياء الغرباني',              'target' => 101000],
        ['area' => 'Riyadh',  'supervisor' => 'Mufed Idris',    'name' => 'عمر المحقني',                'target' => 101000],
        ['area' => 'Qassim',  'supervisor' => null,             'name' => 'اشرف موتين',                 'target' => 115000],
        ['area' => 'Qassim',  'supervisor' => null,             'name' => 'حمزة',                       'target' => 120000],
        ['area' => 'Qassim',  'supervisor' => null,             'name' => 'علي مسعود',                  'target' => 121000],
        ['area' => 'Qassim',  'supervisor' => null,             'name' => 'علاء الدين جلب',             'target' => 105000],
        ['area' => 'Qassim',  'supervisor' => null,             'name' => 'ابوبكر كالام',               'target' => 325000],
        ['area' => 'Dammam',  'supervisor' => 'Anas Gowri',     'name' => 'ابراهيم العلماني',           'target' => 100000],
        ['area' => 'Dammam',  'supervisor' => 'Anas Gowri',     'name' => 'محمد عجوة',                  'target' => 110000],
        ['area' => 'Dammam',  'supervisor' => 'Anas Gowri',     'name' => 'فيصل خان',                   'target' => 120000],
        ['area' => 'Dammam',  'supervisor' => 'Anas Gowri',     'name' => 'عبد المالك النجار',          'target' => 120000],
        ['area' => 'Dammam',  'supervisor' => 'Anas Gowri',     'name' => 'عبيد ايوب',                  'target' => 133000],
        ['area' => 'Madinah', 'supervisor' => 'Sultan',         'name' => 'يوسف احمد',                  'target' => 120000],
        ['area' => 'Madinah', 'supervisor' => 'Sultan',         'name' => 'اسامه الدميني',              'target' => 105000],
        ['area' => 'Abha',    'supervisor' => 'Muhammad Eran',  'name' => 'إيهاب الزبيدي',              'target' => 145000],
        ['area' => 'Jeddah',  'supervisor' => 'Muhammad Hisham','name' => 'حسين',                       'target' => 100000],
        ['area' => 'Jeddah',  'supervisor' => 'Muhammad Hisham','name' => 'عبدالله قمحان',              'target' => 100000],
        ['area' => 'Jeddah',  'supervisor' => 'Muhammad Hisham','name' => 'احمد الشريف',                 'target' => 100000],
    ],
];
