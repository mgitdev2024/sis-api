<?php

return [
    'oauth2' => [
        'access_token_url' => env('SAP_OAUTH_2_ACCESS_TOKEN_URL'),
        'client_id' => env('SAP_OAUTH_2_CLIENT_ID'),
        'client_secret' => env('SAP_OAUTH_2_CLIENT_SECRET'),
    ],

    'basic_auth' => [
        'username' => env('SAP_BASIC_AUTH_USERNAME'),
        'password' => env('SAP_BASIC_AUTH_PASSWORD'),
    ],

    'endpoints' => [
        'outbound_goods_issue' => env('SAP_OUTBOUND_GOODS_ISSUE_API_URL'),
        'inbound_good_receipt' => env('SAP_INBOUND_GOOD_RECEIPT_API_URL'),
        'inbound_purchase_requisition' => env('SAP_INBOUND_PURCHASE_REQUISITION_API_URL'),
    ],
];