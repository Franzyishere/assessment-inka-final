<?php

return [
    'timezone' => 'Asia/Jakarta',
    // Do not use a log/failover mailer for authentication messages.
    'mailer' => env('ASSESSMENT_MAILER', 'smtp'),
];
