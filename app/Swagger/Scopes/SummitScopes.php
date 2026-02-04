<?php
namespace App\Swagger\schemas;
/**
 * Copyright 2017 OpenStack Foundation
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

/**
 * Class SummitScopes
 * @package App\Security
 */
final class SummitScopes
{
    const ReadSummitData = L5_SWAGGER_CONST_HOST . '/summits/read';
    const ReadAllSummitData = L5_SWAGGER_CONST_HOST . '/summits/read/all';

    // me
    const MeRead = L5_SWAGGER_CONST_HOST . '/me/read';
    const AddMyFavorites = L5_SWAGGER_CONST_HOST . '/me/summits/events/favorites/add';
    const DeleteMyFavorites = L5_SWAGGER_CONST_HOST . '/me/summits/events/favorites/delete';
    const AddMyRSVP = L5_SWAGGER_CONST_HOST . '/me/summits/events/rsvp/add';
    const DeleteMyRSVP = L5_SWAGGER_CONST_HOST . '/me/summits/events/rsvp/delete';
    const AddMySchedule = L5_SWAGGER_CONST_HOST . '/me/summits/events/schedule/add';
    const DeleteMySchedule = L5_SWAGGER_CONST_HOST . '/me/summits/events/schedule/delete';
    const AddMyEventFeedback = L5_SWAGGER_CONST_HOST . '/me/summits/events/feedback/add';
    const DeleteMyEventFeedback = L5_SWAGGER_CONST_HOST . '/me/summits/events/feedback/delete';
    const AddMyScheduleShareable = L5_SWAGGER_CONST_HOST . '/me/summits/events/schedule/shareable/add';
    const DeleteMyScheduleShareable = L5_SWAGGER_CONST_HOST . '/me/summits/events/schedule/shareable/delete';
    const SendMyScheduleMail = L5_SWAGGER_CONST_HOST . '/me/summits/events/schedule/mail';
    const EnterEvent = L5_SWAGGER_CONST_HOST . '/me/summits/events/enter';
    const LeaveEvent = L5_SWAGGER_CONST_HOST . '/me/summits/events/leave';
    const WriteMetrics = L5_SWAGGER_CONST_HOST . '/me/summits/metrics/write';
    const ReadMetrics = L5_SWAGGER_CONST_HOST . '/me/summits/metrics/read';

    // registration
    const CreateRegistrationOrders = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/create';
    const CreateOfflineRegistrationOrders = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/create/offline';
    const UpdateRegistrationOrders = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/update';
    const UpdateRegistrationOrdersBadges = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/badges/update';
    const PrintRegistrationOrdersBadges = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/badges/print';
    const UpdateMyRegistrationOrders = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/update/me';
    const DeleteRegistrationOrders = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/delete';
    const DeleteMyRegistrationOrders = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/delete/me';
    const ReadMyRegistrationOrders = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/read/me';
    const ReadRegistrationOrders = L5_SWAGGER_CONST_HOST . '/summits/registration-orders/read';
    const WriteBadgeScan = L5_SWAGGER_CONST_HOST . '/summits/badge-scans/write';
    const WriteMyBadgeScan = L5_SWAGGER_CONST_HOST . '/summits/badge-scans/write/me';
    const ReadBadgeScan = L5_SWAGGER_CONST_HOST . '/summits/badge-scans/read';

    const ReadBadgeScanValidate = L5_SWAGGER_CONST_HOST . '/summits/badge-scans/validate';
    const ReadMyBadgeScan = L5_SWAGGER_CONST_HOST . '/summits/badge-scans/read/me';
    const WriteRegistrationData = L5_SWAGGER_CONST_HOST . '/summits/registration/write';
    const ReadPaymentProfiles = L5_SWAGGER_CONST_HOST . '/summits/payment-gateway-profiles/read';
    const WritePaymentProfiles = L5_SWAGGER_CONST_HOST . '/summits/payment-gateway-profiles/write';
    const WriteRegistrationInvitations = L5_SWAGGER_CONST_HOST . '/summits/registration-invitations/write';
    const ReadRegistrationInvitations = L5_SWAGGER_CONST_HOST . '/summits/registration-invitations/read';
    const WriteSubmissionInvitations = L5_SWAGGER_CONST_HOST . '/summits/submission-invitations/write';
    const ReadSubmissionInvitations = L5_SWAGGER_CONST_HOST . '/summits/submission-invitations/read';

