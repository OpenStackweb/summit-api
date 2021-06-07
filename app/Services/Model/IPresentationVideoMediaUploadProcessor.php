<?php namespace App\Services\Model;
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
use App\Services\Apis\MuxCredentials;

/**
 * Interface IPresentationVideoMediaUploadProcessor
 * @package App\Services\Model
 */
interface IPresentationVideoMediaUploadProcessor
{
    /**
     * @param int $summit_id
     * @param MuxCredentials $credentials
     * @param string|null $mountingFolder
     * @return int
     */
    public function processPublishedPresentationFor(int $summit_id, MuxCredentials $credentials, ?string $mountingFolder = null):int;

    /**
     * @param int $event_id
     * @param string|null $mountingFolder
     * @param MuxCredentials|null $credentials
     * @return bool
     */
    public function processEvent(int $event_id, ?string $mountingFolder, ?MuxCredentials $credentials = null):bool;

    /**
     * @param int $event_id
     * @param MuxCredentials|null $credentials
     */
    public function enableMP4Support(int $event_id, ?MuxCredentials $credentials = null):void;

    /**
     * @param int $summit_id
     * @param MuxCredentials $credentials
     * @param string|null $mail_to
     * @return int
     * @throws \Exception
     */
    public function processSummitEventsStreamURLs
    (
        int $summit_id,
        MuxCredentials $credentials,
        ?string $mail_to
    ):int;

    /**
     * @param int $summit_id
     * @param MuxCredentials $credentials
     * @param string|null $mail_to
     * @return int
     * @throws \Exception
     */
    public function createVideosFromMUXAssets(int $summit_id,
                                              MuxCredentials $credentials,
                                              ?string $mail_to):int;
}