<?php namespace App\Jobs\SponsorServices;
/*
 * Copyright 2025 OpenStack Foundation
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

/**
 * Class EventTypes
 * @package App\Jobs\SponsorServices
 */
final class EventTypes
{
    const string AUTH_USER_ADDED_TO_GROUP = 'auth_user_added_to_group';
    const string AUTH_USER_REMOVED_FROM_GROUP = 'auth_user_removed_from_group';
    const string AUTH_USER_ADDED_TO_SPONSOR_AND_SUMMIT = 'auth_user_added_to_sponsor_and_summit';
    const string AUTH_USER_REMOVED_FROM_SPONSOR_AND_SUMMIT = 'auth_user_removed_from_sponsor_and_summit';
    const string AUTH_USER_REMOVED_FROM_SUMMIT = 'auth_user_removed_from_summit';
}