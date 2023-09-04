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

use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Support\Facades\Log;
use MuxPhp\Api\AssetsApi as MuxAssetApi;
use MuxPhp\Api\PlaybackIDApi as MuxPlaybackIDApi;
use MuxPhp\Api\SigningKeysApi as SigningKeysApi;
use MuxPhp\Configuration;
use MuxPhp\Configuration as MuxConfig;

/**
 * Class MUXApi
 * @package App\Services\Apis
 */
final class MUXApi implements IMUXApi
{

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var SigningKeysApi
     */
    private $signing_key_api;

    /**
     * @var MuxAssetApi
     */
    private $assets_api;

    /**
     * @var MuxPlaybackIDApi
     */
    private $playback_api;


    /**
     * @return array
     * @throws \MuxPhp\ApiException
     */
    public function createUrlSigningKey(): array
    {
        try {
            $res = $this->signing_key_api->createSigningKey();
            $data = $res->getData();
            return
                [
                    'private_key' => $data->getPrivateKey(),
                    'id' => $data->getId(),
                ];
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    public function setCredentials(MuxCredentials $credentials): IMUXApi
    {
        try {
            // Authentication Setup
            $this->config = MuxConfig::getDefaultConfiguration()
                ->setUsername($credentials->getTokenId())
                ->setPassword($credentials->getTokenSecret());

            // API Client Initialization
            $this->assets_api = new MuxAssetApi(
                new GuzzleHttpClient,
                $this->config
            );

            $this->playback_api = new MuxPlaybackIDApi(
                new GuzzleHttpClient,
                $this->config
            );

            $this->signing_key_api = new SigningKeysApi(
                new GuzzleHttpClient,
                $this->config
            );
            return $this;
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }
}