<?php namespace services\apis;
/**
 * Copyright 2024 OpenStack Foundation
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
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\ICacheService;
use models\exceptions\ValidationException;
/**
 * Class MarketingAPI
 * @package services\apis
 */
final class MarketingAPI implements IMarketingAPI
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $base_url;

    /**
     * @var int
     */
    private $cache_ttl;

    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @param ICacheService $cache_service
     * @throws ValidationException
     */
    public function __construct(ICacheService $cache_service)
    {
        $this->base_url = Config::get('marketing.base_url');
        if (is_null($this->base_url))
            throw new ValidationException("Config entry marketing.base_url not set.");

        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory());

        $this->client = new Client([
            'handler'         => $stack,
            'base_uri'        => $this->base_url,
            'timeout'         => Config::get('curl.timeout', 60),
            'allow_redirects' => Config::get('curl.allow_redirects', false),
            'verify'          => Config::get('curl.verify_ssl_cert', true),
        ]);

        $this->cache_ttl = Config::get('marketing.cache_ttl');
        $this->cache_service = $cache_service;
    }

    /**
     * @param string $api_url
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    protected function getEntity(string $api_url, array $params)
    {
        try {
            foreach ($params as $param => $value) {
                $query[$param] = $value;
            }

            $response = $this->client->get($api_url, ['query' => $query]);

            if ($response->getStatusCode() !== 200)
                throw new Exception('invalid status code!');

            $content_type = $response->getHeaderLine('content-type');

            if (empty($content_type))
                throw new Exception('invalid content type!');

            if ($content_type !== 'application/json')
                throw new Exception('invalid content type!');

            return json_decode($response->getBody()->getContents(), true);
        }
        catch(RequestException $ex){
            Log::warning($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfigValues(int $summit_id, string $search_pattern, int $page = 1, int $per_page = 100): array
    {
        Log::debug
        (
            sprintf
            (
                "MarketingAPI::getConfigValues summit %s search_pattern %s page %s per_page %s",
                $summit_id,
                $search_pattern,
                $page,
                $per_page
            )
        );

        try {
            $cache_key = "show_{$summit_id}_email_templates_marketing_vars";
            $cached_data = $this->cache_service->getSingleValue($cache_key);
            if (!is_null($cached_data)) {
                Log::debug
                (
                    sprintf
                    (
                        "MarketingAPI::getConfigValues summit %s search_pattern %s page %s per_page %s cache hit",
                        $summit_id,
                        $search_pattern,
                        $page,
                        $per_page
                    )
                );
                return json_decode($cached_data, true);
            }

            $res = $this->getEntity("/api/public/v1/config-values/all/shows/{$summit_id}",
                [
                    'page' => $page,
                    'per_page' => $per_page,
                    'key__contains' => trim($search_pattern)
                ]
            );

            $payload = [];

            foreach ($res['data'] as $setting) {
                $payload[$setting['key']] =$setting['type'] === 'FILE' ? $setting['file'] : $setting['value'];
            }

            Log::debug
            (
                sprintf
                (
                    "MarketingAPI::getConfigValues summit %s search_pattern %s page %s per_page %s cache miss",
                    $summit_id,
                    $search_pattern,
                    $page,
                    $per_page
                )
            );
            $this->cache_service->setSingleValue($cache_key, json_encode($payload), $this->cache_ttl);
            return $payload;
        } catch (\Exception $ex){
            Log::error($ex);
            return [];
        }
    }
}