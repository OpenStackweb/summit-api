<?php
/**
 * Copyright 2020 OpenStack Foundation
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
    'base_url' => env('CFP_APP_BASE_URL', null),
    'support_email' => env('CFP_SUPPORT_EMAIL', null),
    'client_id' => env('CFP_OAUTH2_CLIENT_ID', null),
    'scopes' => env('CFP_OAUTH2_SCOPES', null),
];