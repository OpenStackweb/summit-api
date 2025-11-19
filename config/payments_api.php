<?php

return [
    'base_url'                                     => env('PAYMENTS_SERVICE_BASE_URL', null),
    'service_client_id'                            => env('PAYMENTS_SERVICE_OAUTH2_CLIENT_ID', null),
    'service_client_secret'                        => env('PAYMENTS_SERVICE_OAUTH2_CLIENT_SECRET', null),
    'service_client_scopes'                        => env('PAYMENTS_SERVICE_OAUTH2_SCOPES', null),
];
