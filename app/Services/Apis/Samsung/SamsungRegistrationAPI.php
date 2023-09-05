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

            $metadata = $summit->getRegistrationFeedMetadata();
            if(!isset($metadata[PayloadParamNames::Forum]))
                $metadata[PayloadParamNames::Forum] = $summit->getExternalSummitId();

            $request = new CheckUserRequest
            (
                $userId,
                $metadata[PayloadParamNames::Forum],
                $metadata[PayloadParamNames::Region],
                $metadata[PayloadParamNames::GBM],
                $metadata[PayloadParamNames::Year]
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
                $summit->getExternalSummitId()
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
            if(!isset($metadata[PayloadParamNames::Forum]))
                $metadata[PayloadParamNames::Forum] = $summit->getExternalSummitId();

            $request = new CheckEmailRequest
            (
                $email,
                $metadata[PayloadParamNames::Forum],
                $metadata[PayloadParamNames::Region],
                $metadata[PayloadParamNames::GBM],
                $metadata[PayloadParamNames::Year]
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
                $summit->getExternalSummitId()
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
            if(!isset($metadata[PayloadParamNames::Forum]))
                $metadata[PayloadParamNames::Forum] = $summit->getExternalSummitId();

            $request = new UserListRequest
            (
                $metadata[PayloadParamNames::Forum],
                $metadata[PayloadParamNames::Region],
                $metadata[PayloadParamNames::GBM],
                $metadata[PayloadParamNames::Year]
            );

            Log::debug
            (
                sprintf
                (
                    "SamsungRegistrationAPI::userList POST %s payload %s",
                    $this->endpoint,
                    $request
                )
            );

            // http://docs.guzzlephp.org/en/stable/request-options.html
            $response = $this->client->request('POST',
                $this->endpoint,
                [
                    'timeout' => 120,
                    'http_errors' => true,
                    'headers' => ['Accept' => 'application/json'],
                    RequestOptions::JSON => (new EncryptedPayload($summit->getExternalRegistrationFeedApiKey(), $request))->getPayload(),
                ]
            );

            return new DecryptedListResponse
            (
                $summit->getExternalRegistrationFeedApiKey(),
                $response->getBody()->getContents(),
                $summit->getExternalSummitId()
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
}