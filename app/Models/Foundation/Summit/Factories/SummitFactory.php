<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\summit\Summit;
/**
 * Class SummitFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitFactory
{
    /**
     * @param array $data
     * @return Summit
     */
    public static function build(array $data){
        return self::populate(new Summit, $data);
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return Summit
     */
    public static function populate(Summit $summit, array $data){

        if(isset($data['name']) ){
            $summit->setName(trim($data['name']));
        }

        if(isset($data['time_zone_id']) ){
            $summit->setTimeZoneId(trim($data['time_zone_id']));
        }

        if(isset($data['max_submission_allowed_per_user']) ){
            $summit->setMaxSubmissionAllowedPerUser(intval($data['max_submission_allowed_per_user']));
        }

        if(isset($data['active']) ){
            $summit->setActive(boolval($data['active']));
        }

        if(isset($data['available_on_api']) ){
            $summit->setAvailableOnApi(boolval($data['available_on_api']));
        }

        if(isset($data['dates_label']) ){
            $summit->setDatesLabel(trim($data['dates_label']));
        }

        if(isset($data['external_summit_id']) ){
            $summit->setExternalSummitId(trim($data['external_summit_id']));
        }

        if(isset($data['calendar_sync_name']) ){
            $summit->setCalendarSyncName(trim($data['calendar_sync_name']));
        }

        if(isset($data['calendar_sync_desc']) ){
            $summit->setCalendarSyncDesc(trim($data['calendar_sync_desc']));
        }

        if(array_key_exists('begin_allow_booking_date', $data) && array_key_exists('end_allow_booking_date', $data)) {
            if (isset($data['begin_allow_booking_date']) && isset($data['end_allow_booking_date'])) {
                $start_datetime = intval($data['begin_allow_booking_date']);
                $start_datetime = new \DateTime("@$start_datetime");
                $start_datetime->setTimezone($summit->getTimeZone());
                $end_datetime = intval($data['end_allow_booking_date']);
                $end_datetime = new \DateTime("@$end_datetime");
                $end_datetime->setTimezone($summit->getTimeZone());
                // set local time from UTC
                $summit->setBeginAllowBookingDate($start_datetime);
                $summit->setEndAllowBookingDate($end_datetime);
            }
            else{
                $summit->clearAllowBookingDates();
            }
        }

        if(array_key_exists('start_date', $data) && array_key_exists('end_date', $data)) {
            if (isset($data['start_date']) && isset($data['end_date'])) {
                $start_datetime = intval($data['start_date']);
                $start_datetime = new \DateTime("@$start_datetime");
                $start_datetime->setTimezone($summit->getTimeZone());
                $end_datetime = intval($data['end_date']);
                $end_datetime = new \DateTime("@$end_datetime");
                $end_datetime->setTimezone($summit->getTimeZone());

                // set local time from UTC
                $summit->setBeginDate($start_datetime);
                $summit->setEndDate($end_datetime);
            }
            else{
                $summit->clearBeginEndDates();
            }
        }

        if(array_key_exists('registration_begin_date', $data) && array_key_exists('registration_end_date', $data)) {
            if (isset($data['registration_begin_date']) && isset($data['registration_end_date'])) {
                $start_datetime = intval($data['registration_begin_date']);
                $start_datetime = new \DateTime("@$start_datetime");
                $start_datetime->setTimezone($summit->getTimeZone());
                $end_datetime = intval($data['registration_end_date']);
                $end_datetime = new \DateTime("@$end_datetime");
                $end_datetime->setTimezone($summit->getTimeZone());

                // set local time from UTC
                $summit->setRegistrationBeginDate($start_datetime);
                $summit->setRegistrationEndDate($end_datetime);
            }
            else{
                $summit->clearRegistrationDates();
            }
        }

        if(array_key_exists('start_showing_venues_date', $data)){
            if (isset($data['start_showing_venues_date'])) {
                $start_datetime = intval($data['start_showing_venues_date']);
                $start_datetime = new \DateTime("@$start_datetime");
                $start_datetime->setTimezone($summit->getTimeZone());

                // set local time from UTC
                $summit->setStartShowingVenuesDate($start_datetime);
            }
            else{
                $summit->clearStartShowingVenuesDate();
            }
        }

        if(array_key_exists('schedule_start_date', $data)) {
            if (isset($data['schedule_start_date'])) {
                $start_datetime = intval($data['schedule_start_date']);
                $start_datetime = new \DateTime("@$start_datetime");
                $start_datetime->setTimezone($summit->getTimeZone());

                // set local time from UTC
                $summit->setScheduleDefaultStartDate($start_datetime);
            }
            else{
                $summit->clearScheduleDefaultStartDate();
            }
        }

        if(isset($data['link']) ){
            $summit->setLink(trim($data['link']));
        }

        if(isset($data['slug']) ){
            $summit->setRawSlug(trim($data['slug']));
        }

        if(isset($data['secondary_registration_link']) ){
            $summit->setSecondaryRegistrationLink(trim($data['secondary_registration_link']));
        }

        if(isset($data['secondary_registration_label']) ){
            $summit->setSecondaryRegistrationLabel(trim($data['secondary_registration_label']));
        }

        if(isset($data['meeting_room_booking_start_time']) ){
            // no need to convert to UTC, its only relative time
            $meeting_room_booking_start_time = intval($data['meeting_room_booking_start_time']);
            $meeting_room_booking_start_time = new \DateTime("@$meeting_room_booking_start_time");
            $summit->setMeetingRoomBookingStartTime($meeting_room_booking_start_time);
        }

        if(isset($data['meeting_room_booking_end_time']) ){
            // no need to convert to UTC, its only relative time
            $meeting_room_booking_end_time = intval($data['meeting_room_booking_end_time']);
            $meeting_room_booking_end_time = new \DateTime("@$meeting_room_booking_end_time");
            $summit->setMeetingRoomBookingEndTime($meeting_room_booking_end_time);
        }

        if(isset($data['meeting_room_booking_slot_length']) ){
            // minutes
            $summit->setMeetingRoomBookingSlotLength(intval($data['meeting_room_booking_slot_length']));
        }

        if(isset($data['meeting_room_booking_max_allowed']) ){
            // maximun books per user
            $summit->setMeetingRoomBookingMaxAllowed(intval($data['meeting_room_booking_max_allowed']));
        }

        // external feed

        if(isset($data['api_feed_type'])){
            $summit->setApiFeedType($data['api_feed_type']);
        }

        if(isset($data['api_feed_url'])){
            $summit->setApiFeedUrl(trim($data['api_feed_url']));
        }

        if(isset($data['api_feed_key'])){
            $summit->setApiFeedKey(trim($data['api_feed_key']));
        }

        // schedule

        if(isset($data['schedule_default_page_url'])){
            $summit->setScheduleDefaultPageUrl(trim($data['schedule_default_page_url']));
        }

        if(isset($data['schedule_default_event_detail_url'])){
            $summit->setScheduleDefaultEventDetailUrl(trim($data['schedule_default_event_detail_url']));
        }

        if(isset($data['schedule_og_site_name'])){
            $summit->setScheduleOgSiteName(trim($data['schedule_og_site_name']));
        }

        if(isset($data['schedule_og_image_url'])){
            $summit->setScheduleOgImageUrl(trim($data['schedule_og_image_url']));
        }

        if(isset($data['schedule_og_image_secure_url'])){
            $summit->setScheduleOgImageSecureUrl(trim($data['schedule_og_image_secure_url']));
        }

        if(isset($data['schedule_og_image_width'])){
            $summit->setScheduleOgImageWidth(intval($data['schedule_og_image_width']));
        }

        if(isset($data['schedule_og_image_height'])){
            $summit->setScheduleOgImageHeight(intval($data['schedule_og_image_height']));
        }

        if(isset($data['schedule_facebook_app_id'])){
            $summit->setScheduleFacebookAppId(trim($data['schedule_facebook_app_id']));
        }

        if(isset($data['schedule_ios_app_name'])){
            $summit->setScheduleIosAppName(trim($data['schedule_ios_app_name']));
        }

        if(isset($data['schedule_ios_app_store_id'])){
            $summit->setScheduleIosAppStoreId(trim($data['schedule_ios_app_store_id']));
        }

        if(isset($data['schedule_ios_app_custom_schema'])){
            $summit->setScheduleIosAppCustomSchema(trim($data['schedule_ios_app_custom_schema']));
        }

        if(isset($data['schedule_android_app_name'])){
            $summit->setScheduleAndroidAppName(trim($data['schedule_android_app_name']));
        }

        if(isset($data['schedule_android_app_package'])){
            $summit->setScheduleAndroidAppPackage(trim($data['schedule_android_app_package']));
        }

        if(isset($data['schedule_android_custom_schema'])){
            $summit->setScheduleAndroidCustomSchema(trim($data['schedule_android_custom_schema']));
        }

        if(isset($data['schedule_twitter_app_name'])){
            $summit->setScheduleTwitterAppName(trim($data['schedule_twitter_app_name']));
        }

        if(isset($data['schedule_twitter_text'])){
            $summit->setScheduleTwitterText(trim($data['schedule_twitter_text']));
        }

        return $summit;
    }
}