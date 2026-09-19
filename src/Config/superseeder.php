<?php

return [
    'bypass' => (bool) env('SUPERSEEDER_BYPASS', false),
    'table' => env('SUPERSEEDER_TABLE', 'seeder_executions'),
    'rollback' => [
        'production_enabled' => (bool) env('SUPERSEEDER_ROLLBACK_PRODUCTION_ENABLED', false),
    ],
];
