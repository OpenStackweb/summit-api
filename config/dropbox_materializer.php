<?php

return [
    'base_url'     => env('DROPBOX_MATERIALIZER_URL', 'http://localhost:8100'),
    'internal_key' => env('DROPBOX_MATERIALIZER_KEY', ''),
    'timeout'      => env('DROPBOX_MATERIALIZER_TIMEOUT', 10),
];
