<?php

return [
    'workspace_path' => env('SCAN_WORKSPACE_PATH') ?: storage_path('app/scan-workspaces'),
    'timeout_seconds' => (int) env('SCAN_TIMEOUT_SECONDS', 1800),
    'max_concurrent_jobs' => (int) env('SCAN_MAX_CONCURRENT_JOBS', 2),
];
