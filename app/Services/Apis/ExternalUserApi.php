<?php namespace App\Services\Apis;
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
use GuzzleHttp\Exception\RequestException;
use models\exceptions\ValidationException;
use libs\utils\ICacheService;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Exception;
/**
 * Class ExternalUserApi
 * @package App\Services
 */
final class ExternalUserApi extends AbstractOAuth2Api
    implements IExternalUserApi
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $scopes;

    /**
     * ExternalUserApi constructor.
     * @param ICacheService $cacheService
     */
    public function __construct(ICacheService $cacheService)
    {
        parent::__construct($cacheService);
        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory());

        $this->client = new Client([
            'handler'         => $stack,
            'base_uri'        => Config::get('idp.base_url') ?? '',
            'timeout'         => Config::get('curl.timeout', 60),
            'allow_redirects' => Config::get('curl.allow_redirects', false),
            'verify'          => Config::get('curl.verify_ssl_cert', true),
        ]);
    }

    /**
     * @param string $email
     * @return null|mixed
     * @throws Exception
     */
    public function getUserByEmail(string $email)
    {
        try {
            Log::debug(sprintf("ExternalUserApi::getUserByEmail email %s", $email));

            $query = [
                'access_token' => $this->getAccessToken()
            ];

            $params = [
                'filter' => 'primary_email==' . $email
            ];

            foreach ($params as $param => $value) {
                $query[$param] = $value;
            }

            $response = $this->client->get('/api/v1/users', [
                    'query' => $query,
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return intval($data['total']) > 0 ? $data['data'][0] : null;
        }
        catch (RequestException $ex){
            $this->cleanAccessToken();
            Log::warning($ex);
            if($ex->getCode() == 404){
                return null;
            }
            throw $ex;
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $company
     * @return mixed
     * @throws Exception
     */
    public function registerUser(string $email, ?string $first_name, ?string $last_name, ?string $company = '')
    {
        Log::debug(sprintf("ExternalUserApi::registerUser email %s first_name %s last_name %s", $email, $first_name, $last_name));

        try {
            $query = [
                'access_token' => $this->getAccessToken()
            ];

            if(empty($email))
                throw new ValidationException("Email field es required.");

            $response = $this->client->post('/api/v1/user-registration-requests', [
                    'query' => $query,
                    RequestOptions::JSON => [
                        'email'      => $email,
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                        'company'    => $company
                    ]
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }

    const AppName = 'REGISTRATION_SERVICE';

    /**
     * @return string
     */
    public function getAppName(): string
    {
        return self::AppName;
    }

    /**
     * @return array
     */
    public function getAppConfig(): array
    {
        return [
            'client_id' => Config::get("registration.service_client_id"),
            'client_secret' => Config::get("registration.service_client_secret"),
            'scopes' => Config::get("registration.service_client_scopes")
        ];
    }

    /**
     * @param int $id
     * @return mixed|null
     * @throws Exception
     */
    public function getUserById(int $id)
    {
        try {
            Log::debug(sprintf("ExternalUserApi::getUserById id %s", $id));

            $query = [
                'access_token' => $this->getAccessToken()
            ];

            $response = $this->client->get(sprintf('/api/v1/users/%s', $id), [
                    'query' => $query,
                ]
            );

            $res =  json_decode($response->getBody()->getContents(), true);

            Log::debug(sprintf("ExternalUserApi::getUserById id %s res %s", $id, json_encode($res)));

            return $res;
        }
        catch (RequestException $ex){
            $this->cleanAccessToken();
            Log::warning($ex);
            if($ex->getCode() == 404){
                return null;
            }
            throw $ex;
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param int $id
     * @param string|null $first_name
     * @param string|null $last_name
     * @param string|null $company_name
     * @return mixed
     * @throws Exception
     */
    public function updateUser(int $id, ?string $first_name, ?string $last_name, ?string $company_name)
    {
        Log::debug
        (
            sprintf
            (
                "ExternalUserApi::updateUser first_name %s last_name %s company_name %s",
                $first_name,
                $last_name,
                $company_name
            )
        );

        try {
            $query = [
                'access_token' => $this->getAccessToken()
            ];

            $response = $this->client->put(sprintf('/api/v1/users/%s', $id), [
                    'query' => $query,
                    RequestOptions::JSON => [
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                        'company'   => $company_name
                    ]
                ]
            );

            $res = json_decode($response->getBody()->getContents(), true);
            Log::debug(sprintf("ExternalUserApi::updateUser id %s res %s", $id, json_encode($res)));
            return $res;
        }
        catch(RequestException $ex){
            $this->cleanAccessToken();
            Log::warning($ex);
            return null;
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param bool $is_redeemed
     * @return mixed
     * @throws Exception
     */
    public function getUserRegistrationRequest(string $email)
    {
        try {
            Log::debug(sprintf("ExternalUserApi::getUserRegistrationRequest email %s", $email));

            $query = [
                'access_token' => $this->getAccessToken(),
            ];

            $params = [
                'filter' => 'email==' . $email
            ];

            foreach ($params as $param => $value) {
                $query[$param] = $value;
            }

            $response = $this->client->get('/api/v1/user-registration-requests', [
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param int $id
     * @param string|null $first_name
     * @param string|null $last_name
     * @param string|null $company_name
     * @param string|null $country
     * @return mixed
     * @throws Exception
     */
    public function updateUserRegistrationRequest(
        int $id, ?string $first_name, ?string $last_name, ?string $company_name, ?string $country)
    {
        Log::debug(sprintf("ExternalUserApi::updateUserRegistrationRequest id %s", $id));

        try {
            $query = [
                'access_token' => $this->getAccessToken()
            ];

            $response = $this->client->put(sprintf('/api/v1/user-registration-requests/%s', $id), [
                    'query' => $query,
                    RequestOptions::JSON => [
                        'first_name'    => $first_name,
                        'last_name'     => $last_name,
                        'company'       => $company_name,
                        'country'       => $country
                    ]
                ]
            );
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }
}

