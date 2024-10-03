<?php namespace App\ModelSerializers\Summit;
/*
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

use models\summit\SummitEvent;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitEventStreamingInfoSerializer
 * @package App\ModelSerializers\Summit
 */
final class SummitEventStreamingInfoSerializer extends SilverStripeSerializer
{
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
        $values = parent::serialize($expand, $fields, $relations, $params);

        $values['streaming_url'] = $event->getStreamingUrl();
        $values['streaming_type'] = $event->getStreamingType();
        $values['etherpad_link'] = $event->getEtherpadLink();
        $values['stream_thumbnail'] = $event->getStreamThumbnailUrl();
        if($event->IsSecureStream()){
            $values['tokens'] = $event->getRegularStreamingTokens();
        }
        return $values;
    }
}