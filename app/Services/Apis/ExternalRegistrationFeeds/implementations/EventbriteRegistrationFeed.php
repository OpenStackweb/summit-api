<?php namespace App\Services\Apis\ExternalRegistrationFeeds\implementations;
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
use App\Services\Apis\AbstractExternalFeed;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeed;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedResponse;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;
use services\apis\EventbriteAPI;
use DateTime;
/**
 * Class EventbriteRegistrationFeed
 * @package App\Services\Apis\ExternalRegistrationFeeds\implementations
 */
final class EventbriteRegistrationFeed extends AbstractExternalFeed
    implements IExternalRegistrationFeed
{

    /**
     * @param int $page
     * @param DateTime|null $changed_since
     * @return IExternalRegistrationFeedResponse|null
     */
    public function getAttendees(int $page = 1, ?DateTime $changed_since = null):?IExternalRegistrationFeedResponse
    {
        try {
            $apiFeedKey = $this->summit->getExternalRegistrationFeedApiKey();
            $eventId    = $this->summit->getExternalSummitId();

            if (empty($apiFeedKey)) {
                Log::warning(sprintf("external_registration_feed_api_key is empty for summit %s", $this->summit->getId()));
                return null;
            }
            if (empty($eventId)) {
                Log::warning(sprintf("external_summit_id is empty for summit %s", $this->summit->getId()));
                return null;
            }

            $api = new EventbriteAPI();

            $api->setCredentials([
                'token' => $apiFeedKey
            ]);

            return $api->getAttendees($this->summit, $page, $changed_since,'promotional_code,order,ticket_class');
        }
        catch(RequestException $ex){
            Log::warning($ex->getMessage());
            throw $ex;
        }
    }

    public function isValidQRCode(string $qr_code_content): bool
    {
        // TODO: Implement isValidQRCode() method.
        throw new \Exception("Not Implemented");
    }

    public function getAttendeeByQRCode(string $qr_code_content)
    {
        throw new \Exception("Not Implemented");
    }

    public function getAttendeeByEmail(string $email)
    {
        throw new \Exception("Not Implemented");
    }

    public function getExternalUserIdFromQRCode(string $qr_code_content): ?string
    {
        throw new \Exception("Not Implemented");
    }

    public function shouldCreateExtraQuestions(): bool
    {
        return false;
    }

    public function checkAttendee(string $external_id): void
    {
        throw new \Exception("Not Implemented");
    }

    public function unCheckAttendee(string $external_id): void
    {
        throw new \Exception("Not Implemented");
    }
}