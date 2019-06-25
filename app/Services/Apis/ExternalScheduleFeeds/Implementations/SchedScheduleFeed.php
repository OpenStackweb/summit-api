<?php namespace App\Services\Apis\ExternalScheduleFeeds;
/**
 * Copyright 2019 OpenStack Foundation
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
use DateTime;
/**
 * Class SchedScheduleFeed
 * @package App\Services\Apis\ExternalScheduleFeeds
 */
final class SchedScheduleFeed  extends AbstractExternalScheduleFeed
{

    /**
     * @return array
     * @throws \Exception
     */
    public function getEvents(): array
    {
        $url = sprintf("%s/api/session/list?api_key=%s&format=json", $this->summit->getApiFeedUrl(), $this->summit->getApiFeedKey());
        $response = $this->get($url);
        $timezone = $this->summit->getTimeZone();
        $events   = [];
        foreach($response as $event){
            // Y-m-d H:i:s
            $event_start = DateTime::createFromFormat('Y-m-d H:i:s', $event['event_start'], $timezone);
            // Y-m-d H:i:s
            $event_end =  DateTime::createFromFormat('Y-m-d H:i:s', $event['event_end'], $timezone);
            $speakers  = [];
            if(isset($event['speakers'])){
                foreach(explode(',', trim($event['speakers'])) as $speaker_full_name){
                    $speakers[] = trim($speaker_full_name);;
                }
            }

            $events[] = [
                'external_id' => trim($event['event_key']),
                'title'       => trim($event['name']),
                'abstract'    => isset($event['description']) ? trim($event['description']) : '',
                'track'       => trim($event['event_type']),
                'location'    => trim($event['venue']),
                "start_date"  => $event_start->getTimestamp(),
                "end_date"    => $event_end->getTimestamp(),
                'speakers'    => $speakers
            ];
        }
        return $events;

    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getSpeakers(): array
    {
        $url = sprintf("%s/api/user/list?api_key=%s&format=json", $this->summit->getApiFeedUrl(), $this->summit->getApiFeedKey());
        $response = $this->get($url);

        $speakers = [];
        foreach($response as $speaker){
            if(!isset($speaker['name'])) continue;
            $speakerFullNameParts = explode(" ", $speaker['name']);
            $speakerFirstName     = trim(trim(array_pop($speakerFullNameParts)));
            $speakerLastName      = trim(implode(" ", $speakerFullNameParts));

            $speakers[trim($speaker['name'])] = [
                'full_name'  => trim($speaker['name']),
                'first_name' => trim($speakerFirstName),
                'last_name'  => trim($speakerLastName),
                'email'      => trim($speaker['email']),
                'company'    => trim($speaker['company']),
                'position'   => trim($speaker['position']),
                'avatar'     => trim($speaker['avatar']),
            ];
        }

        return $speakers;
    }
}