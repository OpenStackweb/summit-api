<?php namespace App\Services\Apis\ExternalRegistrationFeeds\implementations;
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

use App\Services\Apis\AbstractExternalFeed;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeed;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedResponse;
use App\Services\Apis\Samsung\EmptyResponse;
use App\Services\Apis\Samsung\InvalidResponse;
use App\Services\Apis\Samsung\ISamsungRegistrationAPI;
use App\Services\Apis\Samsung\PayloadParamNames;
use App\Services\Apis\Samsung\Regions;
use DateTime;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use services\apis\EventbriteAPI;

/**
 * Class SamsungRegistrationFeed
 * @package App\Services\Apis\ExternalRegistrationFeeds\implementations
 */
final class SamsungRegistrationFeed
    extends AbstractExternalFeed
    implements IExternalRegistrationFeed
{

    /**
     * @var ISamsungRegistrationAPI
     */
    private $api;
    /**
     * @param Summit $summit
     * @param ISamsungRegistrationAPI $api
     * @param ClientInterface $client
     */
    public function __construct(Summit $summit, ISamsungRegistrationAPI $api, ClientInterface $client)
    {
        parent::__construct($summit, $client);
        $this->api = $api;
    }

    public function getAttendees(int $page = 1, ?DateTime $changed_since = null): ?IExternalRegistrationFeedResponse
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

            return $this->api->userList($this->summit);
        }
        catch(InvalidResponse $ex){
            Log::warning($ex->getMessage());
            throw $ex;
        }
        catch (EmptyResponse $ex){
            Log::warning($ex->getMessage());
            throw $ex;
        }
        catch(RequestException $ex){
            Log::warning($ex->getMessage());
            throw $ex;
        }
    }

    public function isValidQRCode(string $qr_code_content): bool
    {
        $qr_json = json_decode($qr_code_content, true);
        if(!$qr_json) return false;
        if(!isset($qr_json[PayloadParamNames::UserId])) return false;
        return true;
    }

    /**
     * @param string $qr_code_content
     * @return array|mixed
     */
    public function getAttendeeByQRCode(string $qr_code_content)
    {
        $qr_json = json_decode($qr_code_content, true);
        if(!$qr_json) return [];
        if(!isset($qr_json[PayloadParamNames::UserId])) return [];

        return $this->api->checkUser($this->summit, $qr_json[PayloadParamNames::UserId]);
    }

    /**
     * @param string $email
     * @return mixed
     */
    public function getAttendeeByEmail(string $email)
    {
        $email = trim($email);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return [];
        return $this->api->checkEmail($this->summit, $email);
    }

    /**
     * @param string $qr_code_content
     * @return string|null
     */
    public function getExternalUserIdFromQRCode(string $qr_code_content): ?string
    {
        $qr_json = json_decode($qr_code_content, true);
        if(!$qr_json) return null;
        return $qr_json[PayloadParamNames::UserId] ?? null;
    }

    public function shouldCreateExtraQuestions(): bool
    {
        return true;
    }
}