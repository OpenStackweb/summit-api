<?php namespace models\summit;
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

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use libs\utils\CacheRegions;
use libs\utils\MUXUtils;

/**
 * Class StreamableEventTrait
 * @package models\summit
 */
trait StreamableEventTrait
{
    /**
     * @param string $cache_key
     * @param string $streaming_url
     * @return array
     */
    protected function getStreamingTokens(string $cache_key, string $streaming_url): array
    {
        $tokens = [];
        $summit = $this->summit;
        $cache_tag = CacheRegions::getCacheRegionFor(CacheRegions::CacheRegionEvents, $this->getId());

        Log::debug("StreamableEventTrait::getStreamingTokens cache key {$cache_key}");

        if(Cache::tags($cache_tag)->has($cache_key)) {
            Log::debug(sprintf("StreamableEventTrait::getStreamingTokens cache hit for event %s", $this->getId()));
            return json_decode(Cache::tags(sprintf('secure_streams_%s', $summit->getId()))->get($cache_key), true);
        }

        if(!$summit->hasMuxPrivateKey()) {
            Log::debug(
                "StreamableEventTrait::getStreamingTokens summit {$summit->getId()} does not have a mux private key set.");
            return [];
        }

        $key_id = $summit->getMuxPrivateKeyId();
        $key_secret = $summit->getMuxPrivateKey();

        if(empty($streaming_url)){
            Log::debug("StreamableEventTrait::getStreamingTokens event {$this->getId()} does not have a stream url set.");
            return [];
        }

        $playback_id = MUXUtils::getPlaybackId($streaming_url);

        if(empty($playback_id)){
            Log::debug("StreamableEventTrait::getStreamingTokens event {$this->getId()} does not have a valid mux url ({$streaming_url}).");
            return [];
        }

        $tokenTypes = [
            'playback_token' => 'v', // video
            'thumbnail_token' => 't', // thumbnail
            'storyboard_token' => 'g', // gif
        ];

        $stream_duration = $this->getStreamDuration();
        if(!$stream_duration) $stream_duration = self::JWT_TTL;
        $exp = time() +  $stream_duration;
        $playback_restriction_id = $this->summit->getMuxPlaybackRestrictionId();

        foreach($tokenTypes as $type => $audience ) {
            $payload = [
                "sub" => $playback_id,
                "aud" => $audience,
                "exp" => $exp,
                "kid" => $key_id,
            ];

            if(!empty($playback_restriction_id)) {
                $payload['playback_restriction_id'] = $playback_restriction_id;
            }

            $tokens[$type] = JWT::encode($payload, base64_decode($key_secret), 'RS256');
        }

        Cache::tags($cache_tag)->put($cache_key, json_encode($tokens),$stream_duration - self::TTL_SKEW);

        return $tokens;
    }
}