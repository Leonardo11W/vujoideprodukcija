<?php

return [

    'ARRAY_MENU' => [
        [
            'start_icon' => 'fa-solid fa-truck-field',
            'title' => 'sidebar.supply',
            'menu_item_type' => 'parent',
            'route' => 'backend.products.index',
            'permission' => ['view_logistics', 'view_logistic_zone'],
            'order' => 8,
            'children' => [
                [
                    'title' => 'sidebar.logistics',
                    'route' => 'backend.logistics.index',
                    'active' => 'app/logistics',
                    'permission' => ['view_logistics'],
                    'order' => 0,
                ],
                [
                    'title' => 'sidebar.logistic_zone',
                    'route' => 'backend.logistic-zones.index',
                    'active' => 'app/logistic-zones',
                    'permission' => ['view_logistic_zone'],
                    'order' => 1,
                ],
            ],
        ],
    ],
];
