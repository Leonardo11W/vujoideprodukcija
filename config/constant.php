<?php

return [

    'SUBSCRIPTION_STATUS' => [
        'PENDING' => 'pending',
        'ACTIVE' => 'active',
        'INACTIVE' => 'inactive',
    ],
    'USER_PERMISSION_ALLOW' => [
        //
    ],
    'MODULES' => [
        [
            'module_name' => 'Branch',
            'more_permission' => ['gallery'],
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Booking',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Service',
            'more_permission' => ['gallery'],
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Service Category',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Service Subcategory',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Staff',
            'more_permission' => ['password'],
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Customer',
            'more_permission' => ['password'],
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Page',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Tax',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Earning',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Review',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Setting',
            'more_permission' => ['holiday', 'bussiness_hours'],
            'is_custom_permission' => 1,
        ],
        [
            'module_name' => 'System',
            'more_permission' => ['settings'],
            'is_custom_permission' => 1,
        ],


        [
            'module_name' => 'Product',
            'more_permission' => ['stock', 'gallary'],
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Product Variations',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Product Category',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Product Subcategory',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Product Brand',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Product Units',
            'is_custom_permission' => 0,
        ],
         [
            'module_name' => 'Tag',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Product Orders',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Reports',
            'more_permission' => ['daily_booking_report', 'staff_report', 'payout_report', 'overall_booking_report', 'product_orders_report'],
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Dashboard',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Logistics',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Logistic Zone',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Location',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Faq',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Promotion',
            'more_permission' => ['coupon'],
            'is_custom_permission' => 0,
        ],
    ],
];
