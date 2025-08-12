<?php namespace App\ModelSerializers\Summit;
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

use App\ModelSerializers\Traits\RequestCache;
use Libs\ModelSerializers\AbstractSerializer;
use libs\utils\CacheRegions;
use models\summit\SummitEvent;

/**
 * Class SummitEventOverflowStreamingSerializer
 * @package ModelSerializers
 */
class SummitEventOverflowStreamingSerializer extends AbstractSerializer
{
    use RequestCache;
    /**
     * @param $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        return $this->cache(
            CacheRegions::getCacheRegionForSummitEvent($this->object->getIdentifier()),
            sprintf("SummitEventOverflowStreamingSerializer_%s", $this->object->getIdentifier()),
            function () use ($expand, $fields, $relations, $params) {

                $event = $this->object;
                if (!$event instanceof SummitEvent) return [];

                $values['id'] = $event->getId();
                $values['title'] = $event->getTitle();
                $values['start_date'] = $event->getStartDate()->getTimestamp();
                $values['end_date'] = $event->getEndDate()->getTimestamp();
                $values['overflow_streaming_url'] = $event->getOverflowStreamingUrl();
                $values['overflow_stream_is_secure'] = $event->getOverflowStreamIsSecure();
                $values['overflow_tokens'] = [];
                if($event->getOverflowStreamIsSecure()){
                    $values['overflow_tokens'] = $event->getOverflowStreamingTokens();
                }
                return $values;
            });
    }
}