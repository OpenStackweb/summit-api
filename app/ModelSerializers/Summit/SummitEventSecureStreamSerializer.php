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
use models\summit\SummitEvent;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitEventSecureStreamSerializer
 * @package App\ModelSerializers\Summit
 */
final class SummitEventSecureStreamSerializer extends SilverStripeSerializer
{
    /**
     * @param $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = []): array
    {
        $event = $this->object;
        if (!$event instanceof SummitEvent) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

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

        $tokens = $event->getStreamingTokens(false);

        if (count($tokens) > 0) {
            $values['tokens'] = $tokens;
        }

        return $values;
    }
}