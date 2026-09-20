<?php

return [
    'bypass' => (bool) env('SUPERSEEDER_BYPASS', false),
    'table' => env('SUPERSEEDER_TABLE', 'seeder_executions'),
    'use_timestamped_seeders' => (bool) env('SUPERSEEDER_USE_TIMESTAMPED_SEEDERS', true),
    'seeders_path' => env('SUPERSEEDER_SEEDERS_PATH'),
    'rollback' => [
        'production_enabled' => (bool) env('SUPERSEEDER_ROLLBACK_PRODUCTION_ENABLED', false),
    ],
];