    const ReadMyRegistrationInvitations = L5_SWAGGER_CONST_HOST . '/summits/registration-invitations/read/me';
    const DoVirtualCheckIn = L5_SWAGGER_CONST_HOST . '/summits/registration/virtual-checkin';
    // bookable rooms
    const ReadBookableRoomsData = L5_SWAGGER_CONST_HOST . '/bookable-rooms/read';
    const WriteMyBookableRoomsReservationData = L5_SWAGGER_CONST_HOST . '/bookable-rooms/my-reservations/write';
    const ReadMyBookableRoomsReservationData = L5_SWAGGER_CONST_HOST . '/bookable-rooms/my-reservations/read';
    const BookableRoomsReservation = L5_SWAGGER_CONST_HOST . '/bookable-rooms/reserve';
    const WriteBookableRoomsData = L5_SWAGGER_CONST_HOST . '/bookable-rooms/write';

    const ReadNotifications = L5_SWAGGER_CONST_HOST . '/summits/read-notifications';
    const WriteNotifications = L5_SWAGGER_CONST_HOST . '/summits/write-notifications';

    const WriteSummitData = L5_SWAGGER_CONST_HOST . '/summits/write';
    const WriteSpeakersData = L5_SWAGGER_CONST_HOST . '/speakers/write';
    const ReadSpeakersData = L5_SWAGGER_CONST_HOST . '/speakers/read';
    const WriteTrackTagGroupsData = L5_SWAGGER_CONST_HOST . '/track-tag-groups/write';
    const WriteTrackQuestionTemplateData = L5_SWAGGER_CONST_HOST . '/track-question-templates/write';
    const WriteMySpeakersData = L5_SWAGGER_CONST_HOST . '/speakers/write/me';
    const ReadMySpeakersData = L5_SWAGGER_CONST_HOST . '/speakers/read/me';

    const PublishEventData = L5_SWAGGER_CONST_HOST . '/summits/publish-event';
    const WriteEventData = L5_SWAGGER_CONST_HOST . '/summits/write-event';
    const WriteVideoData = L5_SWAGGER_CONST_HOST . '/summits/write-videos';
    const WritePresentationVideosData = L5_SWAGGER_CONST_HOST . '/summits/write-presentation-videos';
    const WritePresentationLinksData = L5_SWAGGER_CONST_HOST . '/summits/write-presentation-links';
    const WritePresentationSlidesData = L5_SWAGGER_CONST_HOST . '/summits/write-presentation-slides';
    const WritePresentationMaterialsData = L5_SWAGGER_CONST_HOST . '/summits/write-presentation-materials';

    const WriteAttendeesData = L5_SWAGGER_CONST_HOST . '/attendees/write';
    const WritePromoCodeData = L5_SWAGGER_CONST_HOST . '/promo-codes/write';
    const WriteEventTypeData = L5_SWAGGER_CONST_HOST . '/event-types/write';
    const WriteTracksData = L5_SWAGGER_CONST_HOST . '/tracks/write';
    const WriteTrackGroupsData = L5_SWAGGER_CONST_HOST . '/track-groups/write';
    const WriteLocationsData = L5_SWAGGER_CONST_HOST . '/locations/write';
    const WriteRSVPTemplateData = L5_SWAGGER_CONST_HOST . '/rsvp-templates/write';
    const WriteLocationBannersData = L5_SWAGGER_CONST_HOST . '/locations/banners/write';
    const WriteSummitSpeakerAssistanceData = L5_SWAGGER_CONST_HOST . '/summit-speaker-assistance/write';
    const WriteTicketTypeData = L5_SWAGGER_CONST_HOST . '/ticket-types/write';
    const WritePresentationData = L5_SWAGGER_CONST_HOST . '/summits/write-presentation';

    const WriteTagsData = L5_SWAGGER_CONST_HOST . '/tags/write';
    const ReadTagsData = L5_SWAGGER_CONST_HOST . '/tags/read';

    const ReadSummitAdminGroups = L5_SWAGGER_CONST_HOST . '/summit-administrator-groups/read';
    const WriteSummitAdminGroups = L5_SWAGGER_CONST_HOST . '/summit-administrator-groups/write';

    const ReadSummitMediaFileTypes = L5_SWAGGER_CONST_HOST . '/summit-media-file-types/read';
    const WriteSummitMediaFileTypes = L5_SWAGGER_CONST_HOST . '/summit-media-file-types/write';

    const Allow2PresentationAttendeeVote = L5_SWAGGER_CONST_HOST . '/presentations/attendee-vote';

    const ReadAuditLogs = L5_SWAGGER_CONST_HOST . '/audit-logs/read';

    const WriteAttendeeNotesData = L5_SWAGGER_CONST_HOST . '/attendee/notes/write';
    const ReadAttendeeNotesData = L5_SWAGGER_CONST_HOST . '/attendee/notes/read';

    const WriteSummitsConfirmExternalOrders = L5_SWAGGER_CONST_HOST . '/summits/confirm-external-orders';
    const ReadSummitsConfirmExternalOrders = L5_SWAGGER_CONST_HOST . '/summits/read-external-orders';
}

