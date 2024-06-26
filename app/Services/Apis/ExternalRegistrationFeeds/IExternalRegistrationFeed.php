<?php namespace App\Services\Apis\ExternalRegistrationFeeds;
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
use DateTime;

/**
 * Interface IExternalRegistrationFeed
 * @package App\Services\Apis\ExternalRegistrationFeeds
 */
interface IExternalRegistrationFeed
{
    /**
     * @param int $page
     * @param DateTime|null $changed_since
     * @return IExternalRegistrationFeedResponse|null
     */
    public function getAttendees(int $page = 1, ?DateTime $changed_since = null):?IExternalRegistrationFeedResponse;

    /**
     * @param string $qr_code_content
     * @return bool
     */
    public function isValidQRCode(string $qr_code_content):bool;

    /**
     * @param string $qr_code_content
     * @return string|null
     */
    public function getExternalUserIdFromQRCode(string $qr_code_content):?string;

    /**
     * @param string $qr_code_content
     * @return mixed
     */
    public function getAttendeeByQRCode(string $qr_code_content);

    /**
     * @param string $email
     * @return mixed
     */
    public function getAttendeeByEmail(string $email);

    public function shouldCreateExtraQuestions():bool;

    /**
     * @param string $external_id
     * @return void
     */
    public function checkAttendee(string $external_id):void;

    /**
     * @param string $external_id
     * @return void
     */
    public function unCheckAttendee(string $external_id):void;

}