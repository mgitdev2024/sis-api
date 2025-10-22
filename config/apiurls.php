<?php

return [
    'mgios' => [
        'url' => env('MGIOS_URL'),
        'public_stock_adjustment_pullout_get' => '/public/stock-adjustment/pullout/get/',
        'public_receiving_stock_transfer_get' => '/public/receiving/stock/transfer/get/',
        'public_item_masterdata_collection_get' => '/public/item/masterdata-collection/get',
        'public_item_uom_get' => '/public/item-uom/get',
        'public_get_item_by_department' => '/public/item-by-department/get/',
        'public_get_department_by_category' => '/public/department-by-category/get',

        'item_details_get' => '/item-details/get/',
        'check_item_code' => '/check-item-code/',
        'receiving_stock_transfer_create' => '/receiving/stock-transfer/create',
        'store_inventory_data_update' => '/store-inventory-data/update/',
        'stock_adjustment_create' => '/stock-adjustment/create',
    ],
    'scm' => [
        'url' => env('SCM_URL'),
        'public_reason_list_current_get' => '/public/reason-list/current/get/',
        'item_masterdata_details_get' => '/item/masterdata-details/get/',
        'stock_conversion_item_id_get' => '/stock/conversion/item-id/get/',
        'public_stock_count_lead_time_current_get' => '/public/stock-count-lead-time/current/get',
        'stock_conversion_item_id_get_auto_convert' => '/stock/conversion/item-id/get-auto-convert/',
    ],
    'sds' => [
        'url' => env('SDS_URL'),
        'public_store_list_get' => '/public/store/list',
        'public_store_get_by_code' => '/public/store/list/code/get/',
    ]
];
