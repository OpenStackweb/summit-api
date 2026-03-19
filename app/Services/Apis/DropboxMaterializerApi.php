<?php namespace App\Services\Apis;
/**
 * Copyright 2026 OpenStack Foundation
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
use GuzzleRetry\GuzzleRetryMiddleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Class DropboxMaterializerApi
 * @package App\Services\Apis
 */
final class DropboxMaterializerApi implements IDropboxMaterializerApi
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $internal_key;

    public function __construct()
    {
        $stack = HandlerStack::create();
        $stack->push(GuzzleRetryMiddleware::factory([
            'retry_on_methods' => ['GET'],
        ]));

        $this->client = new Client([
            'handler'         => $stack,
            'base_uri'        => Config::get('dropbox_materializer.base_url', 'http://localhost:8100'),
            'timeout'         => Config::get('dropbox_materializer.timeout', 10),
            'allow_redirects' => false,
            'verify'          => Config::get('curl.verify_ssl_cert', true),
        ]);

        $this->internal_key = Config::get('dropbox_materializer.internal_key', '');
    }

    /**
     * @return array
     */
    private function headers(): array
    {
        return [
            'X-Internal-Key' => $this->internal_key,
            'Accept'         => 'application/json',
            'Content-Type'   => 'application/json',
        ];
    }

    /**
     * @param string $method
     * @param string $uri
     * @return array
     */
    private function request(string $method, string $uri): array
    {
        try {
            $response = $this->client->request($method, $uri, [
                'headers' => $this->headers(),
            ]);

            $body = $response->getBody()->getContents();
            return json_decode($body, true) ?? [];
        } catch (RequestException $ex) {
            Log::warning(
                sprintf(
                    "DropboxMaterializerApi::request %s %s error: %s",
                    $method,
                    $uri,
                    $ex->getMessage()
                )
            );

            $response = $ex->getResponse();
            if ($response) {
                $body = $response->getBody()->getContents();
                $decoded = json_decode($body, true);
                return $decoded ?? ['error' => $ex->getMessage(), 'status' => $response->getStatusCode()];
            }

            return ['error' => $ex->getMessage()];
        }
    }

    /**
     * @param int $summitId
     * @return array
     */
    public function materialize(int $summitId): array
    {
        return $this->request('POST', "/api/sync/materialize/{$summitId}/");
    }

    /**
     * @param int $summitId
     * @param string $venue
     * @param string $room
     * @return array
     */
    public function materializeRoom(int $summitId, string $venue, string $room): array
    {
        $venue = rawurlencode($venue);
        $room = rawurlencode($room);
        return $this->request('POST', "/api/sync/materialize/{$summitId}/{$venue}/{$room}/");
    }

    /**
     * @param int $summitId
     * @return array
     */
    public function backfill(int $summitId): array
    {
        return $this->request('POST', "/api/sync/backfill/{$summitId}/");
    }

    /**
     * @param int $summitId
     * @return array
     */
    public function rebuild(int $summitId): array
    {
        return $this->request('POST', "/api/sync/rebuild/{$summitId}/");
    }

    /**
     * @param int $summitId
     * @return array
     */
    public function preflight(int $summitId): array
    {
        return $this->request('GET', "/api/sync/preflight/{$summitId}/");
    }

    /**
     * @param int $summitId
     * @return array
     */
    public function status(int $summitId): array
    {
        return $this->request('GET', "/api/sync/status/{$summitId}/");
    }
}
