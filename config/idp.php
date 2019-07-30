<?php
/**
 * Copyright 2019 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

return [
    'base_url'                   => env('IDP_BASE_URI', null),
    'authorization_endpoint'     => env('IDP_AUTHORIZATION_ENDPOINT', null),
    'token_endpoint' => env('IDP_TOKEN_ENDPOINT', null),
    'introspection_endpoint' => env('IDP_INTROSPECTION_ENDPOINT', null),
    'userinfo_endpoint'   => env('IDP_USERINFO_ENDPOINT', null),
];