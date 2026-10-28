<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
    'release' => env('SENTRY_RELEASE'),
    'send_default_pii' => false,
    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') !== null
        ? (float) env('SENTRY_TRACES_SAMPLE_RATE')
        : null,
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE') !== null
        ? (float) env('SENTRY_PROFILES_SAMPLE_RATE')
        : null,
    'enable_logs' => false,
    'enable_metrics' => true,
    'ignore_transactions' => ['/up'],
    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'sql_queries' => true,
        'sql_bindings' => false,
        'queue_info' => true,
        'command_info' => true,
        'http_client_requests' => true,
        'notifications' => true,
    ],
    'tracing' => [
        'queue_job_transactions' => true,
        'queue_jobs' => true,
        'sql_queries' => true,
        'sql_bindings' => false,
        'http_client_requests' => true,
        'notifications' => true,
    ],
];
