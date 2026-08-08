<?php

declare(strict_types=1);

return [
    'local_cache_retention_hours' => env('SYNC_LOCAL_CACHE_RETENTION_HOURS', 72),
    'local_order_retention_hours' => env('SYNC_LOCAL_ORDER_RETENTION_HOURS', 168),
    'targets' => [
        'catalog_snapshot_p95' => env('SYNC_CATALOG_SNAPSHOT_P95_MS', 800),
        'order_replay_p95' => env('SYNC_ORDER_REPLAY_P95_MS', 1200),
        'sync_push_p95' => env('SYNC_PUSH_P95_MS', 1000),
        'sync_pull_p95' => env('SYNC_PULL_P95_MS', 700),
        'queue_delay_p95' => env('SYNC_QUEUE_DELAY_P95_MS', 5000),
        'scheduler_freshness_p95' => env('SYNC_SCHEDULER_FRESHNESS_P95_MS', 60000),
    ],
];
