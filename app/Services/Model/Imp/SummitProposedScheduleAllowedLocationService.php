<?php namespace App\Services\Model\Imp;
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

use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedDay;
use App\Models\Foundation\Summit\ProposedSchedule\SummitProposedScheduleAllowedLocation;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitProposedScheduleAllowedLocationService;
use models\exceptions\EntityNotFoundException;
use models\summit\PresentationCategory;

/**
 * Class SummitProposedScheduleAllowedLocationService
 * @package App\Services\Model\Imp
 */
final class SummitProposedScheduleAllowedLocationService
extends AbstractService
implements ISummitProposedScheduleAllowedLocationService
{

    /**
     * @param PresentationCategory $track
     * @param array $payload
     * @return SummitProposedScheduleAllowedLocation|null
     * @throws \Exception
     */
    public function addProposedLocationToTrack(PresentationCategory $track, array $payload): ?SummitProposedScheduleAllowedLocation
    {
        return $this->tx_service->transaction(function() use($track, $payload){
            $location_id = intval($payload['location_id']);
            $location = $track->getSummit()->getLocation($location_id);
            if(is_null($location))
                throw new EntityNotFoundException(sprintf("location %s not found", $location_id));

            return $track->addProposedScheduleAllowedLocation($location);
        });
    }

    /**
     * @param PresentationCategory $track
     * @param int $allowed_location_id
     * @return void
     * @throws \Exception
     */
    public function deleteProposedLocationFromTrack(PresentationCategory $track, int $allowed_location_id): void
    {
        $this->tx_service->transaction(function() use($track, $allowed_location_id){
            $location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $track->removeProposedScheduleAllowedLocation($location);
        });
    }

    /**
     * @param PresentationCategory $track
     * @param int $allowed_location_id
     * @param array $payload
     * @return SummitProposedScheduleAllowedDay|null
     * @throws \Exception
     */
    public function addAllowedDayToProposedLocation(PresentationCategory $track, int $allowed_location_id, array $payload): ?SummitProposedScheduleAllowedDay
    {
        return $this->tx_service->transaction(function() use($track, $allowed_location_id, $payload){

            $location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $day = intval($payload['day']);
            $day = new \DateTime("@$day");
            $day->setTime(0,0,0);
            $day->setTimezone(new \DateTimeZone('UTC'));

            return $location->addAllowedTimeFrame($day, $payload['opening_hour'] ?? null, $payload['closing_hour'] ?? null);
        });
    }

    /**
     * @param PresentationCategory $track
     * @param int $allowed_location_id
     * @param int $allowed_day_id
     * @param array $payload
     * @return SummitProposedScheduleAllowedDay|null
     * @throws \Exception
     */
    public function updateAllowedDayToProposedLocation(PresentationCategory $track, int $allowed_location_id, int $allowed_day_id, array $payload): ?SummitProposedScheduleAllowedDay
    {
        return $this->tx_service->transaction(function() use($track, $allowed_location_id, $allowed_day_id){
            $location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $time_frame = $location->getAllowedTimeFrameById($allowed_day_id);

            if(is_null($time_frame))
                throw new EntityNotFoundException(sprintf("Allowed Day %s not found", $allowed_day_id));

            if(isset($payload['opening_hour']))
                $time_frame->setOpeningHour($payload['opening_hour']);

            if(isset($payload['closing_hour']))
                $time_frame->setClosingHour($payload['closing_hour']);

            return $time_frame;
        });
    }

    public function deleteAllowedDayToProposedLocation(PresentationCategory $track, int $allowed_location_id, int $allowed_day_id): void
    {
        $this->tx_service->transaction(function() use($track, $allowed_location_id, $allowed_day_id){
            $location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $time_frame = $location->getAllowedTimeFrameById($allowed_day_id);

            if(is_null($time_frame))
                throw new EntityNotFoundException(sprintf("Allowed Day %s not found", $allowed_day_id));

            $location->removeAllowedTimeFrame($time_frame);

        });
    }

    /**
     * @param PresentationCategory $track
     * @param int $allowed_location_id
     * @return void
     * @throws \Exception
     */
    public function deleteAllAllowedDayToProposedLocation(PresentationCategory $track, int $allowed_location_id): void
    {
        $this->tx_service->transaction(function() use($track, $allowed_location_id){
            $location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $location->clearAllowedTimeFrames();

        });
    }


    public function deleteAllProposedLocationFromTrack(PresentationCategory $track):void{
        $this->tx_service->transaction(function() use($track){
            $track->clearProposedScheduleAllowedLocations();
        });
    }
}