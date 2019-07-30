<?php namespace App\Services\Apis\ExternalRegistrationFeeds\implementations;
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
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedResponse;
/**
 * Class EventbriteRegistrationFeedResponse
 * @package App\Services\Apis\ExternalRegistrationFeeds\implementations
 */
final class EventbriteRegistrationFeedResponse implements IExternalRegistrationFeedResponse
{
    private $position = 0;
    private $data = [];
    private $attendees = [];

    /**
     * EventbriteRegistrationFeedResponse constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->position  = 0;
        $this->data      = $data;

        if(isset($data['attendees']))
            $this->attendees = $data['attendees'];
    }

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        return $this->attendees[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return isset($this->attendees[$this->position]);
    }

    public function hasData(): bool
    {
        if (!isset($this->data ['pagination'])) return false;
        if (!isset($this->data ['attendees'])) return false;
        return true;
    }

    public function hasMoreItems(): bool
    {
        if (!isset($this->data['pagination'])) return false;
        $pagination     = $this->data['pagination'];
        return boolval($pagination['has_more_items']);
    }
}