<?php

return [
    'namespace' => env('SUPERSEEDER_NAMESPACE', 'Database/Seeders'),
    'bypass' => (bool) env('SUPERSEEDER_BYPASS', false),
    'table' => env('SUPERSEEDER_TABLE', 'seeder_executions'),
];
