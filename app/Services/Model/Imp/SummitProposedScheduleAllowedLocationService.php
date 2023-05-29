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
use models\exceptions\ValidationException;
use models\summit\PresentationCategory;
use models\summit\SummitVenue;

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
                throw new EntityNotFoundException(sprintf("Location %s not found.", $location_id));

            if($location->getClassName() === SummitVenue::ClassName){
                throw new ValidationException("Location is a Venue, you can not add a venue to a track.");
            }

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

            $alloqed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($alloqed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found.", $allowed_location_id));

            if($alloqed_location->getLocation()->getClassName() === SummitVenue::ClassName){
                throw new ValidationException("Location is a Venue, you can not add a venue to a track.");
            }

            $summit = $track->getSummit();
            $day = intval($payload['day']);
            $day = new \DateTime("@$day", new \DateTimeZone("UTC"));
            $localDay = $summit->convertDateFromUTC2TimeZone($day);
            // reset time on local day
            $localDay->setTime(0,0,0);
            $day = $summit->convertDateFromTimeZone2UTC($localDay);

            if(!$summit->dayIsOnSummitPeriod($day, false))
                throw new ValidationException
                (
                    sprintf
                    (
                        "Day %s is not on summit period( %s - %s).",
                        $day->format("Y-m-d"),
                        $summit->getLocalBeginDate()->format("Y-m-d"),
                        $summit->getLocalEndDate()->format("Y-m-d"),
                    )
                );

            return $alloqed_location->addAllowedTimeFrame($day, $payload['opening_hour'] ?? null, $payload['closing_hour'] ?? null);
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
            $alloqed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($alloqed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $time_frame = $alloqed_location->getAllowedTimeFrameById($allowed_day_id);

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
            $alloqed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($alloqed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $time_frame = $alloqed_location->getAllowedTimeFrameById($allowed_day_id);

            if(is_null($time_frame))
                throw new EntityNotFoundException(sprintf("Allowed Day %s not found", $allowed_day_id));

            $alloqed_location->removeAllowedTimeFrame($time_frame);

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
            $alloqed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($alloqed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $alloqed_location->clearAllowedTimeFrames();

        });
    }

    /**
     * @param PresentationCategory $track
     * @return void
     * @throws \Exception
     */
    public function deleteAllProposedLocationFromTrack(PresentationCategory $track):void{
        $this->tx_service->transaction(function() use($track){
            $track->clearProposedScheduleAllowedLocations();
        });
    }
}