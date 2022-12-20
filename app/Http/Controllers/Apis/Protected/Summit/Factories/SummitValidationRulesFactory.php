<?php namespace App\Http\Controllers;
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

use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;
use App\Models\Foundation\Summit\ISummitExternalScheduleFeedType;
use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
/**
 * Class SummitValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitValidationRulesFactory extends AbstractValidationRulesFactory
{

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForAdd(array $payload = []): array
    {
        return [
            'name' => 'required|string|max:50',
            'start_date' => 'required|date_format:U',
            'end_date' => 'required_with:start_date|date_format:U|after_or_equal:start_date',
            'registration_begin_date' => 'nullable|date_format:U',
            'registration_end_date' => 'nullable|required_with:registration_begin_date|date_format:U|after_or_equal:registration_begin_date',
            'start_showing_venues_date' => 'nullable|date_format:U|before_or_equal:start_date',
            'schedule_start_date' => 'nullable|date_format:U|after_or_equal:start_date|before_or_equal:end_date',
            'active' => 'sometimes|boolean',
            'dates_label' => 'nullable|sometimes|string',
            'time_zone_id' => 'required|timezone',
            'available_on_api' => 'sometimes|boolean',
            'calendar_sync_name' => 'nullable|sometimes|string|max:255',
            'calendar_sync_desc' => 'nullable|sometimes|string',
            'link' => 'nullable|sometimes|url',
            'registration_link' => 'nullable|sometimes|url',
            'max_submission_allowed_per_user' => 'nullable|sometimes|integer|min:1',
            'secondary_registration_link' => 'nullable|sometimes|url',
            'secondary_registration_label' => 'nullable|sometimes|string',
            'slug' => 'required|string',
            'meeting_room_booking_start_time' => 'nullable|date_format:U',
            'meeting_room_booking_end_time' => 'nullable|required_with:meeting_room_booking_start_time|date_format:U|after_or_equal:meeting_room_booking_start_time',
            'meeting_room_booking_slot_length' => 'nullable|integer',
            'meeting_room_booking_max_allowed' => 'nullable|integer|min:1',
            'api_feed_type' => sprintf('nullable|in:%s', implode(',', ISummitExternalScheduleFeedType::ValidFeedTypes)),
            'api_feed_url' => 'nullable|string|url|required_with:api_feed_type',
            'api_feed_key' => 'nullable|string|required_with:api_feed_type',
            'begin_allow_booking_date' => 'nullable|date_format:U',
            'end_allow_booking_date' => 'nullable|required_with:begin_allow_booking_date|date_format:U|after_or_equal:begin_allow_booking_date',
            'reassign_ticket_till_date' => 'nullable|date_format:U',
            'registration_disclaimer_content' => 'nullable|string',
            'registration_disclaimer_mandatory' => 'nullable|boolean',
            'registration_reminder_email_days_interval' => 'nullable|integer|min:1',
            'external_summit_id' => 'nullable|string',
            'external_registration_feed_type' => sprintf('nullable|in:%s', implode(',', ISummitExternalRegistrationFeedType::ValidFeedTypes)),
            'external_registration_feed_api_key' => 'nullable|string|required_with:external_registration_feed_type',
            // schedule
            'schedule_default_page_url' => 'nullable|url',
            'schedule_default_event_detail_url' => 'nullable|url',
            'schedule_og_site_name' => 'nullable|string',
            'schedule_og_image_url' => 'nullable|url',
            'schedule_og_image_secure_url' => 'nullable|url',
            'schedule_og_image_width' => 'nullable|integer',
            'schedule_og_image_height' => 'nullable|integer',
            'schedule_facebook_app_id' => 'nullable|string',
            'schedule_ios_app_name' => 'nullable|string',
            'schedule_ios_app_store_id' => 'nullable|string',
            'schedule_ios_app_custom_schema' => 'nullable|string',
            'schedule_android_app_name' => 'nullable|string',
            'schedule_android_app_package' => 'nullable|string',
            'schedule_android_custom_schema' => 'nullable|string',
            'schedule_twitter_app_name' => 'nullable|string',
            'schedule_twitter_text' => 'nullable|string',
            'default_page_url' => 'nullable|url',
            'speaker_confirmation_default_page_url' => 'nullable|url',
            'virtual_site_url' => 'nullable|url',
            'marketing_site_url' => 'nullable|url',
            'virtual_site_oauth2_client_id' => 'nullable|string',
            'marketing_site_oauth2_client_id' => 'nullable|string',
            'support_email' => 'nullable|email',
            'registration_send_qr_as_image_attachment_on_ticket_email' => 'sometimes|boolean',
            'registration_send_ticket_as_pdf_attachment_on_ticket_email' => 'sometimes|boolean',
            'registration_send_ticket_email_automatically' => 'sometimes|boolean',
            'registration_send_order_email_automatically' => 'sometimes|boolean',
            'registration_allow_automatic_reminder_emails' => 'sometimes|boolean',
            'allow_update_attendee_extra_questions' => 'sometimes|boolean',
            'time_zone_label' => 'sometimes|string',
            'registration_allowed_refund_request_till_date' => 'nullable|date_format:U',
            'registration_slug_prefix' => 'sometimes|string|max:50',
        ];
    }

    /**
     * @param array $payload
     * @return array
     */
    public static function buildForUpdate(array $payload = []): array
    {
        return [
            'name' => 'sometimes|string|max:50',
            'start_date' => 'sometimes|date_format:U',
            'end_date' => 'required_with:start_date|date_format:U|after_or_equal:start_date',
            'registration_begin_date' => 'nullable|date_format:U',
            'registration_end_date' => 'nullable|required_with:registration_begin_date|date_format:U|after_or_equal:registration_begin_date',
            'start_showing_venues_date' => 'nullable|date_format:U|before_or_equal:start_date',
            'schedule_start_date' => 'nullable|date_format:U|after_or_equal:start_date|before_or_equal:end_date',
            'active' => 'sometimes|boolean',
            'dates_label' => 'nullable|sometimes|string',
            'time_zone_id' => 'sometimes|timezone',
            'available_on_api' => 'sometimes|boolean',
            'calendar_sync_name' => 'nullable|sometimes|string|max:255',
            'calendar_sync_desc' => 'nullable|sometimes|string',
            'link' => 'nullable|sometimes|url',
            'registration_link' => 'nullable|sometimes|url',
            'max_submission_allowed_per_user' => 'sometimes|integer|min:1',
            'secondary_registration_link' => 'nullable|sometimes|url',
            'secondary_registration_label' => 'nullable|sometimes|string',
            'slug' => 'required|string',
            'meeting_room_booking_start_time' => 'nullable|date_format:U',
            'meeting_room_booking_end_time' => 'nullable|required_with:meeting_room_booking_start_time|date_format:U|after_or_equal:meeting_room_booking_start_time',
            'meeting_room_booking_slot_length' => 'nullable|integer',
            'meeting_room_booking_max_allowed' => 'nullable|integer|min:1',
            'api_feed_type' => sprintf('nullable|in:%s', implode(',', ISummitExternalScheduleFeedType::ValidFeedTypes)),
            'api_feed_url' => 'nullable|string|url|required_with:api_feed_type',
            'api_feed_key' => 'nullable|string|required_with:api_feed_type',
            'begin_allow_booking_date' => 'nullable|date_format:U',
            'end_allow_booking_date' => 'nullable|required_with:begin_allow_booking_date|date_format:U|after_or_equal:begin_allow_booking_date',
            'reassign_ticket_till_date' => 'nullable|date_format:U',
            'registration_disclaimer_content' => 'nullable|string',
            'registration_disclaimer_mandatory' => 'nullable|boolean',
            'registration_reminder_email_days_interval' => 'nullable|integer|min:1',
            'external_summit_id' => 'nullable|string',
            'external_registration_feed_type' => sprintf('nullable|in:%s', implode(',', ISummitExternalRegistrationFeedType::ValidFeedTypes)),
            'external_registration_feed_api_key' => 'nullable|string|required_with:external_registration_feed_type',
            // schedule
            'schedule_default_page_url' => 'nullable|url',
            'schedule_default_event_detail_url' => 'nullable|url',
            'schedule_og_site_name' => 'nullable|string',
            'schedule_og_image_url' => 'nullable|url',
            'schedule_og_image_secure_url' => 'nullable|url',
            'schedule_og_image_width' => 'nullable|integer',
            'schedule_og_image_height' => 'nullable|integer',
            'schedule_facebook_app_id' => 'nullable|string',
            'schedule_ios_app_name' => 'nullable|string',
            'schedule_ios_app_store_id' => 'nullable|string',
            'schedule_ios_app_custom_schema' => 'nullable|string',
            'schedule_android_app_name' => 'nullable|string',
            'schedule_android_app_package' => 'nullable|string',
            'schedule_android_custom_schema' => 'nullable|string',
            'schedule_twitter_app_name' => 'nullable|string',
            'schedule_twitter_text' => 'nullable|string',
            'default_page_url' => 'nullable|url',
            'speaker_confirmation_default_page_url' => 'nullable|url',
            'virtual_site_url' => 'nullable|url',
            'marketing_site_url' => 'nullable|url',
            'virtual_site_oauth2_client_id' => 'nullable|string',
            'marketing_site_oauth2_client_id' => 'nullable|string',
            'support_email' => 'nullable|email',
            'registration_send_qr_as_image_attachment_on_ticket_email' => 'sometimes|boolean',
            'registration_send_ticket_as_pdf_attachment_on_ticket_email' => 'sometimes|boolean',
            'registration_send_ticket_email_automatically' => 'sometimes|boolean',
            'registration_send_order_email_automatically' => 'sometimes|boolean',
            'registration_allow_automatic_reminder_emails' => 'sometimes|boolean',
            'allow_update_attendee_extra_questions' => 'sometimes|boolean',
            'time_zone_label' => 'sometimes|string',
            'registration_allowed_refund_request_till_date' => 'nullable|date_format:U',
            'registration_slug_prefix' => 'sometimes|string|max:50',
        ];
    }
}