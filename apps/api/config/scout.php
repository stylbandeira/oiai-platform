<?php

return [
    'driver' => env('SCOUT_DRIVER', 'database'),
    'prefix' => env('SCOUT_PREFIX', ''),
    'queue' => env('SCOUT_QUEUE', false),
    'chunk' => ['searchable' => 500, 'unsearchable' => 500],
    'soft_delete' => false,
    'identify' => env('SCOUT_IDENTIFY', false),
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'products' => [
                'searchableAttributes' => ['name', 'brand', 'aliases', 'description', 'product_type', 'category', 'search_terms'],
                'filterableAttributes' => ['brand_id', 'category_id', 'validated', 'quantity_dimension', 'unit_id', 'available_states', 'ean', 'sku'],
                'sortableAttributes' => ['average_price', 'popularity', 'offer_count', 'updated_at'],
                'typoTolerance' => [
                    'enabled' => true,
                    'disableOnAttributes' => ['ean', 'sku'],
                ],
            ],
        ],
    ],
];
