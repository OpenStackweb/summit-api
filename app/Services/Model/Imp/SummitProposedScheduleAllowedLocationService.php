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
use App\Utils\Time;
use Illuminate\Support\Facades\Log;
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
            $allowed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($allowed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $track->removeProposedScheduleAllowedLocation($allowed_location);
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

            $allowed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($allowed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found.", $allowed_location_id));

            if($allowed_location->getLocation()->getClassName() === SummitVenue::ClassName){
                throw new ValidationException("Location is a Venue, you can not add a venue to a track.");
            }

            $summit = $track->getSummit();
            $day = intval($payload['day']);
            Log::debug(sprintf("SummitProposedScheduleAllowedLocationService::addAllowedDayToProposedLocation day epoch %s", $day));
            $day = new \DateTime("@$day", new \DateTimeZone("UTC"));
            Log::debug(sprintf("SummitProposedScheduleAllowedLocationService::addAllowedDayToProposedLocation day %s", $day->format("Y-m-d H:i:s")));
            $localDay = $summit->convertDateFromUTC2TimeZone($day);
            Log::debug(sprintf("SummitProposedScheduleAllowedLocationService::addAllowedDayToProposedLocation localDay %s", $localDay->format("Y-m-d H:i:s")));
            // reset time on local day
            $localDay = $localDay->setTime(0,0,0, 0);
            $day = $summit->convertDateFromTimeZone2UTC($localDay);

            if(!$summit->dayIsOnSummitPeriod($day, true))
                throw new ValidationException
                (
                    sprintf
                    (
                        "Day %s is not on summit period( %s - %s).",
                        $day->format("Y-m-d h:i:s"),
                        $summit->getLocalBeginDate()->format("Y-m-d h:i:s"),
                        $summit->getLocalEndDate()->format("Y-m-d h:i:s"),
                    )
                );

            // check opening / closing hours
            $opening_hour = $payload['opening_hour'] ?? null;
            $closing_hour = $payload['closing_hour'] ?? null;

            if(!is_null($opening_hour)){
                list($hour, $minute) =Time::getHourAndMinutesFromInt($opening_hour);
                Log::debug(sprintf("SummitProposedScheduleAllowedLocationService::addAllowedDayToProposedLocation opening_hour %s %s", $hour, $minute));
                $start_local_date = clone $localDay;
                $start_local_date = $start_local_date->setTime($hour, $minute, 0, 0 );
                Log::debug(sprintf("SummitProposedScheduleAllowedLocationService::addAllowedDayToProposedLocation start_local_date %s", $start_local_date->format("Y-m-d H:i:s")));

                if(!$summit->dayIsOnSummitPeriod($summit->convertDateFromTimeZone2UTC($start_local_date), false))
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "Start Day %s is not on summit period( %s - %s).",
                            $start_local_date->format("Y-m-d h:i:s"),
                            $summit->getLocalBeginDate()->format("Y-m-d h:i:s"),
                            $summit->getLocalEndDate()->format("Y-m-d h:i:s"),
                        )
                    );
            }

            if(!is_null($closing_hour)){
                list($hour, $minute) =Time::getHourAndMinutesFromInt($closing_hour);
                Log::debug(sprintf("SummitProposedScheduleAllowedLocationService::addAllowedDayToProposedLocation closing_hour %s %s", $hour, $minute));
                $end_local_date = clone $localDay;
                $end_local_date = $end_local_date->setTime($hour, $minute, 0, 0 );
                Log::debug(sprintf("SummitProposedScheduleAllowedLocationService::addAllowedDayToProposedLocation end_local_date %s", $end_local_date->format("Y-m-d H:i:s")));

                if(!$summit->dayIsOnSummitPeriod($summit->convertDateFromTimeZone2UTC($end_local_date), false))
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "End Day %s is not on summit period( %s - %s).",
                            $end_local_date->format("Y-m-d h:i:s"),
                            $summit->getLocalBeginDate()->format("Y-m-d h:i:s"),
                            $summit->getLocalEndDate()->format("Y-m-d h:i:s"),
                        )
                    );
            }

            return $allowed_location->addAllowedTimeFrame
            (
                $day,
                $opening_hour,
                $closing_hour,
            );
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
            $allowed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($allowed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $time_frame = $allowed_location->getAllowedTimeFrameById($allowed_day_id);

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
            $allowed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($allowed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $time_frame = $allowed_location->getAllowedTimeFrameById($allowed_day_id);

            if(is_null($time_frame))
                throw new EntityNotFoundException(sprintf("Allowed Day %s not found", $allowed_day_id));

            $allowed_location->removeAllowedTimeFrame($time_frame);

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
            $allowed_location = $track->getAllowedLocationById($allowed_location_id);
            if(is_null($allowed_location))
                throw new EntityNotFoundException(sprintf("Allowed Location %s not found", $allowed_location_id));

            $allowed_location->clearAllowedTimeFrames();

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