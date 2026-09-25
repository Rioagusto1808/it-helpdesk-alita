<?php

declare(strict_types=1);

return [
    'app_name' => env('HELPDESK_NAME', 'IT Helpdesk Alita'),

    // Boleh lebih dari satu, pisahkan dengan koma: rio@alita.id,it@alita.id
    'admin_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('HELPDESK_ADMIN_EMAIL', 'rio@alita.id'))))),

    // Kosong = semua domain email pemohon diterima.
    'allowed_email_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('HELPDESK_ALLOWED_EMAIL_DOMAINS', ''))))),

    'max_upload_kb' => (int) env('HELPDESK_MAX_UPLOAD_KB', 5120),
    'tracking_link_days' => (int) env('HELPDESK_TRACKING_LINK_DAYS', 30),
    'auto_close_days' => (int) env('HELPDESK_AUTO_CLOSE_DAYS', 3),
    'queue_board_limit' => 100,

    // Target dalam menit, jam kalender biasa.
    'sla' => [
        'urgent' => ['response' => 30, 'resolve' => 240],
        'tinggi' => ['response' => 120, 'resolve' => 1440],
        'sedang' => ['response' => 240, 'resolve' => 4320],
        'rendah' => ['response' => 1440, 'resolve' => 7200],
    ],
];
