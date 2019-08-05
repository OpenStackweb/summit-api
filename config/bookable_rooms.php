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
    'reservation_lifetime' => env('BOOKABLE_ROOMS_RESERVATION_LIFETIME', 5),
    'admin_email' => env('BOOKABLE_ROOMS_ADMIN_EMAIL'),
    'enable_bookable_rooms_reservation_revocation' => env('ENABLE_BOOKABLE_ROOMS_RESERVATION_REVOCATION', false),
];