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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use GuzzleRetry\GuzzleRetryMiddleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\ConfigurationException;

/**
 * Class PasswordlessAPI
 * @package App\Services\Apis
 */
final class PasswordlessAPI
    implements IPasswordlessAPI
{

    /**
     * @param string $email
     * @param string $client_id
     * @param string $scope
     * @return mixed
     */
    public function generateInlineOTP(string $email, string $client_id, string $scope)
    {
        try {
            $stack = HandlerStack::create();
            $stack->push(GuzzleRetryMiddleware::factory());
            $client = new Client([
                'handler' => $stack,
                'timeout' => Config::get('curl.timeout', 60),
                'allow_redirects' => Config::get('curl.allow_redirects', false),
                'verify' => Config::get('curl.verify_ssl_cert', true)
            ]);

            $impersonated_client_id = Config::get('app.openstackid_client_id', '');
            $impersonated_client_secret = Config::get('app.openstackid_client_secret', '');
            $idp_base_url = Config::get('app.openstackid_base_url', '');

            if (empty($impersonated_client_id)) {
                throw new ConfigurationException('app.openstackid_client_id param is missing!');
            }

            if (empty($impersonated_client_secret)) {
                throw new ConfigurationException('app.openstackid_client_secret param is missing!');
            }

            if (empty($idp_base_url)) {
                throw new ConfigurationException('app.openstackid_base_url param is missing!');
            }

            $url = "{$idp_base_url}/oauth2/auth";

            $payload =  [
                'client_id' => $client_id,
                'scope' => urlencode($scope),
                'email' => urlencode($email),
                'nonce' => str_random(12),
                'response_type' => 'otp',
                'connection' => 'inline',
                'send' => 'code'
            ];

            Log::debug(sprintf("PasswordlessAPI::generateInlineOTP POST %s payload %s", $url, json_encode($payload)));
            // http://docs.guzzlephp.org/en/stable/request-options.html
            $response = $client->request('POST',
                $url,
                [
                    'auth' => [$impersonated_client_id, $impersonated_client_secret],
                    'timeout' => 120,
                    'http_errors' => true,
                    RequestOptions::JSON => $payload
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $ex) {
            Log::warning($ex->getMessage());
            return null;
        }
    }
}