<?php

return [
    'id_server' => env('RUSTDESK_ID_SERVER', '10.100.1.96'),
    'relay_server' => env('RUSTDESK_RELAY_SERVER', '10.100.1.96'),
    'api_server' => env('RUSTDESK_API_SERVER'),
    'public_key' => env('RUSTDESK_PUBLIC_KEY'),
    'open_url_template' => env('RUSTDESK_OPEN_URL_TEMPLATE', 'rustdesk://connect/{id}'),
];
