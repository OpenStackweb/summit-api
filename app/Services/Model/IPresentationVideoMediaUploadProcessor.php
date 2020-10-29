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


/**
 * Interface IPresentationVideoMediaUploadProcessor
 * @package App\Services\Model
 */
interface IPresentationVideoMediaUploadProcessor
{
    /**
     * @param int $summit_id
     * @param string|null $mountingFolder
     * @return int
     */
    public function processPublishedPresentationFor(int $summit_id, ?string $mountingFolder = null):int;

    /**
     * @param int $event_id
     * @param string|null $mountingFolder
     * @return bool
     */
    public function processEvent(int $event_id, ?string $mountingFolder):bool;

    /**
     * @param int $event_id
     */
    public function enableMP4Support(int $event_id):void;
}