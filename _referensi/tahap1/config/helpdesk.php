<?php

return [
    'app_name' => env('HELPDESK_NAME', 'IT Helpdesk Alita'),

    // Bisa lebih dari satu, pisahkan dengan koma: rio@alita.id,it@alita.id
    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HELPDESK_ADMIN_EMAIL', 'rio@alita.id'))
    ))),

    'max_upload_kb' => 5120,
];
