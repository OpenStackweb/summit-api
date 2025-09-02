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
use DateTime;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\IPaymentConstants;
use models\summit\Summit;
use function Psy\debug;

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
        'PublishedEventsCount' => 'published_events_count:json_int',
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
        'SecondaryLogoUrl' => 'secondary_logo:json_url',
        // registration
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
        'SpeakersSupportEmail' => 'speakers_support_email:json_string',
        'RegistrationSendQrAsImageAttachmentOnTicketEmail' => 'registration_send_qr_as_image_attachment_on_ticket_email:json_boolean',
        'RegistrationSendTicketAsPdfAttachmentOnTicketEmail' => 'registration_send_ticket_as_pdf_attachment_on_ticket_email:json_boolean',
        'RegistrationSendTicketEmailAutomatically' => 'registration_send_ticket_email_automatically:json_boolean',
        'RegistrationSendOrderEmailAutomatically' => 'registration_send_order_email_automatically:json_boolean',
        'RegistrationAllowAutomaticReminderEmails' => 'registration_allow_automatic_reminder_emails:json_boolean',
        'Modality' => 'modality:json_string',
        'AllowUpdateAttendeeExtraQuestions' => 'allow_update_attendee_extra_questions:json_boolean',
        'TimeZoneLabel' => 'time_zone_label:json_string',
        'RegistrationAllowedRefundRequestTillDate' => 'registration_allowed_refund_request_till_date:datetime_epoch',
        'RegistrationSlugPrefix' => 'registration_slug_prefix:json_string',
        'MarketingSiteOauth2ClientScopes' => 'marketing_site_oauth2_client_scopes:json_string',
        'DefaultTicketTypeCurrency' => 'default_ticket_type_currency:json_string',
        'DefaultTicketTypeCurrencySymbol' => 'default_ticket_type_currency_symbol:json_string',
    ];

    protected static $allowed_fields = [
        'id',
        'created',
        'last_edited',
        'name',
        'start_date',
        'end_date',
        'registration_begin_date',
        'registration_end_date',
        'start_showing_venues_date',
        'schedule_start_date',
        'active',
        'type_id',
        'dates_label',
        'max_submission_allowed_per_user',
        'published_events_count',
        'time_zone_id',
        'slug',
        'invite_only_registration',
        'meeting_room_booking_start_time',
        'meeting_room_booking_end_time',
        'meeting_room_booking_slot_length',
        'meeting_room_booking_max_allowed',
        'begin_allow_booking_date',
        'end_allow_booking_date',
        'logo',
        'secondary_logo',
        'reassign_ticket_till_date',
        'registration_disclaimer_content',
        'registration_disclaimer_mandatory',
        'registration_reminder_email_days_interval',
        'registration_link',
        'secondary_registration_link',
        'secondary_registration_label',
        'schedule_default_page_url',
        'schedule_default_event_detail_url',
        'schedule_og_site_name',
        'schedule_og_image_url',
        'schedule_og_image_secure_url',
        'schedule_og_image_width',
        'schedule_og_image_height',
        'schedule_facebook_app_id',
        'schedule_ios_app_name',
        'schedule_ios_app_store_id',
        'schedule_ios_app_custom_schema',
        'schedule_android_app_name',
        'schedule_android_app_package',
        'schedule_android_custom_schema',
        'schedule_twitter_app_name',
        'schedule_twitter_text',
        'default_page_url',
        'speaker_confirmation_default_page_url',
        'virtual_site_url',
        'marketing_site_url',
        'support_email',
        'speakers_support_email',
        'registration_send_qr_as_image_attachment_on_ticket_email',
        'registration_send_ticket_as_pdf_attachment_on_ticket_email',
        'registration_send_ticket_email_automatically',
        'registration_send_order_email_automatically',
        'registration_allow_automatic_reminder_emails',
        'modality',
        'allow_update_attendee_extra_questions',
        'time_zone_label',
        'registration_allowed_refund_request_till_date',
        'registration_slug_prefix',
        'marketing_site_oauth2_client_scopes',
        'default_ticket_type_currency',
        'default_ticket_type_currency_symbol',
        'time_zone',
        'schedule_page_url',
        'schedule_event_detail_url',
        'page_url',
        'timestamp',
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
        'lead_report_settings',
        'badge_types',
        'badge_features_types',
        'badge_access_level_types',
        'dates_with_events',
        'supported_currencies',
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

        Log::debug(sprintf("SummitSerializer::serialize expand %s fields %s relations %s", $expand, json_encode($fields), json_encode($relations)));

        if (in_array('dates_with_events', $relations)) {
            $values['dates_with_events'] = [];
            foreach ($summit->getSummitDaysWithEvents() as $day) {
                $values['dates_with_events'][] = $day->format('Y-m-d');
            }
        }

        if (in_array('time_zone', $fields)) {
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
                if (!is_null($start_date) && !is_null($end_date)) {
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
        }

        if (in_array('supported_currencies', $relations)) {
            $values['supported_currencies'] = $summit->getSupportedCurrencies();
        }

        if (in_array('timestamp', $fields))
            $values['timestamp'] = time();

        // pages info
        $main_page = $summit->getMainPage();
        $schedule_page = $summit->getSchedulePage();
        if (in_array('page_url', $fields))
            $values['page_url'] =
                empty($main_page) ? null :
                    sprintf("%s%s", Config::get("server.assets_base_url", 'https://www.openstack.org/'), $main_page);
        if (in_array('schedule_page_url', $fields))
            $values['schedule_page_url'] = empty($schedule_page) ? null :
                sprintf("%s%s", Config::get("server.assets_base_url", 'https://www.openstack.org/'), $schedule_page);
        if (in_array('schedule_event_detail_url', $fields))
            $values['schedule_event_detail_url'] = empty($schedule_page) ? null : sprintf("%s%s/%s", Config::get("server.assets_base_url", 'https://www.openstack.org/'), $schedule_page, 'events/:event_id/:event_title');

        // payment_profiles
        if (in_array('payment_profiles', $relations)) {
            $payment_profiles = [];
            foreach ($summit->getActivePaymentGateWayProfiles() as $profile) {
                Log:;debug
                (
                    sprintf
                    (
                        "SummitSerializer::serialize got profile %s app type %s",
                        $profile->getId(),
                        $profile->getApplicationType()
                    )
                );

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

            Log::debug
            (
                sprintf
                (
                    "SummitSerializer::serialize has_registration_profile %b has default strategy %b serializer type %s",
                    $has_registration_profile,
                    !is_null($build_default_payment_gateway_profile_strategy),
                    $this->getSerializerType()
                )
            );
            if (!$has_registration_profile &&
                !is_null($build_default_payment_gateway_profile_strategy)
            ) {

                $values['payment_profiles'][] =
                    SerializerRegistry::getInstance()->getSerializer
                    (
                        $build_default_payment_gateway_profile_strategy->build(IPaymentConstants::ApplicationTypeRegistration),
                        $this->getSerializerType()
                    )->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'payment_profiles'));

            }

            if (!$has_bookable_rooms_profile &&
                !is_null($build_default_payment_gateway_profile_strategy)
            ) {
                $values['payment_profiles'][] =
                    SerializerRegistry::getInstance()->getSerializer
                    (
                        $build_default_payment_gateway_profile_strategy->build(IPaymentConstants::ApplicationTypeBookableRooms),
                        $this->getSerializerType()
                    )->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'payment_profiles'));
            }
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'featured_speakers':
                        {
                            $featured_speakers = [];
                            foreach ($summit->getOrderedFeaturedSpeakers() as $featuredSpeaker) {
                                if (!$featuredSpeaker->hasSpeaker()) continue;
                                $featured_speakers[] = SerializerRegistry::getInstance()
                                    ->getSerializer($featuredSpeaker->getSpeaker())
                                    ->serialize
                                    (
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    );
                            }
                            $values['featured_speakers'] = $featured_speakers;
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
                                    SerializerRegistry::getInstance()->getSerializer($summit->getType())->serialize
                                    (
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                        AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                        $params
                                    ) : null;
                            }
                        }
                        break;
                    case 'locations':
                        // locations
                        if (in_array('locations', $relations)) {
                            $locations = [];
                            foreach ($summit->getLocations() as $location) {
                                $locations[] = SerializerRegistry::getInstance()->getSerializer($location)->serialize(
                                // is user is already expanding by schedule, its the total expand of the venues
                                    !is_null($expand) && str_contains('schedule', $expand) ?
                                        'floors,rooms' :
                                        AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['locations'] = $locations;
                        }
                        break;
                }
            }
        }

        if (in_array('schedule_settings', $relations) && !isset($values['schedule_settings'])) {
            $schedule_settings = [];
            foreach ($summit->getScheduleSettings() as $config) {
                if (!$config->isEnabled()) continue;
                $schedule_settings[] = $config->getId();
            }
            $values['schedule_settings'] = $schedule_settings;
        }

        if (in_array('lead_report_settings', $relations) && !isset($values['lead_report_settings'])) {
            $lead_report_settings = [];
            foreach ($summit->getLeadReportSettings() as $config) {
                $lead_report_settings[] = $config->getId();
            }
            $values['lead_report_settings'] = $lead_report_settings;
        }

        if (in_array('featured_speakers', $relations) && !isset($values['featured_speakers'])) {
            $featured_speakers = [];
            foreach ($summit->getOrderedFeaturedSpeakers() as $featuredSpeaker) {
                if (!$featuredSpeaker->hasSpeaker()) continue;
                $featured_speakers[] = $featuredSpeaker->getSpeaker()->getId();
            }
            $values['featured_speakers'] = $featured_speakers;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'schedule_settings' => [
            'serializer_type' => SerializerRegistry::SerializerType_Private,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getEnableScheduleSettings',
        ],
        'lead_report_settings' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getLeadReportSettings',
        ],
        'ticket_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTicketTypes',
        ],
        'badge_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getBadgeTypes',
        ],
        'badge_features_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getBadgeFeaturesTypes',
        ],
        'badge_access_level_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getBadgeAccessLevelTypes',
        ],
        'badge_view_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getBadgeViewTypes',
        ],
        'order_extra_questions' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getOrderExtraQuestions',
        ],
        'tax_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTaxTypes',
        ],
        'summit_documents' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSummitDocuments',
        ],
        'meeting_booking_room_allowed_attributes' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getMeetingBookingRoomAllowedAttributes',
        ],
        'summit_sponsors' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getPublishedSummitSponsors',
        ],
        'wifi_connections' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getWifiConnections',
        ],
        'selection_plans' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSelectionPlans',
        ],
        'email_flows_events' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAllEmailFlowsEvents',
        ],
        'presentation_action_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getPresentationActionTypes',
        ],
        'tracks' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getPresentationCategories',
        ],
        'speakers' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSpeakers',
        ],
        'event_types' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getEventTypes',
        ],
        'track_groups' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getCategoryGroups',
        ],
        'sponsors' => [
            'serializer_type' => SerializerRegistry::SerializerType_Public,
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getEventSponsors',
        ],
    ];
}