<?php namespace App\Services\Apis;
/**
 * Copyright 2025 OpenStack Foundation
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
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\ICacheService;

class PaymentsApi extends AbstractOAuth2Api implements IPaymentsApi
{
    /**
     * @var Client
     */
    private $client;


    /**
     * PaymentsApi constructor.
     * @param ICacheService $cacheService
     */
    public function __construct(ICacheService $cacheService)
    {
        parent::__construct($cacheService);
        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory());

        $this->client = new Client([
            'handler' => $stack,
            'base_uri' => Config::get('payments_api.base_url') ?? '',
            'timeout' => Config::get('curl.timeout', 60),
            'allow_redirects' => Config::get('curl.allow_redirects', false),
            'verify' => Config::get('curl.verify_ssl_cert', true),
        ]);
    }

    const AppName = 'PAYMENTS_SERVICE';

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
            'client_id' => Config::get("payments_api.service_client_id"),
            'client_secret' => Config::get("payments_api.service_client_secret"),
            'scopes' => Config::get("payments_api.service_client_scopes")
        ];
    }


    /**
     * @param int $summit_id
     * @param int $id
     * @return mixed|null
     * @throws \Exception
     */
    public function getPaymentProfile(int $summit_id, int $id)
    {
        Log::debug("PaymentsApi::getPaymentProfile", ['summit_id' => $summit_id, 'id' => $id]);

        return $this->invokeWithRetry(function () use ($summit_id, $id) {
            $query = [
                'access_token' => $this->getAccessToken(),
            ];

            $response = $this->client->get(sprintf('/api/v1/summits/%s/payment-profiles/%s', $summit_id, $id),
                [
                    'headers' => [
                        'Accept'        => 'application/json',
                        'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                        'Pragma'        => 'no-cache',
                    ],
                    'query' => $query,
                ]
            );

            return json_decode($response->getBody()->getContents(), true);

        });
    }

}
