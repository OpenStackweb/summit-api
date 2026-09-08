<?php namespace App\ModelSerializers\Traits;
/*
 * Copyright 2026 OpenStack Foundation
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

use models\summit\PresentationSpeaker;

/**
 * Policy Rule 9 (policy/profile-data-handling.md Sec 2 Scope): only an admin-tooling caller
 * (Admin / SummitAdmin / SummitRegistrationAdmin), or the speaker viewing their own record, may
 * bypass the account visibility toggle on the Member name/photo fallback. Anyone else who happens
 * to reach a serializer that renders a speaker's borrowed name - a submitter holding an approved
 * edit-permission request on someone else's profile, an attendee whose ticket carries a speaker's
 * promo code - must see exactly what a Public caller sees.
 *
 * Shared by every serializer that has to resolve the caller at request time, so that rule lives
 * in exactly one place. Serializers that are only ever reached through an admin-tooling endpoint
 * (the admin / track-chair CSV exports: AdminPresentationCSVSerializer,
 * TrackChairPresentationCSVSerializer, the Speakers*PromoCodeCSVSerializer pair) do not use it and
 * hardcode getFullName(true) instead - track chairs count as admin tooling for those exports by
 * decision, see 6cb6647f1. Requires the using class to expose $this->resource_server_context
 * (AbstractSerializer does).
 *
 * @package App\ModelSerializers\Traits
 */
trait AccountVisibilityToggleBypass
{
    /**
     * @param PresentationSpeaker $speaker
     * @return bool
     */
    protected function canBypassAccountVisibilityToggle(PresentationSpeaker $speaker): bool
    {
        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return false;
        if ($current_member->isAdmin() || $current_member->isSummitAdmin() || $current_member->isRegistrationAdmin()) return true;
        return $speaker->hasMember() && $speaker->getMemberId() == $current_member->getId();
    }
}
