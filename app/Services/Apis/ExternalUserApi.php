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
use models\exceptions\ValidationException;
use libs\utils\ICacheService;
use GuzzleHttp\Client;
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

        $this->client = new Client([
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
     * @return mixed
     * @throws Exception
     */
    public function registerUser(string $email, ?string $first_name, ?string $last_name)
    {
        Log::debug(sprintf("ExternalUserApi::registerUser email %s first_name %s last_name %s", $email, $first_name, $last_name));

        try {
            $query = [
                'access_token' => $this->getAccessToken()
            ];

            if(empty($email))
                throw new ValidationException("Email field es required.");

            if(empty($first_name))
                throw new ValidationException("First Name field es required.");

            if(empty($last_name))
                throw new ValidationException("Last Name field es required.");

            $response = $this->client->post('/api/v1/user-registration-requests', [
                    'query' => $query,
                    RequestOptions::JSON => [
                        'email'      => $email,
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                    ]
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $ex) {
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

            return json_decode($response->getBody()->getContents(), true);
        }
        catch (Exception $ex) {
            $this->cleanAccessToken();
            Log::error($ex);
            throw $ex;
        }
    }
}

