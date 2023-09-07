<?php namespace App\ModelSerializers\Summit;
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

use Illuminate\Support\Facades\Log;
use libs\utils\MUXUtils;
use models\summit\SummitEvent;
use ModelSerializers\SilverStripeSerializer;
use \Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
/**
 * Class SummitEventSecureStreamSerializer
 * @package App\ModelSerializers\Summit
 */
final class SummitEventSecureStreamSerializer extends SilverStripeSerializer
{
    const JWT_TTL = 60 * 60 * 24; // secs
    const TTL_SKEW = 60; // secs
    /**
     * @param $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $event = $this->object;
        if (!$event instanceof SummitEvent) return [];
        if (!count($relations)) $relations = $this->getAllowedRelations();
        if(!count($fields)) $fields = $this->getAllowedFields();

        $key = $event->getSecureStreamCacheKey();

        Log::debug(sprintf("SummitEventSecureStreamSerializer::serialize cache key %s", $key));

        if(Cache::has($key)){
            $values = json_decode(Cache::get($key), true);
            Log::debug(sprintf("SummitEventSecureStreamSerializer::serialize cache hit for event %s", $event->getId()));
            return $values;
        }

        $values = parent::serialize($expand, $fields, $relations, $params);
        $summit = $event->getSummit();

        if(!$event->IsSecureStream()){
            Log::debug
            (
                sprintf
                (
                    "SummitEventSecureStreamSerializer::serialize event %s is not secure.",
                    $event->getId()
                )
            );
            return $values;
        }

        if(!$summit->hasMuxPrivateKey()) {
            Log::debug
            (
                sprintf
                (
                    "SummitEventSecureStreamSerializer::serialize summit %s does not have a mux private key set.",
                    $summit->getId()
                )
            );

            return $values;
        }

        $streaming_url = $event->getStreamingUrl();
        if(empty($streaming_url)){
            Log::debug
            (
                sprintf
                (
                    "SummitEventSecureStreamSerializer::serialize event %s does not have a stream url set.",
                    $event->getId()
                )
            );
            return $values;
        }


        $playback_id = MUXUtils::getPlaybackId($streaming_url);

        if(empty($playback_id)){
            Log::debug
            (
                sprintf
                (
                    "SummitEventSecureStreamSerializer::serialize event %s does not have a valid mux url (%s).",
                    $event->getId(),
                    $streaming_url
                )
            );
            return $values;
        }

        $tokens = [];
        $key_id = $summit->getMuxPrivateKeyId();
        $key_secret = $summit->getMuxPrivateKey();

        $tokenTypes = [
            'playback_token' => 'v', // video
            'thumbnail_token' => 't', // thumbnail
            'storyboard_token' => 'g', // gif
        ];

        $stream_duration = $event->getStreamDuration();
        if(!$stream_duration) $stream_duration = self::JWT_TTL;
        $exp = time() +  $stream_duration;

        foreach($tokenTypes as $type => $audience ) {

            $payload = [
                "sub" => $playback_id,
                "aud" => $audience,
                "exp" => $exp,
                "kid" => $key_id,
            ];

           $tokens[$type] = JWT::encode($payload, base64_decode($key_secret), 'RS256');
        }


        $values['tokens'] = $tokens;

        Cache::put($key, json_encode($values),  $stream_duration - self::TTL_SKEW);

        return $values;
    }
}