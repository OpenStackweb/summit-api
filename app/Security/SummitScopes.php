<?php
namespace App\Security;
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
    const ReadSummitData = SCOPE_BASE_REALM.'/summits/read';
    const ReadAllSummitData = SCOPE_BASE_REALM.'/summits/read/all';

    // me
    const MeRead = SCOPE_BASE_REALM.'/me/read';
    const AddMyFavorites = SCOPE_BASE_REALM.'/me/summits/events/favorites/add';
    const DeleteMyFavorites = SCOPE_BASE_REALM.'/me/summits/events/favorites/delete';
    const AddMyRSVP = SCOPE_BASE_REALM.'/me/summits/events/rsvp/add';
    const DeleteMyRSVP = SCOPE_BASE_REALM.'/me/summits/events/rsvp/delete';
    const AddMySchedule = SCOPE_BASE_REALM.'/me/summits/events/schedule/add';
    const DeleteMySchedule = SCOPE_BASE_REALM.'/me/summits/events/schedule/delete';
    const AddMyEventFeedback = SCOPE_BASE_REALM.'/me/summits/events/feedback/add';
    const DeleteMyEventFeedback = SCOPE_BASE_REALM.'/me/summits/events/feedback/delete';
    const AddMyScheduleShareable = SCOPE_BASE_REALM.'/me/summits/events/schedule/shareable/add';
    const DeleteMyScheduleShareable = SCOPE_BASE_REALM.'/me/summits/events/schedule/shareable/delete';
    const SendMyScheduleMail = SCOPE_BASE_REALM.'/me/summits/events/schedule/mail';
    const EnterEvent = SCOPE_BASE_REALM.'/me/summits/events/enter';
    const LeaveEvent = SCOPE_BASE_REALM.'/me/summits/events/leave';
    const WriteMetrics = SCOPE_BASE_REALM.'/me/summits/metrics/write';
    const ReadMetrics = SCOPE_BASE_REALM.'/me/summits/metrics/read';

    // registration
    const CreateRegistrationOrders = SCOPE_BASE_REALM.'/summits/registration-orders/create';
    const CreateOfflineRegistrationOrders = SCOPE_BASE_REALM.'/summits/registration-orders/create/offline';
    const UpdateRegistrationOrders = SCOPE_BASE_REALM.'/summits/registration-orders/update';
    const UpdateRegistrationOrdersBadges = SCOPE_BASE_REALM.'/summits/registration-orders/badges/update';
    const PrintRegistrationOrdersBadges = SCOPE_BASE_REALM.'/summits/registration-orders/badges/print';
    const UpdateMyRegistrationOrders = SCOPE_BASE_REALM.'/summits/registration-orders/update/me';
    const DeleteRegistrationOrders = SCOPE_BASE_REALM.'/summits/registration-orders/delete';
    const DeleteMyRegistrationOrders = SCOPE_BASE_REALM.'/summits/registration-orders/delete/me';
    const ReadMyRegistrationOrders = SCOPE_BASE_REALM.'/summits/registration-orders/read/me';
    const ReadRegistrationOrders = SCOPE_BASE_REALM.'/summits/registration-orders/read';
    const WriteBadgeScan = SCOPE_BASE_REALM.'/summits/badge-scans/write';
    const WriteMyBadgeScan = SCOPE_BASE_REALM.'/summits/badge-scans/write/me';
    const ReadBadgeScan = SCOPE_BASE_REALM.'/summits/badge-scans/read';

    const ReadBadgeScanValidate = SCOPE_BASE_REALM.'/summits/badge-scans/validate';
    const ReadMyBadgeScan = SCOPE_BASE_REALM.'/summits/badge-scans/read/me';
    const WriteRegistrationData = SCOPE_BASE_REALM.'/summits/registration/write';
    const ReadPaymentProfiles = SCOPE_BASE_REALM.'/summits/payment-gateway-profiles/read';
    const WritePaymentProfiles = SCOPE_BASE_REALM.'/summits/payment-gateway-profiles/write';
    const WriteRegistrationInvitations = SCOPE_BASE_REALM.'/summits/registration-invitations/write';
    const ReadRegistrationInvitations = SCOPE_BASE_REALM.'/summits/registration-invitations/read';
    const WriteSubmissionInvitations = SCOPE_BASE_REALM.'/summits/submission-invitations/write';
    const ReadSubmissionInvitations = SCOPE_BASE_REALM.'/summits/submission-invitations/read';

    const ReadMyRegistrationInvitations = SCOPE_BASE_REALM.'/summits/registration-invitations/read/me';
    const DoVirtualCheckIn = SCOPE_BASE_REALM.'/summits/registration/virtual-checkin';
    // bookable rooms
    const ReadBookableRoomsData = SCOPE_BASE_REALM.'/bookable-rooms/read';
    const WriteMyBookableRoomsReservationData = SCOPE_BASE_REALM.'/bookable-rooms/my-reservations/write';
    const ReadMyBookableRoomsReservationData = SCOPE_BASE_REALM.'/bookable-rooms/my-reservations/read';
    const BookableRoomsReservation = SCOPE_BASE_REALM.'/bookable-rooms/reserve';
    const WriteBookableRoomsData = SCOPE_BASE_REALM.'/bookable-rooms/write';

    const ReadNotifications = SCOPE_BASE_REALM.'/summits/read-notifications';
    const WriteNotifications = SCOPE_BASE_REALM.'/summits/write-notifications';

    const WriteSummitData = SCOPE_BASE_REALM.'/summits/write';
    const WriteSpeakersData = SCOPE_BASE_REALM.'/speakers/write';
    const ReadSpeakersData = SCOPE_BASE_REALM.'/speakers/read';
    const WriteTrackTagGroupsData = SCOPE_BASE_REALM.'/track-tag-groups/write';
    const WriteTrackQuestionTemplateData = SCOPE_BASE_REALM.'/track-question-templates/write';
    const WriteMySpeakersData = SCOPE_BASE_REALM.'/speakers/write/me';
    const ReadMySpeakersData = SCOPE_BASE_REALM.'/speakers/read/me';

    const DeleteEventData = SCOPE_BASE_REALM.'/summits/delete-event';
    const PublishEventData = SCOPE_BASE_REALM.'/summits/publish-event';
    const WriteEventData = SCOPE_BASE_REALM.'/summits/write-event';
    const WriteVideoData = SCOPE_BASE_REALM.'/summits/write-videos';
    const WritePresentationVideosData = SCOPE_BASE_REALM.'/summits/write-presentation-videos';
    const WritePresentationLinksData = SCOPE_BASE_REALM.'/summits/write-presentation-links';
    const WritePresentationSlidesData = SCOPE_BASE_REALM.'/summits/write-presentation-slides';
    const WritePresentationMaterialsData = SCOPE_BASE_REALM.'/summits/write-presentation-materials';

    const WriteAttendeesData = SCOPE_BASE_REALM.'/attendees/write';
    const WritePromoCodeData = SCOPE_BASE_REALM.'/promo-codes/write';
    const WriteEventTypeData = SCOPE_BASE_REALM.'/event-types/write';
    const WriteTracksData = SCOPE_BASE_REALM.'/tracks/write';
    const WriteTrackGroupsData = SCOPE_BASE_REALM.'/track-groups/write';
    const WriteLocationsData = SCOPE_BASE_REALM.'/locations/write';
    const WriteRSVPTemplateData = SCOPE_BASE_REALM.'/rsvp-templates/write';
    const WriteLocationBannersData = SCOPE_BASE_REALM.'/locations/banners/write';
    const WriteSummitSpeakerAssistanceData = SCOPE_BASE_REALM.'/summit-speaker-assistance/write';
    const WriteTicketTypeData = SCOPE_BASE_REALM.'/ticket-types/write';
    const WritePresentationData = SCOPE_BASE_REALM.'/summits/write-presentation';

    const WriteTagsData = SCOPE_BASE_REALM.'/tags/write';
    const ReadTagsData = SCOPE_BASE_REALM.'/tags/read';

    const ReadSummitAdminGroups = SCOPE_BASE_REALM.'/summit-administrator-groups/read';
    const WriteSummitAdminGroups = SCOPE_BASE_REALM.'/summit-administrator-groups/write';

    const ReadSummitMediaFileTypes = SCOPE_BASE_REALM.'/summit-media-file-types/read';
    const WriteSummitMediaFileTypes = SCOPE_BASE_REALM.'/summit-media-file-types/write';

    const Allow2PresentationAttendeeVote = SCOPE_BASE_REALM.'/presentations/attendee-vote';

    const ReadAuditLogs = SCOPE_BASE_REALM.'/audit-logs/read';

    const WriteAttendeeNotesData = SCOPE_BASE_REALM.'/attendee/notes/write';
    const ReadAttendeeNotesData = SCOPE_BASE_REALM.'/attendee/notes/read';

    const WriteSummitsConfirmExternalOrders = SCOPE_BASE_REALM.'/summits/confirm-external-orders';
    const ReadSummitsConfirmExternalOrders = SCOPE_BASE_REALM.'/summits/read-external-orders';
}

