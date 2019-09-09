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
use Illuminate\Support\Facades\Log;
/**
 * Class VanderpoelScheduleFeed
 * @package App\Services\Apis\ExternalScheduleFeeds
 */
final class VanderpoelScheduleFeed extends AbstractExternalScheduleFeed
{

    /**
     * @var array
     */
    private $speakers;

    public function getEvents(): array
    {
        $apiFeedUrl = $this->summit->getApiFeedUrl();
        $apiFeedKey = $this->summit->getApiFeedKey();

        if(empty($apiFeedUrl)) {
            Log::warning(sprintf("api_feeed_url is empty for summit %s", $this->summit->getId()));
            return [];
        }
        if(empty($apiFeedKey)) {
            Log::warning(sprintf("api_feeed_key is empty for summit %s", $this->summit->getId()));
            return [];
        }

        $url = sprintf("%s/sessions/%s", $apiFeedUrl, $apiFeedKey);
        $response = $this->get($url);

        $events   = [];
        foreach($response as $event){
            $speakers = [];

            if(isset($event['speakers'])){
                foreach($event['speakers'] as $speaker){
                    $speakerFullName = trim(utf8_encode($speaker['first_name'])).' '.trim(utf8_encode($speaker['last_name']));
                    $speakers[]      = $speakerFullName;
                    if(!isset($this->speakers[$speakerFullName]))
                        $this->speakers[$speakerFullName] = [
                            'full_name'  => $speakerFullName,
                            'first_name' => trim(utf8_encode($speaker['first_name'])),
                            'last_name'  => trim(utf8_encode($speaker['last_name'])),
                            'email'      => $this->getDefaultSpeakerEmail($speakerFullName),
                            'company'    => trim(utf8_encode($speaker['company'])),
                            'position'   => trim(utf8_encode($speaker['job_title'])),
                            'avatar'     => trim($speaker['photo']),
                        ];
                }
            }

            $track = ucwords(str_replace($this::CHARS_TO_REMOVE, " ", trim($event["track"])));

            $events[] = [
                'external_id' => trim($event['id']),
                'title'       => trim(utf8_encode($event['title'])),
                'abstract'    => trim(utf8_encode($event['abstract'])),
                'track'       => $track,
                'location'    => trim(utf8_encode($event['room'])),
                'start_date'  => $event['start_timestamp'],
                'end_date'    => $event['end_timestamp'],
                'speakers'    => $speakers,
                'tags'        => self::encode_array($event["keywords"])
            ];
        }
        return $events;
    }

    private static function encode_array($array){
        if(is_array($array)){
            $res = [];
            foreach ($array as $element){
                $res[] = utf8_encode($element);
            }
            return $res;
        }
        return $array;
    }

    public function getSpeakers(): array
    {
        return $this->speakers;
    }

    public function getDefaultSpeakerEmail(string $speakerFullName): string
    {
        return sprintf("%s@vanderpoel.com", strtolower($speakerFullName));
    }
}