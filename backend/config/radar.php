<?php

return [
    'locations' => [
        'official_hosts' => array_values(array_filter(array_map('trim', explode(',', env('RADAR_OFFICIAL_LOCATION_HOSTS', 'kemendagri.go.id'))))),
        'max_file_bytes' => (int) env('RADAR_LOCATION_MAX_FILE_BYTES', 262144000),
    ],
    'http' => [
        'connect_timeout' => 5,
        'timeout' => 20,
        'retries' => 2,
        'retry_delay_ms' => 250,
        'max_response_bytes' => 5000000,
        'user_agent' => 'RADAR/1.0 public-intelligence',
    ],
];