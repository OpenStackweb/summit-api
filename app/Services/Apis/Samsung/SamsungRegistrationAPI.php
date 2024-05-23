<?php namespace App\Services\Apis\Samsung;
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
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\summit\Summit;

/**
 * Class SamsungRegistrationAPI
 * @package App\Services\Apis\Samsung
 */
final class SamsungRegistrationAPI implements ISamsungRegistrationAPI
{

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client
     * @param string $endpoint
     */
    public function __construct
    (
        ClientInterface $client,
        string $endpoint
    )
    {
        $this->endpoint = $endpoint;
        $this->client = $client;
    }


    /**
     * @param Summit $summit
     * @param string $userId
     * @return array|mixed|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkUser(Summit $summit, string $userId)
    {
        try {
            Log::debug(sprintf("SamsungRegistrationAPI::checkUser summit %s user %s", $summit->getId(), $userId));

            $metadata = $summit->getRegistrationFeedMetadata();
            $metadata[PayloadParamNames::ExternalShowId] = $summit->getExternalSummitId();
            $defaultTicketType = $summit->getFirstDefaultTicketType();
            if(is_null($defaultTicketType))
                throw new ValidationException(sprintf("Summit %s has not default ticket type set.", $summit->getId()));

            $metadata[PayloadParamNames::DefaultTicketId] = $defaultTicketType->getId();
            $metadata[PayloadParamNames::DefaultTicketDescription] = $defaultTicketType->getDescription();
            $metadata[PayloadParamNames::DefaultTicketName] = $defaultTicketType->getName();

            $request = new CheckUserRequest
            (
                $userId,
                $metadata
            );

            Log::debug(sprintf("SamsungRegistrationAPI::checkUser POST %s payload %s", $this->endpoint, $request));

            // http://docs.guzzlephp.org/en/stable/request-options.html
            $response = $this->client->request('POST',
                $this->endpoint,
                [
                    'timeout' => 120,
                    'http_errors' => true,
                    RequestOptions::JSON => (new EncryptedPayload($summit->getExternalRegistrationFeedApiKey(), $request))->getPayload()
                ]
            );

            $response = new DecryptedSingleResponse
            (
                $summit->getExternalRegistrationFeedApiKey(),
                $response->getBody()->getContents(),
                $metadata
            );

            Log::debug(sprintf("SamsungRegistrationAPI::checkUser POST %s response %s", $this->endpoint, $response));

            return $response->getPayload();
        }
        catch (RequestException $ex) {
            Log::warning($ex->getMessage());
            return [];
        }
        catch(EmptyResponse $ex){
            Log::warning($ex->getMessage());
            return [];
        }
        catch (InvalidResponse $ex){
            Log::warning($ex->getMessage());
            return [];
        }
        catch (Exception $ex) {
            Log::error($ex->getMessage());
            return [];
        }
    }

    /**
     * @param Summit $summit
     * @param string $email
     * @param string $region
     * @return array|mixed|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkEmail(Summit $summit,string $email, string $region = Regions::US)
    {
        try {

            $metadata = $summit->getRegistrationFeedMetadata();
            $metadata[PayloadParamNames::ExternalShowId] = $summit->getExternalSummitId();
            $defaultTicketType = $summit->getFirstDefaultTicketType();
            if(is_null($defaultTicketType))
                throw new ValidationException(sprintf("Summit %s has not default ticket type set.", $summit->getId()));

            $metadata[PayloadParamNames::DefaultTicketId] = $defaultTicketType->getId();
            $metadata[PayloadParamNames::DefaultTicketDescription] = $defaultTicketType->getDescription();
            $metadata[PayloadParamNames::DefaultTicketName] = $defaultTicketType->getName();

            $request = new CheckEmailRequest
            (
                $email,
                $metadata
            );

            Log::debug(sprintf("SamsungRegistrationAPI::checkEmail POST %s payload %s", $this->endpoint, $request));

            // http://docs.guzzlephp.org/en/stable/request-options.html
            $response = $this->client->request('POST',
                $this->endpoint,
                [
                    'timeout' => 120,
                    'http_errors' => true,
                    RequestOptions::JSON => (new EncryptedPayload($summit->getExternalRegistrationFeedApiKey(), $request))->getPayload()
                ]
            );

            $response = new DecryptedSingleResponse
            (
                $summit->getExternalRegistrationFeedApiKey(),
                $response->getBody()->getContents(),
                $metadata
            );

            Log::debug(sprintf("SamsungRegistrationAPI::checkEmail POST %s response %s", $this->endpoint, $response));

            return $response->getPayload();
        }
        catch (RequestException $ex) {
            Log::warning($ex->getMessage());
            return [];
        }
        catch(EmptyResponse $ex){
            Log::warning($ex->getMessage());
            return [];
        }
        catch (InvalidResponse $ex){
            Log::warning($ex->getMessage());
            return [];
        }
        catch (Exception $ex) {
            Log::error($ex->getMessage());
            return [];
        }
    }

    /**
     * @param Summit $summit
     * @param string $region
     * @return array|mixed|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function userList(Summit $summit, string $region = Regions::US)
    {
        try {

            $metadata = $summit->getRegistrationFeedMetadata();
            Log::debug
            (
                sprintf
                (
                    "SamsungRegistrationAPI::userList summit %s metadata %s",
                    $summit->getId(),
                    json_encode($metadata, JSON_UNESCAPED_UNICODE)
                )
            );

            $metadata[PayloadParamNames::ExternalShowId] = $summit->getExternalSummitId();
            $defaultTicketType = $summit->getFirstDefaultTicketType();
            if(is_null($defaultTicketType))
                throw new ValidationException(sprintf("Summit %s has not default ticket type set.", $summit->getId()));

            $metadata[PayloadParamNames::DefaultTicketId] = $defaultTicketType->getId();
            $metadata[PayloadParamNames::DefaultTicketDescription] = $defaultTicketType->getDescription();
            $metadata[PayloadParamNames::DefaultTicketName] = $defaultTicketType->getName();

            $request = new UserListRequest($metadata);

            $payload = (new EncryptedPayload($summit->getExternalRegistrationFeedApiKey(), $request))->getPayload();

            // http://docs.guzzlephp.org/en/stable/request-options.html
            $response = $this->client->request('POST',
                $this->endpoint,
                [
                    'timeout' => 120,
                    'http_errors' => true,
                    'headers' => ['Accept' => 'application/json'],
                    RequestOptions::JSON => $payload,
                ]
            );

            Log::debug
            (
                sprintf
                (

                    "SamsungRegistrationAPI::userList POST %s payload %s",
                    $this->endpoint,
                    json_encode($payload)
                )
            );

            return new DecryptedListResponse
            (
                $summit->getExternalRegistrationFeedApiKey(),
                $response->getBody()->getContents(),
                $metadata
            );
        }
        catch (RequestException $ex) {
            Log::warning($ex->getMessage());
            return null;
        }
        catch(EmptyResponse $ex){
            Log::warning($ex->getMessage());
            return null;
        }
        catch(InvalidResponse $ex){
            Log::warning($ex->getMessage());
            return null;
        }
        catch (Exception $ex) {
            Log::error($ex->getMessage());
            return null;
        }
    }

    public function uncheckUser(Summit $summit, string $userId)
    {
        try {

            Log::debug(sprintf("SamsungRegistrationAPI::uncheckUser summit %s user %s", $summit->getId(), $userId));
            $metadata = $summit->getRegistrationFeedMetadata();
            $metadata[PayloadParamNames::ExternalShowId] = $summit->getExternalSummitId();
            $defaultTicketType = $summit->getFirstDefaultTicketType();
            if(is_null($defaultTicketType))
                throw new ValidationException(sprintf("Summit %s has not default ticket type set.", $summit->getId()));

            $metadata[PayloadParamNames::DefaultTicketId] = $defaultTicketType->getId();
            $metadata[PayloadParamNames::DefaultTicketDescription] = $defaultTicketType->getDescription();
            $metadata[PayloadParamNames::DefaultTicketName] = $defaultTicketType->getName();

            $request = new UnCheckUserRequest
            (
                $userId,
                $metadata
            );

            Log::debug(sprintf("SamsungRegistrationAPI::uncheckUser POST %s payload %s", $this->endpoint, $request));

            // http://docs.guzzlephp.org/en/stable/request-options.html
            $response = $this->client->request('POST',
                $this->endpoint,
                [
                    'timeout' => 120,
                    'http_errors' => true,
                    RequestOptions::JSON => (new EncryptedPayload($summit->getExternalRegistrationFeedApiKey(), $request))->getPayload()
                ]
            );

            $response = new DecryptedSingleResponse
            (
                $summit->getExternalRegistrationFeedApiKey(),
                $response->getBody()->getContents(),
                $metadata
            );

            Log::debug(sprintf("SamsungRegistrationAPI::uncheckUser POST %s response %s", $this->endpoint, $response));

            return $response->getPayload();
        }
        catch (RequestException $ex) {
            Log::warning($ex->getMessage());
            return [];
        }
        catch(EmptyResponse $ex){
            Log::warning($ex->getMessage());
            return [];
        }
        catch (InvalidResponse $ex){
            Log::warning($ex->getMessage());
            return [];
        }
        catch (Exception $ex) {
            Log::error($ex->getMessage());
            return [];
        }
    }
}