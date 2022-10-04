<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Security\SummitScopes;
use Google\Service\ServiceUsage\LogDescriptor;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use libs\utils\JsonUtils;
use models\summit\IPaymentConstants;
use models\summit\Summit;
use DateTime;
/**
 * Class SummitSerializer
 * @package ModelSerializers
 */
class SummitSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'BeginDate' => 'start_date:datetime_epoch',
        'EndDate' => 'end_date:datetime_epoch',
        'RegistrationBeginDate' => 'registration_begin_date:datetime_epoch',
        'RegistrationEndDate' => 'registration_end_date:datetime_epoch',
        'StartShowingVenuesDate' => 'start_showing_venues_date:datetime_epoch',
        'ScheduleDefaultStartDate' => 'schedule_start_date:datetime_epoch',
        'Active' => 'active:json_boolean',
        'TypeId' => 'type_id:json_int',
        'DatesLabel' => 'dates_label:json_string',
        'MaxSubmissionAllowedPerUser' => 'max_submission_allowed_per_user:json_int',
        // calculated attributes
        'PresentationVotesCount' => 'presentation_votes_count:json_int',
        'PresentationVotersCount' => 'presentation_voters_count:json_int',
        'AttendeesCount' => 'attendees_count:json_int',
        'PaidTicketsCount' => 'paid_tickets_count:json_int',
        'SpeakersCount' => 'speakers_count:json_int',
        'PresentationsSubmittedCount' => 'presentations_submitted_count:json_int',
        'PublishedEventsCount' => 'published_events_count:json_int',
        'SpeakerAnnouncementEmailAcceptedCount' => 'speaker_announcement_email_accepted_count:json_int',
        'SpeakerAnnouncementEmailRejectedCount' => 'speaker_announcement_email_rejected_count:json_int',
        'SpeakerAnnouncementEmailAlternateCount' => 'speaker_announcement_email_alternate_count:json_int',
        'SpeakerAnnouncementEmailAcceptedAlternateCount' => 'speaker_announcement_email_accepted_alternate_count:json_int',
        'SpeakerAnnouncementEmailAcceptedRejectedCount' => 'speaker_announcement_email_accepted_rejected_count:json_int',
        'SpeakerAnnouncementEmailAlternateRejectedCount' => 'speaker_announcement_email_alternate_rejected_count:json_int',
        'TimeZoneId' => 'time_zone_id:json_string',
        'RawSlug' => 'slug:json_string',
        'InviteOnlyRegistration' => 'invite_only_registration:json_boolean',
        // Bookable rooms attributes
        'MeetingRoomBookingStartTime' => 'meeting_room_booking_start_time:datetime_epoch',
        'MeetingRoomBookingEndTime' => 'meeting_room_booking_end_time:datetime_epoch',
        'MeetingRoomBookingSlotLength' => 'meeting_room_booking_slot_length:json_int',
        'MeetingRoomBookingMaxAllowed' => 'meeting_room_booking_max_allowed:json_int',
        'BeginAllowBookingDate' => 'begin_allow_booking_date:datetime_epoch',
        'EndAllowBookingDate' => 'end_allow_booking_date:datetime_epoch',
        'LogoUrl' => 'logo:json_url',
        // External Feeds
        'ApiFeedType' => 'api_feed_type:json_string',
        'ApiFeedUrl' => 'api_feed_url:json_string',
        'ApiFeedKey' => 'api_feed_key:json_string',
        // registration
        'OrderQRPrefix' => 'order_qr_prefix:json_string',
        'TicketQRPrefix' => 'ticket_qr_prefix:json_string',
        'BadgeQRPrefix' => 'badge_qr_prefix:json_string',
        'QRRegistryFieldDelimiter' => 'qr_registry_field_delimiter:json_string',
        'ReassignTicketTillDate' => 'reassign_ticket_till_date:datetime_epoch',
        'RegistrationDisclaimerContent' => 'registration_disclaimer_content:json_string',
        'RegistrationDisclaimerMandatory' => 'registration_disclaimer_mandatory:json_boolean',
        'RegistrationReminderEmailDaysInterval' => 'registration_reminder_email_days_interval:json_int',
        'RegistrationLink' => 'registration_link:json_url',
        'SecondaryRegistrationLink' => 'secondary_registration_link:json_url',
        'SecondaryRegistrationLabel' => 'secondary_registration_label:json_string',
        // schedule app
        'ScheduleDefaultPageUrl' => 'schedule_default_page_url:json_url',
        'ScheduleDefaultEventDetailUrl' => 'schedule_default_event_detail_url:json_url',
        'ScheduleOgSiteName' => 'schedule_og_site_name:json_string',
        'ScheduleOgImageUrl' => 'schedule_og_image_url:json_string',
        'ScheduleOgImageSecureUrl' => 'schedule_og_image_secure_url:json_string',
        'ScheduleOgImageWidth' => 'schedule_og_image_width:json_int',
        'ScheduleOgImageHeight' => 'schedule_og_image_height:json_int',
        'ScheduleFacebookAppId' => 'schedule_facebook_app_id:json_string',
        'ScheduleIosAppName' => 'schedule_ios_app_name:json_string',
        'ScheduleIosAppStoreId' => 'schedule_ios_app_store_id:json_string',
        'ScheduleIosAppCustomSchema' => 'schedule_ios_app_custom_schema:json_string',
        'ScheduleAndroidAppName' => 'schedule_android_app_name:json_string',
        'ScheduleAndroidAppPackage' => 'schedule_android_app_package:json_string',
        'ScheduleAndroidCustomSchema' => 'schedule_android_custom_schema:json_string',
        'ScheduleTwitterAppName' => 'schedule_twitter_app_name:json_string',
        'ScheduleTwitterText' => 'schedule_twitter_text:json_string',
        'DefaultPageUrl' => 'default_page_url:json_string',
        'SpeakerConfirmationDefaultPageUrl' => 'speaker_confirmation_default_page_url:json_string',
        'VirtualSiteUrl' => 'virtual_site_url:json_string',
        'MarketingSiteUrl' => 'marketing_site_url:json_string',
        'SupportEmail' => 'support_email:json_string',
        'RegistrationSendQrAsImageAttachmentOnTicketEmail' => 'registration_send_qr_as_image_attachment_on_ticket_email:json_boolean',
        'RegistrationSendTicketAsPdfAttachmentOnTicketEmail' => 'registration_send_ticket_as_pdf_attachment_on_ticket_email:json_boolean',
        'RegistrationSendTicketEmailAutomatically' => 'registration_send_ticket_email_automatically:json_boolean',
        'RegistrationSendOrderEmailAutomatically' => 'registration_send_order_email_automatically:json_boolean',
        'RegistrationAllowAutomaticReminderEmails' => 'registration_allow_automatic_reminder_emails:json_boolean',
        'Modality' => 'modality:json_string',
        'AllowUpdateAttendeeExtraQuestions' => 'allow_update_attendee_extra_questions:json_boolean',
        'TimeZoneLabel' => 'time_zone_label:json_string',
        'RegistrationAllowedRefundRequestTillDate' => 'registration_allowed_refund_request_till_date:datetime_epoch',
    ];

    protected static $allowed_relations = [
        'ticket_types',
        'locations',
        'wifi_connections',
        'selection_plans',
        'meeting_booking_room_allowed_attributes',
        'summit_sponsors',
        'order_extra_questions',
        'tax_types',
        'payment_profiles',
        'email_flows_events',
        'summit_documents',
        'featured_speakers',
        'dates_with_events',
        'presentation_action_types',
        'schedule_settings',
        'badge_view_types',
        'badge_access_level_types',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     * @throws HTTP403ForbiddenException
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $summit = $this->object;
        if (!$summit instanceof Summit) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (!count($relations)) $relations = $this->getAllowedRelations();

        $values['dates_with_events'] = [];
        foreach($summit->getSummitDaysWithEvents() as $day){
            $values['dates_with_events'][] = $day->format('Y-m-d');
        }

        $timezone = $summit->getTimeZone();
        $values['time_zone'] = null;

        if (!is_null($timezone)) {
            $time_zone_info = $timezone->getLocation();
            $time_zone_info['name'] = $timezone->getName();
            $summit_start = $summit->getLocalBeginDate() ?? new DateTime('now', $timezone);
            // main offset
            $time_zone_info['offset'] = $timezone->getOffset($summit_start);
            // get all possible offsets ...
            $start_date = $summit->getBeginDate();
            $end_date = $summit->getEndDate();
            if(!is_null($start_date) && !is_null($end_date)) {
                $offsets = [];
                $start_date_epoch = $start_date->getTimestamp();
                $end_date_epoch = $end_date->getTimestamp();
                $res = $timezone->getTransitions($start_date_epoch, $end_date_epoch);

                if ($res && count($res) > 0) {
                    $i = 0;
                    foreach ($res as $t) {
                        $offsets[] = [
                            'from' => $t['ts'],
                            'offset' => $t['offset'],
                            'abbr' => $t['abbr']
                        ];
                        if ($i > 0) {
                            $offsets[$i - 1]['to'] = $t['ts'];
                        }
                        $i++;
                    }
                    // set the last "to" = $end_date_epoch
                    $offsets[count($offsets) - 1]['to'] = $end_date_epoch;
                }

                $time_zone_info['offsets'] = $offsets;
            }
            $values['time_zone'] = $time_zone_info;
        }

        // pages info
        $main_page = $summit->getMainPage();
        $schedule_page = $summit->getSchedulePage();
        $values['page_url'] =
            empty($main_page) ? null :
                sprintf("%s%s", Config::get("server.assets_base_url", 'https://www.openstack.org/'), $main_page);
        $values['schedule_page_url'] = empty($schedule_page) ? null :
            sprintf("%s%s", Config::get("server.assets_base_url", 'https://www.openstack.org/'), $schedule_page);
        $values['schedule_event_detail_url'] = empty($schedule_page) ? null : sprintf("%s%s/%s", Config::get("server.assets_base_url", 'https://www.openstack.org/'), $schedule_page, 'events/:event_id/:event_title');

        // tickets types
        if (in_array('ticket_types', $relations)) {
            $ticket_types = [];
            foreach ($summit->getTicketTypes() as $ticket) {
                $ticket_types[] = SerializerRegistry::getInstance()->getSerializer($ticket)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'ticket_types'));
            }
            $values['ticket_types'] = $ticket_types;
        }

        // badge_access_level_types
        if (in_array('badge_access_level_types', $relations)) {
            $badge_access_level_types = [];
            foreach ($summit->getBadgeAccessLevelTypes() as $accessLevelType) {
                $badge_access_level_types[] = SerializerRegistry::getInstance()->getSerializer($accessLevelType)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'badge_access_level_types'));
            }
            $values['badge_access_level_types'] = $badge_access_level_types;
        }

        // badge_view_types
        if (in_array('badge_view_types', $relations)) {
            $badge_view_types = [];
            foreach ($summit->getBadgeViewTypes() as $viewType) {
                $badge_view_types[] = SerializerRegistry::getInstance()->getSerializer($viewType)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'badge_view_types'));
            }
            $values['badge_view_types'] = $badge_view_types;
        }

        // payment_profiles
        if (in_array('payment_profiles', $relations)) {
            $payment_profiles = [];
            foreach ($summit->getActivePaymentGateWayProfiles() as $profile) {
                $payment_profiles[] = SerializerRegistry::getInstance()->getSerializer
                (
                    $profile,
                    $this->getSerializerType()
                )->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'payment_profiles'));
            }

            $values['payment_profiles'] = $payment_profiles;
            // if this serializer is public then we should show the default profiles
            // if there is not set any

            $has_registration_profile = false;
            $has_bookable_rooms_profile = false;

            foreach ($values['payment_profiles'] as $payment_profile) {
                if ($payment_profile['application_type'] == IPaymentConstants::ApplicationTypeBookableRooms) {
                    $has_bookable_rooms_profile = true;
                }
                if ($payment_profile['application_type'] == IPaymentConstants::ApplicationTypeRegistration) {
                    $has_registration_profile = true;
                }
            }

            $build_default_payment_gateway_profile_strategy = $params['build_default_payment_gateway_profile_strategy'] ?? null;

            if (!$has_registration_profile &&
                !is_null($build_default_payment_gateway_profile_strategy) &&
                $this->getSerializerType() == SerializerRegistry::SerializerType_Public
            ) {

                $values['payment_profiles'][] =
                    SerializerRegistry::getInstance()->getSerializer
                    (
                        $build_default_payment_gateway_profile_strategy->build(IPaymentConstants::ApplicationTypeRegistration),
                        $this->getSerializerType()
                    )->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'payment_profiles'));

            }

            if (!$has_bookable_rooms_profile &&
                !is_null($build_default_payment_gateway_profile_strategy) &&
                $this->getSerializerType() == SerializerRegistry::SerializerType_Public
            ) {
                $values['payment_profiles'][] =
                    SerializerRegistry::getInstance()->getSerializer
                    (
                        $build_default_payment_gateway_profile_strategy->build(IPaymentConstants::ApplicationTypeBookableRooms),
                        $this->getSerializerType()
                    )->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'payment_profiles'));
            }
        }

        if (in_array('order_extra_questions', $relations)) {
            $order_extra_questions = [];
            foreach ($summit->getOrderExtraQuestions() as $question) {
                $order_extra_questions[] = SerializerRegistry::getInstance()->getSerializer($question)->serialize(AbstractSerializer::filterExpandByPrefix($expand, "order_extra_questions"));
            }
            $values['order_extra_questions'] = $order_extra_questions;
        }

        if (in_array('tax_types', $relations)) {
            $tax_types = [];
            foreach ($summit->getTaxTypes() as $tax_type) {
                $tax_types[] = SerializerRegistry::getInstance()->getSerializer($tax_type)->serialize(AbstractSerializer::filterExpandByPrefix($expand, "tax_types"));
            }
            $values['tax_types'] = $tax_types;
        }

        if (in_array('summit_documents', $relations)) {
            $summit_documents = [];
            foreach ($summit->getSummitDocuments() as $document) {
                $summit_documents[] = SerializerRegistry::getInstance()->getSerializer($document)->serialize(AbstractSerializer::filterExpandByPrefix($expand, "summit_documents"));
            }
            $values['summit_documents'] = $summit_documents;
        }

        // meeting_booking_room_allowed_attributes
        if (in_array('meeting_booking_room_allowed_attributes', $relations)) {
            $meeting_booking_room_allowed_attributes = [];
            foreach ($summit->getMeetingBookingRoomAllowedAttributes() as $attr) {
                $meeting_booking_room_allowed_attributes[] = SerializerRegistry::getInstance()->getSerializer($attr)
                    ->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'meeting_booking_room_allowed_attributes'));
            }
            $values['meeting_booking_room_allowed_attributes'] = $meeting_booking_room_allowed_attributes;
        }

        // summit sponsors
        if (in_array('summit_sponsors', $relations)) {
            $summit_sponsors = [];
            // only get published ones
            foreach ($summit->getPublishedSummitSponsors() as $sponsor) {
                $summit_sponsors[] = SerializerRegistry::getInstance()->getSerializer($sponsor)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'summit_sponsors'));
            }
            $values['summit_sponsors'] = $summit_sponsors;
        }

        // locations
        if (in_array('locations', $relations)) {
            $locations = [];
            foreach ($summit->getLocations() as $location) {
                $locations[] = SerializerRegistry::getInstance()->getSerializer($location)->serialize(
                // is user is already expanding by schedule, its the total expand of the venues
                    !is_null($expand) && str_contains('schedule', $expand) ?
                        'floors,rooms' :
                        AbstractSerializer::filterExpandByPrefix($expand, 'locations')
                );
            }
            $values['locations'] = $locations;
        }

        // wifi connections
        if (in_array('wifi_connections', $relations)) {
            $wifi_connections = [];
            foreach ($summit->getWifiConnections() as $wifi_connection) {
                $wifi_connections[] = SerializerRegistry::getInstance()->getSerializer($wifi_connection)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'wifi_connections'));
            }
            $values['wifi_connections'] = $wifi_connections;
        }

        // selection plans
        if (in_array('selection_plans', $relations)) {
            $selection_plans = [];
            foreach ($summit->getSelectionPlans() as $selection_plan) {
                $selection_plans[] = SerializerRegistry::getInstance()->getSerializer($selection_plan)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'selection_plans'));
            }
            $values['selection_plans'] = $selection_plans;
        }

        if (in_array('email_flows_events', $relations)) {
            $email_flows_events = [];
            foreach ($summit->getAllEmailFlowsEvents() as $email_flow_event) {
                $email_flows_events[] = SerializerRegistry::getInstance()->getSerializer($email_flow_event)->serialize(AbstractSerializer::filterExpandByPrefix($expand, "email_flows_events"));
            }
            $values['email_flows_events'] = $email_flows_events;
        }

        // featured_speakers
        if (in_array('featured_speakers', $relations)) {
            $featured_speakers = [];
            foreach ($summit->getOrderedFeaturedSpeakers() as $featuredSpeaker) {
                if(!$featuredSpeaker->hasSpeaker()) continue;
                $featured_speakers[] = $featuredSpeaker->getSpeaker()->getId();
            }
            $values['featured_speakers'] = $featured_speakers;
        }

        // presentation_action_types
        if (in_array('presentation_action_types', $relations)) {
            $presentation_action_types = [];
            foreach ($summit->getPresentationActionTypes() as $action) {
                $presentation_action_types[] = SerializerRegistry::getInstance()->getSerializer($action)->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'presentation_action_types'));
            }
            $values['presentation_action_types'] = $presentation_action_types;
        }

        if (!empty($expand)) {

            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'event_types':
                        {
                            $event_types = [];
                            foreach ($summit->getEventTypes() as $event_type) {
                                $event_types[] = SerializerRegistry::getInstance()->getSerializer($event_type)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                            }
                            $values['event_types'] = $event_types;
                        }
                        break;

                    case 'featured_speakers':
                        {
                            $featured_speakers = [];
                            foreach ($summit->getOrderedFeaturedSpeakers() as $featuredSpeaker) {
                                if(!$featuredSpeaker->hasSpeaker()) continue;
                                $featured_speakers[] = SerializerRegistry::getInstance()->getSerializer($featuredSpeaker->getSpeaker())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                            }
                            $values['featured_speakers'] = $featured_speakers;
                        }
                        break;
                    case 'tracks':
                        {
                            $presentation_categories = [];
                            foreach ($summit->getPresentationCategories() as $cat) {
                                $presentation_categories[] = SerializerRegistry::getInstance()->getSerializer($cat)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                            }
                            $values['tracks'] = $presentation_categories;
                        }
                        break;
                    case 'track_groups':
                        {
                            // track_groups
                            $track_groups = [];
                            foreach ($summit->getCategoryGroups() as $group) {
                                $track_groups[] = SerializerRegistry::getInstance()->getSerializer($group)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                            }
                            $values['track_groups'] = $track_groups;
                        }
                        break;
                    case 'sponsors':
                        {
                            $sponsors = [];
                            foreach ($summit->getEventSponsors() as $company) {
                                $sponsors[] = SerializerRegistry::getInstance()->getSerializer($company)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                            }
                            $values['sponsors'] = $sponsors;
                        }
                        break;
                    case 'speakers':
                        {
                            $speakers = [];
                            foreach ($summit->getSpeakers() as $speaker) {
                                $speakers[] =
                                    SerializerRegistry::getInstance()->getSerializer($speaker)->serialize
                                    (
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation), [], [],
                                        [
                                            'summit_id' => $summit->getId(),
                                            'published' => true
                                        ]
                                    );

                            }
                            $values['speakers'] = $speakers;
                        }
                        break;
                    case 'schedule':
                        {
                            // only could get schedule expanded if summit its available to public or
                            // we had proper scopes
                            if (!$summit->isAvailableOnApi()) {
                                $scopes = $this->resource_server_context->getCurrentScope();
                                $current_realm = Config::get('app.url');
                                $needed_scope = sprintf(SummitScopes::ReadAllSummitData, $current_realm);
                                if (!in_array($needed_scope, $scopes))
                                    throw new HTTP403ForbiddenException;
                            }

                            $event_types = [];
                            foreach ($summit->getEventTypes() as $event_type) {
                                $event_types[] = SerializerRegistry::getInstance()->getSerializer($event_type)->serialize();
                            }
                            $values['event_types'] = $event_types;

                            $presentation_categories = [];
                            foreach ($summit->getPresentationCategories() as $cat) {
                                $presentation_categories[] = SerializerRegistry::getInstance()->getSerializer($cat)->serialize();
                            }
                            $values['tracks'] = $presentation_categories;

                            // track_groups
                            $track_groups = [];
                            foreach ($summit->getCategoryGroups() as $group) {
                                $track_groups[] = SerializerRegistry::getInstance()->getSerializer($group)->serialize();
                            }
                            $values['track_groups'] = $track_groups;

                            $schedule = [];
                            foreach ($summit->getScheduleEvents() as $event) {
                                $schedule[] = SerializerRegistry::getInstance()->getSerializer($event)->serialize();
                            }
                            $values['schedule'] = $schedule;

                            $sponsors = [];
                            foreach ($summit->getEventSponsors() as $company) {
                                $sponsors[] = SerializerRegistry::getInstance()->getSerializer($company)->serialize();
                            }
                            $values['sponsors'] = $sponsors;

                            $speakers = [];
                            foreach ($summit->getSpeakers() as $speaker) {
                                $speakers[] =
                                    SerializerRegistry::getInstance()->getSerializer($speaker)->serialize
                                    (
                                        null, [], [],
                                        [
                                            'summit_id' => $summit->getId(),
                                            'published' => true
                                        ]
                                    );

                            }
                            $values['speakers'] = $speakers;
                        }
                        break;
                    case 'type':
                        {
                            if (isset($values['type_id'])) {
                                unset($values['type_id']);
                                $values['type'] = $summit->hasType() ?
                                    SerializerRegistry::getInstance()->getSerializer($summit->getType())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation)) : null;
                            }
                        }
                        break;
                }
            }
        }

        $values['supported_currencies'] = $summit->getSupportedCurrencies();
        $values['timestamp'] = time();

        if(in_array('schedule_settings', $relations) && !isset($values['schedule_settings'])){
            $schedule_settings = [];
            foreach ($summit->getScheduleSettings() as $config){
                if(!$config->isEnabled()) continue;
                $schedule_settings[] = $config->getId();
            }
            $values['schedule_settings'] = $schedule_settings;
        }
        return $values;
    }

    /**
     * @return string
     */
    protected function getSerializerType(): string
    {
        return SerializerRegistry::SerializerType_Public;
    }

    protected static $expand_mappings = [
        'schedule_settings' => [
            'serializer_type' => SerializerRegistry::SerializerType_Private,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getEnableScheduleSettings',
        ],
    ];
}