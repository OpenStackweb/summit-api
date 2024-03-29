<?php namespace App\Services\Apis;
/*
 * Copyright 2023 OpenStack Foundation
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
 * Interface IMUXApi
 * @package App\Services\Apis
 */
interface IMUXApi
{
    public function setCredentials(MuxCredentials $credentials):IMUXApi;
    /**
     * @return array
     */
    public function createUrlSigningKey():array;

    /**
     * @param array $allowed_domains
     * @param bool $allow_no_referrer
     * @return array
     */
    public function createPlaybackRestriction(array $allowed_domains, bool $allow_no_referrer = false):array;

    /**
     * @param string $playback_restriction_id
     * @return void
     */
    public function deletePlaybackRestriction(string $playback_restriction_id): void;
}