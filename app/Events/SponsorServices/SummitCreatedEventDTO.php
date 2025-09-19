<?php namespace App\Events\SponsorServices;

/*
 * Copyright 2025 OpenStack Foundation
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

class SummitCreatedEventDTO
{
    private int $id;
    private string $name;
    private int $start_date;
    private int $end_date;
    private string $time_zone_id;
    private ?string $support_email;

    public function __construct(
        int    $id,
        string $name,
        int    $start_date,
        int    $end_date,
        string $time_zone_id,
        ?string $support_email
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->time_zone_id = $time_zone_id;
        $this->support_email = $support_email;
    }

    public static function fromSummit($summit): self
    {
        return new self(
            $summit->getId(),
            $summit->getName(),
            $summit->getBeginDate()->getTimestamp(),
            $summit->getEndDate()->getTimestamp(),
            $summit->getTimeZoneId(),
            $summit->getSupportEmail()
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'time_zone_id' => $this->time_zone_id,
            'support_email' => $this->support_email,
        ];
    }
}