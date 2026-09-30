<?php

return [
    'timezone' => 'Asia/Jakarta',
    // Do not use a log/failover mailer for authentication messages.
    'mailer' => env('ASSESSMENT_MAILER', 'smtp'),
    'close_hour' => 17,
    'close_minute' => 0,
];
